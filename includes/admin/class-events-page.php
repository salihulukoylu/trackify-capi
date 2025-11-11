<?php
/**
 * Events Page
 * 
 * Event logs görüntüleme sayfası
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Events_Page {
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    private $logger;
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = trackify_capi()->get_component( 'logger' );
        $this->settings = trackify_capi()->get_component( 'settings' );
    }
    
    /**
     * Render page
     */
    public function render() {
        // Filtreleri al
        $filters = array();
        
        if ( ! empty( $_GET['status'] ) ) {
            $filters['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
        }
        
        if ( ! empty( $_GET['event_name'] ) ) {
            $filters['event_name'] = sanitize_text_field( wp_unslash( $_GET['event_name'] ) );
        }
        
        if ( ! empty( $_GET['date_from'] ) ) {
            $filters['date_from'] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) );
        }
        
        if ( ! empty( $_GET['date_to'] ) ) {
            $filters['date_to'] = sanitize_text_field( wp_unslash( $_GET['date_to'] ) );
        }
        
        // Pagination
        $per_page = 50;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        
        // Logları getir
        $logs = $this->logger->get_recent_logs( $per_page, $filters );
        
        ?>
        <div class="wrap trackify-capi-admin">
            <div class="trackify-capi-header">
                <h1><?php esc_html_e( 'Event Logs', 'trackify-capi' ); ?></h1>
                <p><?php esc_html_e( 'Meta\'ya gönderilen event\'leri görüntüleyin ve filtreleyin', 'trackify-capi' ); ?></p>
            </div>
            
            <!-- Filters -->
            <div class="trackify-logs-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="trackify-capi-logs" />
                    
                    <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                        <div class="trackify-filter-group">
                            <label><?php esc_html_e( 'Event', 'trackify-capi' ); ?></label>
                            <select name="event_name">
                                <option value=""><?php esc_html_e( 'Tüm Event\'ler', 'trackify-capi' ); ?></option>
                                <option value="PageView" <?php selected( $filters['event_name'] ?? '', 'PageView' ); ?>>PageView</option>
                                <option value="ViewContent" <?php selected( $filters['event_name'] ?? '', 'ViewContent' ); ?>>ViewContent</option>
                                <option value="AddToCart" <?php selected( $filters['event_name'] ?? '', 'AddToCart' ); ?>>AddToCart</option>
                                <option value="InitiateCheckout" <?php selected( $filters['event_name'] ?? '', 'InitiateCheckout' ); ?>>InitiateCheckout</option>
                                <option value="Purchase" <?php selected( $filters['event_name'] ?? '', 'Purchase' ); ?>>Purchase</option>
                                <option value="Lead" <?php selected( $filters['event_name'] ?? '', 'Lead' ); ?>>Lead</option>
                            </select>
                        </div>
                        
                        <div class="trackify-filter-group">
                            <label><?php esc_html_e( 'Durum', 'trackify-capi' ); ?></label>
                            <select name="status">
                                <option value=""><?php esc_html_e( 'Tümü', 'trackify-capi' ); ?></option>
                                <option value="success" <?php selected( $filters['status'] ?? '', 'success' ); ?>><?php esc_html_e( 'Başarılı', 'trackify-capi' ); ?></option>
                                <option value="error" <?php selected( $filters['status'] ?? '', 'error' ); ?>><?php esc_html_e( 'Hata', 'trackify-capi' ); ?></option>
                                <option value="pending" <?php selected( $filters['status'] ?? '', 'pending' ); ?>><?php esc_html_e( 'Bekliyor', 'trackify-capi' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="trackify-filter-group">
                            <label><?php esc_html_e( 'Başlangıç Tarihi', 'trackify-capi' ); ?></label>
                            <input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ?? '' ); ?>" />
                        </div>
                        
                        <div class="trackify-filter-group">
                            <label><?php esc_html_e( 'Bitiş Tarihi', 'trackify-capi' ); ?></label>
                            <input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ?? '' ); ?>" />
                        </div>
                        
                        <div>
                            <button type="submit" class="button"><?php esc_html_e( 'Filtrele', 'trackify-capi' ); ?></button>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=trackify-capi-logs' ) ); ?>" class="button">
                                <?php esc_html_e( 'Temizle', 'trackify-capi' ); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Actions -->
            <div style="margin-bottom: 20px;">
                <button type="button" class="button trackify-clear-logs">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e( 'Tüm Logları Temizle', 'trackify-capi' ); ?>
                </button>
                
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=trackify-capi-logs&action=export_csv' ), 'export_logs' ) ); ?>" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'CSV Olarak İndir', 'trackify-capi' ); ?>
                </a>
            </div>
            
            <!-- Logs Table -->
            <?php if ( empty( $logs ) ) : ?>
                <div class="trackify-info-box">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php esc_html_e( 'Log bulunamadı', 'trackify-capi' ); ?></strong>
                        <p><?php esc_html_e( 'Henüz event gönderilmemiş veya filtre kriterlerine uygun log yok.', 'trackify-capi' ); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="trackify-logs-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 15%;"><?php esc_html_e( 'Tarih/Saat', 'trackify-capi' ); ?></th>
                                <th style="width: 15%;"><?php esc_html_e( 'Event', 'trackify-capi' ); ?></th>
                                <th style="width: 20%;"><?php esc_html_e( 'Event ID', 'trackify-capi' ); ?></th>
                                <th style="width: 12%;"><?php esc_html_e( 'Pixel ID', 'trackify-capi' ); ?></th>
                                <th style="width: 10%;"><?php esc_html_e( 'Durum', 'trackify-capi' ); ?></th>
                                <th style="width: 8%;"><?php esc_html_e( 'Response', 'trackify-capi' ); ?></th>
                                <th><?php esc_html_e( 'Detaylar', 'trackify-capi' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $log['created_at'] ) ) ); ?></strong>
                                    <br />
                                    <small style="color: #999;">
                                        <?php echo esc_html( trackify_capi_human_time_diff( $log['created_at'] ) ); ?>
                                    </small>
                                </td>
                                <td><strong><?php echo esc_html( $log['event_name'] ); ?></strong></td>
                                <td>
                                    <code style="font-size: 11px; background: #f5f5f5; padding: 2px 5px; border-radius: 3px;">
                                        <?php echo esc_html( $log['event_id'] ); ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if ( ! empty( $log['pixel_id'] ) ) : ?>
                                        <code style="font-size: 11px;">
                                            <?php echo esc_html( substr( $log['pixel_id'], 0, 12 ) ); ?>...
                                        </code>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = array(
                                        'success' => array( 'bg' => '#d4edda', 'text' => '#155724', 'label' => __( 'Başarılı', 'trackify-capi' ) ),
                                        'error' => array( 'bg' => '#f8d7da', 'text' => '#721c24', 'label' => __( 'Hata', 'trackify-capi' ) ),
                                        'pending' => array( 'bg' => '#fff3cd', 'text' => '#856404', 'label' => __( 'Bekliyor', 'trackify-capi' ) ),
                                    );
                                    
                                    $status = $log['status'] ?? 'pending';
                                    $status_info = $status_colors[ $status ] ?? $status_colors['pending'];
                                    ?>
                                    <span class="status-badge" style="background: <?php echo esc_attr( $status_info['bg'] ); ?>; color: <?php echo esc_attr( $status_info['text'] ); ?>; padding: 3px 10px; border-radius: 3px; font-size: 12px; font-weight: 600; display: inline-block;">
                                        <?php echo esc_html( $status_info['label'] ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( ! empty( $log['response_code'] ) ) : ?>
                                        <code style="font-size: 11px; <?php echo $log['response_code'] === 200 ? 'color: green;' : 'color: red;'; ?>">
                                            <?php echo esc_html( $log['response_code'] ); ?>
                                        </code>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <details>
                                        <summary style="cursor: pointer; color: #0073aa;">
                                            <?php esc_html_e( 'Detayları Göster', 'trackify-capi' ); ?>
                                        </summary>
                                        <div class="trackify-log-details">
                                            <?php if ( ! empty( $log['error_message'] ) ) : ?>
                                                <strong style="color: red;"><?php esc_html_e( 'Hata:', 'trackify-capi' ); ?></strong>
                                                <p><?php echo esc_html( $log['error_message'] ); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if ( ! empty( $log['event_data'] ) ) : ?>
                                                <strong><?php esc_html_e( 'Event Data:', 'trackify-capi' ); ?></strong>
                                                <pre><?php echo esc_html( wp_json_encode( $log['event_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
                                            <?php endif; ?>
                                            
                                            <?php if ( ! empty( $log['response_data'] ) ) : ?>
                                                <strong><?php esc_html_e( 'Response Data:', 'trackify-capi' ); ?></strong>
                                                <pre><?php echo esc_html( wp_json_encode( $log['response_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Info -->
                <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <p style="margin: 0;">
                        <strong><?php esc_html_e( 'Toplam:', 'trackify-capi' ); ?></strong>
                        <?php
                        printf(
                            /* translators: %d: number of logs */
                            esc_html__( '%d log gösteriliyor', 'trackify-capi' ),
                            count( $logs )
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}