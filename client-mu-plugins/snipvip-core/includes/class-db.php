<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_DB {

    const DB_VERSION     = '1.0.0';
    const VERSION_OPTION = 'snipvip_db_version';

    /**
     * Create tables only if schema version changed.
     * Safe to call on every request — cheap option check.
     */
    public static function maybe_create_tables(): void {
        if ( get_option( self::VERSION_OPTION ) === self::DB_VERSION ) {
            return;
        }
        self::create_tables();
    }

    /**
     * Run dbDelta to create / update tables.
     * VIP-safe: uses dbDelta, never raw CREATE TABLE without IF NOT EXISTS.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        // Table 1: snipvip_links
        // Stores every shortened URL
        $links_table = $wpdb->prefix . 'snipvip_links';
        $sql_links = "CREATE TABLE {$links_table} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            slug        VARCHAR(32)         NOT NULL,
            destination TEXT                NOT NULL,
            title       VARCHAR(255)        DEFAULT '',
            is_active   TINYINT(1)          NOT NULL DEFAULT 1,
            created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at  DATETIME                     DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY   slug (slug),
            KEY          user_id (user_id),
            KEY          created_at (created_at)
        ) {$charset};";

        // Table 2: snipvip_clicks
        // Stores every click on a short link
        $clicks_table = $wpdb->prefix . 'snipvip_clicks';
        $sql_clicks = "CREATE TABLE {$clicks_table} (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            link_id    BIGINT(20) UNSIGNED NOT NULL,
            clicked_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash    VARCHAR(64)         DEFAULT '',
            country    VARCHAR(8)          DEFAULT '',
            referrer   VARCHAR(500)        DEFAULT '',
            user_agent VARCHAR(500)        DEFAULT '',
            PRIMARY KEY (id),
            KEY         link_id (link_id),
            KEY         clicked_at (clicked_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_links );
        dbDelta( $sql_clicks );

        update_option( self::VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Get the links table name.
     */
    public static function links_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'snipvip_links';
    }

    /**
     * Get the clicks table name.
     */
    public static function clicks_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'snipvip_clicks';
    }
}