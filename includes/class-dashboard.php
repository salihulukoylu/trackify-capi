<?php
/**
 * Dashboard Widget
 * 
 * WordPress admin dashboard'a widget ekler
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Dashboard {
    
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
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        $this->logger = trackify_capi()->get_component( 'logger' );
        
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
    }
    
    /**
     * Dashboard widget ekle
     * 
     * @since 2.0.0
     */
    public function add_dashboard_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        wp_add_dashboard_widget(
            'trackify_capi_dashboard',
            __( 'Trackify CAPI - Event Tracking', 'trackify-capi' ),
            array( $this, 'render_dashboard_widget' ),
            null,
            null,
            'normal',
            'high'
        );
    }
    
    /**
     * Dashboard widget render
     * 
     * @since 2.0.0
     */
    public function render_dashboard_widget() {
        // Plugin kapalıysa uyarı göster
        if ( ! $this->settings->is_enabled() ) {
            $this->render_disabled_notice();
            return;
        }
        
        // Pixel yapılandırılmamışsa uyarı
        $pixels = $this->settings->get_active_pixels();
        if ( empty( $pixels ) ) {
            $this->render_no_pixel_notice();
            return;
        }
        
        // Stats göster
        $this->render_stats();
    }
    
    /**
     * Plugin kapalı uyarısı
     */
    private function render_disabled_notice() {
        ?>
        <div class="trackify-dashboard-notice notice-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <strong><?php esc_html_e( 'Trackify CAPI devre dışı!', 'trackify-capi' ); ?></strong>
            </p>
            <p><?php esc_html_e( 'Event tracking\'i etkinleştirmek için ayarları yapılandırın.', 'trackify-capi' ); ?></p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Ayarlara Git', 'trackify-capi' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Pixel yapılandırılmamış uyarısı
     */
    private function render_no_pixel_notice() {
        ?>
        <div class="trackify-dashboard-notice notice-info">
            <p>
                <span class="dashicons dashicons-info"></span>
                <strong><?php esc_html_e( 'Pixel yapılandırılmadı!', 'trackify-capi' ); ?></strong>
            </p>
            <p><?php esc_html_e( 'Event tracking başlatmak için Meta Pixel bilgilerinizi girin.', 'trackify-capi' ); ?></p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Pixel Ekle', 'trackify-capi' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * İstatistikleri render et
     */
    private function render_stats() {
        // Son 7 günün istatistikleri
        $stats = $this->logger->get_event_stats( 7 );
        
        // Toplam event sayısı
        $total_events = 0;
        $successful_events = 0;
        $failed_events = 0;
        
        foreach ( $stats as $stat ) {
            $total_events += $stat['total'];
            $successful_events += $stat['successful'];
            $failed_events += $stat['failed'];
        }
        
        // Başarı oranı
        $success_rate = $total_events > 0 ? round( ( $successful_events / $total_events ) * 100, 1 ) : 0;
        
        ?>
        <div class="trackify-dashboard-widget">
            <div class="trackify-stats-grid">
                <!-- Total Events -->
                <div class="trackify-stat-box">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( trackify_capi_format_number( $total_events ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Toplam Event', 'trackify-capi' ); ?></div>
                        <div class="stat-period"><?php esc_html_e( 'Son 7 Gün', 'trackify-capi' ); ?></div>
                    </div>
                </div>
                
                <!-- Success Rate -->
                <div class="trackify-stat-box">
                    <div class="stat-icon success">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( $success_rate ); ?>%</div>
                        <div class="stat-label"><?php esc_html_e( 'Başarı Oranı', 'trackify-capi' ); ?></div>
                        <div class="stat-period">
                            <?php
                            printf(
                                /* translators: %d: number of successful events */
                                esc_html__( '%s başarılı', 'trackify-capi' ),
                                esc_html( trackify_capi_format_number( $successful_events ) )
                            );
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Failed Events -->
                <?php if ( $failed_events > 0 ) : ?>
                <div class="trackify-stat-box">
                    <div class="stat-icon error">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( trackify_capi_format_number( $failed_events ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Başarısız', 'trackify-capi' ); ?></div>
                        <div class="stat-period">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-logs&status=error' ) ); ?>">
                                <?php esc_html_e( 'Hataları Görüntüle', 'trackify-capi' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ( ! empty( $stats ) ) : ?>
            <!-- Event Breakdown -->
            <div class="trackify-event-breakdown">
                <h4><?php esc_html_e( 'Event Dağılımı', 'trackify-capi' ); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Event', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Toplam', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarılı', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarı %', 'trackify-capi' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( array_slice( $stats, 0, 5 ) as $stat ) : ?>
                        <?php
                        $event_success_rate = $stat['total'] > 0 ? round( ( $stat['successful'] / $stat['total'] ) * 100, 1 ) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $stat['event_name'] ); ?></strong></td>
                            <td><?php echo esc_html( trackify_capi_format_number( $stat['total'] ) ); ?></td>
                            <td><?php echo esc_html( trackify_capi_format_number( $stat['successful'] ) ); ?></td>
                            <td>
                                <span class="trackify-success-badge" style="background: <?php echo $event_success_rate >= 90 ? '#28a745' : ( $event_success_rate >= 70 ? '#ffc107' : '#dc3545' ); ?>;">
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
            <div class="trackify-quick-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-logs' ) ); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e( 'Tüm Logları Görüntüle', 'trackify-capi' ); ?>
                </a>
                
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-analytics' ) ); ?>" class="button">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e( 'Detaylı Analitik', 'trackify-capi' ); ?>
                </a>
                
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi' ) ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e( 'Ayarlar', 'trackify-capi' ); ?>
                </a>
            </div>
            
            <?php if ( $this->settings->is_test_mode() ) : ?>
            <div class="trackify-test-mode-notice">
                <span class="dashicons dashicons-flag"></span>
                <strong><?php esc_html_e( 'Test Modu Aktif', 'trackify-capi' ); ?></strong>
                <p><?php esc_html_e( 'Event\'ler test modunda gönderiliyor. Meta Events Manager\'da "Test Events" sekmesinden kontrol edebilirsiniz.', 'trackify-capi' ); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .trackify-dashboard-widget {
            padding: 12px;
        }
        
        .trackify-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .trackify-stat-box {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            border-radius: 4px;
        }
        
        .trackify-stat-box .stat-icon {
            font-size: 32px;
            margin-right: 15px;
            color: #0073aa;
        }
        
        .trackify-stat-box .stat-icon.success {
            color: #28a745;
        }
        
        .trackify-stat-box .stat-icon.error {
            color: #dc3545;
        }
        
        .trackify-stat-box .stat-value {
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
        }
        
        .trackify-stat-box .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .trackify-stat-box .stat-period {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }
        
        .trackify-event-breakdown {
            margin-bottom: 20px;
        }
        
        .trackify-event-breakdown h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .trackify-event-breakdown table {
            font-size: 13px;
        }
        
        .trackify-success-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }
        
        .trackify-quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .trackify-quick-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .trackify-dashboard-notice {
            padding: 15px;
            border-left: 4px solid #ffba00;
            background: #fff8e5;
        }
        
        .trackify-dashboard-notice p {
            margin: 5px 0;
        }
        
        .trackify-dashboard-notice .dashicons {
            color: #ffba00;
            font-size: 20px;
            vertical-align: middle;
        }
        
        .trackify-test-mode-notice {
            margin-top: 15px;
            padding: 10px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }
        
        .trackify-test-mode-notice .dashicons {
            color: #856404;
            float: left;
            margin-right: 10px;
        }
        
        .trackify-test-mode-notice strong {
            display: block;
            color: #856404;
        }
        
        .trackify-test-mode-notice p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #856404;
        }
        </style>
        <?php
    }
}