<?php
/**
 * Plugin Name: Trackify CAPI - Meta Pixel & Conversions API
 * Plugin URI: https://trackify-capi.com
 * Description: Professional Meta Pixel and Conversions API integration for WordPress. Track events with both client-side Pixel and server-side CAPI for maximum accuracy and Event Match Quality (EMQ).
 * Version: 2.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Trackify Team
 * Author URI: https://trackify-capi.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: trackify-capi
 * Domain Path: /languages
 * 
 * @package Trackify_CAPI
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Plugin version
if ( ! defined( 'TRACKIFY_CAPI_VERSION' ) ) {
    define( 'TRACKIFY_CAPI_VERSION', '2.0.0' );
}

// Plugin file
if ( ! defined( 'TRACKIFY_CAPI_FILE' ) ) {
    define( 'TRACKIFY_CAPI_FILE', __FILE__ );
}

// Plugin basename
if ( ! defined( 'TRACKIFY_CAPI_BASENAME' ) ) {
    define( 'TRACKIFY_CAPI_BASENAME', plugin_basename( TRACKIFY_CAPI_FILE ) );
}

// Plugin directory
if ( ! defined( 'TRACKIFY_CAPI_DIR' ) ) {
    define( 'TRACKIFY_CAPI_DIR', plugin_dir_path( TRACKIFY_CAPI_FILE ) );
}

// Plugin URL
if ( ! defined( 'TRACKIFY_CAPI_URL' ) ) {
    define( 'TRACKIFY_CAPI_URL', plugin_dir_url( TRACKIFY_CAPI_FILE ) );
}

// Includes directory
if ( ! defined( 'TRACKIFY_CAPI_INCLUDES' ) ) {
    define( 'TRACKIFY_CAPI_INCLUDES', TRACKIFY_CAPI_DIR . 'includes/' );
}

// Assets directory
if ( ! defined( 'TRACKIFY_CAPI_ASSETS' ) ) {
    define( 'TRACKIFY_CAPI_ASSETS', TRACKIFY_CAPI_URL . 'assets/' );
}

/**
 * Check if plugin requirements are met
 * 
 * @return bool
 */
function trackify_capi_check_requirements() {
    // Check PHP version
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        add_action( 'admin_notices', 'trackify_capi_php_version_notice' );
        return false;
    }
    
    // Check WordPress version
    global $wp_version;
    if ( version_compare( $wp_version, '5.8', '<' ) ) {
        add_action( 'admin_notices', 'trackify_capi_wp_version_notice' );
        return false;
    }
    
    return true;
}

/**
 * PHP version notice
 */
