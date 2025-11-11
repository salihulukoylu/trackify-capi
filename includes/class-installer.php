<?php
/**
 * Plugin Installer
 * 
 * Plugin aktivasyon, deaktivasyon ve silme işlemlerini yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Installer {
    
    /**
     * Plugin aktivasyonu
     * 
     * @since 2.0.0
     */
    public static function activate() {
        // Veritabanı tablolarını oluştur
        self::create_tables();
        
        // Varsayılan ayarları yükle
        self::install_default_settings();
        
        // Log klasörünü oluştur
        self::create_log_directory();
        
        // Cron job'ları planla
        self::schedule_cron_jobs();
        
        // Setup wizard için redirect flag
        set_transient( 'trackify_capi_activation_redirect', true, 30 );
        
        // Versiyon kaydı
        update_option( 'trackify_capi_version', TRACKIFY_CAPI_VERSION );
        update_option( 'trackify_capi_installed_date', time() );
        
        // Aktivasyon hook
        do_action( 'trackify_capi_activated' );
    }
    
    /**
     * Plugin deaktivasyonu
     * 
     * @since 2.0.0
     */
    public static function deactivate() {
        // Cron job'ları temizle
        self::clear_cron_jobs();
        
        // Deaktivasyon hook
        do_action( 'trackify_capi_deactivated' );
    }
    
    /**
     * Veritabanı tablolarını oluştur
     * 
     * @since 2.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Events tablosu
        $table_events = $wpdb->prefix . 'trackify_capi_events';
        
        $sql_events = "CREATE TABLE IF NOT EXISTS $table_events (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_name varchar(50) NOT NULL,
            event_id varchar(100) NOT NULL,
            pixel_id varchar(50) NOT NULL,
            event_time datetime NOT NULL,
            event_data longtext,
            user_data longtext,
            status varchar(20) DEFAULT 'pending',
            response_code int(3),
            response_data longtext,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_name (event_name),
            KEY event_id (event_id),
            KEY pixel_id (pixel_id),
            KEY status (status),
            KEY event_time (event_time)
        ) $charset_collate;";
        
        // Analytics tablosu (günlük istatistikler)
        $table_analytics = $wpdb->prefix . 'trackify_capi_analytics';
        
        $sql_analytics = "CREATE TABLE IF NOT EXISTS $table_analytics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            event_name varchar(50) NOT NULL,
            pixel_id varchar(50) NOT NULL,
            total_events int(11) DEFAULT 0,
            successful_events int(11) DEFAULT 0,
            failed_events int(11) DEFAULT 0,
            avg_emq_score decimal(3,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY date_event_pixel (date, event_name, pixel_id),
            KEY date (date),
            KEY event_name (event_name)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_events );
        dbDelta( $sql_analytics );
        
        // Tablo versiyonunu kaydet
        update_option( 'trackify_capi_db_version', '2.0.0' );
    }
    
    /**
     * Varsayılan ayarları yükle
     * 
     * @since 2.0.0
     */
    private static function install_default_settings() {
        // Ayarlar zaten varsa atlat
        if ( get_option( 'trackify_capi_settings' ) ) {
            return;
        }
        
        $default_settings = array(
            'version' => TRACKIFY_CAPI_VERSION,
            
            // Genel ayarlar
            'enabled' => true,
            'debug_mode' => false,
            'test_mode' => false,
            
            // Pixel ayarları
            'pixels' => array(),
            
            // CAPI ayarları
            'capi_enabled' => true,
            'api_version' => 'v18.0',
            
            // Event ayarları
            'events' => array(
                'PageView' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => false,
                ),
                'ViewContent' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => true,
                ),
                'AddToCart' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => true,
                ),
                'InitiateCheckout' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => true,
                ),
                'Purchase' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => true,
                ),
                'Lead' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => true,
                ),
                'CompleteRegistration' => array(
                    'enabled' => true,
                    'pixel' => true,
                    'capi' => true,
                ),
            ),
            
            // Advanced matching
            'advanced_matching' => array(
                'enabled' => true,
                'hash_email' => true,
                'hash_phone' => true,
                'hash_name' => true,
                'hash_address' => true,
                'capture_fbp' => true,
                'capture_fbc' => true,
            ),
            
            // Entegrasyonlar
            'integrations' => array(
                'woocommerce' => array(
                    'enabled' => true,
                ),
                'forms' => array(
                    'enabled' => true,
                    'default_event' => 'Lead',
                ),
            ),
            
            // Performance
            'performance' => array(
                'use_queue' => false,
                'queue_size' => 10,
                'batch_sending' => false,
            ),
            
            // Logging
            'logging' => array(
                'enabled' => true,
                'log_level' => 'info', // info, warning, error
                'retention_days' => 30,
                'database_logging' => true,
                'file_logging' => false,
            ),
        );
        
        add_option( 'trackify_capi_settings', $default_settings );
    }
    
    /**
     * Log klasörünü oluştur
     * 
     * @since 2.0.0
     */
    private static function create_log_directory() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/trackify-capi/';
        $logs_dir = $log_dir . 'logs/';
        
        // Ana klasör
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }
        
        // Logs klasörü
        if ( ! file_exists( $logs_dir ) ) {
            wp_mkdir_p( $logs_dir );
        }
        
        // .htaccess ile koruma
        $htaccess_file = $log_dir . '.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "# Trackify CAPI Protection\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents( $htaccess_file, $htaccess_content );
        }
        
        // index.php
        $index_file = $log_dir . 'index.php';
        if ( ! file_exists( $index_file ) ) {
            file_put_contents( $index_file, '<?php // Silence is golden' );
        }
        
        $index_file_logs = $logs_dir . 'index.php';
        if ( ! file_exists( $index_file_logs ) ) {
            file_put_contents( $index_file_logs, '<?php // Silence is golden' );
        }
    }
    
    /**
     * Cron job'ları planla
     * 
     * @since 2.0.0
     */
    private static function schedule_cron_jobs() {
        // Günlük log temizleme
        if ( ! wp_next_scheduled( 'trackify_capi_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'trackify_capi_cleanup_logs' );
        }
        
        // Günlük analytics güncelleme
        if ( ! wp_next_scheduled( 'trackify_capi_update_analytics' ) ) {
            wp_schedule_event( time(), 'daily', 'trackify_capi_update_analytics' );
        }
        
        // Queue işleme (her 5 dakikada)
        if ( ! wp_next_scheduled( 'trackify_capi_process_queue' ) ) {
            wp_schedule_event( time(), 'five_minutes', 'trackify_capi_process_queue' );
        }
    }
    
    /**
     * Cron job'ları temizle
     * 
     * @since 2.0.0
     */
    private static function clear_cron_jobs() {
        wp_clear_scheduled_hook( 'trackify_capi_cleanup_logs' );
        wp_clear_scheduled_hook( 'trackify_capi_update_analytics' );
        wp_clear_scheduled_hook( 'trackify_capi_process_queue' );
    }
    
    /**
     * Custom cron interval ekle
     * 
     * @since 2.0.0
     */
    public static function custom_cron_intervals( $schedules ) {
        // Her 5 dakika
        $schedules['five_minutes'] = array(
            'interval' => 300,
            'display'  => __( 'Her 5 Dakika', 'trackify-capi' ),
        );
        
        // Her 15 dakika
        $schedules['fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => __( 'Her 15 Dakika', 'trackify-capi' ),
        );
        
        return $schedules;
    }
}

// Cron interval'i ekle
add_filter( 'cron_schedules', array( 'Trackify_CAPI_Installer', 'custom_cron_intervals' ) );