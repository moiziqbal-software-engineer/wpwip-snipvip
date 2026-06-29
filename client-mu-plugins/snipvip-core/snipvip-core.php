<?php
/**
 * Plugin Name: SnipVIP Core
 * Description: URL shortener core engine — link generation, quota guard, analytics, REST API.
 * Version: 1.0.0
 * Author: Your Name
 */

defined( 'ABSPATH' ) || exit;

// Autoload all classes
require_once __DIR__ . '/includes/class-db.php';
require_once __DIR__ . '/includes/class-link-engine.php';
require_once __DIR__ . '/includes/class-plan-guard.php';
require_once __DIR__ . '/includes/class-analytics.php';
require_once __DIR__ . '/includes/class-rest-api.php';

/**
 * Boot the plugin after WordPress is fully loaded.
 */
add_action( 'plugins_loaded', function() {
    // Create tables if they don't exist yet
    SnipVIP_DB::maybe_create_tables();

    // Boot REST API
    SnipVIP_REST_API::init();

    // Boot redirect handler — catches /?s=abc123
    SnipVIP_Link_Engine::init();
} );