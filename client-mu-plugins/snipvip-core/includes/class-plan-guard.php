<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_Plan_Guard {

    // Plan slugs — match exactly what Stripe sends us later
    const PLAN_FREE       = 'free';
    const PLAN_STARTER    = 'starter';
    const PLAN_PRO        = 'pro';
    const PLAN_ENTERPRISE = 'enterprise';

    /**
     * Get a user's current plan.
     */
    public static function get_plan( int $user_id ): string {
        $plan = get_user_meta( $user_id, 'snipvip_plan', true );
        return in_array( $plan, [ self::PLAN_STARTER, self::PLAN_PRO, self::PLAN_ENTERPRISE ], true )
            ? $plan
            : self::PLAN_FREE;
    }

    /**
     * Get the link limit for a plan.
     * Returns -1 for unlimited.
     */
    public static function get_limit( string $plan ): int {
        $limits = [
            self::PLAN_FREE       => SNIPVIP_FREE_LIMIT,
            self::PLAN_STARTER    => SNIPVIP_STARTER_LIMIT,
            self::PLAN_PRO        => SNIPVIP_PRO_LIMIT,
            self::PLAN_ENTERPRISE => SNIPVIP_ENTERPRISE_LIMIT,
        ];
        return $limits[ $plan ] ?? SNIPVIP_FREE_LIMIT;
    }

    /**
     * Count how many active links a user currently has.
     */
    public static function get_link_count( int $user_id ): int {
        global $wpdb;
        $table = SnipVIP_DB::links_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_active = 1",
                $user_id
            )
        );
    }

    /**
     * Check if user can create another link.
     * Returns true if allowed, WP_Error if not.
     */
    public static function can_create_link( int $user_id ): bool|\WP_Error {
        $plan  = self::get_plan( $user_id );
        $limit = self::get_limit( $plan );

        // Enterprise = unlimited
        if ( -1 === $limit ) {
            return true;
        }

        $count = self::get_link_count( $user_id );

        if ( $count >= $limit ) {
            return new \WP_Error(
                'quota_exceeded',
                sprintf(
                    'You have reached your %s plan limit of %d links. Please upgrade to create more.',
                    ucfirst( $plan ),
                    $limit
                ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Upgrade a user's plan — called by Stripe webhook later.
     */
    public static function set_plan( int $user_id, string $plan ): void {
        update_user_meta( $user_id, 'snipvip_plan', sanitize_key( $plan ) );
    }

    /**
     * Get plan info as an array — useful for REST API responses.
     */
    public static function get_plan_info( int $user_id ): array {
        $plan  = self::get_plan( $user_id );
        $limit = self::get_limit( $plan );
        $count = self::get_link_count( $user_id );

        return [
            'plan'       => $plan,
            'limit'      => $limit,
            'used'       => $count,
            'remaining'  => $limit === -1 ? -1 : max( 0, $limit - $count ),
            'unlimited'  => $limit === -1,
        ];
    }
}