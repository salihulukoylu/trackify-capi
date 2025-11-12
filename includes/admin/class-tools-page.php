<?php
/**
 * Tools Page
 * 
 * Araçlar ve yardımcı özellikler
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Tools_Page {
    
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
     * Constructor
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        $this->logger = trackify_capi()->get_component( 'logger' );
    }
    
    /**
     * Render page
     */
    public function render() {
        // Handle actions
        if ( isset( $_GET['action'] ) && check_admin_referer( 'trackify_tools' ) ) {
            $this->handle_action( sanitize_key( $_GET['action'] ) );
        }
        
        ?>
        <div class="wrap trackify-capi-admin">
            <div class="trackify-capi-header">
                <h1><?php esc_html_e( 'Araçlar', 'trackify-capi' ); ?></h1>
                <p><?php esc_html_e( 'Test, bakım ve yardımcı araçlar', 'trackify-capi' ); ?></p>
            </div>
            
            <?php settings_errors(); ?>
            
            <div class="trackify-settings-grid">
                <!-- Sol Kolon: Test & Debug -->
                <div>
                    <!-- Test Event -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-flag"></span>
                            <?php esc_html_e( 'Test Event Gönder', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'Meta\'ya test event\'i göndererek entegrasyonunuzu test edin.', 'trackify-capi' ); ?></p>
                        
                        <button type="button" class="button button-primary button-large trackify-send-test-event">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e( 'Test Event Gönder', 'trackify-capi' ); ?>
                        </button>
                        
                        <div id="test-event-result" style="margin-top: 15px;"></div>
                    </div>
                    
                    <!-- Clear Logs -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e( 'Logları Temizle', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'Tüm event loglarını veritabanından ve dosyalardan kalıcı olarak silin.', 'trackify-capi' ); ?></p>
                        
                        <button type="button" class="button button-large trackify-clear-logs" onclick="return confirm('<?php esc_attr_e( 'Tüm loglar silinecek. Emin misiniz?', 'trackify-capi' ); ?>');">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e( 'Tüm Logları Temizle', 'trackify-capi' ); ?>
                        </button>
                    </div>
                    
                    <!-- Export/Import Settings -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-database-export"></span>
                            <?php esc_html_e( 'Ayarları Yedekle / Geri Yükle', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'Plugin ayarlarınızı JSON dosyası olarak dışa aktarın veya içe aktarın.', 'trackify-capi' ); ?></p>
                        
                        <p>
                            <button type="button" class="button button-large trackify-export-settings">
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e( 'Ayarları Dışa Aktar', 'trackify-capi' ); ?>
                            </button>
                            
                            <button type="button" class="button button-large" onclick="document.getElementById('trackify-import-file').click();">
                                <span class="dashicons dashicons-upload"></span>
                                <?php esc_html_e( 'Ayarları İçe Aktar', 'trackify-capi' ); ?>
                            </button>
                            
                            <input type="file" id="trackify-import-file" accept=".json" style="display: none;" />
                        </p>
                    </div>
                    
                    <!-- System Info -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-info"></span>
                            <?php esc_html_e( 'Sistem Bilgileri', 'trackify-capi' ); ?>
                        </h2>
                        
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Plugin Versiyonu', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( TRACKIFY_CAPI_VERSION ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'WordPress Versiyonu', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'PHP Versiyonu', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( phpversion() ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'WooCommerce', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WC_VERSION ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Contact Form 7', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( defined( 'WPCF7_VERSION' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WPCF7_VERSION ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'WPForms', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( defined( 'WPFORMS_VERSION' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WPFORMS_VERSION ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Gravity Forms', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( class_exists( 'GFForms' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( GFForms::$version ?? 'N/A' ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Aktif Pixel Sayısı', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo count( $this->settings->get_active_pixels() ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Toplam Log Sayısı', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( number_format_i18n( $this->get_total_log_count() ) ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p style="margin-top: 15px;">
                            <button type="button" class="button" onclick="var text = document.querySelector('.trackify-settings-section table').innerText; navigator.clipboard.writeText(text); alert('<?php esc_attr_e( 'Sistem bilgileri kopyalandı!', 'trackify-capi' ); ?>');">
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php esc_html_e( 'Sistem Bilgilerini Kopyala', 'trackify-capi' ); ?>
                            </button>
                        </p>
                    </div>
                </div>
                
                <!-- Sağ Kolon: Advanced Tools -->
                <div>
                    <!-- Database Maintenance -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-database"></span>
                            <?php esc_html_e( 'Veritabanı Bakımı', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'Veritabanı optimizasyonu ve temizlik işlemleri.', 'trackify-capi' ); ?></p>
                        
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Eski Logları Temizle', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=trackify-capi-tools&action=cleanup_old_logs' ), 'trackify_tools' ) ); ?>" class="button">
                                            <?php esc_html_e( 'Temizle (30+ gün)', 'trackify-capi' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Tabloları Optimize Et', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=trackify-capi-tools&action=optimize_tables' ), 'trackify_tools' ) ); ?>" class="button">
                                            <?php esc_html_e( 'Optimize Et', 'trackify-capi' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- REST API Info -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-rest-api"></span>
                            <?php esc_html_e( 'REST API Bilgileri', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'REST API endpoint\'lerini test edin.', 'trackify-capi' ); ?></p>
                        
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Endpoint', 'trackify-capi' ); ?></th>
                                    <th><?php esc_html_e( 'URL', 'trackify-capi' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Health Check', 'trackify-capi' ); ?></strong></td>
                                    <td><code><?php echo esc_html( rest_url( 'trackify-capi/v1/health' ) ); ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Track Event', 'trackify-capi' ); ?></strong></td>
                                    <td><code><?php echo esc_html( rest_url( 'trackify-capi/v1/track' ) ); ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Stats', 'trackify-capi' ); ?></strong></td>
                                    <td><code><?php echo esc_html( rest_url( 'trackify-capi/v1/stats' ) ); ?></code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Shortcuts -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e( 'Hızlı Linkler', 'trackify-capi' ); ?>
                        </h2>
                        
                        <ul>
                            <li style="margin-bottom: 10px;">
                                <a href="https://www.facebook.com/events_manager2/" target="_blank">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e( 'Meta Events Manager', 'trackify-capi' ); ?>
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="https://developers.facebook.com/docs/marketing-api/conversions-api" target="_blank">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e( 'Conversions API Dokümantasyonu', 'trackify-capi' ); ?>
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="https://www.facebook.com/business/help/402791146561655" target="_blank">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e( 'Event Match Quality (EMQ)', 'trackify-capi' ); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test Event
            $('.trackify-send-test-event').on('click', function() {
                var $button = $(this);
                var $result = $('#test-event-result');
                
                $button.prop('disabled', true).text('<?php esc_attr_e( 'Gönderiliyor...', 'trackify-capi' ); ?>');
                $result.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'trackify_send_test_event',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'trackify_capi_admin' ) ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Bir hata oluştu', 'trackify-capi' ); ?></p></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Test Event Gönder', 'trackify-capi' ); ?>');
                    }
                });
            });
            
            // Clear Logs
            $('.trackify-clear-logs').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'trackify_clear_logs',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'trackify_capi_admin' ) ); ?>'
                    },
                    success: function(response) {
                        alert('<?php esc_attr_e( 'Loglar temizlendi', 'trackify-capi' ); ?>');
                        location.reload();
                    }
                });
            });
            
            // Export Settings
            $('.trackify-export-settings').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'trackify_export_settings',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'trackify_capi_admin' ) ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var blob = new Blob([response.data.json], {type: 'application/json'});
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = 'trackify-capi-settings-' + Date.now() + '.json';
                            a.click();
                        }
                    }
                });
            });
            
            // Import Settings
            $('#trackify-import-file').on('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'trackify_import_settings',
                            nonce: '<?php echo esc_js( wp_create_nonce( 'trackify_capi_admin' ) ); ?>',
                            json: e.target.result
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_attr_e( 'Ayarlar başarıyla içe aktarıldı', 'trackify-capi' ); ?>');
                                location.reload();
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                };
                reader.readAsText(file);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle actions
     * 
     * @param string $action
     */
    private function handle_action( $action ) {
        switch ( $action ) {
            case 'cleanup_old_logs':
                $this->logger->cleanup_old_logs();
                add_settings_error(
                    'trackify_capi',
                    'logs_cleaned',
                    __( 'Eski loglar başarıyla temizlendi.', 'trackify-capi' ),
                    'success'
                );
                break;
                
            case 'optimize_tables':
                $this->optimize_tables();
                add_settings_error(
                    'trackify_capi',
                    'tables_optimized',
                    __( 'Veritabanı tabloları optimize edildi.', 'trackify-capi' ),
                    'success'
                );
                break;
        }
    }
    
    /**
     * Optimize database tables
     */
    private function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'trackify_capi_events',
            $wpdb->prefix . 'trackify_capi_analytics',
        );
        
        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE {$table}" );
        }
    }
    
    /**
     * Get total log count
     * 
     * @return int
     */
    private function get_total_log_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'trackify_capi_events';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }
}