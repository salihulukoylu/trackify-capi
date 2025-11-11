<?php
/**
 * WooCommerce Integration
 * 
 * WooCommerce e-ticaret eventlerini yönetir
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Woocommerce extends Trackify_CAPI_Abstract_Integration {
    
    /**
     * Integration ID
     * 
     * @var string
     */
    protected $id = 'woocommerce';
    
    /**
     * Integration name
     * 
     * @var string
     */
    protected $name = 'WooCommerce';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // ViewContent - Ürün sayfası
        add_action( 'woocommerce_after_single_product', array( $this, 'track_view_content' ) );
        
        // AddToCart - Sepete ekleme
        add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 6 );
        
        // AddToCart - AJAX (loop/archive sayfaları)
        add_action( 'woocommerce_ajax_added_to_cart', array( $this, 'track_ajax_add_to_cart' ) );
        
        // InitiateCheckout - Checkout başlatma
        add_action( 'woocommerce_before_checkout_form', array( $this, 'track_initiate_checkout' ) );
        
        // Purchase - Sipariş tamamlandı
        add_action( 'woocommerce_thankyou', array( $this, 'track_purchase' ), 10, 1 );
        
        // AddPaymentInfo - Ödeme bilgisi eklendi
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'track_add_payment_info' ), 10, 3 );
        
        // RemoveFromCart - Sepetten çıkarma (opsiyonel)
        add_action( 'woocommerce_remove_cart_item', array( $this, 'track_remove_from_cart' ), 10, 2 );
        
        // StartTrial - Abonelik deneme başlangıcı (WooCommerce Subscriptions)
        if ( class_exists( 'WC_Subscriptions' ) ) {
            add_action( 'woocommerce_subscription_status_active', array( $this, 'track_start_trial' ) );
        }
    }
    
    /**
     * ViewContent - Ürün görüntüleme
     * 
     * @since 2.0.0
     */
    public function track_view_content() {
        global $product;
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'view', $product->get_id() );
        
        $custom_data = array(
            'content_ids' => array( (string) $product->get_id() ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price(),
            'currency' => get_woocommerce_currency(),
        );
        
        // Variation ID varsa ekle
        if ( $product->is_type( 'variation' ) ) {
            $custom_data['content_ids'][] = (string) $product->get_parent_id();
        }
        
        $this->send_event( 'ViewContent', $custom_data, array(), $event_id );
        
        $this->debug_log( 'ViewContent tracked', array(
            'product_id' => $product->get_id(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddToCart - Sepete ekleme
     * 
     * @param string $cart_item_key
     * @param int $product_id
     * @param int $quantity
     * @param int $variation_id
     * @param array $variation
     * @param array $cart_item_data
     */
    public function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'atc', $product_id );
        
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price() * $quantity,
            'currency' => get_woocommerce_currency(),
            'num_items' => $quantity,
        );
        
        // Variation varsa ekle
        if ( $variation_id ) {
            $custom_data['content_ids'][] = (string) $variation_id;
        }
        
        $this->send_event( 'AddToCart', $custom_data, array(), $event_id );
        
        $this->debug_log( 'AddToCart tracked', array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddToCart - AJAX (loop sayfaları)
     * 
     * @param int $product_id
     */
    public function track_ajax_add_to_cart( $product_id ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'atc_ajax', $product_id );
        
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price(),
            'currency' => get_woocommerce_currency(),
            'num_items' => 1,
        );
        
        $this->send_event( 'AddToCart', $custom_data, array(), $event_id );
    }
    
    /**
     * InitiateCheckout - Checkout başlatma
     * 
     * @since 2.0.0
     */
    public function track_initiate_checkout() {
        $cart = WC()->cart;
        
        if ( ! $cart || $cart->is_empty() ) {
            return;
        }
        
        $content_ids = array();
        $content_names = array();
        $categories = array();
        
        foreach ( $cart->get_cart() as $item ) {
            $content_ids[] = (string) $item['product_id'];
            
            $product = wc_get_product( $item['product_id'] );
            if ( $product ) {
                $content_names[] = $product->get_name();
                $categories = array_merge( $categories, $this->get_product_categories( $product ) );
            }
        }
        
        $event_id = trackify_capi_generate_event_id( 'ic', md5( implode( '_', $content_ids ) ) );
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $content_names,
            'content_category' => array_unique( $categories ),
            'value' => (float) $cart->get_total( 'raw' ),
            'currency' => get_woocommerce_currency(),
            'num_items' => $cart->get_cart_contents_count(),
        );
        
        $this->send_event( 'InitiateCheckout', $custom_data, array(), $event_id );
        
        $this->debug_log( 'InitiateCheckout tracked', array(
            'cart_total' => $cart->get_total( 'raw' ),
            'num_items' => $cart->get_cart_contents_count(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddPaymentInfo - Ödeme bilgisi eklendi
     * 
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function track_add_payment_info( $order_id, $posted_data, $order ) {
        $event_id = trackify_capi_generate_event_id( 'api', $order_id );
        
        $content_ids = array();
        foreach ( $order->get_items() as $item ) {
            $content_ids[] = (string) $item->get_product_id();
        }
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'value' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_type' => $order->get_payment_method(),
        );
        
        // User data (order'dan)
        $user_data = $this->build_user_data_from_order( $order );
        
        $this->send_event( 'AddPaymentInfo', $custom_data, $user_data, $event_id );
    }
    
    /**
     * Purchase - Sipariş tamamlandı
     * 
     * @param int $order_id
     */
    public function track_purchase( $order_id ) {
        if ( ! $order_id ) {
            return;
        }
        
        // Daha önce track edildiyse tekrar etme
        if ( get_post_meta( $order_id, '_trackify_capi_tracked', true ) ) {
            return;
        }
        
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'purchase', $order_id );
        
        // Ürün bilgileri
        $content_ids = array();
        $content_names = array();
        $categories = array();
        
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $content_ids[] = (string) $product_id;
            $content_names[] = $item->get_name();
            
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $categories = array_merge( $categories, $this->get_product_categories( $product ) );
            }
        }
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $content_names,
            'content_category' => array_unique( $categories ),
            'value' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'num_items' => $order->get_item_count(),
            'order_id' => (string) $order_id,
        );
        
        // User data (order'dan)
        $user_data = $this->build_user_data_from_order( $order );
        
        $this->send_event( 'Purchase', $custom_data, $user_data, $event_id );
        
        // İşaretle
        update_post_meta( $order_id, '_trackify_capi_tracked', 'yes' );
        update_post_meta( $order_id, '_trackify_capi_event_id', $event_id );
        
        $this->debug_log( 'Purchase tracked', array(
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * RemoveFromCart - Sepetten çıkarma
     * 
     * @param string $cart_item_key
     * @param WC_Cart $cart
     */
    public function track_remove_from_cart( $cart_item_key, $cart ) {
        $cart_item = $cart->get_cart_item( $cart_item_key );
        
        if ( ! $cart_item ) {
            return;
        }
        
        $product = wc_get_product( $cart_item['product_id'] );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'rfc', $cart_item['product_id'] );
        
        $custom_data = array(
            'content_ids' => array( (string) $cart_item['product_id'] ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'value' => (float) $product->get_price() * $cart_item['quantity'],
            'currency' => get_woocommerce_currency(),
        );
        
        $this->send_event( 'RemoveFromCart', $custom_data, array(), $event_id );
    }
    
    /**
     * StartTrial - Abonelik deneme başlangıcı
     * 
     * @param WC_Subscription $subscription
     */
    public function track_start_trial( $subscription ) {
        if ( ! $subscription->has_status( 'active' ) ) {
            return;
        }
        
        // Deneme süresi var mı?
        if ( ! $subscription->get_trial_end_date() ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'trial', $subscription->get_id() );
        
        $custom_data = array(
            'content_type' => 'subscription',
            'value' => (float) $subscription->get_total(),
            'currency' => $subscription->get_currency(),
            'predicted_ltv' => $this->calculate_subscription_ltv( $subscription ),
        );
        
        $order = $subscription->get_parent();
        $user_data = $order ? $this->build_user_data_from_order( $order ) : array();
        
        $this->send_event( 'StartTrial', $custom_data, $user_data, $event_id );
    }
    
    /**
     * Order'dan user data oluştur
     * 
     * @param WC_Order $order
     * @return array
     */
    private function build_user_data_from_order( $order ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
        
        $user_data = array();
        
        // Email
        $email = $order->get_billing_email();
        if ( $email ) {
            $user_data['em'] = $hasher->hash_email( $email );
        }
        
        // Telefon
        $phone = $order->get_billing_phone();
        if ( $phone ) {
            $user_data['ph'] = $hasher->hash_phone( $phone );
        }
        
        // İsim
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        
        if ( $first_name ) {
            $user_data['fn'] = $hasher->hash_text( $first_name );
        }
        
        if ( $last_name ) {
            $user_data['ln'] = $hasher->hash_text( $last_name );
        }
        
        // Adres
        $city = $order->get_billing_city();
        $state = $order->get_billing_state();
        $postcode = $order->get_billing_postcode();
        $country = $order->get_billing_country();
        
        if ( $city ) {
            $user_data['ct'] = $hasher->hash_text( $city );
        }
        
        if ( $state ) {
            $user_data['st'] = $hasher->hash_text( $state );
        }
        
        if ( $postcode ) {
            $user_data['zp'] = $hasher->hash_postcode( $postcode );
        }
        
        if ( $country ) {
            $user_data['country'] = $hasher->hash_text( $country );
        }
        
        // External ID (user ID)
        if ( $order->get_customer_id() ) {
            $user_data['external_id'] = (string) $order->get_customer_id();
        }
        
        // Client IP (order'da saklanmışsa)
        $client_ip = $order->get_customer_ip_address();
        if ( $client_ip ) {
            $user_data['client_ip_address'] = $client_ip;
        }
        
        // User agent (order'da saklanmışsa)
        $user_agent = $order->get_customer_user_agent();
        if ( $user_agent ) {
            $user_data['client_user_agent'] = $user_agent;
        }
        
        return $user_data;
    }
    
    /**
     * Ürün kategorilerini al
     * 
     * @param WC_Product $product
     * @return array
     */
    private function get_product_categories( $product ) {
        $categories = array();
        $terms = get_the_terms( $product->get_id(), 'product_cat' );
        
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = $term->name;
            }
        }
        
        return $categories;
    }
    
    /**
     * Subscription LTV hesapla (tahmin)
     * 
     * @param WC_Subscription $subscription
     * @return float
     */
    private function calculate_subscription_ltv( $subscription ) {
        $total = (float) $subscription->get_total();
        
        // Abonelik periyodu
        $billing_period = $subscription->get_billing_period();
        $billing_interval = $subscription->get_billing_interval();
        
        // Ortalama yaşam süresi (12 ay kabul edelim)
        $estimated_lifetime_months = 12;
        
        // Aylık değer hesapla
        $monthly_value = 0;
        
        switch ( $billing_period ) {
            case 'day':
                $monthly_value = $total * 30 / $billing_interval;
                break;
            case 'week':
                $monthly_value = $total * 4 / $billing_interval;
                break;
            case 'month':
                $monthly_value = $total / $billing_interval;
                break;
            case 'year':
                $monthly_value = $total / 12 / $billing_interval;
                break;
        }
        
        // LTV
        $ltv = $monthly_value * $estimated_lifetime_months;
        
        return round( $ltv, 2 );
    }
}