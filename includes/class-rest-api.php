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
                    'event_name' => array(
                        'type' => 'string',
                    ),
                    'date_from' => array(
                        'type' => 'string',
                    ),
                    'date_to' => array(
                        'type' => 'string',
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
        
        // Pixels endpoint (admin only)
        register_rest_route(
            $this->namespace,
            '/pixels',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_pixels' ),
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
                'fluent_forms' => defined( 'FLUENTFORM' ),
                'ninja_forms' => class_exists( 'Ninja_Forms' ),
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
        
        // Toplam hesaplamalar
        $total_events = 0;
        $successful_events = 0;
        $failed_events = 0;
        
        foreach ( $stats as $stat ) {
            $total_events += $stat['total'];
            $successful_events += $stat['successful'];
            $failed_events += $stat['failed'];
        }
        
        $success_rate = $total_events > 0 ? round( ( $successful_events / $total_events ) * 100, 1 ) : 0;
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'days' => $days,
                'summary' => array(
                    'total_events' => $total_events,
                    'successful_events' => $successful_events,
                    'failed_events' => $failed_events,
                    'success_rate' => $success_rate,
                    'average_per_day' => $total_events > 0 ? round( $total_events / $days, 1 ) : 0,
                ),
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
        $event_name = $request->get_param( 'event_name' );
        $date_from = $request->get_param( 'date_from' );
        $date_to = $request->get_param( 'date_to' );
        
        $filters = array();
        
        if ( $status ) {
            $filters['status'] = $status;
        }
        
        if ( $event_name ) {
            $filters['event_name'] = $event_name;
        }
        
        if ( $date_from ) {
            $filters['date_from'] = $date_from;
        }
        
        if ( $date_to ) {
            $filters['date_to'] = $date_to;
        }
        
        $logs = $this->logger->get_recent_logs( $limit, $filters );
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'count' => count( $logs ),
                'filters' => $filters,
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
            'content_category' => 'test',
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
     * Pixels endpoint
     * 
     * @return WP_REST_Response
     */
    public function get_pixels() {
        $all_pixels = $this->settings->get( 'pixels', array() );
        $active_pixels = $this->settings->get_active_pixels();
        
        // Token'ları gizle
        $safe_pixels = array();
        foreach ( $all_pixels as $pixel ) {
            $safe_pixel = $pixel;
            if ( ! empty( $safe_pixel['access_token'] ) ) {
                $safe_pixel['access_token'] = substr( $safe_pixel['access_token'], 0, 10 ) . '...';
            }
            $safe_pixels[] = $safe_pixel;
        }
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'total_pixels' => count( $all_pixels ),
                'active_pixels' => count( $active_pixels ),
                'pixels' => $safe_pixels,
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