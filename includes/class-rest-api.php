<?php
/**
 * REST API Endpoints
 * 
 * Custom REST API endpoint'leri yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Rest_API {
    
    /**
     * API namespace
     * 
     * @var string
     */
    private $namespace = 'trackify-capi/v1';
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * CAPI instance
     * 
     * @var Trackify_CAPI_CAPI
     */
    private $capi;
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    private $logger;
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        $this->capi = trackify_capi()->get_component( 'capi' );
        $this->logger = trackify_capi()->get_component( 'logger' );
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * REST route'ları kaydet
     * 
     * @since 2.0.0
     */
    public function register_routes() {
        // Track event endpoint
        register_rest_route(
            $this->namespace,
            '/track',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'track_event' ),
                'permission_callback' => '__return_true',
                'args' => array(
                    'event_name' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_id' => array(
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'custom_data' => array(
                        'required' => false,
                        'type' => 'object',
                    ),
                    'user_data' => array(
                        'required' => false,
                        'type' => 'object',
                    ),
                ),
            )
        );
        
        // Health check endpoint
        register_rest_route(
            $this->namespace,
            '/health',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'health_check' ),
                'permission_callback' => '__return_true',
            )
        );
        
        // Stats endpoint (admin only)
        register_rest_route(
            $this->namespace,
            '/stats',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_stats' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
                'args' => array(
                    'days' => array(
                        'default' => 7,
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 90,
                    ),
                ),
            )
        );
        
        // Logs endpoint (admin only)
        register_rest_route(
            $this->namespace,
            '/logs',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_logs' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
                'args' => array(
                    'limit' => array(
                        'default' => 50,
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                    ),
                    'status' => array(
                        'type' => 'string',
                        'enum' => array( 'success', 'error', 'pending' ),
                    ),
                ),
            )
        );
        
        // Test event endpoint (admin only)
        register_rest_route(
            $this->namespace,
            '/test',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'send_test_event' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
            )
        );
    }
    
    /**
     * Track event endpoint handler
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function track_event( $request ) {
        $event_name = $request->get_param( 'event_name' );
        $event_id = $request->get_param( 'event_id' );
        $custom_data = $request->get_param( 'custom_data' );
        $user_data = $request->get_param( 'user_data' );
        
        // CAPI kapalıysa hata
        if ( ! $this->settings->is_capi_enabled() ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'Conversions API devre dışı', 'trackify-capi' ),
                ),
                400
            );
        }
        
        // Event gönder
        $result = $this->capi->send_event(
            $event_name,
            $custom_data ? (array) $custom_data : array(),
            $user_data ? (array) $user_data : array(),
            $event_id
        );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code(),
                ),
                500
            );
        }
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Event başarıyla gönderildi', 'trackify-capi' ),
                'data' => $result,
            ),
            200
        );
    }
    
    /**
     * Health check endpoint
     * 
     * @return WP_REST_Response
     */
    public function health_check() {
        $pixels = $this->settings->get_active_pixels();
        
        $status = array(
            'status' => 'ok',
            'version' => TRACKIFY_CAPI_VERSION,
            'plugin_enabled' => $this->settings->is_enabled(),
            'pixel_configured' => ! empty( $pixels ),
            'capi_enabled' => $this->settings->is_capi_enabled(),
            'pixels_count' => count( $pixels ),
            'integrations' => array(
                'woocommerce' => class_exists( 'WooCommerce' ),
                'cf7' => defined( 'WPCF7_VERSION' ),
                'wpforms' => defined( 'WPFORMS_VERSION' ),
                'gravity_forms' => class_exists( 'GFForms' ),
                'elementor' => defined( 'ELEMENTOR_VERSION' ),
            ),
            'timestamp' => current_time( 'mysql' ),
        );
        
        return new WP_REST_Response( $status, 200 );
    }
    
    /**
     * Stats endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_stats( $request ) {
        $days = $request->get_param( 'days' );
        
        $stats = $this->logger->get_event_stats( $days );
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'days' => $days,
                'stats' => $stats,
            ),
            200
        );
    }
    
    /**
     * Logs endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_logs( $request ) {
        $limit = $request->get_param( 'limit' );
        $status = $request->get_param( 'status' );
        
        $filters = array();
        if ( $status ) {
            $filters['status'] = $status;
        }
        
        $logs = $this->logger->get_recent_logs( $limit, $filters );
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'count' => count( $logs ),
                'logs' => $logs,
            ),
            200
        );
    }
    
    /**
     * Test event gönder
     * 
     * @return WP_REST_Response
     */
    public function send_test_event() {
        $event_id = trackify_capi_generate_event_id( 'test', 'api' );
        
        $custom_data = array(
            'content_name' => 'Test Event from REST API',
            'value' => 99.99,
            'currency' => 'USD',
        );
        
        $result = $this->capi->send_event( 'PageView', $custom_data, array(), $event_id );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                ),
                500
            );
        }
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Test event başarıyla gönderildi', 'trackify-capi' ),
                'event_id' => $event_id,
                'result' => $result,
            ),
            200
        );
    }
    
    /**
     * Admin permission kontrolü
     * 
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }
}