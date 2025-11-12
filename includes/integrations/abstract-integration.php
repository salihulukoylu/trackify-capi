<?php
/**
 * Abstract Integration Class
 * 
 * Tüm entegrasyon sınıfları için base class
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Trackify_CAPI_Abstract_Integration {
    
    /**
     * Integration ID
     * 
     * @var string
     */
    protected $id = '';
    
    /**
     * Integration name
     * 
     * @var string
     */
    protected $name = '';
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    protected $settings;
    
    /**
     * CAPI instance
     * 
     * @var Trackify_CAPI_CAPI
     */
    protected $capi;
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    protected $logger;
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        $this->capi = trackify_capi()->get_component( 'capi' );
        $this->logger = trackify_capi()->get_component( 'logger' );
        
        // Plugin aktif değilse çık
        if ( ! $this->is_plugin_active() ) {
            return;
        }
        
        // Integration etkin değilse çık
        if ( ! $this->is_enabled() ) {
            return;
        }
        
        // Hook'ları başlat
        $this->init_hooks();
    }
    
    /**
     * Hook'ları başlat (her integration override etmeli)
     * 
     * @abstract
     */
    abstract protected function init_hooks();
    
    /**
     * Plugin aktif mi? (her integration override etmeli)
     * 
     * @abstract
     * @return bool
     */
    abstract protected function is_plugin_active();
    
    /**
     * Integration ID'yi al
     * 
     * @return string
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Integration adını al
     * 
     * @return string
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Integration etkin mi?
     * 
     * @return bool
     */
    public function is_enabled() {
        $setting_key = 'integrations.' . $this->id . '.enabled';
        return $this->settings->get( $setting_key, true );
    }
    
    /**
     * Event gönder
     * 
     * @param string $event_name Event adı
     * @param array $custom_data Custom data
     * @param array $user_data User data (opsiyonel)
     * @param string $event_id Event ID (opsiyonel)
     * @return array|WP_Error
     */
    protected function send_event( $event_name, $custom_data = array(), $user_data = array(), $event_id = null ) {
        // Event etkin mi kontrol et
        if ( ! $this->settings->is_event_enabled( $event_name, 'capi' ) ) {
            return new WP_Error( 'event_disabled', sprintf(
                __( '%s event devre dışı', 'trackify-capi' ),
                $event_name
            ) );
        }
        
        // Event ID yoksa oluştur
        if ( empty( $event_id ) ) {
            $event_id = trackify_capi_generate_event_id( strtolower( $event_name ) );
        }
        
        // Event gönder
        return $this->capi->send_event(
            $event_name,
            $custom_data,
            $user_data,
            $event_id
        );
    }
    
    /**
     * Currency kodu al
     * 
     * @return string
     */
    protected function get_currency() {
        // Varsayılan
        $currency = 'USD';
        
        // WooCommerce varsa ondan al
        if ( function_exists( 'get_woocommerce_currency' ) ) {
            $currency = get_woocommerce_currency();
        }
        
        return apply_filters( 'trackify_capi_currency', $currency );
    }
    
    /**
     * User data al (şu anki kullanıcı için)
     * 
     * @return array
     */
    protected function get_user_data() {
        $user_data = array();
        $current_user = wp_get_current_user();
        
        // Hash helper
        $hasher = trackify_capi_get_data_hasher();
        
        // Email
        if ( $current_user->ID && ! empty( $current_user->user_email ) ) {
            $user_data['em'] = $hasher->hash_email( $current_user->user_email );
        }
        
        // First name
        if ( $current_user->ID && ! empty( $current_user->first_name ) ) {
            $user_data['fn'] = $hasher->hash_text( $current_user->first_name );
        }
        
        // Last name
        if ( $current_user->ID && ! empty( $current_user->last_name ) ) {
            $user_data['ln'] = $hasher->hash_text( $current_user->last_name );
        }
        
        // Phone (from user meta)
        if ( $current_user->ID ) {
            $phone = get_user_meta( $current_user->ID, 'billing_phone', true );
            if ( ! empty( $phone ) ) {
                $user_data['ph'] = $hasher->hash_phone( $phone );
            }
        }
        
        // Client IP & User Agent
        $user_data['client_ip_address'] = $this->get_client_ip();
        $user_data['client_user_agent'] = $this->get_user_agent();
        
        // FBC & FBP cookies
        $cookie_manager = trackify_capi_get_cookie_manager();
        $user_data['fbc'] = $cookie_manager->get_fbc();
        $user_data['fbp'] = $cookie_manager->get_fbp();
        
        return apply_filters( 'trackify_capi_user_data', $user_data );
    }
    
    /**
     * Client IP al
     * 
     * @return string
     */
    protected function get_client_ip() {
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
                
                // X-Forwarded-For birden fazla IP içerebilir
                if ( strpos( $ip, ',' ) !== false ) {
                    $ips = explode( ',', $ip );
                    $ip = trim( $ips[0] );
                }
                
                // IP validasyonu
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * User agent al
     * 
     * @return string
     */
    protected function get_user_agent() {
        if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
        }
        
        return '';
    }
    
    /**
     * Event source URL al
     * 
     * @return string
     */
    protected function get_event_source_url() {
        if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
            $protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            return $protocol . '://' . 
                   sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . 
                   sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        }
        
        return home_url( '/' );
    }
    
    /**
     * Duplicate event kontrolü
     * 
     * @param string $event_key Unique event key
     * @return bool True if duplicate, false if new
     */
    protected function is_duplicate_event( $event_key ) {
        $transient_key = 'trackify_event_' . md5( $event_key );
        
        // Transient varsa duplicate
        if ( get_transient( $transient_key ) ) {
            return true;
        }
        
        // Transient set et (5 dakika)
        set_transient( $transient_key, true, 300 );
        
        return false;
    }
    
    /**
     * Log kaydet
     * 
     * @param string $message
     * @param array $data
     * @param string $level
     */
    protected function log( $message, $data = array(), $level = 'info' ) {
        $data['integration'] = $this->id;
        $data['message'] = $message;
        
        $this->logger->log( $data, $level );
    }
    
    /**
     * Debug log
     * 
     * @param string $message
     * @param array $data
     */
    protected function debug_log( $message, $data = array() ) {
        if ( $this->settings->is_debug_mode() ) {
            $this->log( $message, $data, 'debug' );
        }
    }
}