<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_Checkout {

    // Stripe price IDs — replace these with your real ones from Stripe dashboard
    const PLANS = [
        'starter'    => [
            'price_id' => 'price_starter_id_from_stripe',
            'name'     => 'Starter',
            'limit'    => 100,
            'amount'   => '$9/mo',
        ],
        'pro'        => [
            'price_id' => 'price_pro_id_from_stripe',
            'name'     => 'Pro',
            'limit'    => 1000,
            'amount'   => '$29/mo',
        ],
        'enterprise' => [
            'price_id' => 'price_enterprise_id_from_stripe',
            'name'     => 'Enterprise',
            'limit'    => -1,
            'amount'   => '$99/mo',
        ],
    ];

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes(): void {

        // POST /wp-json/snipvip/v1/stripe/checkout
        // Creates a Stripe Checkout session and returns the URL
        register_rest_route( 'snipvip/v1', '/stripe/checkout', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'create_session' ],
            'permission_callback' => fn() => is_user_logged_in(),
            'args'                => [
                'plan' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => fn( $v ) => array_key_exists( $v, self::PLANS ),
                ],
            ],
        ] );

        // GET /wp-json/snipvip/v1/stripe/plans
        // Returns all available plans — used by the pricing page
        register_rest_route( 'snipvip/v1', '/stripe/plans', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_plans' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Create a Stripe Checkout session.
     * Redirects the user to Stripe's hosted payment page.
     */
    public static function create_session( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $plan_key = $request->get_param( 'plan' );
        $plan     = self::PLANS[ $plan_key ];
        $user     = wp_get_current_user();

        $secret_key = defined( 'SNIPVIP_STRIPE_SECRET_KEY' )
            ? SNIPVIP_STRIPE_SECRET_KEY
            : '';

        if ( empty( $secret_key ) ) {
            return new \WP_Error(
                'stripe_not_configured',
                'Stripe is not configured.',
                [ 'status' => 500 ]
            );
        }

        // Get or create Stripe customer ID for this user
        $customer_id = get_user_meta( $user->ID, 'snipvip_stripe_customer_id', true );

        // Build the Stripe API request
        $body = [
            'mode'                => 'subscription',
            'payment_method_types' => [ 'card' ],
            'line_items'          => [
                [
                    'price'    => $plan['price_id'],
                    'quantity' => 1,
                ],
            ],
            'success_url'         => home_url( '/dashboard/?upgraded=1' ),
            'cancel_url'          => home_url( '/pricing/?cancelled=1' ),
            'client_reference_id' => (string) $user->ID,
            'customer_email'      => $customer_id ? null : $user->user_email,
            'customer'            => $customer_id ?: null,
            'metadata'            => [
                'user_id'   => $user->ID,
                'plan'      => $plan_key,
                'site_url'  => home_url(),
            ],
            'subscription_data'   => [
                'metadata' => [
                    'user_id' => $user->ID,
                    'plan'    => $plan_key,
                ],
            ],
        ];

        // Remove null values — Stripe rejects them
        $body = array_filter( $body, fn( $v ) => ! is_null( $v ) );

        // Call Stripe API using wp_remote_post (VIP-safe, no curl directly)
        $response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => self::flatten_for_stripe( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error(
                'stripe_request_failed',
                'Could not connect to Stripe. Please try again.',
                [ 'status' => 502 ]
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['url'] ) ) {
            $error = $data['error']['message'] ?? 'Unknown Stripe error.';
            return new \WP_Error( 'stripe_error', $error, [ 'status' => 500 ] );
        }

        return rest_ensure_response( [
            'checkout_url' => $data['url'],
            'session_id'   => $data['id'],
        ] );
    }

    /**
     * Return all plan details for the pricing page.
     */
    public static function get_plans( \WP_REST_Request $request ): \WP_REST_Response {
        $plans = [];

        foreach ( self::PLANS as $key => $plan ) {
            $plans[] = [
                'key'    => $key,
                'name'   => $plan['name'],
                'amount' => $plan['amount'],
                'limit'  => $plan['limit'],
            ];
        }

        return rest_ensure_response( [ 'plans' => $plans ] );
    }

    /**
     * Flatten a nested array into Stripe's form-encoded format.
     * Stripe API uses bracket notation: line_items[0][price]=xxx
     */
    private static function flatten_for_stripe( array $data, string $prefix = '' ): array {
        $result = [];

        foreach ( $data as $key => $value ) {
            $full_key = $prefix ? "{$prefix}[{$key}]" : $key;

            if ( is_array( $value ) ) {
                $result = array_merge( $result, self::flatten_for_stripe( $value, $full_key ) );
            } else {
                $result[ $full_key ] = $value;
            }
        }

        return $result;
    }
}