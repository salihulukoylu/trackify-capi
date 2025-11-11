<?php
/**
 * Meta Conversions API Manager
 * 
 * Server-to-server event tracking yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_CAPI {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    private $logger;
    
    /**
     * API base URL
     * 
     * @var string
     */
    private $api_base_url = 'https://graph.facebook.com/';
    
    /**
     * Event queue
     * 
     * @var array
     */
    private $event_queue = array();
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        $this->logger = trackify_capi()->get_component( 'logger' );
        
        // Shutdown'da queue'yu işle
        add_action( 'shutdown', array( $this, 'process_queue' ) );
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
    public function send_event( $event_name, $custom_data = array(), $user_data = array(), $event_id = null ) {
        // CAPI kapalıysa çık
        if ( ! $this->settings->is_capi_enabled() ) {
            return new WP_Error( 'capi_disabled', __( 'Conversions API devre dışı', 'trackify-capi' ) );
        }
        
        // Event bu tip için kapalıysa çık
        if ( ! $this->settings->is_event_enabled( $event_name, 'capi' ) ) {
            return new WP_Error( 'event_disabled', __( 'Bu event CAPI için devre dışı', 'trackify-capi' ) );
        }
        
        // Event ID oluştur
        if ( ! $event_id ) {
            $event_id = $this->generate_event_id( $event_name );
        }
        
        // User data hazırla
        if ( empty( $user_data ) ) {
            $user_data = $this->build_user_data();
        }
        
        // Event data oluştur
        $event_data = array(
            'event_name' => $event_name,
            'event_time' => time(),
            'event_id' => $event_id,
            'action_source' => 'website',
            'event_source_url' => $this->get_current_url(),
            'user_data' => $user_data,
            'custom_data' => $custom_data,
        );
        
        // Queue kullanılıyorsa kuyruğa ekle
        if ( $this->settings->get( 'performance.use_queue' ) ) {
            $this->add_to_queue( $event_data );
            return array( 'queued' => true, 'event_id' => $event_id );
        }
        
        // Direkt gönder
        return $this->send_to_facebook( array( $event_data ) );
    }
    
    /**
     * Birden fazla event gönder (batch)
     * 
     * @param array $events
     * @return array|WP_Error
     */
    public function send_batch( $events ) {
        if ( empty( $events ) ) {
            return new WP_Error( 'empty_batch', __( 'Gönderilecek event yok', 'trackify-capi' ) );
        }
        
        return $this->send_to_facebook( $events );
    }
    
    /**
     * Facebook'a event gönder
     * 
     * @param array $events
     * @return array|WP_Error
     */
    private function send_to_facebook( $events ) {
        $pixels = $this->settings->get_active_pixels();
        
        if ( empty( $pixels ) ) {
            return new WP_Error( 'no_pixel', __( 'Aktif pixel bulunamadı', 'trackify-capi' ) );
        }
        
        $responses = array();
        
        // Her pixel için gönder
        foreach ( $pixels as $pixel ) {
            $response = $this->send_to_pixel( $pixel, $events );
            $responses[ $pixel['pixel_id'] ] = $response;
        }
        
        return $responses;
    }
    
    /**
     * Belirli bir pixel'e event gönder
     * 
     * @param array $pixel
     * @param array $events
     * @return array|WP_Error
     */
    private function send_to_pixel( $pixel, $events ) {
        $pixel_id = $pixel['pixel_id'];
        $access_token = $pixel['access_token'];
        $api_version = $this->settings->get( 'api_version', 'v18.0' );
        
        $url = $this->api_base_url . $api_version . '/' . $pixel_id . '/events';
        
        // Request body
        $body = array(
            'data' => $events,
            'access_token' => $access_token,
        );
        
        // Test mode aktifse test event code ekle
        if ( $this->settings->is_test_mode() && ! empty( $pixel['test_event_code'] ) ) {
            $body['test_event_code'] = $pixel['test_event_code'];
        }
        
        // API isteği
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
            'timeout' => 30,
            'sslverify' => true,
        ) );
        
        // Hata kontrolü
        if ( is_wp_error( $response ) ) {
            $this->log_error( $pixel_id, $events, $response );
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        // Log kaydet
        $this->log_response( $pixel_id, $events, $response_code, $response_body );
        
        // Başarı kontrolü
        if ( $response_code !== 200 ) {
            return new WP_Error(
                'api_error',
                $response_body['error']['message'] ?? __( 'API hatası', 'trackify-capi' ),
                array( 'response_code' => $response_code, 'response' => $response_body )
            );
        }
        
        return array(
            'success' => true,
            'events_received' => $response_body['events_received'] ?? count( $events ),
            'messages' => $response_body['messages'] ?? array(),
        );
    }
    
    /**
     * User data oluştur
     * 
     * @param WC_Order|null $order
     * @return array
     */
    private function build_user_data( $order = null ) {
        $user_data = array();
        
        // IP adresi
        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $user_data['client_ip_address'] = $this->get_client_ip();
        }
        
        // User agent
        if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $user_data['client_user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
        }
        
        // FBP ve FBC cookies
        if ( ! empty( $_COOKIE['_fbp'] ) ) {
            $user_data['fbp'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbp'] ) );
        }
        
        if ( ! empty( $_COOKIE['_fbc'] ) ) {
            $user_data['fbc'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbc'] ) );
        }
        
        // Order varsa billing bilgilerini ekle
        if ( $order ) {
            $user_data = array_merge( $user_data, $this->get_order_user_data( $order ) );
        } elseif ( is_user_logged_in() ) {
            $user_data = array_merge( $user_data, $this->get_logged_in_user_data() );
        }
        
        return apply_filters( 'trackify_capi_user_data', $user_data, $order );
    }
    
    /**
     * Order'dan user data çıkar
     * 
     * @param WC_Order $order
     * @return array
     */
    private function get_order_user_data( $order ) {
        $data = array();
        $hasher = new Trackify_CAPI_Data_Hasher();
        
        // Email
        $email = $order->get_billing_email();
        if ( $email ) {
            $data['em'] = $hasher->hash_email( $email );
        }
        
        // Telefon
        $phone = $order->get_billing_phone();
        if ( $phone ) {
            $data['ph'] = $hasher->hash_phone( $phone );
        }
        
        // İsim
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        
        if ( $first_name ) {
            $data['fn'] = $hasher->hash_text( $first_name );
        }
        
        if ( $last_name ) {
            $data['ln'] = $hasher->hash_text( $last_name );
        }
        
        // Adres
        $city = $order->get_billing_city();
        $state = $order->get_billing_state();
        $postcode = $order->get_billing_postcode();
        $country = $order->get_billing_country();
        
        if ( $city ) {
            $data['ct'] = $hasher->hash_text( $city );
        }
        
        if ( $state ) {
            $data['st'] = $hasher->hash_text( $state );
        }
        
        if ( $postcode ) {
            $data['zp'] = $hasher->hash_text( $postcode );
        }
        
        if ( $country ) {
            $data['country'] = $hasher->hash_text( $country );
        }
        
        // External ID (user ID)
        if ( $order->get_customer_id() ) {
            $data['external_id'] = (string) $order->get_customer_id();
        }
        
        return $data;
    }
    
    /**
     * Giriş yapmış kullanıcıdan user data çıkar
     * 
     * @return array
     */
    private function get_logged_in_user_data() {
        $data = array();
        $user = wp_get_current_user();
        $hasher = new Trackify_CAPI_Data_Hasher();
        
        // Email
        if ( $user->user_email ) {
            $data['em'] = $hasher->hash_email( $user->user_email );
        }
        
        // İsim
        if ( $user->first_name ) {
            $data['fn'] = $hasher->hash_text( $user->first_name );
        }
        
        if ( $user->last_name ) {
            $data['ln'] = $hasher->hash_text( $user->last_name );
        }
        
        // External ID
        $data['external_id'] = (string) $user->ID;
        
        // WooCommerce customer data
        if ( class_exists( 'WooCommerce' ) ) {
            $customer = new WC_Customer( $user->ID );
            
            // Telefon
            $phone = $customer->get_billing_phone();
            if ( $phone ) {
                $data['ph'] = $hasher->hash_phone( $phone );
            }
            
            // Adres
            if ( $customer->get_billing_city() ) {
                $data['ct'] = $hasher->hash_text( $customer->get_billing_city() );
            }
            
            if ( $customer->get_billing_state() ) {
                $data['st'] = $hasher->hash_text( $customer->get_billing_state() );
            }
            
            if ( $customer->get_billing_postcode() ) {
                $data['zp'] = $hasher->hash_text( $customer->get_billing_postcode() );
            }
            
            if ( $customer->get_billing_country() ) {
                $data['country'] = $hasher->hash_text( $customer->get_billing_country() );
            }
        }
        
        return $data;
    }
    
    /**
     * Event ID oluştur
     * 
     * @param string $event_name
     * @return string
     */
    private function generate_event_id( $event_name ) {
        return sanitize_key( $event_name ) . '_' . uniqid() . '_' . time();
    }
    
    /**
     * Mevcut URL'i al
     * 
     * @return string
     */
    private function get_current_url() {
        if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
            $protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            return $protocol . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        }
        return home_url( '/' );
    }
    
    /**
     * Client IP al
     * 
     * @return string
     */
    private function get_client_ip() {
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
     * Event'i kuyruğa ekle
     * 
     * @param array $event_data
     */
    private function add_to_queue( $event_data ) {
        $this->event_queue[] = $event_data;
    }
    
    /**
     * Queue'yu işle
     * 
     * @since 2.0.0
     */
    public function process_queue() {
        if ( empty( $this->event_queue ) ) {
            return;
        }
        
        // Batch sending aktifse toplu gönder
        if ( $this->settings->get( 'performance.batch_sending' ) ) {
            $this->send_batch( $this->event_queue );
        } else {
            // Teker teker gönder
            foreach ( $this->event_queue as $event ) {
                $this->send_to_facebook( array( $event ) );
            }
        }
        
        // Queue'yu temizle
        $this->event_queue = array();
    }
    
    /**
     * Response logla
     * 
     * @param string $pixel_id
     * @param array $events
     * @param int $response_code
     * @param array $response_body
     */
    private function log_response( $pixel_id, $events, $response_code, $response_body ) {
        if ( ! $this->settings->get( 'logging.enabled' ) ) {
            return;
        }
        
        foreach ( $events as $event ) {
            $this->logger->log( array(
                'event_name' => $event['event_name'],
                'event_id' => $event['event_id'],
                'pixel_id' => $pixel_id,
                'status' => $response_code === 200 ? 'success' : 'error',
                'response_code' => $response_code,
                'response_data' => $response_body,
            ) );
        }
    }
    
    /**
     * Error logla
     * 
     * @param string $pixel_id
     * @param array $events
     * @param WP_Error $error
     */
    private function log_error( $pixel_id, $events, $error ) {
        if ( ! $this->settings->get( 'logging.enabled' ) ) {
            return;
        }
        
        foreach ( $events as $event ) {
            $this->logger->log( array(
                'event_name' => $event['event_name'],
                'event_id' => $event['event_id'],
                'pixel_id' => $pixel_id,
                'status' => 'error',
                'error_message' => $error->get_error_message(),
            ) );
        }
    }
}