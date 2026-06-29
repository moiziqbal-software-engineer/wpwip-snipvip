<?php
defined( 'ABSPATH' ) || exit;

class SnipVIP_REST_API {

    const NAMESPACE = 'snipvip/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes(): void {

        // POST /wp-json/snipvip/v1/shorten — create a short link
        register_rest_route( self::NAMESPACE, '/shorten', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'shorten' ],
            'permission_callback' => [ __CLASS__, 'require_login' ],
            'args'                => [
                'url'   => [
                    'required'          => true,
                    'sanitize_callback' => 'esc_url_raw',
                    'validate_callback' => fn( $v ) => filter_var( $v, FILTER_VALIDATE_URL ),
                ],
                'title' => [
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ],
            ],
        ] );

        // GET /wp-json/snipvip/v1/links — list user's links
        register_rest_route( self::NAMESPACE, '/links', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_links' ],
            'permission_callback' => [ __CLASS__, 'require_login' ],
        ] );

        // DELETE /wp-json/snipvip/v1/links/{id} — delete a link
        register_rest_route( self::NAMESPACE, '/links/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ __CLASS__, 'delete_link' ],
            'permission_callback' => [ __CLASS__, 'require_login' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                ],
            ],
        ] );

        // GET /wp-json/snipvip/v1/links/{id}/stats — click analytics
        register_rest_route( self::NAMESPACE, '/links/(?P<id>\d+)/stats', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'link_stats' ],
            'permission_callback' => [ __CLASS__, 'require_login' ],
        ] );

        // GET /wp-json/snipvip/v1/plan — user's current plan info
        register_rest_route( self::NAMESPACE, '/plan', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'plan_info' ],
            'permission_callback' => [ __CLASS__, 'require_login' ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Endpoint handlers
    // -------------------------------------------------------------------------

    public static function shorten( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $user_id = get_current_user_id();
        $result  = SnipVIP_Link_Engine::create_link(
            $user_id,
            $request->get_param( 'url' ),
            $request->get_param( 'title' )
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'link'    => $result,
            'plan'    => SnipVIP_Plan_Guard::get_plan_info( $user_id ),
        ] );
    }

    public static function list_links( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        $links   = SnipVIP_Link_Engine::get_user_links( $user_id );
        $plan    = SnipVIP_Plan_Guard::get_plan_info( $user_id );

        return rest_ensure_response( [
            'links' => $links,
            'plan'  => $plan,
        ] );
    }

    public static function delete_link( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $result = SnipVIP_Link_Engine::delete_link(
            (int) $request->get_param( 'id' ),
            get_current_user_id()
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'success' => true ] );
    }

    public static function link_stats( \WP_REST_Request $request ): \WP_REST_Response {
        $stats = SnipVIP_Analytics::get_link_stats( (int) $request->get_param( 'id' ) );
        return rest_ensure_response( $stats );
    }

    public static function plan_info( \WP_REST_Request $request ): \WP_REST_Response {
        return rest_ensure_response(
            SnipVIP_Plan_Guard::get_plan_info( get_current_user_id() )
        );
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    public static function require_login(): bool|\WP_Error {
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'not_logged_in',
                'You must be logged in to use SnipVIP.',
                [ 'status' => 401 ]
            );
        }
        return true;
    }
}