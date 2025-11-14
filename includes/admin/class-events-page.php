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
        // CSV export
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' && check_admin_referer( 'export_logs' ) ) {
            $this->export_csv();
            return;
        }
        
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
                <button type="button" class="button trackify-clear-logs" onclick="return confirm('<?php esc_attr_e( 'Tüm loglar silinecek. Emin misiniz?', 'trackify-capi' ); ?>');">
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
                                <th style="width: 20%;"><?php esc_html_e( 'Event Adı', 'trackify-capi' ); ?></th>
                                <th style="width: 15%;"><?php esc_html_e( 'Event ID', 'trackify-capi' ); ?></th>
                                <th style="width: 10%;"><?php esc_html_e( 'Durum', 'trackify-capi' ); ?></th>
                                <th style="width: 10%;"><?php esc_html_e( 'Response', 'trackify-capi' ); ?></th>
                                <th style="width: 25%;"><?php esc_html_e( 'Tarih', 'trackify-capi' ); ?></th>
                                <th style="width: 20%;"><?php esc_html_e( 'Detaylar', 'trackify-capi' ); ?></th>
                            </tr>
                        </thead>
                        <tbody> 
                            <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $log['event_name'] ); ?></strong></td>
                                <td><code><?php echo esc_html( substr( $log['event_id'], 0, 20 ) . '...' ); ?></code></td>
                                <td>
                                    <?php
                                    $status_class = $log['status'] === 'success' ? 'success' : ( $log['status'] === 'error' ? 'error' : 'pending' );
                                    $status_text = $log['status'] === 'success' ? __( 'Başarılı', 'trackify-capi' ) : ( $log['status'] === 'error' ? __( 'Hata', 'trackify-capi' ) : __( 'Bekliyor', 'trackify-capi' ) );
                                    ?>
                                    <span class="status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( $status_text ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( ! empty( $log['response_code'] ) ) : ?>
                                        <code><?php echo esc_html( $log['response_code'] ); ?></code>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $log['created_at'] ) ) ); ?></td>
                                <td>
                                    <button type="button" class="button button-small view-details" data-log-id="<?php echo esc_attr( $log['id'] ?? 0 ); ?>">
                                        <?php esc_html_e( 'Detayları Gör', 'trackify-capi' ); ?>
                                    </button>
                                    
                                    <?php if ( ! empty( $log['error_message'] ) ) : ?>
                                        <div class="trackify-log-details" style="display:none;" id="log-details-<?php echo esc_attr( $log['id'] ?? 0 ); ?>">
                                            <strong><?php esc_html_e( 'Hata:', 'trackify-capi' ); ?></strong>
                                            <p><?php echo esc_html( $log['error_message'] ); ?></p>
                                            
                                            <?php if ( ! empty( $log['event_data'] ) ) : ?>
                                                <strong><?php esc_html_e( 'Event Data:', 'trackify-capi' ); ?></strong>
                                                <pre><?php echo esc_html( wp_json_encode( $log['event_data'], JSON_PRETTY_PRINT ) ); ?></pre>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.view-details').on('click', function() {
                var logId = $(this).data('log-id');
                var $details = $('#log-details-' + logId);
                $details.toggle();
            });
            
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
        });
        </script>
        <?php
    }
    
    /**
     * Export logs as CSV
     */
    private function export_csv() {
        $filters = array();
       
        if ( ! empty( $_GET['status'] ) ) {
            $filters['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
        }
        
        if ( ! empty( $_GET['event_name'] ) ) {
            $filters['event_name'] = sanitize_text_field( wp_unslash( $_GET['event_name'] ) );
        }
        
        $csv = $this->logger->export_logs_csv( $filters );
        
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=trackify-capi-logs-' . gmdate( 'Y-m-d' ) . '.csv' );
        
        echo $csv;
        exit;
    }
}