function trackify_capi_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'Trackify CAPI:', 'trackify-capi' ); ?></strong>
            <?php
            printf(
                /* translators: %s: Required PHP version */
                esc_html__( 'Bu plugin PHP 7.4 veya üstü gerektirir. Sunucunuzda PHP %s yüklü.', 'trackify-capi' ),
                esc_html( PHP_VERSION )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * WordPress version notice
 */
function trackify_capi_wp_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'Trackify CAPI:', 'trackify-capi' ); ?></strong>
            <?php
            printf(
                /* translators: %s: Required WordPress version */
                esc_html__( 'Bu plugin WordPress 5.8 veya üstü gerektirir. Sitenizde WordPress %s yüklü.', 'trackify-capi' ),
                esc_html( get_bloginfo( 'version' ) )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Load plugin
 */
function trackify_capi_load_plugin() {
    // Check requirements
    if ( ! trackify_capi_check_requirements() ) {
        return;
    }
    
    // Load core class
    require_once TRACKIFY_CAPI_INCLUDES . 'class-core.php';
    
    // Initialize plugin
    Trackify_CAPI_Core::instance();
}

// Load plugin
add_action( 'plugins_loaded', 'trackify_capi_load_plugin' );

/**
 * Get plugin instance
 * 
 * @return Trackify_CAPI_Core
 */
function trackify_capi() {
    return Trackify_CAPI_Core::instance();
}

/**
 * Plugin activation hook
 */
register_activation_hook( TRACKIFY_CAPI_FILE, function() {
    // Check requirements before activation
    if ( ! trackify_capi_check_requirements() ) {
        deactivate_plugins( TRACKIFY_CAPI_BASENAME );
        
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            wp_die(
                sprintf(
                    /* translators: %s: Required PHP version */
                    esc_html__( 'Trackify CAPI PHP 7.4 veya üstü gerektirir. Sunucunuzda PHP %s yüklü.', 'trackify-capi' ),
                    esc_html( PHP_VERSION )
                ),
                esc_html__( 'Plugin Activation Error', 'trackify-capi' ),
                array( 'back_link' => true )
            );
        }
        
        global $wp_version;
        if ( version_compare( $wp_version, '5.8', '<' ) ) {
            wp_die(
                sprintf(
                    /* translators: %s: Required WordPress version */
                    esc_html__( 'Trackify CAPI WordPress 5.8 veya üstü gerektirir. Sitenizde WordPress %s yüklü.', 'trackify-capi' ),
                    esc_html( $wp_version )
                ),
                esc_html__( 'Plugin Activation Error', 'trackify-capi' ),
                array( 'back_link' => true )
            );
        }
    }
    
    // Load dependencies for activation
    require_once TRACKIFY_CAPI_INCLUDES . 'class-installer.php';
    
    // Run activation
    $installer = new Trackify_CAPI_Installer();
    $installer->install();
    
    // Set activation redirect flag
    set_transient( 'trackify_capi_activation_redirect', true, 30 );
});

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( TRACKIFY_CAPI_FILE, function() {
    // Clear scheduled hooks
    wp_clear_scheduled_hook( 'trackify_capi_cleanup_logs' );
    wp_clear_scheduled_hook( 'trackify_capi_update_analytics' );
    wp_clear_scheduled_hook( 'trackify_capi_process_queue' );
    
    // Delete transients
    delete_transient( 'trackify_capi_activation_redirect' );
    
    do_action( 'trackify_capi_deactivated' );
});

/**
 * Add action links to plugin page
 * 
 * @param array $links
 * @return array
 */
function trackify_capi_plugin_action_links( $links ) {
    $custom_links = array(
        '<a href="' . admin_url( 'admin.php?page=trackify-capi-settings' ) . '">' . __( 'Ayarlar', 'trackify-capi' ) . '</a>',
        '<a href="' . admin_url( 'admin.php?page=trackify-capi-logs' ) . '">' . __( 'Logs', 'trackify-capi' ) . '</a>',
        '<a href="https://trackify-capi.com/docs/" target="_blank">' . __( 'Dokümantasyon', 'trackify-capi' ) . '</a>',
    );
    
    return array_merge( $custom_links, $links );
}
add_filter( 'plugin_action_links_' . TRACKIFY_CAPI_BASENAME, 'trackify_capi_plugin_action_links' );

/**
 * Add meta links to plugin page
 * 
 * @param array $links
 * @param string $file
 * @return array
 */
function trackify_capi_plugin_row_meta( $links, $file ) {
    if ( TRACKIFY_CAPI_BASENAME === $file ) {
        $row_meta = array(
            'docs' => '<a href="https://trackify-capi.com/docs/" target="_blank">' . __( 'Dokümantasyon', 'trackify-capi' ) . '</a>',
            'support' => '<a href="https://wordpress.org/support/plugin/trackify-capi/" target="_blank">' . __( 'Destek', 'trackify-capi' ) . '</a>',
            'rate' => '<a href="https://wordpress.org/support/plugin/trackify-capi/reviews/#new-post" target="_blank">' . __( 'Puan Ver', 'trackify-capi' ) . '</a>',
        );
        
        return array_merge( $links, $row_meta );
    }
    
    return $links;
}
add_filter( 'plugin_row_meta', 'trackify_capi_plugin_row_meta', 10, 2 );

/**
 * Display admin notices
 */
