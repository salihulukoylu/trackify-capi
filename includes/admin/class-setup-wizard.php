<?php
/**
 * Setup Wizard
 * 
 * İlk kurulum sihirbazı - 4 adımlı kolay kurulum
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Setup_Wizard {
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Current step
     * 
     * @var string
     */
    private $step = '';
    
    /**
     * Steps
     * 
     * @var array
     */
    private $steps = array();
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        
        // Steps tanımla
        $this->steps = array(
            'welcome' => array(
                'name'    => __( 'Hoş Geldiniz', 'trackify-capi' ),
                'view'    => array( $this, 'setup_welcome' ),
                'handler' => '',
            ),
            'site_type' => array(
                'name'    => __( 'Site Tipi', 'trackify-capi' ),
                'view'    => array( $this, 'setup_site_type' ),
                'handler' => array( $this, 'setup_site_type_save' ),
            ),
            'pixel' => array(
                'name'    => __( 'Pixel Ayarları', 'trackify-capi' ),
                'view'    => array( $this, 'setup_pixel' ),
                'handler' => array( $this, 'setup_pixel_save' ),
            ),
            'events' => array(
                'name'    => __( 'Event Ayarları', 'trackify-capi' ),
                'view'    => array( $this, 'setup_events' ),
                'handler' => array( $this, 'setup_events_save' ),
            ),
            'complete' => array(
                'name'    => __( 'Tamamlandı', 'trackify-capi' ),
                'view'    => array( $this, 'setup_complete' ),
                'handler' => '',
            ),
        );
        
        // Hook'lar
        add_action( 'admin_menu', array( $this, 'admin_menus' ) );
        add_action( 'admin_init', array( $this, 'setup_wizard' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    /**
     * Admin menüye ekle (gizli sayfa)
     * 
     * @since 2.0.0
     */
    public function admin_menus() {
        add_dashboard_page(
            __( 'Trackify CAPI Kurulum', 'trackify-capi' ),
            __( 'Trackify CAPI Kurulum', 'trackify-capi' ),
            'manage_options',
            'trackify-capi-setup',
            ''
        );
    }
    
    /**
     * Wizard'ı başlat
     * 
     * @since 2.0.0
     */
    public function setup_wizard() {
        if ( empty( $_GET['page'] ) || 'trackify-capi-setup' !== $_GET['page'] ) {
            return;
        }
        
        // Current step
        $this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : 'welcome';
        
        // Step geçerli mi?
        if ( ! array_key_exists( $this->step, $this->steps ) ) {
            $this->step = 'welcome';
        }
        
        // Form submit
        if ( ! empty( $_POST['save_step'] ) && ! empty( $this->steps[ $this->step ]['handler'] ) ) {
            check_admin_referer( 'trackify-capi-setup' );
            call_user_func( $this->steps[ $this->step ]['handler'] );
        }
        
        ob_start();
        $this->setup_wizard_header();
        $this->setup_wizard_steps();
        $this->setup_wizard_content();
        $this->setup_wizard_footer();
        exit;
    }
    
    /**
     * Scripts yükle
     * 
     * @param string $hook
     */
    public function enqueue_scripts( $hook ) {
        if ( 'dashboard_page_trackify-capi-setup' !== $hook ) {
            return;
        }
        
        wp_enqueue_style(
            'trackify-capi-setup',
            TRACKIFY_CAPI_ASSETS . 'css/setup-wizard.css',
            array(),
            TRACKIFY_CAPI_VERSION
        );
        
        wp_enqueue_script(
            'trackify-capi-setup',
            TRACKIFY_CAPI_ASSETS . 'js/setup-wizard.js',
            array( 'jquery' ),
            TRACKIFY_CAPI_VERSION,
            true
        );
    }
    
    /**
     * Wizard header
     */
    private function setup_wizard_header() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php esc_html_e( 'Trackify CAPI Kurulum Sihirbazı', 'trackify-capi' ); ?></title>
            <?php do_action( 'admin_print_styles' ); ?>
            <?php do_action( 'admin_print_scripts' ); ?>
            <?php do_action( 'admin_head' ); ?>
        </head>
        <body class="trackify-setup wp-core-ui">
            <div class="trackify-setup-wrapper">
                <div class="trackify-setup-container">
                    <div class="trackify-setup-logo">
                        <h1>
                            <img src="<?php echo esc_url( TRACKIFY_CAPI_ASSETS . 'images/logo.png' ); ?>" alt="Trackify CAPI" />
                            Trackify CAPI
                        </h1>
                    </div>
        <?php
    }
    
    /**
     * Wizard steps progress bar
     */
    private function setup_wizard_steps() {
        ?>
        <ol class="trackify-setup-steps">
            <?php
            $step_number = 1;
            foreach ( $this->steps as $step_key => $step ) :
                $is_current = ( $step_key === $this->step );
                $is_completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true );
                ?>
                <li class="<?php echo $is_current ? 'active' : ''; ?> <?php echo $is_completed ? 'done' : ''; ?>">
                    <span class="step-number"><?php echo esc_html( $step_number ); ?></span>
                    <span class="step-name"><?php echo esc_html( $step['name'] ); ?></span>
                </li>
                <?php
                $step_number++;
            endforeach;
            ?>
        </ol>
        <?php
    }
    
    /**
     * Wizard content
     */
    private function setup_wizard_content() {
        echo '<div class="trackify-setup-content">';
        call_user_func( $this->steps[ $this->step ]['view'] );
        echo '</div>';
    }
    
    /**
     * Wizard footer
     */
    private function setup_wizard_footer() {
        ?>
                </div>
            </div>
            <?php do_action( 'admin_footer' ); ?>
            <?php do_action( 'admin_print_footer_scripts' ); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * Step: Welcome
     */
    private function setup_welcome() {
        ?>
        <div class="trackify-setup-step">
            <h2><?php esc_html_e( 'Trackify CAPI\'ye Hoş Geldiniz! 🎉', 'trackify-capi' ); ?></h2>
            
            <p class="lead">
                <?php esc_html_e( 'Meta Pixel ve Conversions API entegrasyonunuzu birkaç adımda tamamlayalım.', 'trackify-capi' ); ?>
            </p>
            
            <div class="trackify-features">
                <div class="feature">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3><?php esc_html_e( 'Kolay Kurulum', 'trackify-capi' ); ?></h3>
                    <p><?php esc_html_e( '5 dakikada kurulumu tamamlayın', 'trackify-capi' ); ?></p>
                </div>
                
                <div class="feature">
                    <span class="dashicons dashicons-chart-line"></span>
                    <h3><?php esc_html_e( 'Yüksek EMQ', 'trackify-capi' ); ?></h3>
                    <p><?php esc_html_e( 'Server-side tracking ile mükemmel event quality', 'trackify-capi' ); ?></p>
                </div>
                
                <div class="feature">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <h3><?php esc_html_e( 'Çoklu Entegrasyon', 'trackify-capi' ); ?></h3>
                    <p><?php esc_html_e( 'WooCommerce, formlar, LMS ve daha fazlası', 'trackify-capi' ); ?></p>
                </div>
            </div>
            
            <div class="trackify-setup-actions">
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Kuruluma Başla', 'trackify-capi' ); ?> →
                </a>
                
                <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large">
                    <?php esc_html_e( 'Şimdi Değil', 'trackify-capi' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Step: Site Type
     */
    private function setup_site_type() {
        ?>
        <form method="post" class="trackify-setup-step">
            <?php wp_nonce_field( 'trackify-capi-setup' ); ?>
            
            <h2><?php esc_html_e( 'Sitenizin Türü Nedir?', 'trackify-capi' ); ?></h2>
            
            <p><?php esc_html_e( 'Size özel ayarları hazırlayabilmemiz için sitenizin türünü seçin:', 'trackify-capi' ); ?></p>
            
            <div class="trackify-site-types">
                <label class="site-type-card">
                    <input type="radio" name="site_type" value="ecommerce" checked />
                    <div class="card-content">
                        <span class="dashicons dashicons-cart"></span>
                        <h3><?php esc_html_e( 'E-Ticaret', 'trackify-capi' ); ?></h3>
                        <p><?php esc_html_e( 'Online mağaza, ürün satışı', 'trackify-capi' ); ?></p>
                    </div>
                </label>
                
                <label class="site-type-card">
                    <input type="radio" name="site_type" value="blog" />
                    <div class="card-content">
                        <span class="dashicons dashicons-admin-post"></span>
                        <h3><?php esc_html_e( 'Blog / Haber', 'trackify-capi' ); ?></h3>
                        <p><?php esc_html_e( 'İçerik sitesi, blog', 'trackify-capi' ); ?></p>
                    </div>
                </label>
                
                <label class="site-type-card">
                    <input type="radio" name="site_type" value="corporate" />
                    <div class="card-content">
                        <span class="dashicons dashicons-building"></span>
                        <h3><?php esc_html_e( 'Kurumsal', 'trackify-capi' ); ?></h3>
                        <p><?php esc_html_e( 'Şirket sitesi, lead generation', 'trackify-capi' ); ?></p>
                    </div>
                </label>
                
                <label class="site-type-card">
                    <input type="radio" name="site_type" value="education" />
                    <div class="card-content">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <h3><?php esc_html_e( 'Eğitim / Kurs', 'trackify-capi' ); ?></h3>
                        <p><?php esc_html_e( 'Online kurslar, LMS', 'trackify-capi' ); ?></p>
                    </div>
                </label>
                
                <label class="site-type-card">
                    <input type="radio" name="site_type" value="membership" />
                    <div class="card-content">
                        <span class="dashicons dashicons-groups"></span>
                        <h3><?php esc_html_e( 'Üyelik Sitesi', 'trackify-capi' ); ?></h3>
                        <p><?php esc_html_e( 'Abonelik, üyelik sistemi', 'trackify-capi' ); ?></p>
                    </div>
                </label>
                
                <label class="site-type-card">
                    <input type="radio" name="site_type" value="other" />
                    <div class="card-content">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <h3><?php esc_html_e( 'Diğer', 'trackify-capi' ); ?></h3>
                        <p><?php esc_html_e( 'Özel yapılandırma', 'trackify-capi' ); ?></p>
                    </div>
                </label>
            </div>
            
            <div class="trackify-setup-actions">
                <button type="submit" name="save_step" class="button button-primary button-large">
                    <?php esc_html_e( 'Devam Et', 'trackify-capi' ); ?> →
                </button>
                
                <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large">
                    <?php esc_html_e( 'Şimdi Değil', 'trackify-capi' ); ?>
                </a>
            </div>
        </form>
        <?php
    }
    
    /**
     * Save: Site Type
     */
    private function setup_site_type_save() {
        $site_type = ! empty( $_POST['site_type'] ) ? sanitize_key( $_POST['site_type'] ) : 'other';
        
        // Site tipini kaydet
        update_option( 'trackify_capi_site_type', $site_type );
        
        // Site tipine göre varsayılan ayarları uygula
        $this->apply_site_type_defaults( $site_type );
        
        wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }
    
    /**
     * Site tipine göre varsayılan ayarlar
     * 
     * @param string $site_type
     */
    private function apply_site_type_defaults( $site_type ) {
        $defaults = array();
        
        switch ( $site_type ) {
            case 'ecommerce':
                $defaults['integrations']['woocommerce']['enabled'] = true;
                $defaults['events']['ViewContent']['enabled'] = true;
                $defaults['events']['AddToCart']['enabled'] = true;
                $defaults['events']['InitiateCheckout']['enabled'] = true;
                $defaults['events']['Purchase']['enabled'] = true;
                break;
                
            case 'blog':
                $defaults['events']['PageView']['enabled'] = true;
                $defaults['events']['ViewContent']['enabled'] = true;
                break;
                
            case 'corporate':
                $defaults['integrations']['forms']['enabled'] = true;
                $defaults['events']['Lead']['enabled'] = true;
                $defaults['events']['CompleteRegistration']['enabled'] = false;
                break;
                
            case 'education':
                $defaults['events']['CompleteRegistration']['enabled'] = true;
                $defaults['events']['Purchase']['enabled'] = true;
                break;
                
            case 'membership':
                $defaults['events']['CompleteRegistration']['enabled'] = true;
                $defaults['events']['Subscribe']['enabled'] = true;
                break;
        }
        
        if ( ! empty( $defaults ) ) {
            $this->settings->update( $defaults );
        }
    }
    
    /**
     * Step: Pixel Settings
     */
    private function setup_pixel() {
        $pixel_id = $this->settings->get( 'pixels.0.pixel_id', '' );
        $access_token = $this->settings->get( 'pixels.0.access_token', '' );
        $test_event_code = $this->settings->get( 'pixels.0.test_event_code', '' );
        ?>
        <form method="post" class="trackify-setup-step">
            <?php wp_nonce_field( 'trackify-capi-setup' ); ?>
            
            <h2><?php esc_html_e( 'Meta Pixel Bilgileriniz', 'trackify-capi' ); ?></h2>
            
            <p><?php esc_html_e( 'Meta Events Manager\'dan Pixel ID ve Access Token bilgilerinizi girin:', 'trackify-capi' ); ?></p>
            
            <div class="trackify-form-group">
                <label for="pixel_id">
                    <?php esc_html_e( 'Meta Pixel ID', 'trackify-capi' ); ?>
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="pixel_id" 
                    name="pixel_id" 
                    value="<?php echo esc_attr( $pixel_id ); ?>" 
                    class="regular-text"
                    placeholder="123456789012345"
                    required
                />
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: Meta Events Manager URL */
                        __( 'Pixel ID\'nizi <a href="%s" target="_blank">Meta Events Manager</a> > Data Sources > Pixels bölümünden bulabilirsiniz.', 'trackify-capi' ),
                        'https://business.facebook.com/events_manager2'
                    );
                    ?>
                </p>
            </div>
            
            <div class="trackify-form-group">
                <label for="access_token">
                    <?php esc_html_e( 'Conversions API Access Token', 'trackify-capi' ); ?>
                    <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    id="access_token" 
                    name="access_token" 
                    value="<?php echo esc_attr( $access_token ); ?>" 
                    class="regular-text"
                    placeholder="EAAxxxxxxxxxx"
                    required
                />
                <p class="description">
                    <?php esc_html_e( 'Events Manager > Settings > Conversions API > Generate Access Token', 'trackify-capi' ); ?>
                </p>
            </div>
            
            <div class="trackify-form-group">
                <label for="test_event_code">
                    <?php esc_html_e( 'Test Event Code', 'trackify-capi' ); ?>
                    <span class="optional">(<?php esc_html_e( 'Opsiyonel', 'trackify-capi' ); ?>)</span>
                </label>
                <input 
                    type="text" 
                    id="test_event_code" 
                    name="test_event_code" 
                    value="<?php echo esc_attr( $test_event_code ); ?>" 
                    class="regular-text"
                    placeholder="TEST12345"
                />
                <p class="description">
                    <?php esc_html_e( 'Test eventleri göndermek için Meta\'dan test kodu alın. Kurulum testleri için yararlıdır.', 'trackify-capi' ); ?>
                </p>
            </div>
            
            <div class="trackify-info-box">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php esc_html_e( 'Bilgi:', 'trackify-capi' ); ?></strong>
                    <?php esc_html_e( 'Access Token hassas bir bilgidir. Güvenli bir şekilde saklanır ve hiçbir zaman üçüncü şahıslarla paylaşılmaz.', 'trackify-capi' ); ?>
                </div>
            </div>
            
            <div class="trackify-setup-actions">
                <button type="submit" name="save_step" class="button button-primary button-large">
                    <?php esc_html_e( 'Devam Et', 'trackify-capi' ); ?> →
                </button>
                
                <a href="<?php echo esc_url( $this->get_previous_step_link() ); ?>" class="button button-large">
                    ← <?php esc_html_e( 'Geri', 'trackify-capi' ); ?>
                </a>
            </div>
        </form>
        <?php
    }
    
    /**
     * Save: Pixel Settings
     */
    private function setup_pixel_save() {
        $pixel_id = ! empty( $_POST['pixel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['pixel_id'] ) ) : '';
        $access_token = ! empty( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';
        $test_event_code = ! empty( $_POST['test_event_code'] ) ? sanitize_text_field( wp_unslash( $_POST['test_event_code'] ) ) : '';
        
        // Validasyon
        if ( empty( $pixel_id ) || empty( $access_token ) ) {
            wp_die( esc_html__( 'Pixel ID ve Access Token gereklidir.', 'trackify-capi' ) );
        }
        
        // Pixel bilgilerini kaydet
        $pixels = array(
            array(
                'name' => __( 'Ana Pixel', 'trackify-capi' ),
                'pixel_id' => $pixel_id,
                'access_token' => $access_token,
                'test_event_code' => $test_event_code,
                'enabled' => true,
            ),
        );
        
        $this->settings->set( 'pixels', $pixels );
        
        wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }
    
    /**
     * Step: Events
     */
    private function setup_events() {
        $events = $this->settings->get( 'events', array() );
        ?>
        <form method="post" class="trackify-setup-step">
            <?php wp_nonce_field( 'trackify-capi-setup' ); ?>
            
            <h2><?php esc_html_e( 'Hangi Eventleri Takip Etmek İstiyorsunuz?', 'trackify-capi' ); ?></h2>
            
            <p><?php esc_html_e( 'Takip etmek istediğiniz eventleri seçin. Daha sonra ayarlardan değiştirebilirsiniz.', 'trackify-capi' ); ?></p>
            
            <div class="trackify-events-list">
                <?php
                $available_events = array(
                    'PageView' => array(
                        'label' => __( 'PageView', 'trackify-capi' ),
                        'description' => __( 'Her sayfa yüklendiğinde', 'trackify-capi' ),
                        'icon' => 'dashicons-visibility',
                    ),
                    'ViewContent' => array(
                        'label' => __( 'ViewContent', 'trackify-capi' ),
                        'description' => __( 'Ürün/içerik görüntülendiğinde', 'trackify-capi' ),
                        'icon' => 'dashicons-welcome-view-site',
                    ),
                    'AddToCart' => array(
                        'label' => __( 'AddToCart', 'trackify-capi' ),
                        'description' => __( 'Sepete ürün eklendiğinde', 'trackify-capi' ),
                        'icon' => 'dashicons-cart',
                    ),
                    'InitiateCheckout' => array(
                        'label' => __( 'InitiateCheckout', 'trackify-capi' ),
                        'description' => __( 'Ödeme sayfasına gidildiğinde', 'trackify-capi' ),
                        'icon' => 'dashicons-money-alt',
                    ),
                    'Purchase' => array(
                        'label' => __( 'Purchase', 'trackify-capi' ),
                        'description' => __( 'Sipariş tamamlandığında', 'trackify-capi' ),
                        'icon' => 'dashicons-yes-alt',
                    ),
                    'Lead' => array(
                        'label' => __( 'Lead', 'trackify-capi' ),
                        'description' => __( 'Form gönderildiğinde', 'trackify-capi' ),
                        'icon' => 'dashicons-email-alt',
                    ),
                    'CompleteRegistration' => array(
                        'label' => __( 'CompleteRegistration', 'trackify-capi' ),
                        'description' => __( 'Kullanıcı kaydı tamamlandığında', 'trackify-capi' ),
                        'icon' => 'dashicons-admin-users',
                    ),
                );
                
                foreach ( $available_events as $event_key => $event_info ) :
                    $is_enabled = ! empty( $events[ $event_key ]['enabled'] );
                    ?>
                    <label class="event-item">
                        <input 
                            type="checkbox" 
                            name="events[<?php echo esc_attr( $event_key ); ?>]" 
                            value="1"
                            <?php checked( $is_enabled ); ?>
                        />
                        <div class="event-content">
                            <span class="dashicons <?php echo esc_attr( $event_info['icon'] ); ?>"></span>
                            <div>
                                <strong><?php echo esc_html( $event_info['label'] ); ?></strong>
                                <p><?php echo esc_html( $event_info['description'] ); ?></p>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div class="trackify-setup-actions">
                <button type="submit" name="save_step" class="button button-primary button-large">
                    <?php esc_html_e( 'Kurulumu Tamamla', 'trackify-capi' ); ?> →
                </button>
                
                <a href="<?php echo esc_url( $this->get_previous_step_link() ); ?>" class="button button-large">
                    ← <?php esc_html_e( 'Geri', 'trackify-capi' ); ?>
                </a>
            </div>
        </form>
        <?php
    }
    
    /**
     * Save: Events
     */
    private function setup_events_save() {
        $selected_events = ! empty( $_POST['events'] ) ? (array) $_POST['events'] : array();
        
        // Event ayarlarını güncelle
        $events = $this->settings->get( 'events', array() );
        
        foreach ( $events as $event_name => $event_settings ) {
            $events[ $event_name ]['enabled'] = isset( $selected_events[ $event_name ] );
        }
        
        $this->settings->set( 'events', $events );
        
        // Kurulum tamamlandı
        update_option( 'trackify_capi_setup_completed', true );
        update_option( 'trackify_capi_setup_date', time() );
        
        wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }
    
    /**
     * Step: Complete
     */
    private function setup_complete() {
        ?>
        <div class="trackify-setup-step trackify-setup-complete">
            <div class="success-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            
            <h2><?php esc_html_e( 'Kurulum Tamamlandı! 🎉', 'trackify-capi' ); ?></h2>
            
            <p class="lead">
                <?php esc_html_e( 'Trackify CAPI başarıyla yapılandırıldı ve çalışmaya hazır!', 'trackify-capi' ); ?>
            </p>
            
            <div class="next-steps">
                <h3><?php esc_html_e( 'Şimdi Ne Yapmalısınız?', 'trackify-capi' ); ?></h3>
                
                <ol>
                    <li>
                        <strong><?php esc_html_e( 'Test Siparişi Verin', 'trackify-capi' ); ?></strong>
                        <p><?php esc_html_e( 'WooCommerce\'de test siparişi vererek event\'lerin gönderildiğini doğrulayın.', 'trackify-capi' ); ?></p>
                    </li>
                    
                    <li>
                        <strong><?php esc_html_e( 'Event Logs Kontrol Edin', 'trackify-capi' ); ?></strong>
                        <p><?php esc_html_e( 'Trackify CAPI > Event Logs sayfasından gönderilen event\'leri görüntüleyin.', 'trackify-capi' ); ?></p>
                    </li>
                    
                    <li>
                        <strong><?php esc_html_e( 'Meta Events Manager\'da Doğrulayın', 'trackify-capi' ); ?></strong>
                        <p><?php esc_html_e( 'Meta Events Manager\'da event\'lerin geldiğini ve EMQ skorunuzu kontrol edin.', 'trackify-capi' ); ?></p>
                    </li>
                </ol>
            </div>
            
            <div class="trackify-setup-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi' ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Dashboard\'a Git', 'trackify-capi' ); ?>
                </a>
                
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-logs' ) ); ?>" class="button button-large">
                    <?php esc_html_e( 'Event Logs\'u Görüntüle', 'trackify-capi' ); ?>
                </a>
            </div>
            
            <div class="trackify-help">
                <p>
                    <?php
                    printf(
                        /* translators: %s: documentation URL */
                        __( 'Yardıma mı ihtiyacınız var? <a href="%s" target="_blank">Dokümantasyonu</a> inceleyin.', 'trackify-capi' ),
                        'https://docs.trackify.io'
                    );
                    ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Next step link
     * 
     * @return string
     */
    private function get_next_step_link() {
        $keys = array_keys( $this->steps );
        $current_index = array_search( $this->step, $keys, true );
        
        if ( isset( $keys[ $current_index + 1 ] ) ) {
            return add_query_arg( 'step', $keys[ $current_index + 1 ], admin_url( 'index.php?page=trackify-capi-setup' ) );
        }
        
        return admin_url( 'admin.php?page=trackify-capi' );
    }
    
    /**
     * Previous step link
     * 
     * @return string
     */
    private function get_previous_step_link() {
        $keys = array_keys( $this->steps );
        $current_index = array_search( $this->step, $keys, true );
        
        if ( isset( $keys[ $current_index - 1 ] ) ) {
            return add_query_arg( 'step', $keys[ $current_index - 1 ], admin_url( 'index.php?page=trackify-capi-setup' ) );
        }
        
        return admin_url( 'index.php?page=trackify-capi-setup' );
    }
}