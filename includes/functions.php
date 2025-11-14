<?php
/**
 * Helper Functions
 * 
 * Global yardımcı fonksiyonlar
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Data Hasher instance
 * 
 * @return Trackify_CAPI_Data_Hasher
 */
function trackify_capi_get_data_hasher() {
    static $hasher = null;
    
    if ( is_null( $hasher ) ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
    }
    
    return $hasher;
}

/**
 * Get Cookie Manager instance
 * 
 * @return Trackify_CAPI_Cookie_Manager
 */
function trackify_capi_get_cookie_manager() {
    static $cookie_manager = null;
    
    if ( is_null( $cookie_manager ) ) {
        $cookie_manager = new Trackify_CAPI_Cookie_Manager();
    }
    
    return $cookie_manager;
}

/**
 * Generate unique event ID
 * 
 * @param string $prefix Event prefix
 * @param string|int $identifier Optional identifier
 * @return string
 */
function trackify_capi_generate_event_id( $prefix = 'event', $identifier = '' ) {
    return sanitize_key( $prefix ) . '_' . 
           ( $identifier ? $identifier . '_' : '' ) . 
           uniqid() . '_' . 
           time();
}

/**
 * Get client IP address
 * 
 * @return string
 */
function trackify_capi_get_client_ip() {
    $ip_keys = array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    );
    
    foreach ( $ip_keys as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
            
            if ( strpos( $ip, ',' ) !== false ) {
                $ips = explode( ',', $ip );
                $ip = trim( $ips[0] );
            }
            
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Get current URL
 * 
 * @return string
 */
function trackify_capi_get_current_url() {
    if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
        $protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . 
               sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . 
               sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
    }
    
    return home_url( '/' );
}

/**
 * Format number with localization
 * 
 * @param int|float $number
 * @param int $decimals
 * @return string
 */
function trackify_capi_format_number( $number, $decimals = 0 ) {
    return number_format_i18n( $number, $decimals );
}

/**
 * Human time difference
 * 
 * @param string|int $datetime Timestamp or date string
 * @return string
 */
function trackify_capi_human_time_diff( $datetime ) {
    $timestamp = is_numeric( $datetime ) ? $datetime : strtotime( $datetime );
    
    return sprintf(
        /* translators: %s: Time difference */
        __( '%s ago', 'trackify-capi' ),
        human_time_diff( $timestamp, current_time( 'timestamp' ) )
    );
}

/**
 * Check if SSL
 * 
 * @return bool
 */
function trackify_capi_is_ssl() {
    return is_ssl();
}

/**
 * Check if current page is Trackify CAPI admin page
 * 
 * @return bool
 */
function trackify_capi_is_admin_page() {
    if ( ! is_admin() ) {
        return false;
    }
    
    $screen = get_current_screen();
    
    if ( ! $screen ) {
        return false;
    }
    
    return strpos( $screen->id, 'trackify-capi' ) !== false;
}

/**
 * Log message
 * 
 * @param string $message
 * @param array $context
 * @param string $level
 */
function trackify_capi_log( $message, $context = array(), $level = 'info' ) {
    if ( ! function_exists( 'trackify_capi' ) ) {
        return;
    }
    
    $logger = trackify_capi()->get_component( 'logger' );
    
    if ( ! $logger ) {
        return;
    }
    
    $context['message'] = $message;
    $logger->log( $context, $level );
}

/**
 * Debug log (only in debug mode)
 * 
 * @param mixed $data
 * @param string $title
 */
function trackify_capi_debug( $data, $title = 'Debug' ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }
    
    error_log( '========== TRACKIFY CAPI: ' . $title . ' ==========' );
    error_log( print_r( $data, true ) );
    error_log( '========== END DEBUG ==========' );
}

/**
 * Check if WooCommerce is active
 * 
 * @return bool
 */
function trackify_capi_is_woocommerce_active() {
    return class_exists( 'WooCommerce' );
}

/**
 * Get WooCommerce currency
 * 
 * @return string
 */
function trackify_capi_get_currency() {
    if ( function_exists( 'get_woocommerce_currency' ) ) {
        return get_woocommerce_currency();
    }
    
    return 'USD';
}

/**
 * Sanitize array recursively
 * 
 * @param array $array
 * @return array
 */
function trackify_capi_sanitize_array( $array ) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = trackify_capi_sanitize_array( $value );
        } else {
            $value = sanitize_text_field( $value );
        }
    }
    
    return $array;
}

/**
 * Get plugin data
 * 
 * @return array
 */
function trackify_capi_get_plugin_data() {
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    return get_plugin_data( TRACKIFY_CAPI_FILE );
}

/**
 * Check if user has admin capability
 * 
 * @return bool
 */
function trackify_capi_user_can_manage() {
    return current_user_can( 'manage_options' );
}

/**
 * Get available integrations
 * 
 * @return array
 */
function trackify_capi_get_available_integrations() {
    return array(
        'woocommerce' => array(
            'name' => 'WooCommerce',
            'active' => class_exists( 'WooCommerce' ),
            'required' => 'WooCommerce plugin',
        ),
        'cf7' => array(
            'name' => 'Contact Form 7',
            'active' => defined( 'WPCF7_VERSION' ),
            'required' => 'Contact Form 7 plugin',
        ),
        'wpforms' => array(
            'name' => 'WPForms',
            'active' => defined( 'WPFORMS_VERSION' ),
            'required' => 'WPForms plugin',
        ),
        'gravity_forms' => array(
            'name' => 'Gravity Forms',
            'active' => class_exists( 'GFForms' ),
            'required' => 'Gravity Forms plugin',
        ),
        'elementor' => array(
            'name' => 'Elementor Forms',
            'active' => defined( 'ELEMENTOR_PRO_VERSION' ),
            'required' => 'Elementor Pro plugin',
        ),
        'fluent_forms' => array(
            'name' => 'Fluent Forms',
            'active' => defined( 'FLUENTFORM' ),
            'required' => 'Fluent Forms plugin',
        ),
        'ninja_forms' => array(
            'name' => 'Ninja Forms',
            'active' => class_exists( 'Ninja_Forms' ),
            'required' => 'Ninja Forms plugin',
        ),
    );
}

/**
 * Get system info
 * 
 * @return array
 */
function trackify_capi_get_system_info() {
    global $wpdb;
    
    return array(
        'plugin_version' => TRACKIFY_CAPI_VERSION,
        'wordpress_version' => get_bloginfo( 'version' ),
        'php_version' => PHP_VERSION,
        'mysql_version' => $wpdb->db_version(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'max_upload_size' => wp_max_upload_size(),
        'memory_limit' => WP_MEMORY_LIMIT,
        'max_execution_time' => ini_get( 'max_execution_time' ),
        'timezone' => wp_timezone_string(),
        'locale' => get_locale(),
    );
}

/**
 * Create Event Builder instance
 * 
 * @param string $event_name
 * @return Trackify_CAPI_Event_Builder
 */
function trackify_capi_event( $event_name ) {
    return new Trackify_CAPI_Event_Builder( $event_name );
}

/**
 * Quick track event (shorthand)
 * 
 * @param string $event_name
 * @param array $custom_data
 * @param array $user_data
 * @return array|WP_Error
 */
function trackify_capi_track( $event_name, $custom_data = array(), $user_data = array() ) {
    $capi = trackify_capi()->get_component( 'capi' );
    
    if ( ! $capi ) {
        return new WP_Error( 'no_capi', __( 'CAPI not initialized', 'trackify-capi' ) );
    }
    
    return $capi->send_event( $event_name, $custom_data, $user_data );
}