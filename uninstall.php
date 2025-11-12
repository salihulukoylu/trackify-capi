<?php
/**
 * Uninstall Script
 * 
 * Plugin silindiğinde çalışır ve tüm verileri temizler
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

// WordPress uninstall işlemi değilse çık
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Veritabanı tablolarını sil
 */
function trackify_capi_uninstall_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'trackify_capi_events',
        $wpdb->prefix . 'trackify_capi_analytics',
    );
    
    foreach ( $tables as $table ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }
}

/**
 * Options'ları sil
 */
function trackify_capi_uninstall_options() {
    delete_option( 'trackify_capi_settings' );
    delete_option( 'trackify_capi_version' );
    delete_option( 'trackify_capi_db_version' );
    delete_option( 'trackify_capi_installed_date' );
    delete_option( 'trackify_capi_setup_completed' );
    delete_option( 'trackify_capi_setup_date' );
    delete_option( 'trackify_capi_site_type' );
    
    // User meta temizliği
    delete_metadata( 'user', 0, 'trackify_capi_wc_notice_dismissed', '', true );
}

/**
 * Transient'ları temizle
 */
function trackify_capi_uninstall_transients() {
    global $wpdb;
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_trackify_capi_%' 
        OR option_name LIKE '_transient_timeout_trackify_capi_%'"
    );
}

/**
 * Cron job'ları temizle
 */
function trackify_capi_uninstall_cron() {
    wp_clear_scheduled_hook( 'trackify_capi_cleanup_logs' );
    wp_clear_scheduled_hook( 'trackify_capi_update_analytics' );
    wp_clear_scheduled_hook( 'trackify_capi_process_queue' );
}

/**
 * Log dosyalarını sil
 */
function trackify_capi_uninstall_files() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/trackify-capi/';
    
    if ( file_exists( $plugin_dir ) ) {
        trackify_capi_delete_directory( $plugin_dir );
    }
}

/**
 * Klasörü recursive olarak sil
 * 
 * @param string $dir
 * @return bool
 */
function trackify_capi_delete_directory( $dir ) {
    if ( ! file_exists( $dir ) ) {
        return true;
    }
    
    if ( ! is_dir( $dir ) ) {
        return unlink( $dir );
    }
    
    foreach ( scandir( $dir ) as $item ) {
        if ( $item === '.' || $item === '..' ) {
            continue;
        }
        
        if ( ! trackify_capi_delete_directory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
            return false;
        }
    }
    
    return rmdir( $dir );
}

// Run uninstall
trackify_capi_uninstall_tables();
trackify_capi_uninstall_options();
trackify_capi_uninstall_transients();
trackify_capi_uninstall_cron();
trackify_capi_uninstall_files();