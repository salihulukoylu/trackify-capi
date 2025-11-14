<?php
/**
 * AJAX Handlers
 * 
 * Admin AJAX isteklerini yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_AJAX_Handlers {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    private $logger;
    
    /**
     * CAPI instance
     * 
     * @var Trackify_CAPI_CAPI
     */
    private $capi;
    
    /**
     * Constructor
     * 
     * @param Trackify_CAPI_Settings $settings
     * @param Trackify_CAPI_Logger $logger
     * @param Trackify_CAPI_CAPI $capi
     */
    public function __construct( $settings, $logger, $capi ) {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->capi = $capi;
        
        $this->init_hooks();
    }
    
    /**
     * Init hooks
     */
    private function init_hooks() {
        // Admin AJAX
        add_action( 'wp_ajax_trackify_send_test_event', array( $this, 'send_test_event' ) );
        add_action( 'wp_ajax_trackify_clear_logs', array( $this, 'clear_logs' ) );
        add_action( 'wp_ajax_trackify_export_settings', array( $this, 'export_settings' ) );
        add_action( 'wp_ajax_trackify_import_settings', array( $this, 'import_settings' ) );
        add_action( 'wp_ajax_trackify_get_stats', array( $this, 'get_stats' ) );
        add_action( 'wp_ajax_trackify_get_recent_logs', array( $this, 'get_recent_logs' ) );
        add_action( 'wp_ajax_trackify_delete_log', array( $this, 'delete_log' ) );
        add_action( 'wp_ajax_trackify_test_pixel', array( $this, 'test_pixel' ) );
        add_action( 'wp_ajax_trackify_verify_access_token', array( $this, 'verify_access_token' ) );
        
        // Frontend AJAX (logged in users)
        add_action( 'wp_ajax_trackify_track_event', array( $this, 'track_event_ajax' ) );
        
        // Public AJAX
        add_action( 'wp_ajax_nopriv_trackify_track_event', array( $this, 'track_event_ajax' ) );
    }
    
    /**
     * Send test event
     */
    public function send_test_event() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $event_id = trackify_capi_generate_event_id( 'test', 'admin' );
        
        $result = $this->capi->send_event(
            'PageView',
            array(
                'content_name' => 'Test Event from Admin Panel',
                'content_category' => 'test',
                'value' => 99.99,
                'currency' => 'USD',
            ),
            array(),
            $event_id
        );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Test event başarıyla gönderildi! Meta Events Manager\'da kontrol edebilirsiniz.', 'trackify-capi' ),
            'event_id' => $event_id,
            'result' => $result,
        ) );
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $this->logger->clear_all_logs();
        
        wp_send_json_success( array(
            'message' => __( 'Tüm loglar temizlendi', 'trackify-capi' ),
        ) );
    }
    
    /**
     * Export settings
     */
    public function export_settings() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $json = $this->settings->export();
        
        wp_send_json_success( array(
            'json' => $json,
        ) );
    }
    
    /**
     * Import settings
     */
    public function import_settings() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';
        
        if ( empty( $json ) ) {
            wp_send_json_error( array(
                'message' => __( 'JSON verisi bulunamadı', 'trackify-capi' ),
            ) );
        }
        
        $result = $this->settings->import( $json );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Ayarlar başarıyla içe aktarıldı', 'trackify-capi' ),
        ) );
    }
    
    /**
     * Get stats
     */
    public function get_stats() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 7;
        $stats = $this->logger->get_event_stats( $days );
        
        // Calculate totals
        $total_events = 0;
        $successful_events = 0;
        $failed_events = 0;
        
        foreach ( $stats as $stat ) {
            $total_events += $stat['total'];
            $successful_events += $stat['successful'];
            $failed_events += $stat['failed'];
        }
        
        $success_rate = $total_events > 0 ? round( ( $successful_events / $total_events ) * 100, 1 ) : 0;
        
        wp_send_json_success( array(
            'stats' => $stats,
            'summary' => array(
                'total_events' => $total_events,
                'successful_events' => $successful_events,
                'failed_events' => $failed_events,
                'success_rate' => $success_rate,
                'average_per_day' => $total_events > 0 ? round( $total_events / $days, 1 ) : 0,
            ),
        ) );
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 50;
        $filters = array();
        
        if ( ! empty( $_POST['status'] ) ) {
            $filters['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
        }
        
        if ( ! empty( $_POST['event_name'] ) ) {
            $filters['event_name'] = sanitize_text_field( wp_unslash( $_POST['event_name'] ) );
        }
        
        $logs = $this->logger->get_recent_logs( $limit, $filters );
        
        wp_send_json_success( array(
            'logs' => $logs,
            'count' => count( $logs ),
        ) );
    }
    
    /**
     * Delete specific log
     */
    public function delete_log() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;
        
        if ( ! $log_id ) {
            wp_send_json_error( array(
                'message' => __( 'Geçersiz log ID', 'trackify-capi' ),
            ) );
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'trackify_capi_events';
        
        $result = $wpdb->delete(
            $table,
            array( 'id' => $log_id ),
            array( '%d' )
        );
        
        if ( $result === false ) {
            wp_send_json_error( array(
                'message' => __( 'Log silinemedi', 'trackify-capi' ),
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Log başarıyla silindi', 'trackify-capi' ),
        ) );
    }
    
    /**
     * Test pixel connection
     */
    public function test_pixel() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $pixel_id = isset( $_POST['pixel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['pixel_id'] ) ) : '';
        
        if ( empty( $pixel_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Pixel ID gerekli', 'trackify-capi' ),
            ) );
        }
        
        // Check if pixel exists in Meta
        $url = 'https://graph.facebook.com/v18.0/' . $pixel_id;
        
        $response = wp_remote_get( $url );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array(
                'message' => $response->get_error_message(),
            ) );
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['error'] ) ) {
            wp_send_json_error( array(
                'message' => $data['error']['message'],
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Pixel doğrulandı', 'trackify-capi' ),
            'pixel_name' => $data['name'] ?? '',
        ) );
    }
    
    /**
     * Verify access token
     */
    public function verify_access_token() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetki hatası', 'trackify-capi' ),
            ) );
        }
        
        $access_token = isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';
        
        if ( empty( $access_token ) ) {
            wp_send_json_error( array(
                'message' => __( 'Access Token gerekli', 'trackify-capi' ),
            ) );
        }
        
        // Test token with Graph API
        $url = 'https://graph.facebook.com/v18.0/me?access_token=' . $access_token;
        
        $response = wp_remote_get( $url );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array(
                'message' => $response->get_error_message(),
            ) );
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['error'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Geçersiz Access Token', 'trackify-capi' ),
                'error' => $data['error']['message'],
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Access Token doğrulandı', 'trackify-capi' ),
            'app_name' => $data['name'] ?? '',
        ) );
    }
    
    /**
     * Track event via AJAX
     */
    public function track_event_ajax() {
        check_ajax_referer( 'trackify_capi_nonce', 'nonce' );
        
        $event_name = isset( $_POST['event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['event_name'] ) ) : '';
        $event_data = isset( $_POST['event_data'] ) ? json_decode( stripslashes( $_POST['event_data'] ), true ) : array();
        
        if ( empty( $event_name ) ) {
            wp_send_json_error( array(
                'message' => __( 'Event name gerekli', 'trackify-capi' ),
            ) );
        }
        
        $event_id = trackify_capi_generate_event_id( strtolower( $event_name ) );
        
        $result = $this->capi->send_event(
            $event_name,
            $event_data,
            array(),
            $event_id
        );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Event gönderildi', 'trackify-capi' ),
            'event_id' => $event_id,
        ) );
    }
}