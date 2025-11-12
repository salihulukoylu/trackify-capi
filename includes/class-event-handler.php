<?php
/**
 * Event Handler
 * 
 * Tüm event tracking işlemlerini yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Event_Handler {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Pixel instance
     * 
     * @var Trackify_CAPI_Pixel
     */
    private $pixel;
    
    /**
     * CAPI instance
     * 
     * @var Trackify_CAPI_CAPI
     */
    private $capi;
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    private $logger;
    
    /**
     * Constructor
     * 
     * @param Trackify_CAPI_Settings $settings
     * @param Trackify_CAPI_Pixel $pixel
     * @param Trackify_CAPI_CAPI $capi
     * @param Trackify_CAPI_Logger $logger
     */
    public function __construct( $settings, $pixel, $capi, $logger ) {
        $this->settings = $settings;
        $this->pixel = $pixel;
        $this->capi = $capi;
        $this->logger = $logger;
        
        $this->init_hooks();
    }
    
    /**
     * Init hooks
     */
    private function init_hooks() {
        // PageView
        add_action( 'wp_footer', array( $this, 'track_pageview' ), 5 );
        
        // Search
        add_action( 'pre_get_posts', array( $this, 'track_search' ) );
        
        // Allow other plugins to hook into event tracking
        do_action( 'trackify_capi_event_handler_init', $this );
    }
    
    /**
     * Track event (generic method)
     * 
     * @param string $event_name
     * @param array $event_data
     * @param array $custom_data
     * @param array $user_data
     * @return bool
     */
    public function track_event( $event_name, $event_data = array(), $custom_data = array(), $user_data = array() ) {
        // Check if plugin is enabled
        if ( ! $this->settings->is_enabled() ) {
            return false;
        }
        
        // Check if event is enabled
        if ( ! $this->settings->is_event_enabled( $event_name ) ) {
            return false;
        }
        
        $success = true;
        
        // Track via Pixel
        if ( $this->settings->is_event_enabled( $event_name, 'pixel' ) ) {
            $pixel_success = $this->pixel->track_event( $event_name, $event_data, $custom_data );
            
            if ( ! $pixel_success ) {
                $success = false;
            }
        }
        
        // Track via CAPI
        if ( $this->settings->is_event_enabled( $event_name, 'capi' ) && $this->settings->is_capi_enabled() ) {
            $capi_success = $this->capi->send_event( $event_name, $event_data, $custom_data, $user_data );
            
            if ( ! $capi_success ) {
                $success = false;
            }
        }
        
        do_action( 'trackify_capi_event_tracked', $event_name, $event_data, $custom_data, $user_data );
        
        return $success;
    }
    
    /**
     * Track PageView
     */
    public function track_pageview() {
        if ( ! $this->settings->is_event_enabled( 'PageView' ) ) {
            return;
        }
        
        $event_data = array(
            'content_name' => wp_get_document_title(),
            'content_category' => $this->get_page_category(),
        );
        
        // Get user data for CAPI
        $user_data = $this->get_current_user_data();
        
        $this->track_event( 'PageView', $event_data, array(), $user_data );
    }
    
    /**
     * Track Search
     * 
     * @param WP_Query $query
     */
    public function track_search( $query ) {
        if ( ! $query->is_main_query() || ! $query->is_search() ) {
            return;
        }
        
        if ( ! $this->settings->is_event_enabled( 'Search' ) ) {
            return;
        }
        
        $search_string = get_search_query();
        
        if ( empty( $search_string ) ) {
            return;
        }
        
        $event_data = array(
            'search_string' => $search_string,
            'content_category' => 'search',
        );
        
        $user_data = $this->get_current_user_data();
        
        $this->track_event( 'Search', $event_data, array(), $user_data );
    }
    
    /**
     * Track ViewContent
     * 
     * @param string $content_id
     * @param string $content_name
     * @param string $content_type
     * @param float $value
     * @param string $currency
     * @param array $extra_data
     */
    public function track_view_content( $content_id, $content_name, $content_type, $value = 0, $currency = 'USD', $extra_data = array() ) {
        $event_data = array_merge( array(
            'content_ids' => array( $content_id ),
            'content_name' => $content_name,
            'content_type' => $content_type,
            'value' => $value,
            'currency' => $currency,
        ), $extra_data );
        
        $user_data = $this->get_current_user_data();
        
        $this->track_event( 'ViewContent', $event_data, array(), $user_data );
    }
    
    /**
     * Track AddToCart
     * 
     * @param string $content_id
     * @param string $content_name
     * @param float $value
     * @param string $currency
     * @param array $extra_data
     */
    public function track_add_to_cart( $content_id, $content_name, $value, $currency = 'USD', $extra_data = array() ) {
        $event_data = array_merge( array(
            'content_ids' => array( $content_id ),
            'content_name' => $content_name,
            'content_type' => 'product',
            'value' => $value,
            'currency' => $currency,
        ), $extra_data );
        
        $user_data = $this->get_current_user_data();
        
        $this->track_event( 'AddToCart', $event_data, array(), $user_data );
    }
    
    /**
     * Track InitiateCheckout
     * 
     * @param array $content_ids
     * @param float $value
     * @param string $currency
     * @param int $num_items
     * @param array $extra_data
     */
    public function track_initiate_checkout( $content_ids, $value, $currency = 'USD', $num_items = 1, $extra_data = array() ) {
        $event_data = array_merge( array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'value' => $value,
            'currency' => $currency,
            'num_items' => $num_items,
        ), $extra_data );
        
        $user_data = $this->get_current_user_data();
        
        $this->track_event( 'InitiateCheckout', $event_data, array(), $user_data );
    }
    
    /**
     * Track Purchase
     * 
     * @param string $order_id
     * @param float $value
     * @param string $currency
     * @param array $content_ids
     * @param int $num_items
     * @param array $user_data
     * @param array $extra_data
     */
    public function track_purchase( $order_id, $value, $currency, $content_ids, $num_items, $user_data = array(), $extra_data = array() ) {
        $event_data = array_merge( array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'value' => $value,
            'currency' => $currency,
            'num_items' => $num_items,
        ), $extra_data );
        
        $custom_data = array(
            'order_id' => $order_id,
        );
        
        // Merge with provided user data
        if ( empty( $user_data ) ) {
            $user_data = $this->get_current_user_data();
        }
        
        $this->track_event( 'Purchase', $event_data, $custom_data, $user_data );
    }
    
    /**
     * Track Lead
     * 
     * @param string $content_name
     * @param string $content_category
     * @param array $user_data
     * @param array $extra_data
     */
    public function track_lead( $content_name, $content_category = 'form', $user_data = array(), $extra_data = array() ) {
        $event_data = array_merge( array(
            'content_name' => $content_name,
            'content_category' => $content_category,
        ), $extra_data );
        
        // Merge with provided user data
        if ( empty( $user_data ) ) {
            $user_data = $this->get_current_user_data();
        }
        
        $this->track_event( 'Lead', $event_data, array(), $user_data );
    }
    
    /**
     * Track CompleteRegistration
     * 
     * @param string $registration_method
     * @param string $status
     * @param array $user_data
     */
    public function track_complete_registration( $registration_method = 'website', $status = 'completed', $user_data = array() ) {
        $event_data = array(
            'content_name' => 'registration',
            'status' => $status,
        );
        
        $custom_data = array(
            'registration_method' => $registration_method,
        );
        
        // Merge with provided user data
        if ( empty( $user_data ) ) {
            $user_data = $this->get_current_user_data();
        }
        
        $this->track_event( 'CompleteRegistration', $event_data, $custom_data, $user_data );
    }
    
    /**
     * Get current user data
     * 
     * @return array
     */
    private function get_current_user_data() {
        $user_data = array();
        
        // Check if advanced matching is enabled
        if ( ! $this->settings->is_advanced_matching_enabled() ) {
            return $user_data;
        }
        
        // Get user data from cookie manager
        $cookie_manager = new Trackify_CAPI_Cookie_Manager();
        
        // FBP & FBC
        $user_data['fbp'] = $cookie_manager->get_fbp();
        $user_data['fbc'] = $cookie_manager->get_fbc();
        
        // User IP
        $user_data['client_ip_address'] = $this->get_user_ip();
        $user_data['client_user_agent'] = $this->get_user_agent();
        
        // Logged-in user data
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            
            if ( $this->settings->get( 'advanced_matching.hash_email' ) ) {
                $user_data['em'] = ! empty( $user->user_email ) ? hash( 'sha256', strtolower( trim( $user->user_email ) ) ) : '';
            }
            
            if ( $this->settings->get( 'advanced_matching.hash_name' ) ) {
                $user_data['fn'] = ! empty( $user->first_name ) ? hash( 'sha256', strtolower( trim( $user->first_name ) ) ) : '';
                $user_data['ln'] = ! empty( $user->last_name ) ? hash( 'sha256', strtolower( trim( $user->last_name ) ) ) : '';
            }
        }
        
        return apply_filters( 'trackify_capi_user_data', $user_data );
    }
    
    /**
     * Get user IP address
     * 
     * @return string
     */
    private function get_user_ip() {
        $ip = '';
        
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        
        return $ip;
    }
    
    /**
     * Get user agent
     * 
     * @return string
     */
    private function get_user_agent() {
        return ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    }
    
    /**
     * Get page category
     * 
     * @return string
     */
    private function get_page_category() {
        if ( is_front_page() ) {
            return 'homepage';
        } elseif ( is_shop() || is_product_category() || is_product_tag() ) {
            return 'shop';
        } elseif ( is_product() ) {
            return 'product';
        } elseif ( is_cart() ) {
            return 'cart';
        } elseif ( is_checkout() ) {
            return 'checkout';
        } elseif ( is_account_page() ) {
            return 'account';
        } elseif ( is_single() ) {
            return 'post';
        } elseif ( is_page() ) {
            return 'page';
        } elseif ( is_category() || is_tag() || is_archive() ) {
            return 'archive';
        } elseif ( is_search() ) {
            return 'search';
        } else {
            return 'other';
        }
    }
}