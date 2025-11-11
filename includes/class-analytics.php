<?php
/**
 * Analytics
 * 
 * Event analytics ve raporlama
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Analytics {
    
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
        $this->logger = trackify_capi()->get_component( 'logger' );
        
        // Günlük analytics güncelleme cron
        add_action( 'trackify_capi_update_analytics', array( $this, 'update_daily_analytics' ) );
    }
    
    /**
     * Günlük analytics güncelle
     * 
     * @since 2.0.0
     */
    public function update_daily_analytics() {
        // Bu fonksiyon cron tarafından günlük çalıştırılır
        // Şu an için analytics tablosu otomatik güncelleniyor (logger'da)
        // İleride ek hesaplamalar eklenebilir
        
        do_action( 'trackify_capi_analytics_updated' );
    }
    
    /**
     * Event istatistiklerini al
     * 
     * @param int $days
     * @return array
     */
    public function get_event_stats( $days = 30 ) {
        return $this->logger->get_event_stats( $days );
    }
    
    /**
     * Tarih aralığında analytics al
     * 
     * @param string $date_from
     * @param string $date_to
     * @param string $pixel_id
     * @return array
     */
    public function get_analytics( $date_from = null, $date_to = null, $pixel_id = null ) {
        return $this->logger->get_analytics( $date_from, $date_to, $pixel_id );
    }
    
    /**
     * Günlük event grafiği için veri hazırla
     * 
     * @param int $days
     * @return array
     */
    public function get_chart_data( $days = 30 ) {
        $analytics = $this->get_analytics(
            gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
            current_time( 'Y-m-d' )
        );
        
        // Tarihe göre grupla
        $chart_data = array();
        
        foreach ( $analytics as $row ) {
            $date = $row['date'];
            
            if ( ! isset( $chart_data[ $date ] ) ) {
                $chart_data[ $date ] = array(
                    'date' => $date,
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0,
                    'events' => array(),
                );
            }
            
            $chart_data[ $date ]['total'] += $row['total_events'];
            $chart_data[ $date ]['successful'] += $row['successful_events'];
            $chart_data[ $date ]['failed'] += $row['failed_events'];
            $chart_data[ $date ]['events'][ $row['event_name'] ] = $row['total_events'];
        }
        
        return array_values( $chart_data );
    }
}