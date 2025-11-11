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
                        
                        <button type="button" class="button button-large trackify-clear-logs">
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
                            
                            <button type="button" class="button button-large trackify-import-settings">
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
                                    <td><strong><?php esc_html_e( 'WordPress', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'PHP Versiyonu', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'MySQL Versiyonu', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( $GLOBALS['wpdb']->db_version() ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'WooCommerce', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WC()->version ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span> <?php esc_html_e( 'Yüklü değil', 'trackify-capi' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Server Software', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'PHP Memory Limit', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Max Upload Size', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Aktif Pixel Sayısı', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo count( $this->settings->get_active_pixels() ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Toplam Log Sayısı', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( trackify_capi_format_number( $this->get_total_log_count() ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Log Dizini Boyutu', 'trackify-capi' ); ?></strong></td>
                                    <td><?php echo esc_html( size_format( $this->get_log_directory_size() ) ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p style="margin-top: 15px;">
                            <button type="button" class="button" onclick="navigator.clipboard.writeText(document.querySelector('.trackify-settings-section table').innerText); alert('Sistem bilgileri kopyalandı!');">
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
                    
                    <!-- REST API Test -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-rest-api"></span>
                            <?php esc_html_e( 'REST API Test', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'REST API endpoint\'lerini test edin.', 'trackify-capi' ); ?></p>
                        
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong>/health</strong></td>
                                    <td>
                                        <a href="<?php echo esc_url( rest_url( 'trackify-capi/v1/health' ) ); ?>" target="_blank" class="button button-small">
                                            <?php esc_html_e( 'Test Et', 'trackify-capi' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>/stats</strong></td>
                                    <td>
                                        <a href="<?php echo esc_url( rest_url( 'trackify-capi/v1/stats' ) ); ?>" target="_blank" class="button button-small">
                                            <?php esc_html_e( 'Test Et', 'trackify-capi' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Debug Information -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e( 'Debug Bilgileri', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'Sorun giderme için yararlı bilgiler.', 'trackify-capi' ); ?></p>
                        
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e( 'WP_DEBUG', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                                            <span style="color: orange;">✓</span> <?php esc_html_e( 'Aktif', 'trackify-capi' ); ?>
                                        <?php else : ?>
                                            <span style="color: green;">✗</span> <?php esc_html_e( 'Pasif', 'trackify-capi' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'SCRIPT_DEBUG', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) : ?>
                                            <span style="color: orange;">✓</span> <?php esc_html_e( 'Aktif', 'trackify-capi' ); ?>
                                        <?php else : ?>
                                            <span style="color: green;">✗</span> <?php esc_html_e( 'Pasif', 'trackify-capi' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'HTTPS', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( is_ssl() ) : ?>
                                            <span style="color: green;">✓</span> <?php esc_html_e( 'Aktif', 'trackify-capi' ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span> <?php esc_html_e( 'Pasif', 'trackify-capi' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Cron', 'trackify-capi' ); ?></strong></td>
                                    <td>
                                        <?php if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) : ?>
                                            <span style="color: green;">✓</span> <?php esc_html_e( 'Aktif', 'trackify-capi' ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span> <?php esc_html_e( 'Devre Dışı', 'trackify-capi' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Integrations Status -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php esc_html_e( 'Entegrasyon Durumu', 'trackify-capi' ); ?>
                        </h2>
                        
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong>WooCommerce</strong></td>
                                    <td>
                                        <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WC()->version ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Contact Form 7</strong></td>
                                    <td>
                                        <?php if ( defined( 'WPCF7_VERSION' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WPCF7_VERSION ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>WPForms</strong></td>
                                    <td>
                                        <?php if ( defined( 'WPFORMS_VERSION' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( WPFORMS_VERSION ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Gravity Forms</strong></td>
                                    <td>
                                        <?php if ( class_exists( 'GFForms' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( GFForms::$version ?? 'N/A' ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Elementor</strong></td>
                                    <td>
                                        <?php if ( defined( 'ELEMENTOR_VERSION' ) ) : ?>
                                            <span style="color: green;">✓</span> <?php echo esc_html( ELEMENTOR_VERSION ); ?>
                                        <?php else : ?>
                                            <span style="color: red;">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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
    
    /**
     * Get log directory size
     * 
     * @return int
     */
    private function get_log_directory_size() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/trackify-capi/logs/';
        
        if ( ! file_exists( $log_dir ) ) {
            return 0;
        }
        
        $size = 0;
        $files = glob( $log_dir . '*' );
        
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                $size += filesize( $file );
            }
        }
        
        return $size;
    }
}