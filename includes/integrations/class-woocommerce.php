<?php
/**
 * WooCommerce Integration
 * 
 * WooCommerce e-ticaret eventlerini yönetir:
 * - ViewContent (Ürün görüntüleme)
 * - AddToCart (Sepete ekleme)
 * - InitiateCheckout (Ödeme başlatma)
 * - AddPaymentInfo (Ödeme bilgisi)
 * - Purchase (Satın alma)
 * - RemoveFromCart (Sepetten çıkarma)
 * - Search (Ürün araması)
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
     * Tracked sessions (prevent duplicates)
     * 
     * @var array
     */
    private $tracked_sessions = array();
    
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
        add_action( 'woocommerce_before_checkout_form', array( $this, 'track_initiate_checkout' ), 10 );
        
        // Purchase - Sipariş tamamlandı
        add_action( 'woocommerce_thankyou', array( $this, 'track_purchase' ), 10, 1 );
        
        // AddPaymentInfo - Ödeme bilgisi eklendi
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'track_add_payment_info' ), 10, 3 );
        
        // RemoveFromCart - Sepetten çıkarma
        add_action( 'woocommerce_cart_item_removed', array( $this, 'track_remove_from_cart' ), 10, 2 );
        
        // Search - Ürün araması
        add_action( 'pre_get_posts', array( $this, 'track_product_search' ) );
        
        // WooCommerce Subscriptions support
        if ( class_exists( 'WC_Subscriptions' ) ) {
            add_action( 'woocommerce_subscription_status_active', array( $this, 'track_start_trial' ) );
            add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'track_subscribe' ) );
        }
        
        // Client-side events için script ekle
        add_action( 'wp_footer', array( $this, 'add_client_side_scripts' ), 999 );
    }
    
    /**
     * Check if plugin is active
     * 
     * @return bool
     */
    protected function is_plugin_active() {
        return class_exists( 'WooCommerce' );
    }
    
    /**
     * ViewContent - Ürün görüntüleme
     * 
     * @since 2.0.0
     */
    public function track_view_content() {
        global $product;
        
        if ( ! $product || ! is_object( $product ) ) {
            return;
        }
        
        $product_id = $product->get_id();
        $event_id = trackify_capi_generate_event_id( 'view', $product_id );
        
        // Duplicate kontrolü
        if ( $this->is_duplicate_event( $event_id ) ) {
            return;
        }
        
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price(),
            'currency' => $this->get_currency(),
        );
        
        // Variation ise parent ID'yi de ekle
        if ( $product->is_type( 'variation' ) ) {
            $custom_data['content_ids'][] = (string) $product->get_parent_id();
        }
        
        // Brand varsa ekle
        $brand = $this->get_product_brand( $product );
        if ( $brand ) {
            $custom_data['brand'] = $brand;
        }
        
        $this->send_event( 'ViewContent', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'ViewContent tracked', array(
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
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
        $product = wc_get_product( $variation_id ? $variation_id : $product_id );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = trackify_capi_generate_event_id( 'atc', $product_id . '_' . time() );
        
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price() * $quantity,
            'currency' => $this->get_currency(),
            'num_items' => $quantity,
        );
        
        // Variation varsa ekle
        if ( $variation_id ) {
            $custom_data['content_ids'][] = (string) $variation_id;
        }
        
        $this->send_event( 'AddToCart', $custom_data, $this->get_user_data(), $event_id );
        
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
        
        $event_id = trackify_capi_generate_event_id( 'atc_ajax', $product_id . '_' . time() );
        
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price(),
            'currency' => $this->get_currency(),
            'num_items' => 1,
        );
        
        $this->send_event( 'AddToCart', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'AddToCart AJAX tracked', array(
            'product_id' => $product_id,
            'event_id' => $event_id,
        ) );
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
        
        // Duplicate kontrolü
        if ( $this->is_duplicate_event( $event_id ) ) {
            return;
        }
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $content_names,
            'content_category' => array_unique( $categories ),
            'value' => (float) $cart->get_total( 'raw' ),
            'currency' => $this->get_currency(),
            'num_items' => $cart->get_cart_contents_count(),
        );
        
        $this->send_event( 'InitiateCheckout', $custom_data, $this->get_user_data(), $event_id );
        
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
        
        $this->debug_log( 'AddPaymentInfo tracked', array(
            'order_id' => $order_id,
            'payment_method' => $order->get_payment_method(),
            'event_id' => $event_id,
        ) );
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
        if ( get_post_meta( $order_id, '_trackify_capi_purchase_tracked', true ) ) {
            $this->debug_log( 'Purchase already tracked', array( 'order_id' => $order_id ) );
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
        $contents = array();
        $categories = array();
        
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $content_ids[] = (string) $product_id;
            $content_names[] = $item->get_name();
            
            $contents[] = array(
                'id' => (string) $product_id,
                'quantity' => $item->get_quantity(),
                'item_price' => (float) $item->get_total() / $item->get_quantity(),
            );
            
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $categories = array_merge( $categories, $this->get_product_categories( $product ) );
            }
        }
        
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $contents,
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
        update_post_meta( $order_id, '_trackify_capi_purchase_tracked', 'yes' );
        update_post_meta( $order_id, '_trackify_capi_event_id', $event_id );
        update_post_meta( $order_id, '_trackify_capi_tracked_at', current_time( 'mysql' ) );
        
        $this->debug_log( 'Purchase tracked', array(
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'num_items' => $order->get_item_count(),
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
        
        $event_id = trackify_capi_generate_event_id( 'rfc', $cart_item['product_id'] . '_' . time() );
        
        $custom_data = array(
            'content_ids' => array( (string) $cart_item['product_id'] ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'value' => (float) $product->get_price() * $cart_item['quantity'],
            'currency' => $this->get_currency(),
            'num_items' => $cart_item['quantity'],
        );
        
        // Custom event olarak gönder (Meta'da standart değil)
        $this->send_event( 'RemoveFromCart', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'RemoveFromCart tracked', array(
            'product_id' => $cart_item['product_id'],
            'quantity' => $cart_item['quantity'],
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Search - Ürün araması
     * 
     * @param WP_Query $query
     */
    public function track_product_search( $query ) {
        if ( ! $query->is_main_query() || ! $query->is_search() ) {
            return;
        }
        
        // Sadece product post type araması
        $post_type = $query->get( 'post_type' );
        if ( $post_type !== 'product' ) {
            return;
        }
        
        $search_query = $query->get( 's' );
        
        if ( empty( $search_query ) ) {
            return;
        }
        
        // Session kontrolü - aynı aramayı tekrar gönderme
        $session_key = 'search_' . md5( $search_query );
        if ( isset( $this->tracked_sessions[ $session_key ] ) ) {
            return;
        }
        
        $this->tracked_sessions[ $session_key ] = true;
        
        $event_id = trackify_capi_generate_event_id( 'search', md5( $search_query ) );
        
        $custom_data = array(
            'search_string' => $search_query,
            'content_category' => 'product',
        );
        
        $this->send_event( 'Search', $custom_data, $this->get_user_data(), $event_id );
        
        $this->debug_log( 'Search tracked', array(
            'search_query' => $search_query,
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * StartTrial - Abonelik deneme başlangıcı (WooCommerce Subscriptions)
     * 
     * @param WC_Subscription $subscription
     */
    public function track_start_trial( $subscription ) {
        $event_id = trackify_capi_generate_event_id( 'trial', $subscription->get_id() );
        
        $custom_data = array(
            'content_type' => 'subscription',
            'value' => (float) $subscription->get_total(),
            'currency' => $subscription->get_currency(),
            'predicted_ltv' => $this->calculate_subscription_ltv( $subscription ),
        );
        
        // User data
        $order = $subscription->get_parent();
        $user_data = $order ? $this->build_user_data_from_order( $order ) : $this->get_user_data();
        
        $this->send_event( 'StartTrial', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'StartTrial tracked', array(
            'subscription_id' => $subscription->get_id(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Subscribe - Abonelik yenileme (WooCommerce Subscriptions)
     * 
     * @param WC_Subscription $subscription
     */
    public function track_subscribe( $subscription ) {
        $event_id = trackify_capi_generate_event_id( 'subscribe', $subscription->get_id() . '_' . time() );
        
        $custom_data = array(
            'content_type' => 'subscription',
            'value' => (float) $subscription->get_total(),
            'currency' => $subscription->get_currency(),
            'predicted_ltv' => $this->calculate_subscription_ltv( $subscription ),
        );
        
        // User data
        $order = $subscription->get_parent();
        $user_data = $order ? $this->build_user_data_from_order( $order ) : $this->get_user_data();
        
        $this->send_event( 'Subscribe', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'Subscribe tracked', array(
            'subscription_id' => $subscription->get_id(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Client-side tracking scripts ekle
     * 
     * @since 2.0.0
     */
    public function add_client_side_scripts() {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            'use strict';
            
            // AddToCart button tracking (variable products)
            $(document).on('click', '.single_add_to_cart_button:not(.disabled)', function(e) {
                if (typeof fbq === 'undefined') return;
                
                var $button = $(this);
                var $form = $button.closest('form.cart');
                
                // Product ID al
                var productId = $form.find('input[name="product_id"]').val() || 
                               $form.find('input[name="add-to-cart"]').val() ||
                               $button.val();
                               
                if (!productId) return;
                
                // Quantity al
                var quantity = parseInt($form.find('input[name="quantity"]').val()) || 1;
                
                // Product bilgileri
                var productName = $('.product_title').text() || '';
                var priceText = $('.woocommerce-Price-amount').first().text();
                var productPrice = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                
                if (isNaN(productPrice)) productPrice = 0;
                
                // Track AddToCart
                fbq('track', 'AddToCart', {
                    content_ids: [productId],
                    content_type: 'product',
                    content_name: productName,
                    value: productPrice * quantity,
                    currency: '<?php echo esc_js( $this->get_currency() ); ?>',
                    num_items: quantity
                });
                
                console.log('[Trackify CAPI] AddToCart tracked (client-side):', productId);
            });
            
            // Loop AddToCart AJAX tracking
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                if (typeof fbq === 'undefined') return;
                
                var productId = $button.data('product_id');
                var productName = $button.data('product_name') || '';
                var quantity = $button.data('quantity') || 1;
                
                if (!productId) return;
                
                fbq('track', 'AddToCart', {
                    content_ids: [productId],
                    content_type: 'product',
                    content_name: productName,
                    num_items: quantity
                });
                
                console.log('[Trackify CAPI] AddToCart AJAX tracked (client-side):', productId);
            });
            
        });
        </script>
        <?php
    }
    
    /**
     * Get product categories
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
     * Get product brand
     * 
     * @param WC_Product $product
     * @return string|null
     */
    private function get_product_brand( $product ) {
        // Yoast WooCommerce SEO
        $brand = get_post_meta( $product->get_id(), '_yoast_wpseo_brand', true );
        
        if ( ! empty( $brand ) ) {
            return $brand;
        }
        
        // Perfect Brands for WooCommerce
        $terms = get_the_terms( $product->get_id(), 'pwb-brand' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            return $terms[0]->name;
        }
        
        return null;
    }
    
    /**
     * Build user data from order
     * 
     * @param WC_Order $order
     * @return array
     */
    private function build_user_data_from_order( $order ) {
        $user_data = array();
        $hasher = trackify_capi_get_data_hasher();
        
        // Email
        $email = $order->get_billing_email();
        if ( ! empty( $email ) ) {
            $user_data['em'] = $hasher->hash_email( $email );
        }
        
        // Phone
        $phone = $order->get_billing_phone();
        if ( ! empty( $phone ) ) {
            $user_data['ph'] = $hasher->hash_phone( $phone );
        }
        
        // First Name
        $first_name = $order->get_billing_first_name();
        if ( ! empty( $first_name ) ) {
            $user_data['fn'] = $hasher->hash_text( $first_name );
        }
        
        // Last Name
        $last_name = $order->get_billing_last_name();
        if ( ! empty( $last_name ) ) {
            $user_data['ln'] = $hasher->hash_text( $last_name );
        }
        
        // City
        $city = $order->get_billing_city();
        if ( ! empty( $city ) ) {
            $user_data['ct'] = $hasher->hash_text( $city );
        }
        
        // State
        $state = $order->get_billing_state();
        if ( ! empty( $state ) ) {
            $user_data['st'] = $hasher->hash_text( $state );
        }
        
        // Zip
        $postcode = $order->get_billing_postcode();
        if ( ! empty( $postcode ) ) {
            $user_data['zp'] = $hasher->hash_text( $postcode );
        }
        
        // Country
        $country = $order->get_billing_country();
        if ( ! empty( $country ) ) {
            $user_data['country'] = $hasher->hash_text( strtolower( $country ) );
        }
        
        // Client IP & User Agent
        $user_data['client_ip_address'] = $this->get_client_ip();
        $user_data['client_user_agent'] = $this->get_user_agent();
        
        // FBC & FBP cookies
        $cookie_manager = trackify_capi_get_cookie_manager();
        $user_data['fbc'] = $cookie_manager->get_fbc();
        $user_data['fbp'] = $cookie_manager->get_fbp();
        
        return $user_data;
    }
    
    /**
     * Calculate subscription LTV
     * 
     * @param WC_Subscription $subscription
     * @return float
     */
    private function calculate_subscription_ltv( $subscription ) {
        $total = (float) $subscription->get_total();
        $billing_period = $subscription->get_billing_period();
        $billing_interval = $subscription->get_billing_interval();
        
        // Yıllık değere çevir
        $periods_per_year = 1;
        
        switch ( $billing_period ) {
            case 'day':
                $periods_per_year = 365 / $billing_interval;
                break;
            case 'week':
                $periods_per_year = 52 / $billing_interval;
                break;
            case 'month':
                $periods_per_year = 12 / $billing_interval;
                break;
            case 'year':
                $periods_per_year = 1 / $billing_interval;
                break;
        }
        
        // 2 yıllık LTV hesapla (ortalama abonelik süresi)
        $ltv = $total * $periods_per_year * 2;
        
        return round( $ltv, 2 );
    }
}