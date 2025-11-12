<?php
/**
 * Event Logger
 * 
 * Event'leri veritabanı ve/veya dosyaya loglar
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Logger {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Log levels
     * 
     * @var array
     */
    private $log_levels = array(
        'debug'   => 1,
        'info'    => 2,
        'warning' => 3,
        'error'   => 4,
    );
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        
        // Cron job: Log temizleme
        add_action( 'trackify_capi_cleanup_logs', array( $this, 'cleanup_old_logs' ) );
    }
    
    /**
     * Log kaydet
     * 
     * @param array $data Log verisi
     * @param string $level Log seviyesi (debug, info, warning, error)
     * @return bool|int
     */
    public function log( $data, $level = 'info' ) {
        // Logging kapalıysa çık
        if ( ! $this->settings->get( 'logging.enabled' ) ) {
            return false;
        }
        
        // Log level kontrolü
        $current_level = $this->settings->get( 'logging.log_level', 'info' );
        if ( ! $this->should_log( $level, $current_level ) ) {
            return false;
        }
        
        // Veritabanına log
        $db_result = false;
        if ( $this->settings->get( 'logging.database_logging' ) ) {
            $db_result = $this->log_to_database( $data, $level );
        }
        
        // Dosyaya log
        if ( $this->settings->get( 'logging.file_logging' ) ) {
            $this->log_to_file( $data, $level );
        }
        
        return $db_result;
    }
    
    /**
     * Log level kontrolü
     * 
     * @param string $level
     * @param string $min_level
     * @return bool
     */
    private function should_log( $level, $min_level ) {
        $level_value = $this->log_levels[ $level ] ?? 2;
        $min_level_value = $this->log_levels[ $min_level ] ?? 2;
        
        return $level_value >= $min_level_value;
    }
    
    /**
     * Veritabanına log kaydet
     * 
     * @param array $data
     * @param string $level
     * @return int|false
     */
    private function log_to_database( $data, $level ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trackify_capi_events';
        
        // Veri hazırlama
        $insert_data = array(
            'event_name'     => $data['event_name'] ?? '',
            'event_id'       => $data['event_id'] ?? '',
            'pixel_id'       => $data['pixel_id'] ?? '',
            'event_time'     => isset( $data['event_time'] ) ? gmdate( 'Y-m-d H:i:s', $data['event_time'] ) : current_time( 'mysql', true ),
            'event_data'     => isset( $data['event_data'] ) ? wp_json_encode( $data['event_data'] ) : null,
            'user_data'      => isset( $data['user_data'] ) ? wp_json_encode( $data['user_data'] ) : null,
            'status'         => $data['status'] ?? 'pending',
            'response_code'  => $data['response_code'] ?? null,
            'response_data'  => isset( $data['response_data'] ) ? wp_json_encode( $data['response_data'] ) : null,
            'error_message'  => $data['error_message'] ?? null,
            'created_at'     => current_time( 'mysql', true ),
        );
        
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
        );
        
        if ( $result === false ) {
            error_log( 'Trackify CAPI: Database log failed - ' . $wpdb->last_error );
            return false;
        }
        
        // Analytics güncelle
        $this->update_analytics( $data );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Dosyaya log kaydet
     * 
     * @param array $data
     * @param string $level
     */
    private function log_to_file( $data, $level ) {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/trackify-capi/logs/events-' . gmdate( 'Y-m-d' ) . '.log';
        
        // Log klasörü yoksa oluştur
        $log_dir = dirname( $log_file );
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }
        
        // Log mesajı oluştur
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $level_upper = strtoupper( $level );
        $event_name = $data['event_name'] ?? 'UNKNOWN';
        $event_id = $data['event_id'] ?? '';
        $status = $data['status'] ?? 'unknown';
        
        $log_message = sprintf(
            "[%s] [%s] Event: %s | ID: %s | Status: %s",
            $timestamp,
            $level_upper,
            $event_name,
            $event_id,
            $status
        );
        
        // Hata varsa ekle
        if ( ! empty( $data['error_message'] ) ) {
            $log_message .= ' | Error: ' . $data['error_message'];
        }
        
        // Response code varsa ekle
        if ( ! empty( $data['response_code'] ) ) {
            $log_message .= ' | Response Code: ' . $data['response_code'];
        }
        
        $log_message .= "\n";
        
        // Debug mode'da detaylı bilgi
        if ( $this->settings->is_debug_mode() ) {
            $log_message .= '  Data: ' . wp_json_encode( $data ) . "\n";
        }
        
        // Dosyaya yaz
        file_put_contents( $log_file, $log_message, FILE_APPEND );
    }
    
    /**
     * Analytics güncelle
     * 
     * @param array $data
     */
    private function update_analytics( $data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trackify_capi_analytics';
        $date = current_time( 'Y-m-d' );
        $event_name = $data['event_name'] ?? '';
        $pixel_id = $data['pixel_id'] ?? '';
        $status = $data['status'] ?? 'pending';
        
        if ( empty( $event_name ) ) {
            return;
        }
        
        // Mevcut kaydı bul
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE date = %s AND event_name = %s AND pixel_id = %s",
            $date,
            $event_name,
            $pixel_id
        ), ARRAY_A );
        
        if ( $existing ) {
            // Güncelle
            $wpdb->update(
                $table_name,
                array(
                    'total_events' => $existing['total_events'] + 1,
                    'successful_events' => $status === 'success' ? $existing['successful_events'] + 1 : $existing['successful_events'],
                    'failed_events' => $status === 'error' ? $existing['failed_events'] + 1 : $existing['failed_events'],
                ),
                array(
                    'date' => $date,
                    'event_name' => $event_name,
                    'pixel_id' => $pixel_id,
                ),
                array( '%d', '%d', '%d' ),
                array( '%s', '%s', '%s' )
            );
        } else {
            // Yeni kayıt
            $wpdb->insert(
                $table_name,
                array(
                    'date' => $date,
                    'event_name' => $event_name,
                    'pixel_id' => $pixel_id,
                    'total_events' => 1,
                    'successful_events' => $status === 'success' ? 1 : 0,
                    'failed_events' => $status === 'error' ? 1 : 0,
                ),
                array( '%s', '%s', '%s', '%d', '%d', '%d' )
            );
        }
    }
    
    /**
     * Son logları getir
     * 
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function get_recent_logs( $limit = 50, $filters = array() ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trackify_capi_events';
        
        // WHERE clause oluştur
        $where = array( '1=1' );
        
        if ( ! empty( $filters['event_name'] ) ) {
            $where[] = $wpdb->prepare( 'event_name = %s', $filters['event_name'] );
        }
        
        if ( ! empty( $filters['pixel_id'] ) ) {
            $where[] = $wpdb->prepare( 'pixel_id = %s', $filters['pixel_id'] );
        }
        
        if ( ! empty( $filters['status'] ) ) {
            $where[] = $wpdb->prepare( 'status = %s', $filters['status'] );
        }
        
        if ( ! empty( $filters['date_from'] ) ) {
            $where[] = $wpdb->prepare( 'created_at >= %s', $filters['date_from'] );
        }
        
        if ( ! empty( $filters['date_to'] ) ) {
            $where[] = $wpdb->prepare( 'created_at <= %s', $filters['date_to'] );
        }
        
        $where_clause = implode( ' AND ', $where );
        
        // Query
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d";
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
        
        // JSON decode
        foreach ( $results as &$result ) {
            if ( ! empty( $result['event_data'] ) ) {
                $result['event_data'] = json_decode( $result['event_data'], true );
            }
            if ( ! empty( $result['user_data'] ) ) {
                $result['user_data'] = json_decode( $result['user_data'], true );
            }
            if ( ! empty( $result['response_data'] ) ) {
                $result['response_data'] = json_decode( $result['response_data'], true );
            }
        }
        
        return $results;
    }
    
    /**
     * Analytics verisi getir
     * 
     * @param string $date_from
     * @param string $date_to
     * @param string $pixel_id
     * @return array
     */
    public function get_analytics( $date_from = null, $date_to = null, $pixel_id = null ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trackify_capi_analytics';
        
        // Varsayılan: Son 30 gün
        if ( ! $date_from ) {
            $date_from = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
        }
        
        if ( ! $date_to ) {
            $date_to = current_time( 'Y-m-d' );
        }
        
        // WHERE clause
        $where = array();
        $where[] = $wpdb->prepare( 'date >= %s', $date_from );
        $where[] = $wpdb->prepare( 'date <= %s', $date_to );
        
        if ( $pixel_id ) {
            $where[] = $wpdb->prepare( 'pixel_id = %s', $pixel_id );
        }
        
        $where_clause = implode( ' AND ', $where );
        
        // Query
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY date DESC, event_name ASC";
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $query, ARRAY_A );
    }
    
    /**
     * Event istatistiklerini getir
     * 
     * @param int $days
     * @return array
     */
    public function get_event_stats( $days = 7 ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trackify_capi_analytics';
        $date_from = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
        
        $query = $wpdb->prepare(
            "SELECT 
                event_name,
                SUM(total_events) as total,
                SUM(successful_events) as successful,
                SUM(failed_events) as failed
            FROM {$table_name}
            WHERE date >= %s
            GROUP BY event_name
            ORDER BY total DESC",
            $date_from
        );
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $query, ARRAY_A );
    }
    
    /**
     * Tüm logları temizle
     * 
     * @return bool
     */
    public function clear_all_logs() {
        global $wpdb;
        
        // Veritabanı
        $table_name = $wpdb->prefix . 'trackify_capi_events';
        $wpdb->query( "TRUNCATE TABLE {$table_name}" );
        
        // Dosyalar
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/trackify-capi/logs/';
        
        if ( file_exists( $log_dir ) ) {
            $files = glob( $log_dir . '*.log' );
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                }
            }
        }
        
        return true;
    }
    
    /**
     * Eski logları temizle (cron job)
     * 
     * @since 2.0.0
     */
    public function cleanup_old_logs() {
        $retention_days = $this->settings->get( 'logging.retention_days', 30 );
        
        // Veritabanı temizliği
        if ( $this->settings->get( 'logging.database_logging' ) ) {
            $this->cleanup_database_logs( $retention_days );
        }
        
        // Dosya temizliği
        if ( $this->settings->get( 'logging.file_logging' ) ) {
            $this->cleanup_file_logs( $retention_days );
        }
    }
    
    /**
     * Veritabanı loglarını temizle
     * 
     * @param int $retention_days
     */
    private function cleanup_database_logs( $retention_days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trackify_capi_events';
        $cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );
        
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < %s",
            $cutoff_date
        ) );
        
        // Analytics tablosunu da temizle
        $analytics_table = $wpdb->prefix . 'trackify_capi_analytics';
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$analytics_table} WHERE date < %s",
            gmdate( 'Y-m-d', strtotime( "-{$retention_days} days" ) )
        ) );
    }
    
    /**
     * Log dosyalarını temizle
     * 
     * @param int $retention_days
     */
    private function cleanup_file_logs( $retention_days ) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/trackify-capi/logs/';
        
        if ( ! file_exists( $log_dir ) ) {
            return;
        }
        
        $files = glob( $log_dir . '*.log' );
        $cutoff_time = strtotime( "-{$retention_days} days" );
        
        foreach ( $files as $file ) {
            if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
                unlink( $file );
            }
        }
    }
    
    /**
     * Log export (CSV)
     * 
     * @param array $filters
     * @return string CSV content
     */
    public function export_logs_csv( $filters = array() ) {
        $logs = $this->get_recent_logs( 10000, $filters );
        
        if ( empty( $logs ) ) {
            return '';
        }
        
        // CSV header
        $csv = "Event Name,Event ID,Pixel ID,Event Time,Status,Response Code,Error Message,Created At\n";
        
        // CSV rows
        foreach ( $logs as $log ) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log['event_name'],
                $log['event_id'],
                $log['pixel_id'],
                $log['event_time'],
                $log['status'],
                $log['response_code'] ?? '',
                $log['error_message'] ?? '',
                $log['created_at']
            );
        }
        
        return $csv;
    }
}