<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_Analytics {

    /**
     * Record a click on a short link.
     * Called on every redirect — kept deliberately lightweight.
     */
    public static function record_click( string $slug ): void {
        global $wpdb;

        $links_table  = SnipVIP_DB::links_table();
        $clicks_table = SnipVIP_DB::clicks_table();

        // Get link ID from slug
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $link_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$links_table} WHERE slug = %s LIMIT 1",
                $slug
            )
        );

        if ( ! $link_id ) {
            return;
        }

        // Hash the IP for GDPR compliance — we track patterns not individuals
        $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ip_hash = $ip ? hash( 'sha256', $ip . wp_salt() ) : '';

        $referrer   = isset( $_SERVER['HTTP_REFERER'] )    ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) )    : '';
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $clicks_table,
            [
                'link_id'    => (int) $link_id,
                'clicked_at' => current_time( 'mysql', true ),
                'ip_hash'    => $ip_hash,
                'referrer'   => substr( $referrer, 0, 500 ),
                'user_agent' => substr( $user_agent, 0, 500 ),
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Get click stats for a specific link.
     */
    public static function get_link_stats( int $link_id ): array {
        global $wpdb;
        $table = SnipVIP_DB::clicks_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE link_id = %d",
                $link_id
            )
        );

        // Clicks per day for the last 7 days
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $daily = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(clicked_at) as date, COUNT(*) as clicks
                 FROM {$table}
                 WHERE link_id = %d
                   AND clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY DATE(clicked_at)
                 ORDER BY date ASC",
                $link_id
            ),
            ARRAY_A
        );

        return [
            'total_clicks' => $total,
            'daily'        => $daily ?: [],
        ];
    }
}