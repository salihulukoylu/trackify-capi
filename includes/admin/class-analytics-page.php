<?php
/**
 * Analytics Page
 * 
 * Event analytics ve grafikler
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Analytics_Page {
    
    /**
     * Analytics instance
     * 
     * @var Trackify_CAPI_Analytics
     */
    private $analytics;
    
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
        $this->analytics = trackify_capi()->get_component( 'analytics' );
        $this->logger = trackify_capi()->get_component( 'logger' );
    }
    
    /**
     * Render page
     */
    public function render() {
        // Tarih aralığını al
        $days = isset( $_GET['days'] ) ? intval( $_GET['days'] ) : 30;
        $days = max( 1, min( 90, $days ) ); // 1-90 gün arası
        
        // İstatistikleri al
        $stats = $this->logger->get_event_stats( $days );
        $chart_data = $this->analytics->get_chart_data( $days );
        
        // Toplam hesaplamalar
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
        <div class="wrap trackify-capi-admin">
            <div class="trackify-capi-header">
                <h1><?php esc_html_e( 'Analytics', 'trackify-capi' ); ?></h1>
                <p><?php esc_html_e( 'Event istatistikleri ve performans analizi', 'trackify-capi' ); ?></p>
            </div>
            
            <!-- Date Range Selector -->
            <div style="margin-bottom: 20px;">
                <form method="get" style="display: inline-block;">
                    <input type="hidden" name="page" value="trackify-capi-analytics" />
                    <select name="days" onchange="this.form.submit()">
                        <option value="7" <?php selected( $days, 7 ); ?>><?php esc_html_e( 'Son 7 Gün', 'trackify-capi' ); ?></option>
                        <option value="14" <?php selected( $days, 14 ); ?>><?php esc_html_e( 'Son 14 Gün', 'trackify-capi' ); ?></option>
                        <option value="30" <?php selected( $days, 30 ); ?>><?php esc_html_e( 'Son 30 Gün', 'trackify-capi' ); ?></option>
                        <option value="60" <?php selected( $days, 60 ); ?>><?php esc_html_e( 'Son 60 Gün', 'trackify-capi' ); ?></option>
                        <option value="90" <?php selected( $days, 90 ); ?>><?php esc_html_e( 'Son 90 Gün', 'trackify-capi' ); ?></option>
                    </select>
                </form>
            </div>
            
            <!-- Key Metrics -->
            <div class="trackify-analytics-grid">
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value"><?php echo esc_html( trackify_capi_format_number( $total_events ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Toplam Event', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change">
                        <?php
                        printf(
                            /* translators: %d: number of days */
                            esc_html__( 'Son %d Gün', 'trackify-capi' ),
                            $days
                        );
                        ?>
                    </div>
                </div>
                
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value" style="color: #28a745;"><?php echo esc_html( trackify_capi_format_number( $successful_events ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Başarılı Event', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change positive">
                        <?php echo esc_html( $success_rate ); ?>% <?php esc_html_e( 'başarı oranı', 'trackify-capi' ); ?>
                    </div>
                </div>
                
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value" style="color: #dc3545;"><?php echo esc_html( trackify_capi_format_number( $failed_events ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Başarısız Event', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change negative">
                        <?php echo esc_html( round( 100 - $success_rate, 1 ) ); ?>% <?php esc_html_e( 'hata oranı', 'trackify-capi' ); ?>
                    </div>
                </div>
                
                <div class="trackify-metric-card">
                    <div class="trackify-metric-value"><?php echo esc_html( number_format_i18n( $total_events > 0 ? $total_events / $days : 0, 1 ) ); ?></div>
                    <div class="trackify-metric-label"><?php esc_html_e( 'Günlük Ortalama', 'trackify-capi' ); ?></div>
                    <div class="trackify-metric-change"><?php esc_html_e( 'Event / Gün', 'trackify-capi' ); ?></div>
                </div>
            </div>
            
            <!-- Chart -->
            <?php if ( ! empty( $chart_data ) ) : ?>
            <div class="trackify-chart-container">
                <h3><?php esc_html_e( 'Event Trendi', 'trackify-capi' ); ?></h3>
                <canvas id="trackify-events-chart" style="max-height: 400px;"></canvas>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
            <script>
            (function() {
                const ctx = document.getElementById('trackify-events-chart');
                if (!ctx) return;
                
                const chartData = <?php echo wp_json_encode( $chart_data ); ?>;
                
                const labels = chartData.map(d => d.date);
                const totalData = chartData.map(d => d.total);
                const successData = chartData.map(d => d.successful);
                const failedData = chartData.map(d => d.failed);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '<?php esc_html_e( 'Toplam', 'trackify-capi' ); ?>',
                                data: totalData,
                                borderColor: '#0073aa',
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                tension: 0.3
                            },
                            {
                                label: '<?php esc_html_e( 'Başarılı', 'trackify-capi' ); ?>',
                                data: successData,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.3
                            },
                            {
                                label: '<?php esc_html_e( 'Başarısız', 'trackify-capi' ); ?>',
                                data: failedData,
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                tension: 0.3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            })();
            </script>
            <?php endif; ?>
            
            <!-- Event Breakdown -->
            <?php if ( ! empty( $stats ) ) : ?>
            <div class="trackify-chart-container">
                <h3><?php esc_html_e( 'Event Detayları', 'trackify-capi' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Event Adı', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Toplam', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarılı', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarısız', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Başarı Oranı', 'trackify-capi' ); ?></th>
                            <th><?php esc_html_e( 'Oran', 'trackify-capi' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats as $stat ) : ?>
                        <?php
                        $event_success_rate = $stat['total'] > 0 ? round( ( $stat['successful'] / $stat['total'] ) * 100, 1 ) : 0;
                        $event_percentage = $total_events > 0 ? round( ( $stat['total'] / $total_events ) * 100, 1 ) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $stat['event_name'] ); ?></strong></td>
                            <td><?php echo esc_html( trackify_capi_format_number( $stat['total'] ) ); ?></td>
                            <td style="color: #28a745;"><?php echo esc_html( trackify_capi_format_number( $stat['successful'] ) ); ?></td>
                            <td style="color: #dc3545;"><?php echo esc_html( trackify_capi_format_number( $stat['failed'] ) ); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="trackify-success-badge" style="background: <?php echo $event_success_rate >= 90 ? '#28a745' : ( $event_success_rate >= 70 ? '#ffc107' : '#dc3545' ); ?>; color: white; padding: 3px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                        <?php echo esc_html( $event_success_rate ); ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #f0f0f1; border-radius: 3px; height: 20px; overflow: hidden;">
                                        <div style="background: #0073aa; height: 100%; width: <?php echo esc_attr( $event_percentage ); ?>%;"></div>
                                    </div>
                                    <span style="font-size: 12px; color: #666;"><?php echo esc_html( $event_percentage ); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else : ?>
            <div class="trackify-info-box">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php esc_html_e( 'Henüz veri yok', 'trackify-capi' ); ?></strong>
                    <p><?php esc_html_e( 'Seçilen tarih aralığında event bulunamadı.', 'trackify-capi' ); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}