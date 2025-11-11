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
        // Activation
        register_activation_hook( TRACKIFY_CAPI_FILE, array( $this, 'activate' ) );
        
        // Deactivation
        register_deactivation_hook( TRACKIFY_CAPI_FILE, array( $this, 'deactivate' ) );
        
        // Init
        add_action( 'init', array( $this, 'init' ) );
        
        // Admin init
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Add Help menu
        add_action( 'admin_menu', array( $this, 'add_help_menu' ), 100 );
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
        
        // Admin
        if ( is_admin() ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'class-admin.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'class-setup-wizard.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-settings-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-events-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-analytics-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-tools-page.php';
            require_once TRACKIFY_CAPI_INCLUDES . 'admin/class-help-page.php';
        }
        
        // Integrations
        require_once TRACKIFY_CAPI_INCLUDES . 'integrations/abstract-integration.php';
        
        // WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'integrations/class-woocommerce.php';
        }
        
        // Forms
        require_once TRACKIFY_CAPI_INCLUDES . 'forms/abstract-form.php';
        
        // Contact Form 7
        if ( defined( 'WPCF7_VERSION' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'forms/class-cf7.php';
        }
        
        // WPForms
        if ( defined( 'WPFORMS_VERSION' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'forms/class-wpforms.php';
        }
        
        // Gravity Forms
        if ( class_exists( 'GFForms' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'forms/class-gravity-forms.php';
        }
        
        // Elementor Forms (PRO)
        if ( defined( 'ELEMENTOR_PRO_VERSION' ) && class_exists( '\ElementorPro\Modules\Forms\Module' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'forms/class-elementor-forms.php';
        }
        
        // Fluent Forms
        if ( defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'forms/class-fluent-forms.php';
        }
        
        // Ninja Forms
        if ( class_exists( 'Ninja_Forms' ) || function_exists( 'Ninja_Forms' ) ) {
            require_once TRACKIFY_CAPI_INCLUDES . 'forms/class-ninja-forms.php';
        }
    }
    
    /**
     * Init components
     */
    private function init_components() {
        // Core components
        $this->components['settings'] = new Trackify_CAPI_Settings();
        $this->components['logger'] = new Trackify_CAPI_Logger();
        $this->components['pixel'] = new Trackify_CAPI_Pixel();
        $this->components['capi'] = new Trackify_CAPI_CAPI();
        $this->components['analytics'] = new Trackify_CAPI_Analytics();
        $this->components['rest_api'] = new Trackify_CAPI_Rest_API();
        $this->components['dashboard'] = new Trackify_CAPI_Dashboard();
        
        // Admin components
        if ( is_admin() ) {
            $this->components['admin'] = new Trackify_CAPI_Admin();
            $this->components['setup_wizard'] = new Trackify_CAPI_Setup_Wizard();
        }
        
        // Integration components
        if ( class_exists( 'WooCommerce' ) ) {
            $this->components['woocommerce'] = new Trackify_CAPI_Woocommerce();
        }
        
        // Form components
        if ( defined( 'WPCF7_VERSION' ) ) {
            $this->components['cf7'] = new Trackify_CAPI_CF7();
        }
        
        if ( defined( 'WPFORMS_VERSION' ) ) {
            $this->components['wpforms'] = new Trackify_CAPI_Wpforms();
        }
        
        if ( class_exists( 'GFForms' ) ) {
            $this->components['gravity_forms'] = new Trackify_CAPI_Gravity_Forms();
        }
        
        if ( defined( 'ELEMENTOR_PRO_VERSION' ) && class_exists( '\ElementorPro\Modules\Forms\Module' ) ) {
            $this->components['elementor_forms'] = new Trackify_CAPI_Elementor_Forms();
        }
        
        if ( defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ) ) {
            $this->components['fluent_forms'] = new Trackify_CAPI_Fluent_Forms();
        }
        
        if ( class_exists( 'Ninja_Forms' ) || function_exists( 'Ninja_Forms' ) ) {
            $this->components['ninja_forms'] = new Trackify_CAPI_Ninja_Forms();
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
     * Activate
     */
    public function activate() {
        // Run installer
        $installer = new Trackify_CAPI_Installer();
        $installer->install();
        
        // Set activation redirect flag
        set_transient( 'trackify_capi_activation_redirect', true, 30 );
        
        do_action( 'trackify_capi_activated' );
    }
    
    /**
     * Deactivate
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook( 'trackify_capi_cleanup_logs' );
        wp_clear_scheduled_hook( 'trackify_capi_update_analytics' );
        wp_clear_scheduled_hook( 'trackify_capi_process_queue' );
        
        do_action( 'trackify_capi_deactivated' );
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
        
        // Get active pixels
        $pixels = $settings->get_active_pixels();
        
        if ( empty( $pixels ) ) {
            return;
        }
        
        // Enqueue pixel events script
        wp_enqueue_script(
            'trackify-capi-pixel-events',
            TRACKIFY_CAPI_ASSETS . 'js/pixel-events.js',
            array( 'jquery' ),
            TRACKIFY_CAPI_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'trackify-capi-pixel-events',
            'trackifyCAPI',
            array(
                'pixelId' => $pixels[0]['pixel_id'],
                'pixels' => $pixels,
                'restUrl' => rest_url( 'trackify-capi/v1' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'debug' => $settings->is_debug_mode(),
                'hasWooCommerce' => class_exists( 'WooCommerce' ) ? 'yes' : 'no',
                'currency' => class_exists( 'WooCommerce' ) ? get_woocommerce_currency() : 'USD',
            )
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on plugin pages
        if ( ! trackify_capi_is_admin_page() ) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'trackify-capi-admin',
            TRACKIFY_CAPI_ASSETS . 'css/admin.css',
            array(),
            TRACKIFY_CAPI_VERSION
        );
        
        // Dashboard CSS (for dashboard widget)
        if ( 'index.php' === $hook ) {
            wp_enqueue_style(
                'trackify-capi-dashboard',
                TRACKIFY_CAPI_ASSETS . 'css/dashboard.css',
                array(),
                TRACKIFY_CAPI_VERSION
            );
        }
        
        // Admin JS
        wp_enqueue_script(
            'trackify-capi-admin',
            TRACKIFY_CAPI_ASSETS . 'js/admin.js',
            array( 'jquery' ),
            TRACKIFY_CAPI_VERSION,
            true
        );
        
        // Analytics JS (for analytics page)
        if ( strpos( $hook, 'trackify-capi-analytics' ) !== false ) {
            wp_enqueue_script(
                'trackify-capi-analytics',
                TRACKIFY_CAPI_ASSETS . 'js/analytics.js',
                array( 'jquery' ),
                TRACKIFY_CAPI_VERSION,
                true
            );
        }
        
        // Localize admin script
        wp_localize_script(
            'trackify-capi-admin',
            'trackifyCAPIAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'trackify_capi_admin' ),
                'restUrl' => rest_url( 'trackify-capi/v1' ),
            )
        );
    }
    
    /**
     * Add help menu
     */
    public function add_help_menu() {
        add_submenu_page(
            'trackify-capi',
            __( 'Yardım', 'trackify-capi' ),
            __( 'Yardım', 'trackify-capi' ),
            'manage_options',
            'trackify-capi-help',
            array( $this, 'render_help_page' )
        );
    }
    
    /**
     * Render help page
     */
    public function render_help_page() {
        $help_page = new Trackify_CAPI_Help_Page();
        $help_page->render();
    }
}