<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_Webhook {

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes(): void {

        // POST /wp-json/snipvip/v1/stripe/webhook
        // Stripe sends events here — payment success, cancellation, etc.
        register_rest_route( 'snipvip/v1', '/stripe/webhook', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle' ],
            'permission_callback' => '__return_true', // Stripe signs the payload instead
        ] );
    }

    /**
     * Main webhook handler.
     * Verifies Stripe signature then routes to the right handler.
     */
    public static function handle( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {

        $webhook_secret = defined( 'SNIPVIP_STRIPE_WEBHOOK_SECRET' )
            ? SNIPVIP_STRIPE_WEBHOOK_SECRET
            : '';

        if ( empty( $webhook_secret ) ) {
            return new \WP_Error(
                'webhook_not_configured',
                'Webhook secret not configured.',
                [ 'status' => 500 ]
            );
        }

        // Get raw body — must verify signature against raw bytes
        $payload   = $request->get_body();
        $sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) )
            : '';

        // Verify the webhook came from Stripe
        $event = self::verify_signature( $payload, $sig_header, $webhook_secret );

        if ( is_wp_error( $event ) ) {
            return $event;
        }

        // Route to the right handler based on event type
        switch ( $event['type'] ) {

            case 'checkout.session.completed':
                self::handle_checkout_completed( $event['data']['object'] );
                break;

            case 'customer.subscription.updated':
                self::handle_subscription_updated( $event['data']['object'] );
                break;

            case 'customer.subscription.deleted':
                self::handle_subscription_deleted( $event['data']['object'] );
                break;

            case 'invoice.payment_failed':
                self::handle_payment_failed( $event['data']['object'] );
                break;

            default:
                // We don't handle this event type — tell Stripe it's OK
                break;
        }

        // Always return 200 so Stripe stops retrying
        return rest_ensure_response( [ 'received' => true ] );
    }

    /**
     * checkout.session.completed
     * Fired when user successfully pays.
     * Upgrade their plan immediately.
     */
    private static function handle_checkout_completed( array $session ): void {
        $user_id = (int) ( $session['client_reference_id'] ?? 0 );
        $plan    = sanitize_key( $session['metadata']['plan'] ?? '' );
        // Temporary debug
    error_log( 'SnipVIP Webhook — session: ' . wp_json_encode( $session ) );
    error_log( 'SnipVIP Webhook — user_id: ' . $user_id . ' plan: ' . $plan );
        if ( ! $user_id || ! $plan ) {
            return;
        }

        // Save Stripe customer ID so future checkouts reuse it
        $customer_id = $session['customer'] ?? '';
        if ( $customer_id ) {
            update_user_meta( $user_id, 'snipvip_stripe_customer_id', sanitize_text_field( $customer_id ) );
        }

        // Save subscription ID for future management
        $subscription_id = $session['subscription'] ?? '';
        if ( $subscription_id ) {
            update_user_meta( $user_id, 'snipvip_stripe_subscription_id', sanitize_text_field( $subscription_id ) );
        }

        // Upgrade the plan
        self::upgrade_user( $user_id, $plan );
    }

    /**
     * customer.subscription.updated
     * Fired when user changes plan (upgrade or downgrade).
     */
    private static function handle_subscription_updated( array $subscription ): void {
        $user_id = self::get_user_from_subscription( $subscription );

        if ( ! $user_id ) {
            return;
        }

        // Get the new plan from subscription metadata
        $plan = sanitize_key( $subscription['metadata']['plan'] ?? '' );

        if ( ! $plan ) {
            return;
        }

        // Only upgrade if subscription is active
        $status = $subscription['status'] ?? '';
        if ( 'active' === $status || 'trialing' === $status ) {
            self::upgrade_user( $user_id, $plan );
        }
    }

    /**
     * customer.subscription.deleted
     * Fired when subscription is cancelled.
     * Drop user back to free plan.
     */
    private static function handle_subscription_deleted( array $subscription ): void {
        $user_id = self::get_user_from_subscription( $subscription );

        if ( ! $user_id ) {
            return;
        }

        // Downgrade to free
        update_user_meta( $user_id, 'snipvip_plan', 'free' );
        delete_user_meta( $user_id, 'snipvip_stripe_subscription_id' );
    }

    /**
     * invoice.payment_failed
     * Fired when a renewal payment fails.
     * Log it — in production you'd also email the user.
     */
    private static function handle_payment_failed( array $invoice ): void {
        $customer_id = $invoice['customer'] ?? '';

        if ( ! $customer_id ) {
            return;
        }

        // Find the user by Stripe customer ID
        $users = get_users( [
            'meta_key'   => 'snipvip_stripe_customer_id',
            'meta_value' => sanitize_text_field( $customer_id ),
            'number'     => 1,
        ] );

        if ( empty( $users ) ) {
            return;
        }

        $user_id = $users[0]->ID;

        // Log the failed payment
        update_user_meta(
            $user_id,
            'snipvip_last_payment_failed',
            current_time( 'mysql', true )
        );

        // In production: trigger an email to the user here
        // wp_mail( $users[0]->user_email, 'Payment failed', '...' );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Upgrade a user to a paid plan.
     * Single place where plan changes happen — easy to audit.
     */
    private static function upgrade_user( int $user_id, string $plan ): void {
        update_user_meta( $user_id, 'snipvip_plan', $plan );
        update_user_meta( $user_id, 'snipvip_plan_activated_at', current_time( 'mysql', true ) );
    }

    /**
     * Find a WordPress user from a Stripe subscription object.
     * Checks metadata first, falls back to customer ID lookup.
     */
    private static function get_user_from_subscription( array $subscription ): int {
        // Try metadata first — fastest path
        $user_id = (int) ( $subscription['metadata']['user_id'] ?? 0 );

        if ( $user_id && get_userdata( $user_id ) ) {
            return $user_id;
        }

        // Fallback: look up by Stripe customer ID
        $customer_id = $subscription['customer'] ?? '';

        if ( ! $customer_id ) {
            return 0;
        }

        $users = get_users( [
            'meta_key'   => 'snipvip_stripe_customer_id',
            'meta_value' => sanitize_text_field( $customer_id ),
            'number'     => 1,
            'fields'     => 'ids',
        ] );

        return ! empty( $users ) ? (int) $users[0] : 0;
    }

    /**
     * Verify Stripe webhook signature.
     * Protects against fake webhook calls from bad actors.
     *
     * @return array|WP_Error  Parsed event or error.
     */
    private static function verify_signature( string $payload, string $sig_header, string $secret ): array|\WP_Error {

        if ( empty( $sig_header ) ) {
            return new \WP_Error(
                'missing_signature',
                'No Stripe signature found.',
                [ 'status' => 400 ]
            );
        }

        // Parse the signature header: t=timestamp,v1=signature
        $parts     = explode( ',', $sig_header );
        $timestamp = '';
        $signatures = [];

        foreach ( $parts as $part ) {
            [ $key, $value ] = explode( '=', $part, 2 );
            if ( 't' === $key ) {
                $timestamp = $value;
            } elseif ( 'v1' === $key ) {
                $signatures[] = $value;
            }
        }

        if ( empty( $timestamp ) || empty( $signatures ) ) {
            return new \WP_Error(
                'invalid_signature_format',
                'Invalid Stripe signature format.',
                [ 'status' => 400 ]
            );
        }

        // Reject webhooks older than 5 minutes — replay attack protection
        if ( abs( time() - (int) $timestamp ) > 300 ) {
            return new \WP_Error(
                'webhook_expired',
                'Webhook timestamp is too old.',
                [ 'status' => 400 ]
            );
        }

        // Compute expected signature
        $signed_payload  = $timestamp . '.' . $payload;
        $expected        = hash_hmac( 'sha256', $signed_payload, $secret );

        // Compare against all v1 signatures Stripe sent
        $verified = false;
        foreach ( $signatures as $sig ) {
            if ( hash_equals( $expected, $sig ) ) {
                $verified = true;
                break;
            }
        }

        if ( ! $verified ) {
            return new \WP_Error(
                'invalid_signature',
                'Stripe signature verification failed.',
                [ 'status' => 403 ]
            );
        }

        // Decode and return the event
        $event = json_decode( $payload, true );

        if ( empty( $event['type'] ) ) {
            return new \WP_Error(
                'invalid_payload',
                'Could not parse Stripe event.',
                [ 'status' => 400 ]
            );
        }

        return $event;
    }
}