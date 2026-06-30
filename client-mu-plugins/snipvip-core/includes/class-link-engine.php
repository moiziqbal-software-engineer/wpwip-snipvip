<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_Link_Engine {

    const SLUG_LENGTH  = 7;
    const REDIRECT_TTL = 300; // cache redirect lookups for 5 minutes

    /**
     * Register the redirect handler on init.
     * Catches requests like /?s=abc1234 and redirects.
     */
    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'handle_redirect' ] );
    }

    /**
     * If the request has ?s=slug, look it up and redirect.
     * VIP-safe: uses wp_cache, no file system, proper exit.
     */
    public static function handle_redirect(): void {
        $slug = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        if ( empty( $slug ) ) {
            return;
        }

        $destination = self::resolve_slug( $slug );

        if ( ! $destination ) {
            // Slug not found — let WordPress render a 404
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return;
        }

        // Track the click asynchronously
        SnipVIP_Analytics::record_click( $slug );

        // 301 redirect — permanent, CDN-cacheable
        wp_redirect( esc_url_raw( $destination ), 301 );
        exit;
    }

    /**
     * Look up a slug → destination URL.
     * Uses object cache so repeated hits don't query the DB.
     */
    public static function resolve_slug( string $slug ): string|false {
        $cache_key = 'snipvip_slug_' . $slug;
        $cached    = wp_cache_get( $cache_key, 'snipvip' );

        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = SnipVIP_DB::links_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $destination = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT destination FROM {$table}
                 WHERE slug = %s
                   AND is_active = 1
                   AND ( expires_at IS NULL OR expires_at > NOW() )
                 LIMIT 1",
                $slug
            )
        );

        if ( $destination ) {
            wp_cache_set( $cache_key, $destination, 'snipvip', self::REDIRECT_TTL );
        }

        return $destination ?: false;
    }

    /**
     * Create a new short link.
     * Runs the plan guard check before inserting.
     *
     * @return array|WP_Error  The created link data or an error.
     */
    public static function create_link( int $user_id, string $destination, string $title = '' ): array|\WP_Error {

        // Validate the destination URL
        $destination = esc_url_raw( $destination );
        if ( ! filter_var( $destination, FILTER_VALIDATE_URL ) ) {
            return new \WP_Error( 'invalid_url', 'Please enter a valid URL.', [ 'status' => 400 ] );
        }

        // Check quota
        $allowed = SnipVIP_Plan_Guard::can_create_link( $user_id );
        if ( is_wp_error( $allowed ) ) {
            return $allowed;
        }

        // Generate a unique slug
        $slug = self::generate_unique_slug();
        if ( ! $slug ) {
            return new \WP_Error( 'slug_error', 'Could not generate a unique slug. Please try again.', [ 'status' => 500 ] );
        }

        global $wpdb;
        $table = SnipVIP_DB::links_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'     => $user_id,
                'slug'        => $slug,
                'destination' => $destination,
                'title'       => sanitize_text_field( $title ),
                'is_active'   => 1,
                'created_at'  => current_time( 'mysql', true ),
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s' ]
        );

        if ( ! $inserted ) {
            return new \WP_Error( 'db_error', 'Could not save the link. Please try again.', [ 'status' => 500 ] );
        }

        $link_id  = $wpdb->insert_id;
        $short_url = home_url( '/?s=' . $slug );

        return [
            'id'          => $link_id,
            'slug'        => $slug,
            'short_url'   => $short_url,
            'destination' => $destination,
            'title'       => $title,
            'created_at'  => current_time( 'mysql', true ),
            'clicks'      => 0,
        ];
    }

    /**
     * Get all links for a user.
     */
    public static function get_user_links( int $user_id, int $limit = 50, int $offset = 0 ): array {
        global $wpdb;
    
        $links_table  = SnipVIP_DB::links_table();
        $clicks_table = SnipVIP_DB::clicks_table();
    
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, COUNT(c.id) as click_count
                 FROM {$links_table} l
                 LEFT JOIN {$clicks_table} c ON c.link_id = l.id
                 WHERE l.user_id = %d AND l.is_active = 1
                 GROUP BY l.id
                 ORDER BY l.created_at DESC
                 LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            ),
            ARRAY_A
        );
    
        // Add short URL in PHP not SQL
        return array_map( function( $link ) {
            $link['short_url'] = home_url( '/?s=' . $link['slug'] );
            return $link;
        }, $results ?: [] );
    }

    /**
     * Delete (deactivate) a link — only if it belongs to the user.
     */
    public static function delete_link( int $link_id, int $user_id ): bool|\WP_Error {
        global $wpdb;
        $table = SnipVIP_DB::links_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $updated = $wpdb->update(
            $table,
            [ 'is_active' => 0 ],
            [ 'id' => $link_id, 'user_id' => $user_id ],
            [ '%d' ],
            [ '%d', '%d' ]
        );

        if ( false === $updated ) {
            return new \WP_Error( 'delete_failed', 'Could not delete the link.', [ 'status' => 500 ] );
        }

        if ( 0 === $updated ) {
            return new \WP_Error( 'not_found', 'Link not found or does not belong to you.', [ 'status' => 404 ] );
        }

        return true;
    }

    /**
     * Generate a unique random slug.
     * Tries up to 5 times before giving up.
     */
    private static function generate_unique_slug(): string|false {
        global $wpdb;
        $table = SnipVIP_DB::links_table();

        for ( $i = 0; $i < 5; $i++ ) {
            $slug = self::random_slug();

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE slug = %s LIMIT 1",
                    $slug
                )
            );

            if ( ! $exists ) {
                return $slug;
            }
        }

        return false;
    }

    /**
     * Generate a random alphanumeric slug.
     * VIP-safe: uses wp_rand(), no openssl dependency.
     */
    private static function random_slug(): string {
        $chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $slug   = '';
        $length = strlen( $chars );

        for ( $i = 0; $i < self::SLUG_LENGTH; $i++ ) {
            $slug .= $chars[ wp_rand( 0, $length - 1 ) ];
        }

        return $slug;
    }
}