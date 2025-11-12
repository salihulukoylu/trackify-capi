<?php
/**
 * WooCommerce Integration
 * 
 * WooCommerce e-ticaret eventlerini yönetir
 * - ViewContent (Ürün görüntüleme)
 * - AddToCart (Sepete ekleme)
 * - InitiateCheckout (Ödeme başlatma)
 * - AddPaymentInfo (Ödeme bilgisi)
 * - Purchase (Satın alma)
 * - RemoveFromCart (Sepetten çıkarma)
 * - AddToWishlist (İstek listesi - opsiyonel)
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
        
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'ViewContent' ) ) {
            return;
        }
        
        $product_id = $product->get_id();
        $event_id = $this->generate_event_id( 'view', $product_id );
        
        // Custom data
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price(),
            'currency' => get_woocommerce_currency(),
        );
        
        // Variation ise parent ID'yi de ekle
        if ( $product->is_type( 'variation' ) ) {
            $custom_data['content_ids'][] = (string) $product->get_parent_id();
        }
        
        // Stock durumu
        if ( ! $product->is_in_stock() ) {
            $custom_data['availability'] = 'out of stock';
        }
        
        // Brand varsa (yoast woo seo vb.)
        $brand = $this->get_product_brand( $product );
        if ( $brand ) {
            $custom_data['brand'] = $brand;
        }
        
        // User data
        $user_data = $this->build_user_data();
        
        // Track event
        $this->track_event( 'ViewContent', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'ViewContent tracked', array(
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddToCart - Sepete ekleme (normal)
     * 
     * @param string $cart_item_key Cart item key
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @param int $variation_id Variation ID
     * @param array $variation Variation data
     * @param array $cart_item_data Cart item data
     */
    public function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'AddToCart' ) ) {
            return;
        }
        
        $product = wc_get_product( $variation_id ? $variation_id : $product_id );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = $this->generate_event_id( 'atc', $product_id . '_' . time() );
        
        // Custom data
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price() * $quantity,
            'currency' => get_woocommerce_currency(),
            'num_items' => $quantity,
        );
        
        // Variation ID varsa ekle
        if ( $variation_id ) {
            $custom_data['content_ids'][] = (string) $variation_id;
            $custom_data['variant'] = $this->get_variation_attributes( $variation );
        }
        
        // Brand
        $brand = $this->get_product_brand( $product );
        if ( $brand ) {
            $custom_data['brand'] = $brand;
        }
        
        // User data
        $user_data = $this->build_user_data();
        
        // Track event
        $this->track_event( 'AddToCart', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'AddToCart tracked', array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $quantity,
            'value' => $custom_data['value'],
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddToCart - AJAX (loop/archive sayfalarından)
     * 
     * @param int $product_id Product ID
     */
    public function track_ajax_add_to_cart( $product_id ) {
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'AddToCart' ) ) {
            return;
        }
        
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = $this->generate_event_id( 'atc_ajax', $product_id . '_' . time() );
        
        // Quantity'yi cart'tan al
        $quantity = 1;
        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $item ) {
                if ( $item['product_id'] == $product_id ) {
                    $quantity = $item['quantity'];
                    break;
                }
            }
        }
        
        // Custom data
        $custom_data = array(
            'content_ids' => array( (string) $product_id ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price() * $quantity,
            'currency' => get_woocommerce_currency(),
            'num_items' => $quantity,
        );
        
        // User data
        $user_data = $this->build_user_data();
        
        // Track event
        $this->track_event( 'AddToCart', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'AddToCart (AJAX) tracked', array(
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
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'InitiateCheckout' ) ) {
            return;
        }
        
        $cart = WC()->cart;
        
        if ( ! $cart || $cart->is_empty() ) {
            return;
        }
        
        // Session kontrolü - aynı checkout'u birden fazla kez track etme
        $session_key = 'ic_' . md5( wp_json_encode( $cart->get_cart() ) );
        if ( isset( $this->tracked_sessions[ $session_key ] ) ) {
            return;
        }
        
        $this->tracked_sessions[ $session_key ] = true;
        
        // Cart data
        $content_ids = array();
        $contents = array();
        $categories = array();
        
        foreach ( $cart->get_cart() as $item ) {
            $content_ids[] = (string) $item['product_id'];
            
            $product = wc_get_product( $item['product_id'] );
            if ( $product ) {
                $contents[] = array(
                    'id' => (string) $item['product_id'],
                    'quantity' => $item['quantity'],
                    'item_price' => (float) $product->get_price(),
                );
                
                $product_categories = $this->get_product_categories( $product );
                if ( is_array( $product_categories ) ) {
                    $categories = array_merge( $categories, $product_categories );
                }
            }
        }
        
        $event_id = $this->generate_event_id( 'ic', md5( implode( '_', $content_ids ) ) );
        
        // Custom data
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $contents,
            'content_category' => ! empty( $categories ) ? array_unique( $categories ) : array(),
            'value' => (float) $cart->get_total( 'raw' ),
            'currency' => get_woocommerce_currency(),
            'num_items' => $cart->get_cart_contents_count(),
        );
        
        // User data
        $user_data = $this->build_user_data();
        
        // Track event
        $this->track_event( 'InitiateCheckout', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'InitiateCheckout tracked', array(
            'cart_total' => $cart->get_total( 'raw' ),
            'num_items' => $cart->get_cart_contents_count(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * AddPaymentInfo - Ödeme bilgisi eklendi
     * 
     * @param int $order_id Order ID
     * @param array $posted_data Posted data
     * @param WC_Order $order Order object
     */
    public function track_add_payment_info( $order_id, $posted_data, $order ) {
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'AddPaymentInfo' ) ) {
            return;
        }
        
        $event_id = $this->generate_event_id( 'api', $order_id );
        
        // Content IDs
        $content_ids = array();
        $contents = array();
        
        foreach ( $order->get_items() as $item ) {
            $content_ids[] = (string) $item->get_product_id();
            $contents[] = array(
                'id' => (string) $item->get_product_id(),
                'quantity' => $item->get_quantity(),
                'item_price' => (float) $item->get_total() / $item->get_quantity(),
            );
        }
        
        // Custom data
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $contents,
            'value' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_type' => $order->get_payment_method(),
        );
        
        // User data (order'dan)
        $user_data = $this->build_user_data_from_order( $order );
        
        // Track event
        $this->track_event( 'AddPaymentInfo', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'AddPaymentInfo tracked', array(
            'order_id' => $order_id,
            'payment_method' => $order->get_payment_method(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Purchase - Sipariş tamamlandı
     * 
     * @param int $order_id Order ID
     */
    public function track_purchase( $order_id ) {
        if ( ! $order_id ) {
            return;
        }
        
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'Purchase' ) ) {
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
        
        // Test order'ları skip et (eğer ayarlarda belirtilmişse)
        if ( $this->settings->get( 'woocommerce.skip_test_orders' ) ) {
            if ( $order->get_meta( '_test_order' ) || $order->get_total() == 0 ) {
                return;
            }
        }
        
        $event_id = $this->generate_event_id( 'purchase', $order_id );
        
        // Content IDs ve contents
        $content_ids = array();
        $contents = array();
        $categories = array();
        
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $content_ids[] = (string) $product_id;
            
            $contents[] = array(
                'id' => (string) $product_id,
                'quantity' => $item->get_quantity(),
                'item_price' => (float) ( $item->get_total() / $item->get_quantity() ),
            );
            
            // Get product for categories
            $product = $item->get_product();
            if ( $product ) {
                $product_categories = $this->get_product_categories( $product );
                if ( is_array( $product_categories ) ) {
                    $categories = array_merge( $categories, $product_categories );
                }
            }
        }
        
        // Custom data
        $custom_data = array(
            'content_ids' => $content_ids,
            'content_type' => 'product',
            'contents' => $contents,
            'content_category' => ! empty( $categories ) ? array_unique( $categories ) : array(),
            'value' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'num_items' => $order->get_item_count(),
            'order_id' => (string) $order_id,
        );
        
        // Shipping ve tax bilgileri
        $custom_data['shipping'] = (float) $order->get_shipping_total();
        $custom_data['tax'] = (float) $order->get_total_tax();
        
        // Payment method
        $custom_data['payment_type'] = $order->get_payment_method();
        
        // Coupon varsa
        $coupons = $order->get_coupon_codes();
        if ( ! empty( $coupons ) ) {
            $custom_data['coupon_code'] = implode( ', ', $coupons );
        }
        
        // First time customer mı?
        $customer_id = $order->get_customer_id();
        if ( $customer_id ) {
            $customer_orders = wc_get_orders( array(
                'customer_id' => $customer_id,
                'status' => array( 'wc-completed', 'wc-processing' ),
                'limit' => 2,
            ) );
            
            if ( count( $customer_orders ) <= 1 ) {
                $custom_data['first_time_buyer'] = true;
            }
        }
        
        // User data (order'dan)
        $user_data = $this->build_user_data_from_order( $order );
        
        // Track event
        $this->track_event( 'Purchase', $custom_data, $user_data, $event_id );
        
        // Meta olarak işaretle
        update_post_meta( $order_id, '_trackify_capi_purchase_tracked', true );
        update_post_meta( $order_id, '_trackify_capi_event_id', $event_id );
        update_post_meta( $order_id, '_trackify_capi_tracked_at', current_time( 'mysql' ) );
        
        $this->debug_log( 'Purchase tracked', array(
            'order_id' => $order_id,
            'order_total' => $order->get_total(),
            'num_items' => $order->get_item_count(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * RemoveFromCart - Sepetten çıkarma
     * 
     * @param string $cart_item_key Cart item key
     * @param WC_Cart $cart Cart object
     */
    public function track_remove_from_cart( $cart_item_key, $cart ) {
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'RemoveFromCart' ) ) {
            return;
        }
        
        $cart_item = $cart->removed_cart_contents[ $cart_item_key ];
        
        if ( ! $cart_item ) {
            return;
        }
        
        $product = wc_get_product( $cart_item['product_id'] );
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = $this->generate_event_id( 'rfc', $cart_item['product_id'] . '_' . time() );
        
        // Custom data
        $custom_data = array(
            'content_ids' => array( (string) $cart_item['product_id'] ),
            'content_type' => 'product',
            'content_name' => $product->get_name(),
            'content_category' => $this->get_product_categories( $product ),
            'value' => (float) $product->get_price() * $cart_item['quantity'],
            'currency' => get_woocommerce_currency(),
            'num_items' => $cart_item['quantity'],
        );
        
        // User data
        $user_data = $this->build_user_data();
        
        // Track custom event (RemoveFromCart Meta'da standart değil ama custom olarak gönderebiliriz)
        $this->track_event( 'RemoveFromCart', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'RemoveFromCart tracked', array(
            'product_id' => $cart_item['product_id'],
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Search - Ürün araması
     * 
     * @param WP_Query $query Query object
     */
    public function track_product_search( $query ) {
        // Event etkin değilse çık
        if ( ! $this->is_event_enabled( 'Search' ) ) {
            return;
        }
        
        // Sadece ana query ve WooCommerce ürün araması
        if ( ! $query->is_main_query() || ! $query->is_search() || ! isset( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] !== 'product' ) {
            return;
        }
        
        $search_string = get_search_query();
        
        if ( empty( $search_string ) ) {
            return;
        }
        
        // Session kontrolü - aynı aramayı birden fazla track etme
        $session_key = 'search_' . md5( $search_string );
        if ( isset( $this->tracked_sessions[ $session_key ] ) ) {
            return;
        }
        
        $this->tracked_sessions[ $session_key ] = true;
        
        $event_id = $this->generate_event_id( 'search', md5( $search_string ) );
        
        // Custom data
        $custom_data = array(
            'search_string' => $search_string,
            'content_type' => 'product',
        );
        
        // User data
        $user_data = $this->build_user_data();
        
        // Track event
        $this->track_event( 'Search', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'Search tracked', array(
            'search_string' => $search_string,
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * StartTrial - Abonelik deneme başlangıcı (WooCommerce Subscriptions)
     * 
     * @param WC_Subscription $subscription Subscription object
     */
    public function track_start_trial( $subscription ) {
        if ( ! $subscription->has_status( 'active' ) ) {
            return;
        }
        
        // Deneme süresi var mı?
        if ( ! $subscription->get_trial_end_date() ) {
            return;
        }
        
        $event_id = $this->generate_event_id( 'trial', $subscription->get_id() );
        
        // Custom data
        $custom_data = array(
            'content_type' => 'subscription',
            'value' => (float) $subscription->get_total(),
            'currency' => $subscription->get_currency(),
            'predicted_ltv' => $this->calculate_subscription_ltv( $subscription ),
            'trial_period' => $subscription->get_trial_period(),
        );
        
        // User data
        $order = $subscription->get_parent();
        $user_data = $order ? $this->build_user_data_from_order( $order ) : $this->build_user_data();
        
        // Track event
        $this->track_event( 'StartTrial', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'StartTrial tracked', array(
            'subscription_id' => $subscription->get_id(),
            'event_id' => $event_id,
        ) );
    }
    
    /**
     * Subscribe - Abonelik yenileme (WooCommerce Subscriptions)
     * 
     * @param WC_Subscription $subscription Subscription object
     */
    public function track_subscribe( $subscription ) {
        $event_id = $this->generate_event_id( 'subscribe', $subscription->get_id() . '_' . time() );
        
        // Custom data
        $custom_data = array(
            'content_type' => 'subscription',
            'value' => (float) $subscription->get_total(),
            'currency' => $subscription->get_currency(),
            'predicted_ltv' => $this->calculate_subscription_ltv( $subscription ),
        );
        
        // User data
        $order = $subscription->get_parent();
        $user_data = $order ? $this->build_user_data_from_order( $order ) : $this->build_user_data();
        
        // Track event
        $this->track_event( 'Subscribe', $custom_data, $user_data, $event_id );
        
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
        if ( ! $this->settings->get( 'pixel.enabled' ) ) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            'use strict';
            
            // AddToCart button tracking
            $(document).on('click', '.single_add_to_cart_button:not(.disabled)', function(e) {
                if (typeof fbq === 'undefined') return;
                
                var $button = $(this);
                var $form = $button.closest('form.cart');
                var productId = $form.find('input[name="product_id"]').val() || 
                               $form.find('input[name="add-to-cart"]').val() ||
                               $button.val();
                               
                if (!productId) return;
                
                var quantity = $form.find('input[name="quantity"]').val() || 1;
                var productName = $('.product_title').text() || '';
                var productPrice = $('.woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, '');
                
                // Track AddToCart
                fbq('track', 'AddToCart', {
                    content_ids: [productId],
                    content_type: 'product',
                    content_name: productName,
                    value: parseFloat(productPrice) * parseInt(quantity),
                    currency: '<?php echo esc_js( get_woocommerce_currency() ); ?>',
                    num_items: parseInt(quantity)
                });
                
                console.log('[Trackify CAPI] AddToCart tracked (client-side):', productId);
            });
            
            // AJAX Add to Cart tracking
            $(document.body).on('added_to_cart', function(e, fragments, cart_hash, $button) {
                if (typeof fbq === 'undefined') return;
                
                var productId = $button.data('product_id');
                var productName = $button.data('product_name') || '';
                var quantity = $button.data('quantity') || 1;
                
                if (!productId) return;
                
                fbq('track', 'AddToCart', {
                    content_ids: [productId.toString()],
                    content_type: 'product',
                    content_name: productName,
                    num_items: quantity
                });
                
                console.log('[Trackify CAPI] AddToCart (AJAX) tracked:', productId);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Helper: Ürün kategorilerini al
     * 
     * @param WC_Product $product Product object
     * @return array Category names
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
     * Helper: Ürün markasını al
     * 
     * @param WC_Product $product Product object
     * @return string|null Brand name
     */
    private function get_product_brand( $product ) {
        // Yoast WooCommerce SEO
        if ( class_exists( 'Yoast_WooCommerce_Brand' ) ) {
            $brands = get_the_terms( $product->get_id(), 'yoast_wc_brand' );
            if ( $brands && ! is_wp_error( $brands ) ) {
                return reset( $brands )->name;
            }
        }
        
        // Perfect Brands for WooCommerce
        if ( taxonomy_exists( 'pwb-brand' ) ) {
            $brands = get_the_terms( $product->get_id(), 'pwb-brand' );
            if ( $brands && ! is_wp_error( $brands ) ) {
                return reset( $brands )->name;
            }
        }
        
        // WooCommerce Brands
        if ( taxonomy_exists( 'product_brand' ) ) {
            $brands = get_the_terms( $product->get_id(), 'product_brand' );
            if ( $brands && ! is_wp_error( $brands ) ) {
                return reset( $brands )->name;
            }
        }
        
        return null;
    }
    
    /**
     * Helper: Variation attributes'ları al
     * 
     * @param array $variation Variation data
     * @return string Formatted attributes
     */
    private function get_variation_attributes( $variation ) {
        if ( empty( $variation ) ) {
            return '';
        }
        
        $attributes = array();
        foreach ( $variation as $key => $value ) {
            if ( strpos( $key, 'attribute_' ) === 0 ) {
                $attributes[] = $value;
            }
        }
        
        return implode( ', ', $attributes );
    }
    
    /**
     * Helper: Subscription LTV hesapla
     * 
     * @param WC_Subscription $subscription Subscription object
     * @return float Predicted LTV
     */
    private function calculate_subscription_ltv( $subscription ) {
        $total = (float) $subscription->get_total();
        $billing_interval = $subscription->get_billing_interval();
        $billing_period = $subscription->get_billing_period();
        
        // Yıllık değere çevir
        $periods_per_year = array(
            'day' => 365,
            'week' => 52,
            'month' => 12,
            'year' => 1,
        );
        
        $periods = isset( $periods_per_year[ $billing_period ] ) ? $periods_per_year[ $billing_period ] : 1;
        $annual_value = ( $total * $periods ) / $billing_interval;
        
        // 3 yıllık tahmini LTV (değiştirilebilir)
        $ltv_years = apply_filters( 'trackify_capi_subscription_ltv_years', 3 );
        
        return $annual_value * $ltv_years;
    }
    
    /**
     * Helper: Order'dan user data oluştur
     * 
     * @param WC_Order $order Order object
     * @return array User data
     */
    private function build_user_data_from_order( $order ) {
        $user_data = array();
        
        // Email
        $email = $order->get_billing_email();
        if ( $email ) {
            $user_data['em'] = $this->hash_data( $email );
        }
        
        // Phone
        $phone = $order->get_billing_phone();
        if ( $phone ) {
            $user_data['ph'] = $this->hash_data( $this->normalize_phone( $phone ) );
        }
        
        // Name
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        
        if ( $first_name ) {
            $user_data['fn'] = $this->hash_data( strtolower( $first_name ) );
        }
        
        if ( $last_name ) {
            $user_data['ln'] = $this->hash_data( strtolower( $last_name ) );
        }
        
        // Location
        $city = $order->get_billing_city();
        $state = $order->get_billing_state();
        $postcode = $order->get_billing_postcode();
        $country = $order->get_billing_country();
        
        if ( $city ) {
            $user_data['ct'] = $this->hash_data( strtolower( $city ) );
        }
        
        if ( $state ) {
            $user_data['st'] = $this->hash_data( strtolower( $state ) );
        }
        
        if ( $postcode ) {
            $user_data['zp'] = $this->hash_data( preg_replace( '/\s+/', '', strtolower( $postcode ) ) );
        }
        
        if ( $country ) {
            $user_data['country'] = $this->hash_data( strtolower( $country ) );
        }
        
        // External ID (customer ID)
        $customer_id = $order->get_customer_id();
        if ( $customer_id ) {
            $user_data['external_id'] = $this->hash_data( (string) $customer_id );
        }
        
        // FBP, FBC cookies
        $this->add_cookies_to_user_data( $user_data );
        
        // Client IP & User Agent
        $user_data['client_ip_address'] = $this->get_client_ip();
        $user_data['client_user_agent'] = $this->get_user_agent();
        
        return $user_data;
    }
}