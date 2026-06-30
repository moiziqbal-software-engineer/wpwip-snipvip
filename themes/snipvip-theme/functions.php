<?php
defined( 'ABSPATH' ) || exit;

/**
 * Theme setup
 */
add_action( 'after_setup_theme', function() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [
        'script',
        'style',
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ] );
} );

/**
 * Enqueue styles and scripts
 */
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'snipvip-theme',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get( 'Version' )
    );

    // Only load dashboard JS on the dashboard page
    if ( is_page( 'dashboard' ) ) {
        wp_enqueue_script(
            'snipvip-dashboard',
            get_template_directory_uri() . '/assets/js/dashboard.js',
            [],
            '1.0.0',
            true
        );

        // Pass REST API URL and nonce to JS
        wp_localize_script( 'snipvip-dashboard', 'SnipVIP', [
            'apiUrl' => rest_url( 'snipvip/v1' ),
            'nonce'  => wp_create_nonce( 'wp_rest' ),
            'user'   => [
                'id'       => get_current_user_id(),
                'loggedIn' => is_user_logged_in(),
            ],
        ] );
    }

    // Load homepage JS on front page
    if ( is_front_page() ) {
        wp_enqueue_script(
            'snipvip-home',
            get_template_directory_uri() . '/assets/js/home.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script( 'snipvip-home', 'SnipVIP', [
            'apiUrl'      => rest_url( 'snipvip/v1' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'loginUrl'    => wp_login_url( home_url( '/dashboard/' ) ),
            'dashboardUrl'=> home_url( '/dashboard/' ),
            'user'        => [
                'id'       => get_current_user_id(),
                'loggedIn' => is_user_logged_in(),
            ],
        ] );
    }

    // Load pricing JS on pricing page
    if ( is_page( 'pricing' ) ) {
        wp_enqueue_script(
            'snipvip-pricing',
            get_template_directory_uri() . '/assets/js/pricing.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script( 'snipvip-pricing', 'SnipVIP', [
            'apiUrl'   => rest_url( 'snipvip/v1' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'loginUrl' => wp_login_url( home_url( '/pricing/' ) ),
            'user'     => [
                'id'       => get_current_user_id(),
                'loggedIn' => is_user_logged_in(),
            ],
        ] );
    }
} );

/**
 * Redirect logged-out users away from dashboard
 */
add_action( 'template_redirect', function() {
    if ( is_page( 'dashboard' ) && ! is_user_logged_in() ) {
        wp_redirect( wp_login_url( home_url( '/dashboard/' ) ) );
        exit;
    }
} );

/**
 * Redirect logged-in users away from login page to dashboard
 */
add_action( 'template_redirect', function() {
    if ( is_user_logged_in() && $GLOBALS['pagenow'] === 'wp-login.php' ) {
        wp_redirect( home_url( '/dashboard/' ) );
        exit;
    }
} );

/**
 * Custom login/register redirect to dashboard
 */
add_filter( 'login_redirect', function( $redirect_to, $request, $user ) {
    return home_url( '/dashboard/' );
}, 10, 3 );

/**
 * Add custom nav menu
 */
add_action( 'after_setup_theme', function() {
    register_nav_menus( [
        'primary' => 'Primary Navigation',
    ] );
} );

/**
 * Helper — render the site nav
 */
function snipvip_nav(): void {
    $is_logged_in = is_user_logged_in();
    ?>
    <nav class="site-nav">
        <div class="container">
            <div class="site-nav__inner">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-nav__logo">
                    Snip<span>VIP</span>
                </a>
                <div class="site-nav__links">
                    <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--ghost">
                        Pricing
                    </a>
                    <?php if ( $is_logged_in ) : ?>
                        <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="btn btn--outline">
                            Dashboard
                        </a>
                        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="btn btn--ghost">
                            Log out
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn--outline">
                            Log in
                        </a>
                        <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="btn btn--primary">
                            Sign up free
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * Helper — render the site footer
 */
function snipvip_footer(): void {
    ?>
    <footer class="site-footer">
        <div class="container">
            <p>
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                <strong>SnipVIP</strong> &mdash;
                Built on <a href="https://wpvip.com" target="_blank" rel="noopener">WordPress VIP</a>
            </p>
        </div>
    </footer>
    <?php
}

// Use custom auth page instead of wp-login.php
add_filter( 'login_url', function( $url ) {
    return home_url( '/login/' );
}, 10 );

add_filter( 'register_url', function( $url ) {
    return home_url( '/login/?action=register' );
}, 10 );
add_filter( 'theme_page_templates', function( $templates ) {
    $templates['page-auth.php'] = 'Auth Page';
    return $templates;
} );