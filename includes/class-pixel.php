<?php
/**
 * Meta Pixel Manager
 * 
 * Meta Pixel kodunu yönetir ve client-side tracking işlemlerini halleder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Pixel {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        
        // Hook'lar
        if ( ! is_admin() ) {
            add_action( 'wp_head', array( $this, 'inject_pixel_base_code' ), 1 );
            add_action( 'wp_footer', array( $this, 'inject_pixel_events' ), 999 );
        }
    }
    
    /**
     * Meta Pixel base kodunu inject et
     * 
     * @since 2.0.0
     */
    public function inject_pixel_base_code() {
        // Plugin kapalıysa çık
        if ( ! $this->settings->is_enabled() ) {
            return;
        }
        
        // Aktif pixel'leri al
        $pixels = $this->settings->get_active_pixels();
        
        if ( empty( $pixels ) ) {
            return;
        }
        
        ?>
        <!-- Meta Pixel Code - Trackify CAPI v<?php echo esc_attr( TRACKIFY_CAPI_VERSION ); ?> -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        
        <?php foreach ( $pixels as $pixel ) : ?>
        fbq('init', '<?php echo esc_js( $pixel['pixel_id'] ); ?>'<?php echo $this->get_advanced_matching_data(); ?>);
        <?php endforeach; ?>
        
        fbq('track', 'PageView');
        </script>
        <noscript>
            <?php foreach ( $pixels as $pixel ) : ?>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id=<?php echo esc_attr( $pixel['pixel_id'] ); ?>&ev=PageView&noscript=1"/>
            <?php endforeach; ?>
        </noscript>
        <!-- End Meta Pixel Code -->
        <?php
    }
    
    /**
     * Advanced matching data hazırla
     * 
     * @return string
     */
    private function get_advanced_matching_data() {
        if ( ! $this->settings->is_advanced_matching_enabled() ) {
            return '';
        }
        
        $data = array();
        
        // Giriş yapmış kullanıcı
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            
            // Email
            if ( $this->settings->get( 'advanced_matching.hash_email' ) && $user->user_email ) {
                $data['em'] = strtolower( trim( $user->user_email ) );
            }
            
            // İsim
            if ( $this->settings->get( 'advanced_matching.hash_name' ) ) {
                if ( $user->first_name ) {
                    $data['fn'] = strtolower( trim( $user->first_name ) );
                }
                if ( $user->last_name ) {
                    $data['ln'] = strtolower( trim( $user->last_name ) );
                }
            }
        }
        
        // WooCommerce billing data
        if ( class_exists( 'WooCommerce' ) && is_user_logged_in() ) {
            $customer = new WC_Customer( get_current_user_id() );
            
            // Telefon
            if ( $this->settings->get( 'advanced_matching.hash_phone' ) && $customer->get_billing_phone() ) {
                $phone = preg_replace( '/[^0-9]/', '', $customer->get_billing_phone() );
                if ( ! empty( $phone ) ) {
                    $data['ph'] = $phone;
                }
            }
            
            // Adres bilgileri
            if ( $this->settings->get( 'advanced_matching.hash_address' ) ) {
                if ( $customer->get_billing_city() ) {
                    $data['ct'] = strtolower( trim( $customer->get_billing_city() ) );
                }
                if ( $customer->get_billing_state() ) {
                    $data['st'] = strtolower( trim( $customer->get_billing_state() ) );
                }
                if ( $customer->get_billing_postcode() ) {
                    $data['zp'] = strtolower( trim( $customer->get_billing_postcode() ) );
                }
                if ( $customer->get_billing_country() ) {
                    $data['country'] = strtolower( trim( $customer->get_billing_country() ) );
                }
            }
        }
        
        if ( empty( $data ) ) {
            return '';
        }
        
        return ', ' . wp_json_encode( $data );
    }
    
    /**
     * Özel pixel eventlerini inject et
     * 
     * @since 2.0.0
     */
    public function inject_pixel_events() {
        // Plugin kapalıysa çık
        if ( ! $this->settings->is_enabled() ) {
            return;
        }
        
        // Sayfa tipine göre özel eventler
        $this->inject_page_specific_events();
    }
    
    /**
     * Sayfa tipine göre özel eventler
     * 
     * @since 2.0.0
     */
    private function inject_page_specific_events() {
        // WooCommerce ürün sayfası
        if ( function_exists( 'is_product' ) && is_product() ) {
            $this->inject_view_content_event();
        }
        
        // WooCommerce checkout
        if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
            $this->inject_initiate_checkout_event();
        }
        
        // WooCommerce order received (thank you page)
        if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
            $this->inject_purchase_event();
        }
        
        // Arama sayfası
        if ( is_search() ) {
            $this->inject_search_event();
        }
    }
    
    /**
     * ViewContent event (ürün sayfası)
     * 
     * @since 2.0.0
     */
    private function inject_view_content_event() {
        if ( ! $this->settings->is_event_enabled( 'ViewContent', 'pixel' ) ) {
            return;
        }
        
        global $product;
        
        if ( ! $product ) {
            return;
        }
        
        $event_id = 'view_' . $product->get_id() . '_' . time();
        
        ?>
        <script>
        if (typeof fbq !== 'undefined') {
            fbq('track', 'ViewContent', {
                content_ids: ['<?php echo esc_js( $product->get_id() ); ?>'],
                content_type: 'product',
                content_name: '<?php echo esc_js( $product->get_name() ); ?>',
                value: <?php echo esc_js( $product->get_price() ); ?>,
                currency: '<?php echo esc_js( get_woocommerce_currency() ); ?>'
            }, {
                eventID: '<?php echo esc_js( $event_id ); ?>'
            });
            
            console.log('[Trackify CAPI] ViewContent tracked:', '<?php echo esc_js( $event_id ); ?>');
        }
        </script>
        <?php
    }
    
    /**
     * InitiateCheckout event
     * 
     * @since 2.0.0
     */
    private function inject_initiate_checkout_event() {
        if ( ! $this->settings->is_event_enabled( 'InitiateCheckout', 'pixel' ) ) {
            return;
        }
        
        if ( ! WC()->cart ) {
            return;
        }
        
        $cart = WC()->cart;
        $content_ids = array();
        
        foreach ( $cart->get_cart() as $item ) {
            $content_ids[] = $item['product_id'];
        }
        
        $event_id = 'ic_' . md5( implode( '_', $content_ids ) . time() );
        
        ?>
        <script>
        if (typeof fbq !== 'undefined') {
            fbq('track', 'InitiateCheckout', {
                content_ids: <?php echo wp_json_encode( $content_ids ); ?>,
                content_type: 'product',
                value: <?php echo esc_js( $cart->get_total( 'raw' ) ); ?>,
                currency: '<?php echo esc_js( get_woocommerce_currency() ); ?>',
                num_items: <?php echo esc_js( $cart->get_cart_contents_count() ); ?>
            }, {
                eventID: '<?php echo esc_js( $event_id ); ?>'
            });
            
            console.log('[Trackify CAPI] InitiateCheckout tracked:', '<?php echo esc_js( $event_id ); ?>');
        }
        </script>
        <?php
    }
    
    /**
     * Purchase event (thank you page)
     * 
     * @since 2.0.0
     */
    private function inject_purchase_event() {
        if ( ! $this->settings->is_event_enabled( 'Purchase', 'pixel' ) ) {
            return;
        }
        
        global $wp;
        
        if ( empty( $wp->query_vars['order-received'] ) ) {
            return;
        }
        
        $order_id = absint( $wp->query_vars['order-received'] );
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        // Daha önce track edildiyse tekrar etme
        if ( get_post_meta( $order_id, '_trackify_pixel_tracked', true ) ) {
            return;
        }
        
        $content_ids = array();
        foreach ( $order->get_items() as $item ) {
            $content_ids[] = $item->get_product_id();
        }
        
        $event_id = 'purchase_' . $order_id;
        
        ?>
        <script>
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Purchase', {
                content_ids: <?php echo wp_json_encode( $content_ids ); ?>,
                content_type: 'product',
                value: <?php echo esc_js( $order->get_total() ); ?>,
                currency: '<?php echo esc_js( $order->get_currency() ); ?>',
                num_items: <?php echo esc_js( $order->get_item_count() ); ?>
            }, {
                eventID: '<?php echo esc_js( $event_id ); ?>'
            });
            
            console.log('[Trackify CAPI] Purchase tracked:', '<?php echo esc_js( $event_id ); ?>');
        }
        </script>
        <?php
        
        // İşaretle
        update_post_meta( $order_id, '_trackify_pixel_tracked', 'yes' );
    }
    
    /**
     * Search event
     * 
     * @since 2.0.0
     */
    private function inject_search_event() {
        $search_query = get_search_query();
        
        if ( empty( $search_query ) ) {
            return;
        }
        
        $event_id = 'search_' . md5( $search_query . time() );
        
        ?>
        <script>
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Search', {
                search_string: '<?php echo esc_js( $search_query ); ?>'
            }, {
                eventID: '<?php echo esc_js( $event_id ); ?>'
            });
            
            console.log('[Trackify CAPI] Search tracked:', '<?php echo esc_js( $event_id ); ?>');
        }
        </script>
        <?php
    }
    
    /**
     * Custom event track et (programatik kullanım)
     * 
     * @param string $event_name
     * @param array $data
     * @param string $event_id
     * @since 2.0.0
     */
    public function track_custom_event( $event_name, $data = array(), $event_id = null ) {
        if ( ! $event_id ) {
            $event_id = sanitize_key( $event_name ) . '_' . time();
        }
        
        ?>
        <script>
        if (typeof fbq !== 'undefined') {
            fbq('trackCustom', '<?php echo esc_js( $event_name ); ?>', <?php echo wp_json_encode( $data ); ?>, {
                eventID: '<?php echo esc_js( $event_id ); ?>'
            });
            
            console.log('[Trackify CAPI] Custom event tracked:', '<?php echo esc_js( $event_name ); ?>', '<?php echo esc_js( $event_