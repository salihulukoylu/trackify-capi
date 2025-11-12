<?php
/**
 * Easy Digital Downloads Integration
 * 
 * EDD e-commerce eventlerini yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_EDD extends Trackify_CAPI_Abstract_Integration {
    
    /**
     * Integration ID
     * 
     * @var string
     */
    protected $id = 'edd';
    
    /**
     * Integration name
     * 
     * @var string
     */
    protected $name = 'Easy Digital Downloads';
    
    /**
     * Hook'ları başlat
     */
    protected function init_hooks() {
        // ViewContent - Download sayfası
        add_action( 'edd_after_download_content', array( $this, 'track_view_content' ), 10, 1 );
        
        // AddToCart - Sepete ekleme
        add_action( 'edd_post_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 2 );
        
        // InitiateCheckout - Checkout başlatma
        add_action( 'edd_before_checkout_cart', array( $this, 'track_initiate_checkout' ) );
        
        // Purchase - Satın alma
        add_action( 'edd_complete_purchase', array( $this, 'track_purchase' ), 10, 1 );
        
        // Client-side tracking
        add_action( 'wp_footer', array( $this, 'add_client_side_scripts' ), 999 );
    }
    
    /**
     * Plugin aktif mi?
     */
    protected function is_plugin_active() {
        return class_exists( 'Easy_Digital_Downloads' );
    }
    
    /**
     * ViewContent - Download görüntüleme
     */
    public function track_view_content( $download_id ) {
        $download = edd_get_download( $download_id );
        
        if ( ! $download ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'view', $download_id );
        
        if ( $this->is_duplicate_event( $event_id ) ) {
            return;
        }
        
        $custom_data = array(
            'content_ids' => array( (string) $download_id ),
            'content_type' => 'product',
            'content_name' => $download->post_title,
            'content_category' => $this->get_download_categories( $download_id ),
            'value' => (float) edd_get_download_price( $download_id ),
            'currency' => $this->get_currency(),
        );
        
        $this->send_event( 'ViewContent', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'ViewContent tracked', array(
            'download_id' => $download_id,
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddToCart - Sepete ekleme
     */
    public function track_add_to_cart( $download_id, $options ) {
        $download = edd_get_download( $download_id );
        
        if ( ! $download ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'atc', $download_id . '_' . time() );
        
        $price = isset( $options['price_id'] ) ? 
                 edd_get_price_option_amount( $download_id, $options['price_id'] ) : 
                 edd_get_download_price( $download_id );
        
        $custom_data = array(
            'content_ids' => array( (string) $download_id ),
            'content_type' => 'product',
            'content_name' => $download->post_title,
            'content_category' => $this->get_download_categories( $download_id ),
            'value' => (float) $price,
            'currency' => $this->get_currency(),
            'num_items' => 1,
        );
        
        $this->send_event( 'AddToCart', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'AddToCart tracked', array(
            'download_id' => $download_id,
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * InitiateCheckout - Checkout başlatma
     */
    public function track_initiate_checkout() {
        $cart = edd_get_cart_contents();
        
        if ( empty( $cart ) ) {
            return;
        }
        
        $content_ids = array();
        $content_names = array();
        $categories = array();
        
        foreach ( $cart as $item ) {
            $content_ids[] = (string) $item['id'];
            $content_names[] = get_the_title( $item['id'] );
            $categories = array_merge( $categories, $this->get_download_categories( $item['id'] ) );
        }
        
        $event_id = trackify_capi_generate_event_id( 'ic', md5( implode( '_', $content_ids ) ) );
        
        if ( $this->is_duplicate_event( $event_id ) ) {
            return;
        }
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $content_names,
            'content_category' => array_unique( $categories ),
            'value' => (float) edd_get_cart_total(),
            'currency' => $this->get_currency(),
            'num_items' => edd_get_cart_quantity(),
        );
        
        $this->send_event( 'InitiateCheckout', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'InitiateCheckout tracked', array(
            'cart_total' => edd_get_cart_total(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Purchase - Satın alma
     */
    public function track_purchase( $payment_id ) {
        if ( get_post_meta( $payment_id, '_trackify_capi_purchase_tracked', true ) ) {
            return;
        }
        
        $payment = edd_get_payment( $payment_id );
        
        if ( ! $payment ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'purchase', $payment_id );
        
        $cart_items = $payment->cart_details;
        $content_ids = array();
        $content_names = array();
        $contents = array();
        $categories = array();
        
        foreach ( $cart_items as $item ) {
            $download_id = $item['id'];
            $content_ids[] = (string) $download_id;
            $content_names[] = $item['name'];
            
            $contents[] = array(
                'id' => (string) $download_id,
                'quantity' => $item['quantity'],
                'item_price' => (float) $item['price'],
            );
            
            $categories = array_merge( $categories, $this->get_download_categories( $download_id ) );
        }
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $contents,
            'content_category' => array_unique( $categories ),
            'value' => (float) $payment->total,
            'currency' => $payment->currency,
            'num_items' => count( $cart_items ),
            'order_id' => (string) $payment_id,
        );
        
        // User data from payment
        $user_data = $this->build_user_data_from_payment( $payment );
        
        $this->send_event( 'Purchase', $custom_data, $user_data, $event_id );
        
        update_post_meta( $payment_id, '_trackify_capi_purchase_tracked', 'yes' );
        update_post_meta( $payment_id, '_trackify_capi_event_id', $event_id );
        
        $this->debug_log( 'Purchase tracked', array(
            'payment_id' => $payment_id,
            'total' => $payment->total,
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Client-side tracking scripts
     */
    public function add_client_side_scripts() {
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            'use strict';
            
            // AddToCart AJAX tracking
            $(document.body).on('edd_cart_item_added', function(event, response) {
                if (typeof fbq === 'undefined') return;
                
                if (response && response.download) {
                    fbq('track', 'AddToCart', {
                        content_ids: [response.download.id],
                        content_name: response.download.title,
                        content_type: 'product',
                        value: response.download.price,
                        currency: '<?php echo esc_js( $this->get_currency() ); ?>'
                    });
                    
                    console.log('[Trackify CAPI] EDD AddToCart tracked:', response.download.id);
                }
            });
            
        });
        </script>
        <?php
    }
    
    /**
     * Get download categories
     */
    private function get_download_categories( $download_id ) {
        $categories = array();
        $terms = get_the_terms( $download_id, 'download_category' );
        
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = $term->name;
            }
        }
        
        return $categories;
    }
    
    /**
     * Build user data from payment
     */
    private function build_user_data_from_payment( $payment ) {
        $user_data = array();
        $hasher = trackify_capi_get_data_hasher();
        
        $user_info = $payment->user_info;
        
        // Email
        if ( ! empty( $user_info['email'] ) ) {
            $user_data['em'] = $hasher->hash_email( $user_info['email'] );
        }
        
        // First name
        if ( ! empty( $user_info['first_name'] ) ) {
            $user_data['fn'] = $hasher->hash_text( $user_info['first_name'] );
        }
        
        // Last name
        if ( ! empty( $user_info['last_name'] ) ) {
            $user_data['ln'] = $hasher->hash_text( $user_info['last_name'] );
        }
        
        // Address
        if ( ! empty( $user_info['address'] ) ) {
            $address = $user_info['address'];
            
            if ( ! empty( $address['city'] ) ) {
                $user_data['ct'] = $hasher->hash_text( $address['city'] );
            }
            
            if ( ! empty( $address['state'] ) ) {
                $user_data['st'] = $hasher->hash_text( $address['state'] );
            }
            
            if ( ! empty( $address['zip'] ) ) {
                $user_data['zp'] = $hasher->hash_text( $address['zip'] );
            }
            
            if ( ! empty( $address['country'] ) ) {
                $user_data['country'] = $hasher->hash_text( strtolower( $address['country'] ) );
            }
        }
        
        // Client info
        $user_data['client_ip_address'] = $this->get_client_ip();
        $user_data['client_user_agent'] = $this->get_user_agent();
        
        // Cookies
        $cookie_manager = trackify_capi_get_cookie_manager();
        $user_data['fbc'] = $cookie_manager->get_fbc();
        $user_data['fbp'] = $cookie_manager->get_fbp();
        
        return $user_data;
    }
}