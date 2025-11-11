<?php
/**
 * Admin Class
 * 
 * Admin panel yönetimi ve menü oluşturma
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Admin {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        
        // Admin menü
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Admin init
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_trackify_send_test_event', array( $this, 'ajax_send_test_event' ) );
        add_action( 'wp_ajax_trackify_clear_logs', array( $this, 'ajax_clear_logs' ) );
        add_action( 'wp_ajax_trackify_export_settings', array( $this, 'ajax_export_settings' ) );
        add_action( 'wp_ajax_trackify_import_settings', array( $this, 'ajax_import_settings' ) );
        
        // Admin notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        
        // Plugin action links
        add_filter( 'plugin_action_links_' . TRACKIFY_CAPI_BASENAME, array( $this, 'plugin_action_links' ) );
    }
    
    /**
     * Admin menü ekle
     * 
     * @since 2.0.0
     */
    public function add_admin_menu() {
        // Ana menü
        add_menu_page(
            __( 'Trackify CAPI', 'trackify-capi' ),
            __( 'Trackify CAPI', 'trackify-capi' ),
            'manage_options',
            'trackify-capi',
            array( $this, 'render_dashboard_page' ),
            'dashicons-analytics',
            56
        );
        
        // Dashboard (ana sayfa)
        add_submenu_page(
            'trackify-capi',
            __( 'Dashboard', 'trackify-capi' ),
            __( 'Dashboard', 'trackify-capi' ),
            'manage_options',
            'trackify-capi',
            array( $this, 'render_dashboard_page' )
        );
        
        // Settings
        add_submenu_page(
            'trackify-capi',
            __( 'Ayarlar', 'trackify-capi' ),
            __( 'Ayarlar', 'trackify-capi' ),
            'manage_options',
            'trackify-capi-settings',
            array( $this, 'render_settings_page' )
        );
        
        // Event Logs
        add_submenu_page(
            'trackify-capi',
            __( 'Event Logs', 'trackify-capi' ),
            __( 'Event Logs', 'trackify-capi' ),
            'manage_options',
            'trackify-capi-logs',
            array( $this, 'render_logs_page' )
        );
        
        // Analytics
        add_submenu_page(
            'trackify-capi',
            __( 'Analytics', 'trackify-capi' ),
            __( 'Analytics', 'trackify-capi' ),
            'manage_options',
            'trackify-capi-analytics',
            array( $this, 'render_analytics_page' )
        );
        
        // Tools
        add_submenu_page(
            'trackify-capi',
            __( 'Araçlar', 'trackify-capi' ),
            __( 'Araçlar', 'trackify-capi' ),
            'manage_options',
            'trackify-capi-tools',
            array( $this, 'render_tools_page' )
        );
    }
    
    /**
     * Admin init
     * 
     * @since 2.0.0
     */
    public function admin_init() {
        // Ayarları kaydet
        register_setting(
            'trackify_capi_settings_group',
            'trackify_capi_settings',
            array( $this, 'sanitize_settings' )
        );
    }
    
    /**
     * Ayarları sanitize et
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_settings( $input ) {
        return $this->settings->sanitize_settings( $input );
    }
    
    /**
     * Dashboard page render
     */
    public function render_dashboard_page() {
        require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-settings-page.php';
        $page = new Trackify_CAPI_Settings_Page();
        $page->render_dashboard();
    }
    
    /**
     * Settings page render
     */
    public function render_settings_page() {
        require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-settings-page.php';
        $page = new Trackify_CAPI_Settings_Page();
        $page->render();
    }
    
    /**
     * Logs page render
     */
    public function render_logs_page() {
        require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-events-page.php';
        $page = new Trackify_CAPI_Events_Page();
        $page->render();
    }
    
    /**
     * Analytics page render
     */
    public function render_analytics_page() {
        require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-analytics-page.php';
        $page = new Trackify_CAPI_Analytics_Page();
        $page->render();
    }
    
    /**
     * Tools page render
     */
    public function render_tools_page() {
        require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-tools-page.php';
        $page = new Trackify_CAPI_Tools_Page();
        $page->render();
    }
    
    /**
     * AJAX: Test event gönder
     */
    public function ajax_send_test_event() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Yetki hatası', 'trackify-capi' ) ) );
        }
        
        $capi = trackify_capi()->get_component( 'capi' );
        $event_id = trackify_capi_generate_event_id( 'test', 'admin' );
        
        $result = $capi->send_event(
            'PageView',
            array(
                'content_name' => 'Test Event from Admin',
                'value' => 99.99,
                'currency' => 'USD',
            ),
            array(),
            $event_id
        );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Test event başarıyla gönderildi', 'trackify-capi' ),
            'event_id' => $event_id,
            'result' => $result,
        ) );
    }
    
    /**
     * AJAX: Logları temizle
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        
        $logger = trackify_capi()->get_component( 'logger' );
        $logger->clear_all_logs();
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Ayarları export et
     */
    public function ajax_export_settings() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        
        $json = $this->settings->export();
        
        wp_send_json_success( array( 'json' => $json ) );
    }
    
    /**
     * AJAX: Ayarları import et
     */
    public function ajax_import_settings() {
        check_ajax_referer( 'trackify_capi_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        
        $json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';
        
        $result = $this->settings->import( $json );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        wp_send_json_success();
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Kurulum tamamlanmadıysa uyar
        if ( ! get_option( 'trackify_capi_setup_completed' ) && trackify_capi_is_admin_page() ) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e( 'Trackify CAPI:', 'trackify-capi' ); ?></strong>
                    <?php esc_html_e( 'Kurulumu tamamlamak için setup wizard\'ı çalıştırın.', 'trackify-capi' ); ?>
                    <a href="<?php echo esc_url( admin_url( 'index.php?page=trackify-capi-setup' ) ); ?>" class="button button-primary" style="margin-left:10px;">
                        <?php esc_html_e( 'Kuruluma Başla', 'trackify-capi' ); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Plugin action links
     * 
     * @param array $links
     * @return array
     */
    public function plugin_action_links( $links ) {
        $custom_links = array(
            '<a href="' . admin_url( 'admin.php?page=trackify-capi-settings' ) . '">' . __( 'Ayarlar', 'trackify-capi' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=trackify-capi-logs' ) . '">' . __( 'Logs', 'trackify-capi' ) . '</a>',
        );
        
        return array_merge( $custom_links, $links );
    }
}