function trackify_capi_admin_notices() {
    // Don't show notices on setup wizard page
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'trackify-capi-setup' ) {
        return;
    }
    
    // Setup not completed notice
    if ( ! get_option( 'trackify_capi_setup_completed' ) && current_user_can( 'manage_options' ) ) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Trackify CAPI:', 'trackify-capi' ); ?></strong>
                <?php esc_html_e( 'Kurulumu tamamlamak için setup wizard\'ı çalıştırın.', 'trackify-capi' ); ?>
                <a href="<?php echo esc_url( admin_url( 'index.php?page=trackify-capi-setup' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Kuruluma Başla', 'trackify-capi' ); ?>
                </a>
                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'trackify_skip_setup', '1' ), 'trackify_skip_setup' ) ); ?>" class="button" style="margin-left: 5px;">
                    <?php esc_html_e( 'Kurulumu Atla', 'trackify-capi' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    // Handle skip setup
    if ( isset( $_GET['trackify_skip_setup'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'trackify_skip_setup' ) ) {
        update_option( 'trackify_capi_setup_completed', true );
        wp_safe_redirect( admin_url( 'admin.php?page=trackify-capi' ) );
        exit;
    }
    
    // Setup complete notice
    if ( isset( $_GET['setup_complete'] ) && $_GET['setup_complete'] === '1' ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Trackify CAPI:', 'trackify-capi' ); ?></strong>
                <?php esc_html_e( 'Kurulum başarıyla tamamlandı! 🎉', 'trackify-capi' ); ?>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'trackify_capi_admin_notices' );

/**
 * Load plugin textdomain
 */
function trackify_capi_load_textdomain() {
    load_plugin_textdomain(
        'trackify-capi',
        false,
        dirname( TRACKIFY_CAPI_BASENAME ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'trackify_capi_load_textdomain' );

/**
 * Add admin body class
 * 
 * @param string $classes
 * @return string
 */
function trackify_capi_admin_body_class( $classes ) {
    if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'trackify-capi' ) !== false ) {
        $classes .= ' trackify-capi-admin-page';
    }
    
    return $classes;
}
add_filter( 'admin_body_class', 'trackify_capi_admin_body_class' );

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
 * AJAX: Dismiss admin notice
 */
function trackify_capi_dismiss_notice() {
    check_ajax_referer( 'trackify_capi_dismiss_notice', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }
    
    $notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';
    
    if ( $notice ) {
        update_user_meta( get_current_user_id(), 'trackify_capi_dismissed_' . $notice, true );
        wp_send_json_success();
    }
    
    wp_send_json_error();
}
add_action( 'wp_ajax_trackify_capi_dismiss_notice', 'trackify_capi_dismiss_notice' );

/**
 * Debug function
 * 
 * @param mixed $data
 * @param string $title
 */
function trackify_capi_debug( $data, $title = 'Debug' ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }
    
    error_log( '========== TRACKIFY CAPI DEBUG: ' . $title . ' ==========' );
    error_log( print_r( $data, true ) );
    error_log( '========== END DEBUG ==========' );
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
    if ( trackify_capi_is_woocommerce_active() ) {
        return get_woocommerce_currency();
    }
    
    return 'USD';
}

/**
 * Log function
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
    
    $logger->log( $message, $context, $level );
}

/**
 * Format number
 * 
 * @param int|float $number
 * @return string
 */
function trackify_capi_format_number( $number ) {
    return number_format_i18n( $number );
}

/**
 * Human time diff
 * 
 * @param string $datetime
 * @return string
 */
function trackify_capi_human_time_diff( $datetime ) {
    return sprintf(
        /* translators: %s: Time difference */
        __( '%s önce', 'trackify-capi' ),
        human_time_diff( strtotime( $datetime ), current_time( 'timestamp' ) )
    );
}

/**
 * Generate event ID
 * 
 * @param string $prefix
 * @param string|int $identifier
 * @return string
 */
function trackify_capi_generate_event_id( $prefix = 'event', $identifier = '' ) {
    return sanitize_key( $prefix ) . '_' . ( $identifier ? $identifier . '_' : '' ) . uniqid() . '_' . time();
}

/**
 * Get client IP
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
 * Check if SSL
 * 
 * @return bool
 */
function trackify_capi_is_ssl() {
    return is_ssl();
}

/**
 * Get current URL
 * 
 * @return string
 */
function trackify_capi_get_current_url() {
    if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
        $protocol = trackify_capi_is_ssl() ? 'https' : 'http';
        return $protocol . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
    }
    
    return home_url( '/' );
}

