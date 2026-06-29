<?php
/**
 * Plugin Name: SnipVIP Stripe Billing
 * Description: Stripe Checkout and webhook handling for SnipVIP plans.
 * Version:     1.0.0
 * Author:      Moiz Iqbal
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-checkout.php';
require_once __DIR__ . '/class-webhook.php';

add_action( 'plugins_loaded', function() {
    SnipVIP_Checkout::init();
    SnipVIP_Webhook::init();
} );