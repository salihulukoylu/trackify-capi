<?php
/**
 * Event Builder
 * 
 * Event verilerini hazırlamak için yardımcı sınıf
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Event_Builder {
    
    /**
     * Event data
     * 
     * @var array
     */
    private $event_data = array();
    
    /**
     * Constructor
     * 
     * @param string $event_name Event adı
     */
    public function __construct( $event_name ) {
        $this->event_data = array(
            'event_name' => $event_name,
            'event_time' => time(),
            'event_id' => $this->generate_event_id( $event_name ),
            'action_source' => 'website',
            'event_source_url' => $this->get_current_url(),
            'user_data' => array(),
            'custom_data' => array(),
        );
    }
    
    /**
     * Event ID ekle
     * 
     * @param string $event_id
     * @return self
     */
    public function set_event_id( $event_id ) {
        $this->event_data['event_id'] = $event_id;
        return $this;
    }
    
    /**
     * Event time ekle
     * 
     * @param int $timestamp
     * @return self
     */
    public function set_event_time( $timestamp ) {
        $this->event_data['event_time'] = $timestamp;
        return $this;
    }
    
    /**
     * Action source ekle
     * 
     * @param string $source website, app, phone_call, chat, email, other
     * @return self
     */
    public function set_action_source( $source ) {
        $allowed = array( 'website', 'app', 'phone_call', 'chat', 'email', 'other' );
        
        if ( in_array( $source, $allowed, true ) ) {
            $this->event_data['action_source'] = $source;
        }
        
        return $this;
    }
    
    /**
     * Event source URL ekle
     * 
     * @param string $url
     * @return self
     */
    public function set_event_source_url( $url ) {
        $this->event_data['event_source_url'] = $url;
        return $this;
    }
    
    /**
     * User data ekle
     * 
     * @param array $user_data
     * @return self
     */
    public function set_user_data( $user_data ) {
        $this->event_data['user_data'] = array_merge( $this->event_data['user_data'], $user_data );
        return $this;
    }
    
    /**
     * Email ekle (otomatik hash'ler)
     * 
     * @param string $email
     * @return self
     */
    public function add_email( $email ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
        $this->event_data['user_data']['em'] = $hasher->hash_email( $email );
        return $this;
    }
    
    /**
     * Telefon ekle (otomatik hash'ler)
     * 
     * @param string $phone
     * @return self
     */
    public function add_phone( $phone ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
        $this->event_data['user_data']['ph'] = $hasher->hash_phone( $phone );
        return $this;
    }
    
    /**
     * İsim ekle (otomatik hash'ler)
     * 
     * @param string $first_name
     * @param string $last_name
     * @return self
     */
    public function add_name( $first_name, $last_name = '' ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
        
        if ( $first_name ) {
            $this->event_data['user_data']['fn'] = $hasher->hash_text( $first_name );
        }
        
        if ( $last_name ) {
            $this->event_data['user_data']['ln'] = $hasher->hash_text( $last_name );
        }
        
        return $this;
    }
    
    /**
     * Adres ekle (otomatik hash'ler)
     * 
     * @param string $city
     * @param string $state
     * @param string $postcode
     * @param string $country
     * @return self
     */
    public function add_address( $city = '', $state = '', $postcode = '', $country = '' ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
        
        if ( $city ) {
            $this->event_data['user_data']['ct'] = $hasher->hash_text( $city );
        }
        
        if ( $state ) {
            $this->event_data['user_data']['st'] = $hasher->hash_text( $state );
        }
        
        if ( $postcode ) {
            $this->event_data['user_data']['zp'] = $hasher->hash_postcode( $postcode );
        }
        
        if ( $country ) {
            $this->event_data['user_data']['country'] = $hasher->hash_text( $country );
        }
        
        return $this;
    }
    
    /**
     * External ID ekle
     * 
     * @param string $external_id User ID
     * @return self
     */
    public function add_external_id( $external_id ) {
        $this->event_data['user_data']['external_id'] = (string) $external_id;
        return $this;
    }
    
    /**
     * Client IP ekle
     * 
     * @param string $ip
     * @return self
     */
    public function add_client_ip( $ip = null ) {
        if ( is_null( $ip ) ) {
            $ip = $this->get_client_ip();
        }
        
        $this->event_data['user_data']['client_ip_address'] = $ip;
        return $this;
    }
    
    /**
     * Client user agent ekle
     * 
     * @param string $user_agent
     * @return self
     */
    public function add_client_user_agent( $user_agent = null ) {
        if ( is_null( $user_agent ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
        }
        
        if ( $user_agent ) {
            $this->event_data['user_data']['client_user_agent'] = $user_agent;
        }
        
        return $this;
    }
    
    /**
     * FBP cookie ekle
     * 
     * @param string $fbp
     * @return self
     */
    public function add_fbp( $fbp = null ) {
        if ( is_null( $fbp ) && ! empty( $_COOKIE['_fbp'] ) ) {
            $fbp = sanitize_text_field( wp_unslash( $_COOKIE['_fbp'] ) );
        }
        
        if ( $fbp ) {
            $this->event_data['user_data']['fbp'] = $fbp;
        }
        
        return $this;
    }
    
    /**
     * FBC cookie ekle
     * 
     * @param string $fbc
     * @return self
     */
    public function add_fbc( $fbc = null ) {
        if ( is_null( $fbc ) && ! empty( $_COOKIE['_fbc'] ) ) {
            $fbc = sanitize_text_field( wp_unslash( $_COOKIE['_fbc'] ) );
        }
        
        if ( $fbc ) {
            $this->event_data['user_data']['fbc'] = $fbc;
        }
        
        return $this;
    }
    
    /**
     * Custom data ekle
     * 
     * @param array $custom_data
     * @return self
     */
    public function set_custom_data( $custom_data ) {
        $this->event_data['custom_data'] = array_merge( $this->event_data['custom_data'], $custom_data );
        return $this;
    }
    
    /**
     * Content IDs ekle
     * 
     * @param array $content_ids
     * @return self
     */
    public function add_content_ids( $content_ids ) {
        $this->event_data['custom_data']['content_ids'] = array_map( 'strval', (array) $content_ids );
        return $this;
    }
    
    /**
     * Content type ekle
     * 
     * @param string $content_type product, product_group
     * @return self
     */
    public function add_content_type( $content_type ) {
        $this->event_data['custom_data']['content_type'] = $content_type;
        return $this;
    }
    
    /**
     * Content name ekle
     * 
     * @param string $content_name
     * @return self
     */
    public function add_content_name( $content_name ) {
        $this->event_data['custom_data']['content_name'] = $content_name;
        return $this;
    }
    
    /**
     * Content category ekle
     * 
     * @param string|array $content_category
     * @return self
     */
    public function add_content_category( $content_category ) {
        $this->event_data['custom_data']['content_category'] = $content_category;
        return $this;
    }
    
    /**
     * Value ekle
     * 
     * @param float $value
     * @param string $currency
     * @return self
     */
    public function add_value( $value, $currency = 'USD' ) {
        $this->event_data['custom_data']['value'] = (float) $value;
        $this->event_data['custom_data']['currency'] = $currency;
        return $this;
    }
    
    /**
     * Currency ekle
     * 
     * @param string $currency
     * @return self
     */
    public function add_currency( $currency ) {
        $this->event_data['custom_data']['currency'] = $currency;
        return $this;
    }
    
    /**
     * Num items ekle
     * 
     * @param int $num_items
     * @return self
     */
    public function add_num_items( $num_items ) {
        $this->event_data['custom_data']['num_items'] = (int) $num_items;
        return $this;
    }
    
    /**
     * Search string ekle
     * 
     * @param string $search_string
     * @return self
     */
    public function add_search_string( $search_string ) {
        $this->event_data['custom_data']['search_string'] = $search_string;
        return $this;
    }
    
    /**
     * Order ID ekle
     * 
     * @param string $order_id
     * @return self
     */
    public function add_order_id( $order_id ) {
        $this->event_data['custom_data']['order_id'] = (string) $order_id;
        return $this;
    }
    
    /**
     * Predicted LTV ekle
     * 
     * @param float $predicted_ltv
     * @return self
     */
    public function add_predicted_ltv( $predicted_ltv ) {
        $this->event_data['custom_data']['predicted_ltv'] = (float) $predicted_ltv;
        return $this;
    }
    
    /**
     * WooCommerce order'dan otomatik doldur
     * 
     * @param WC_Order $order
     * @return self
     */
    public function from_woocommerce_order( $order ) {
        // User data
        $this->add_email( $order->get_billing_email() );
        $this->add_phone( $order->get_billing_phone() );
        $this->add_name( $order->get_billing_first_name(), $order->get_billing_last_name() );
        $this->add_address(
            $order->get_billing_city(),
            $order->get_billing_state(),
            $order->get_billing_postcode(),
            $order->get_billing_country()
        );
        
        if ( $order->get_customer_id() ) {
            $this->add_external_id( $order->get_customer_id() );
        }
        
        // Custom data
        $content_ids = array();
        foreach ( $order->get_items() as $item ) {
            $content_ids[] = $item->get_product_id();
        }
        
        $this->add_content_ids( $content_ids );
        $this->add_content_type( 'product' );
        $this->add_value( $order->get_total(), $order->get_currency() );
        $this->add_num_items( $order->get_item_count() );
        $this->add_order_id( $order->get_id() );
        
        return $this;
    }
    
    /**
     * Event data'yı al
     * 
     * @return array
     */
    public function get_event_data() {
        return $this->event_data;
    }
    
    /**
     * Event'i gönder
     * 
     * @return array|WP_Error
     */
    public function send() {
        $capi = trackify_capi()->get_component( 'capi' );
        
        return $capi->send_event(
            $this->event_data['event_name'],
            $this->event_data['custom_data'],
            $this->event_data['user_data'],
            $this->event_data['event_id']
        );
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
     * Current URL al
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
}