<?php
/**
 * Settings Page
 * 
 * Ana ayarlar sayfası
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Settings_Page {
    
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
     * Dashboard render
     */
    public function render_dashboard() {
        $stats = $this->logger->get_event_stats( 7 );
        $pixels = $this->settings->get_active_pixels();
        
        ?>
        <div class="wrap trackify-capi-admin">
            <div class="trackify-capi-header">
                <h1>
                    <img src="<?php echo esc_url( TRACKIFY_CAPI_ASSETS . 'images/logo.png' ); ?>" alt="Trackify CAPI" />
                    <?php esc_html_e( 'Trackify CAPI Dashboard', 'trackify-capi' ); ?>
                </h1>
                <p><?php esc_html_e( 'Meta Pixel ve Conversions API event tracking yönetimi', 'trackify-capi' ); ?></p>
            </div>
            
            <!-- Quick Stats -->
            <div class="trackify-analytics-grid">
                <?php
                $total_events = 0;
                $successful_events = 0;
                $failed_events = 0;
                
                foreach ( $stats as $stat ) {
                    $total_events += $stat['total'];
                    $successful_events += $stat['successful'];
                    $failed_events += $stat['failed'];
                }
                
                $success_rate = $total_events > 0 ? round( ( $successful_events / $total_events ) * 100, 1 ) : 0;
                ?>
                
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value"><?php echo esc_html( trackify_capi_format_number( $total_events ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Toplam Event', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change"><?php esc_html_e( 'Son 7 Gün', 'trackify-capi' ); ?></div>
                </div>
                
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value" style="color: #28a745;"><?php echo esc_html( $success_rate ); ?>%</div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Başarı Oranı', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change positive">
                        <?php echo esc_html( trackify_capi_format_number( $successful_events ) ); ?> <?php esc_html_e( 'başarılı', 'trackify-capi' ); ?>
                    </div>
                </div>
                
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value"><?php echo esc_html( count( $pixels ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Aktif Pixel', 'trackify-capi' ); ?></div>
                </div>
                
                <?php if ( $failed_events > 0 ) : ?>
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value" style="color: #dc3545;"><?php echo esc_html( trackify_capi_format_number( $failed_events ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Başarısız Event', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-logs&status=error' ) ); ?>">
                            <?php esc_html_e( 'Hataları Görüntüle', 'trackify-capi' ); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Top Events -->
            <?php if ( ! empty( $stats ) ) : ?>
            <div class="trackify-settings-section">
                <h2><?php esc_html_e( 'En Çok Kullanılan Event\'ler', 'trackify-capi' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Event', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Toplam', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarılı', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarısız', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarı Oranı', 'trackify-capi' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( array_slice( $stats, 0, 10 ) as $stat ) : ?>
                        <?php
                        $event_success_rate = $stat['total'] > 0 ? round( ( $stat['successful'] / $stat['total'] ) * 100, 1 ) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $stat['event_name'] ); ?></strong></td>
                            <td><?php echo esc_html( trackify_capi_format_number( $stat['total'] ) ); ?></td>
                            <td><?php echo esc_html( trackify_capi_format_number( $stat['successful'] ) ); ?></td>
                            <td><?php echo esc_html( trackify_capi_format_number( $stat['failed'] ) ); ?></td>
                            <td>
                                <span class="trackify-success-badge" style="background: <?php echo $event_success_rate >= 90 ? '#28a745' : ( $event_success_rate >= 70 ? '#ffc107' : '#dc3545' ); ?>; color: white; padding: 3px 10px; border-radius: 3px; font-size: 12px;">
                                    <?php echo esc_html( $event_success_rate ); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="trackify-settings-section">
                <h2><?php esc_html_e( 'Hızlı Erişim', 'trackify-capi' ); ?></h2>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-settings' ) ); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e( 'Ayarları Yönet', 'trackify-capi' ); ?>
                    </a>
                    
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-logs' ) ); ?>" class="button button-large">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php esc_html_e( 'Event Logs', 'trackify-capi' ); ?>
                    </a>
                    
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-analytics' ) ); ?>" class="button button-large">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php esc_html_e( 'Analytics', 'trackify-capi' ); ?>
                    </a>
                    
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-tools' ) ); ?>" class="button button-large">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e( 'Araçlar', 'trackify-capi' ); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page render
     */
    public function render() {
        $settings = $this->settings->get_all();
        $pixels = $this->settings->get( 'pixels', array() );
        $events = $this->settings->get( 'events', array() );
        
        ?>
        <div class="wrap trackify-capi-admin">
            <div class="trackify-capi-header">
                <h1><?php esc_html_e( 'Trackify CAPI Ayarları', 'trackify-capi' ); ?></h1>
                <p><?php esc_html_e( 'Meta Pixel ve Conversions API yapılandırması', 'trackify-capi' ); ?></p>
            </div>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'trackify_capi_settings_group' ); ?>
                
                <div class="trackify-settings-grid">
                    <!-- Sol Kolon: Ana Ayarlar -->
                    <div>
                        <!-- Genel Ayarlar -->
                        <div class="trackify-settings-section">
                            <h2><?php esc_html_e( 'Genel Ayarlar', 'trackify-capi' ); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Plugin Durumu', 'trackify-capi' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="trackify_capi_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
                                            <?php esc_html_e( 'Trackify CAPI\'yi etkinleştir', 'trackify-capi' ); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php esc_html_e( 'Conversions API', 'trackify-capi' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="trackify_capi_settings[capi_enabled]" value="1" <?php checked( ! empty( $settings['capi_enabled'] ) ); ?> />
                                            <?php esc_html_e( 'Server-side tracking\'i etkinleştir', 'trackify-capi' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e( 'Event\'lerin Meta\'ya sunucu üzerinden gönderilmesini sağlar. EMQ için önerilir.', 'trackify-capi' ); ?>
                                        </p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php esc_html_e( 'Test Modu', 'trackify-capi' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="trackify_capi_settings[test_mode]" value="1" <?php checked( ! empty( $settings['test_mode'] ) ); ?> />
                                            <?php esc_html_e( 'Test modunu aktif et', 'trackify-capi' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e( 'Event\'ler Meta Events Manager\'da "Test Events" olarak görünür.', 'trackify-capi' ); ?>
                                        </p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php esc_html_e( 'Debug Modu', 'trackify-capi' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="trackify_capi_settings[debug_mode]" value="1" <?php checked( ! empty( $settings['debug_mode'] ) ); ?> />
                                            <?php esc_html_e( 'Debug mode\'u aktif et', 'trackify-capi' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e( 'Detaylı log kaydı tutar. Sadece sorun giderme için kullanın.', 'trackify-capi' ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Pixel Ayarları -->
                        <div class="trackify-settings-section">
                            <h2>
                                <?php esc_html_e( 'Meta Pixel\'ler', 'trackify-capi' ); ?>
                                <button type="button" class="button trackify-add-pixel" style="margin-left: 10px;">
                                    <?php esc_html_e( '+ Yeni Pixel Ekle', 'trackify-capi' ); ?>
                                </button>
                            </h2>
                            
                            <div class="trackify-pixel-cards">
                                <?php if ( ! empty( $pixels ) ) : ?>
                                    <?php foreach ( $pixels as $index => $pixel ) : ?>
                                    <div class="trackify-pixel-card <?php echo ! empty( $pixel['enabled'] ) ? 'active' : 'inactive'; ?>">
                                        <div class="trackify-pixel-card-header">
                                            <div>
                                                <input type="text" 
                                                       name="trackify_capi_settings[pixels][<?php echo esc_attr( $index ); ?>][name]" 
                                                       value="<?php echo esc_attr( $pixel['name'] ?? '' ); ?>" 
                                                       placeholder="<?php esc_attr_e( 'Pixel Adı', 'trackify-capi' ); ?>"
                                                       class="regular-text" />
                                            </div>
                                            <div class="trackify-pixel-card-actions">
                                                <label>
                                                    <input type="checkbox" 
                                                           name="trackify_capi_settings[pixels][<?php echo esc_attr( $index ); ?>][enabled]" 
                                                           value="1" 
                                                           class="trackify-pixel-toggle"
                                                           <?php checked( ! empty( $pixel['enabled'] ) ); ?> />
                                                    <?php esc_html_e( 'Aktif', 'trackify-capi' ); ?>
                                                </label>
                                                <button type="button" class="button trackify-remove-pixel">×</button>
                                            </div>
                                        </div>
                                        
                                        <table class="form-table">
                                            <tr>
                                                <th><?php esc_html_e( 'Pixel ID', 'trackify-capi' ); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="trackify_capi_settings[pixels][<?php echo esc_attr( $index ); ?>][pixel_id]" 
                                                           value="<?php echo esc_attr( $pixel['pixel_id'] ?? '' ); ?>" 
                                                           class="regular-text"
                                                           placeholder="123456789012345" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?php esc_html_e( 'Access Token', 'trackify-capi' ); ?></th>
                                                <td>
                                                    <input type="password" 
                                                           name="trackify_capi_settings[pixels][<?php echo esc_attr( $index ); ?>][access_token]" 
                                                           value="<?php echo esc_attr( $pixel['access_token'] ?? '' ); ?>" 
                                                           class="regular-text"
                                                           placeholder="EAAxxxxxxxxxx" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?php esc_html_e( 'Test Event Code', 'trackify-capi' ); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="trackify_capi_settings[pixels][<?php echo esc_attr( $index ); ?>][test_event_code]" 
                                                           value="<?php echo esc_attr( $pixel['test_event_code'] ?? '' ); ?>" 
                                                           class="regular-text"
                                                           placeholder="TEST12345" />
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="trackify-info-box">
                                        <span class="dashicons dashicons-info"></span>
                                        <div>
                                            <strong><?php esc_html_e( 'Henüz pixel eklenmedi', 'trackify-capi' ); ?></strong>
                                            <p><?php esc_html_e( '"Yeni Pixel Ekle" butonuna tıklayarak başlayın.', 'trackify-capi' ); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Hidden template for new pixel -->
                            <div class="trackify-pixel-card trackify-pixel-card-template" style="display:none;">
                                <div class="trackify-pixel-card-header">
                                    <div>
                                        <input type="text" name="trackify_capi_settings[pixels][][name]" placeholder="<?php esc_attr_e( 'Pixel Adı', 'trackify-capi' ); ?>" class="regular-text" />
                                    </div>
                                    <div class="trackify-pixel-card-actions">
                                        <label>
                                            <input type="checkbox" name="trackify_capi_settings[pixels][][enabled]" value="1" class="trackify-pixel-toggle" checked />
                                            <?php esc_html_e( 'Aktif', 'trackify-capi' ); ?>
                                        </label>
                                        <button type="button" class="button trackify-remove-pixel">×</button>
                                    </div>
                                </div>
                                <table class="form-table">
                                    <tr>
                                        <th><?php esc_html_e( 'Pixel ID', 'trackify-capi' ); ?></th>
                                        <td><input type="text" name="trackify_capi_settings[pixels][][pixel_id]" class="regular-text" placeholder="123456789012345" /></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Access Token', 'trackify-capi' ); ?></th>
                                        <td><input type="password" name="trackify_capi_settings[pixels][][access_token]" class="regular-text" placeholder="EAAxxxxxxxxxx" /></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Test Event Code', 'trackify-capi' ); ?></th>
                                        <td><input type="text" name="trackify_capi_settings[pixels][][test_event_code]" class="regular-text" placeholder="TEST12345" /></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Event Ayarları -->
                        <div class="trackify-settings-section">
                            <h2><?php esc_html_e( 'Event Ayarları', 'trackify-capi' ); ?></h2>
                            
                            <p><?php esc_html_e( 'Hangi event\'lerin track edileceğini seçin:', 'trackify-capi' ); ?></p>
                            
                            <ul class="trackify-event-list">
                                <?php
                                $available_events = array(
                                    'PageView' => __( 'Her sayfa yüklendiğinde', 'trackify-capi' ),
                                    'ViewContent' => __( 'Ürün/içerik görüntülendiğinde', 'trackify-capi' ),
                                    'AddToCart' => __( 'Sepete ürün eklendiğinde', 'trackify-capi' ),
                                    'InitiateCheckout' => __( 'Ödeme sayfasına gidildiğinde', 'trackify-capi' ),
                                    'Purchase' => __( 'Sipariş tamamlandığında', 'trackify-capi' ),
                                    'Lead' => __( 'Form gönderildiğinde', 'trackify-capi' ),
                                    'CompleteRegistration' => __( 'Kullanıcı kaydı tamamlandığında', 'trackify-capi' ),
                                );
                                
                                foreach ( $available_events as $event_key => $event_desc ) :
                                    $is_enabled = ! empty( $events[ $event_key ]['enabled'] );
                                    $pixel_enabled = ! empty( $events[ $event_key ]['pixel'] );
                                    $capi_enabled = ! empty( $events[ $event_key ]['capi'] );
                                ?>
                                <li class="trackify-event-item">
                                    <input type="checkbox" 
                                           name="trackify_capi_settings[events][<?php echo esc_attr( $event_key ); ?>][enabled]" 
                                           value="1" 
                                           id="event_<?php echo esc_attr( $event_key ); ?>"
                                           <?php checked( $is_enabled ); ?> />
                                    <label for="event_<?php echo esc_attr( $event_key ); ?>">
                                        <span class="trackify-event-name"><?php echo esc_html( $event_key ); ?></span>
                                        <div style="font-size: 12px; color: #666;"><?php echo esc_html( $event_desc ); ?></div>
                                    </label>
                                    <div class="trackify-event-badges">
                                        <label title="<?php esc_attr_e( 'Client-side Pixel', 'trackify-capi' ); ?>">
                                            <input type="checkbox" 
                                                   name="trackify_capi_settings[events][<?php echo esc_attr( $event_key ); ?>][pixel]" 
                                                   value="1"
                                                   <?php checked( $pixel_enabled ); ?> />
                                            <span class="trackify-badge pixel">Pixel</span>
                                        </label>
                                        <label title="<?php esc_attr_e( 'Server-side CAPI', 'trackify-capi' ); ?>">
                                            <input type="checkbox" 
                                                   name="trackify_capi_settings[events][<?php echo esc_attr( $event_key ); ?>][capi]" 
                                                   value="1"
                                                   <?php checked( $capi_enabled ); ?> />
                                            <span class="trackify-badge capi">CAPI</span>
                                        </label>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Sağ Kolon: Yardım & Bilgi -->
                    <div>
                        <!-- Yardım -->
                        <div class="trackify-settings-section">
                            <h2><?php esc_html_e( 'Yardım', 'trackify-capi' ); ?></h2>
                            
                            <h3><?php esc_html_e( 'Meta Pixel ID Nasıl Bulunur?', 'trackify-capi' ); ?></h3>
                            <ol>
                                <li><?php esc_html_e( 'Meta Events Manager\'a gidin', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Data Sources > Pixels seçin', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Pixel ID\'nizi kopyalayın', 'trackify-capi' ); ?></li>
                            </ol>
                            
                            <h3><?php esc_html_e( 'Access Token Nasıl Alınır?', 'trackify-capi' ); ?></h3>
                            <ol>
                                <li><?php esc_html_e( 'Events Manager > Settings > Conversions API', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( '"Generate Access Token" tıklayın', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Token\'ı kopyalayın', 'trackify-capi' ); ?></li>
                            </ol>
                            
                            <p>
                                <a href="https://business.facebook.com/events_manager2" target="_blank" class="button">
                                    <?php esc_html_e( 'Meta Events Manager\'ı Aç', 'trackify-capi' ); ?>
                                </a>
                            </p>
                        </div>
                        
                        <!-- Sistem Durumu -->
                        <div class="trackify-settings-section">
                            <h2><?php esc_html_e( 'Sistem Durumu', 'trackify-capi' ); ?></h2>
                            
                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e( 'Plugin Versiyonu', 'trackify-capi' ); ?></td>
                                        <td><strong><?php echo esc_html( TRACKIFY_CAPI_VERSION ); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'WordPress', 'trackify-capi' ); ?></td>
                                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'PHP', 'trackify-capi' ); ?></td>
                                        <td><?php echo esc_html( PHP_VERSION ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'WooCommerce', 'trackify-capi' ); ?></td>
                                        <td>
                                            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                                <span style="color: green;">✓</span> <?php echo esc_html( WC()->version ); ?>
                                            <?php else : ?>
                                                <span style="color: red;">✗</span> <?php esc_html_e( 'Yüklü değil', 'trackify-capi' ); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Aktif Pixel', 'trackify-capi' ); ?></td>
                                        <td><strong><?php echo count( $this->settings->get_active_pixels() ); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php submit_button( __( 'Ayarları Kaydet', 'trackify-capi' ), 'primary large' ); ?>
            </form>
        </div>
        <?php
    }
}