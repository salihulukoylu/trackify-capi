<?php
/**
 * Core Class
 * 
 * Plugin'in ana motoru - tüm bileşenleri yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Core {
    
    /**
     * Plugin instance
     * 
     * @var Trackify_CAPI_Core
     */
    private static $instance = null;
    
    /**
     * Components
     * 
     * @var array
     */
    private $components = array();
    
    /**
     * Get instance
     * 
     * @return Trackify_CAPI_Core
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Init hooks
     */
    private function init_hooks() {
        // Init
        add_action( 'init', array( $this, 'init' ) );
        
        // Admin init
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Utils
        require_once TRACKIFY_CAPI_INCLUDES . 'functions.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'utils/class-data-hasher.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'utils/class-event-builder.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'utils/class-cookie-manager.php';
        
        // Core classes
        require_once TRACKIFY_CAPI_INCLUDES . 'class-installer.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-settings.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-logger.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-pixel.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-capi.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-analytics.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-rest-api.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-dashboard.php';
        require_once TRACKIFY_CAPI_INCLUDES . 'class-event-handler.php';
        
        // Admin
        if ( is_admin() ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'class-admin.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'class-setup-wizard.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-settings-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-logs-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-tools-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-help-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'ajax/class-ajax-handlers.php';
        }
        
        // Integrations
        if ( class_exists( 'WooCommerce' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'integrations/class-woocommerce-integration.php';
        }
    }
    
    /**
     * Init components
     */
    private function init_components() {
        // Settings
        $this->components['settings'] = new Trackify_CAPI_Settings();
        
        // Logger
        $this->components['logger'] = new Trackify_CAPI_Logger( $this->components['settings'] );
        
        // Pixel
        $this->components['pixel'] = new Trackify_CAPI_Pixel( $this->components['settings'] );
        
        // CAPI
        $this->components['capi'] = new Trackify_CAPI_CAPI( 
            $this->components['settings'],
            $this->components['logger']
        );
        
        // Analytics
        $this->components['analytics'] = new Trackify_CAPI_Analytics( $this->components['logger'] );
        
        // Dashboard
        $this->components['dashboard'] = new Trackify_CAPI_Dashboard(
            $this->components['settings'],
            $this->components['logger'],
            $this->components['analytics']
        );
        
        // Event Handler
        $this->components['event_handler'] = new Trackify_CAPI_Event_Handler(
            $this->components['settings'],
            $this->components['pixel'],
            $this->components['capi'],
            $this->components['logger']
        );
        
        // REST API
        $this->components['rest_api'] = new Trackify_CAPI_REST_API(
            $this->components['settings'],
            $this->components['logger']
        );
        
        // Admin
        if ( is_admin() ) {
            $this->components['admin'] = new Trackify_CAPI_Admin(
                $this->components['settings'],
                $this->components['logger']
            );
            
            $this->components['ajax'] = new Trackify_CAPI_AJAX_Handlers(
                $this->components['settings'],
                $this->components['logger'],
                $this->components['capi']
            );
        }
        
        // WooCommerce Integration
        if ( class_exists( 'WooCommerce' ) ) {
            $this->components['woocommerce'] = new Trackify_CAPI_WooCommerce_Integration(
                $this->components['settings'],
                $this->components['event_handler']
            );
        }
        
        do_action( 'trackify_capi_components_loaded', $this->components );
    }
    
    /**
     * Get component
     * 
     * @param string $name
     * @return mixed|null
     */
    public function get_component( $name ) {
        return isset( $this->components[ $name ] ) ? $this->components[ $name ] : null;
    }
    
    /**
     * Init
     */
    public function init() {
        // Load textdomain
        load_plugin_textdomain( 'trackify-capi', false, dirname( TRACKIFY_CAPI_BASENAME ) . '/languages' );
        
        // Check if setup completed
        if ( ! get_option( 'trackify_capi_setup_completed' ) && is_admin() ) {
            // Redirect to setup wizard on first install
            if ( get_transient( 'trackify_capi_activation_redirect' ) ) {
                delete_transient( 'trackify_capi_activation_redirect' );
                
                if ( ! isset( $_GET['activate-multi'] ) ) {
                    wp_safe_redirect( admin_url( 'index.php?page=trackify-capi-setup' ) );
                    exit;
                }
            }
        }
        
        do_action( 'trackify_capi_init' );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Check plugin version and run updates if needed
        $current_version = get_option( 'trackify_capi_version', '0.0.0' );
        
        if ( version_compare( $current_version, TRACKIFY_CAPI_VERSION, '<' ) ) {
            $this->update_plugin( $current_version );
        }
        
        do_action( 'trackify_capi_admin_init' );
    }
    
    /**
     * Update plugin
     * 
     * @param string $old_version
     */
    private function update_plugin( $old_version ) {
        $installer = new Trackify_CAPI_Installer();
        $installer->update( $old_version );
        
        // Update version
        update_option( 'trackify_capi_version', TRACKIFY_CAPI_VERSION );
        
        do_action( 'trackify_capi_updated', $old_version, TRACKIFY_CAPI_VERSION );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        $settings = $this->get_component( 'settings' );
        
        // Only load if plugin is enabled
        if ( ! $settings->is_enabled() ) {
            return;
        }
        
        // Pixel script
        if ( $settings->get( 'pixels' ) ) {
            wp_enqueue_script(
                'trackify-capi-frontend',
                TRACKIFY_CAPI_ASSETS . 'js/frontend.js',
                array( 'jquery' ),
                TRACKIFY_CAPI_VERSION,
                true
            );
            
            wp_localize_script( 'trackify-capi-frontend', 'trackifyCAPI', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'restUrl' => rest_url( 'trackify-capi/v1/' ),
                'nonce' => wp_create_nonce( 'trackify_capi_nonce' ),
                'pixels' => $settings->get_active_pixels(),
                'debugMode' => $settings->is_debug_mode(),
            ) );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on plugin pages
        if ( strpos( $hook, 'trackify-capi' ) === false ) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'trackify-capi-admin',
            TRACKIFY_CAPI_ASSETS . 'css/admin.css',
            array(),
            TRACKIFY_CAPI_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'trackify-capi-admin',
            TRACKIFY_CAPI_ASSETS . 'js/admin.js',
            array( 'jquery' ),
            TRACKIFY_CAPI_VERSION,
            true
        );
        
        wp_localize_script( 'trackify-capi-admin', 'trackifyCAPIAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'trackify-capi/v1/' ),
            'nonce' => wp_create_nonce( 'trackify_capi_admin_nonce' ),
            'i18n' => array(
                'confirm' => __( 'Emin misiniz?', 'trackify-capi' ),
                'success' => __( 'İşlem başarılı!', 'trackify-capi' ),
                'error' => __( 'Bir hata oluştu!', 'trackify-capi' ),
            ),
        ) );
    }
}