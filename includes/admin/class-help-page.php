<?php
/**
 * Help Page
 * 
 * Yardım ve dokümantasyon sayfası
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Help_Page {
    
    /**
     * Render page
     */
    public function render() {
        ?>
        <div class="wrap trackify-capi-admin">
            <div class="trackify-capi-header">
                <h1>
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e( 'Yardım & Dokümantasyon', 'trackify-capi' ); ?>
                </h1>
                <p><?php esc_html_e( 'Trackify CAPI kullanım kılavuzu ve SSS', 'trackify-capi' ); ?></p>
            </div>
            
            <div class="trackify-settings-grid">
                <!-- Sol Kolon -->
                <div>
                    <!-- Hızlı Başlangıç -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-superhero"></span>
                            <?php esc_html_e( 'Hızlı Başlangıç', 'trackify-capi' ); ?>
                        </h2>
                        
                        <h3><?php esc_html_e( '1. Meta Pixel Oluşturma', 'trackify-capi' ); ?></h3>
                        <ol>
                            <li><?php esc_html_e( 'Meta Business Suite\'e gidin: business.facebook.com', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Events Manager > Data Sources > Pixels', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( '"Add" butonuna tıklayıp yeni pixel oluşturun', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Pixel ID\'nizi kopyalayın', 'trackify-capi' ); ?></li>
                        </ol>
                        
                        <h3><?php esc_html_e( '2. Access Token Alma', 'trackify-capi' ); ?></h3>
                        <ol>
                            <li><?php esc_html_e( 'Events Manager > Settings sekmesine gidin', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Conversions API bölümünü bulun', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( '"Generate Access Token" tıklayın', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Token\'ı güvenli bir yere kaydedin', 'trackify-capi' ); ?></li>
                        </ol>
                        
                        <h3><?php esc_html_e( '3. Trackify CAPI Kurulumu', 'trackify-capi' ); ?></h3>
                        <ol>
                            <li><?php esc_html_e( 'Trackify CAPI > Ayarlar sayfasına gidin', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( '"Yeni Pixel Ekle" butonuna tıklayın', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Pixel ID ve Access Token\'ı girin', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Pixel\'i aktif edin ve kaydedin', 'trackify-capi' ); ?></li>
                        </ol>
                        
                        <h3><?php esc_html_e( '4. Event\'leri Etkinleştirme', 'trackify-capi' ); ?></h3>
                        <ol>
                            <li><?php esc_html_e( 'Ayarlar sayfasında Event Ayarları bölümüne gidin', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Track etmek istediğiniz event\'leri seçin', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Her event için Pixel ve/veya CAPI seçin', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Ayarları kaydedin', 'trackify-capi' ); ?></li>
                        </ol>
                        
                        <div class="trackify-info-box">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php esc_html_e( 'Test Modu', 'trackify-capi' ); ?></strong>
                                <p><?php esc_html_e( 'İlk kurulumda Test Modu\'nu aktif edin. Event\'ler Meta Events Manager\'da "Test Events" sekmesinde görünecektir.', 'trackify-capi' ); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Event Türleri -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-tag"></span>
                            <?php esc_html_e( 'Event Türleri', 'trackify-capi' ); ?>
                        </h2>
                        
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Event', 'trackify-capi' ); ?></th>
                                    <th><?php esc_html_e( 'Ne Zaman Tetiklenir', 'trackify-capi' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>PageView</strong></td>
                                    <td><?php esc_html_e( 'Her sayfa yüklendiğinde', 'trackify-capi' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>ViewContent</strong></td>
                                    <td><?php esc_html_e( 'Ürün veya içerik detay sayfası görüntülendiğinde', 'trackify-capi' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>AddToCart</strong></td>
                                    <td><?php esc_html_e( 'Sepete ürün eklendiğinde', 'trackify-capi' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>InitiateCheckout</strong></td>
                                    <td><?php esc_html_e( 'Ödeme sürecine başlandığında', 'trackify-capi' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Purchase</strong></td>
                                    <td><?php esc_html_e( 'Sipariş tamamlandığında (en önemli event)', 'trackify-capi' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Lead</strong></td>
                                    <td><?php esc_html_e( 'Form gönderildiğinde (CF7, WPForms, vb.)', 'trackify-capi' ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>CompleteRegistration</strong></td>
                                    <td><?php esc_html_e( 'Kullanıcı kaydı tamamlandığında', 'trackify-capi' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- SSS -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-editor-help"></span>
                            <?php esc_html_e( 'Sık Sorulan Sorular (SSS)', 'trackify-capi' ); ?>
                        </h2>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'Pixel ve CAPI arasındaki fark nedir?', 'trackify-capi' ); ?></strong></summary>
                            <p><?php esc_html_e( 'Pixel (client-side): Kullanıcının tarayıcısından Meta\'ya veri gönderir. Ad blocker\'lar ve cookie kısıtlamaları etkileyebilir.', 'trackify-capi' ); ?></p>
                            <p><?php esc_html_e( 'CAPI (server-side): Sunucunuzdan direkt Meta\'ya veri gönderir. Daha güvenilir ve doğrudur.', 'trackify-capi' ); ?></p>
                            <p><?php esc_html_e( 'En iyi sonuçlar için ikisini birlikte kullanın!', 'trackify-capi' ); ?></p>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'Event ID neden önemli?', 'trackify-capi' ); ?></strong></summary>
                            <p><?php esc_html_e( 'Event ID, aynı event\'in Pixel ve CAPI tarafından çift gönderilmesini (deduplication) önler. Meta, aynı Event ID\'ye sahip event\'leri sadece bir kere sayar.', 'trackify-capi' ); ?></p>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'Test event\'leri nasıl kontrol ederim?', 'trackify-capi' ); ?></strong></summary>
                            <p><?php esc_html_e( 'Meta Events Manager > Test Events sekmesine gidin. Test modundaki event\'ler burada gerçek zamanlı görünür.', 'trackify-capi' ); ?></p>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'Event\'ler neden Meta\'da görünmüyor?', 'trackify-capi' ); ?></strong></summary>
                            <ol>
                                <li><?php esc_html_e( 'Pixel ID ve Access Token\'ın doğru olduğundan emin olun', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Test modunu aktif edip Test Events\'te kontrol edin', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Event Logs sayfasından hata mesajlarını kontrol edin', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Sunucunuzun Meta API\'ye erişebildiğinden emin olun', 'trackify-capi' ); ?></li>
                            </ol>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'EMQ (Event Match Quality) nasıl iyileştirilir?', 'trackify-capi' ); ?></strong></summary>
                            <ol>
                                <li><?php esc_html_e( 'Hem Pixel hem de CAPI kullanın', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Müşteri bilgilerinin (email, telefon) hash\'lenmesini sağlayın', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'FBP ve FBC cookie\'lerinin düzgün çalıştığından emin olun', 'trackify-capi' ); ?></li>
                                <li><?php esc_html_e( 'Event ID\'leri Pixel ve CAPI arasında eşleştirin', 'trackify-capi' ); ?></li>
                            </ol>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'WooCommerce Purchase event\'i çalışmıyor?', 'trackify-capi' ); ?></strong></summary>
                            <p><?php esc_html_e( 'WooCommerce entegrasyonunun aktif olduğundan ve Purchase event\'inin etkin olduğundan emin olun. Ayrıca thank you page\'in düzgün yüklendiğini kontrol edin.', 'trackify-capi' ); ?></p>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'Form event\'leri track edilmiyor?', 'trackify-capi' ); ?></strong></summary>
                            <p><?php esc_html_e( 'Contact Form 7, WPForms veya Gravity Forms eklentisinin yüklü ve aktif olduğundan emin olun. Lead event\'inin etkin olması gerekir.', 'trackify-capi' ); ?></p>
                        </details>
                        
                        <details>
                            <summary><strong><?php esc_html_e( 'Loglar ne kadar süre saklanır?', 'trackify-capi' ); ?></strong></summary>
                            <p><?php esc_html_e( 'Varsayılan olarak 30 gün. Eski loglar otomatik olarak silinir. Bu süreyi değiştirmek için Araçlar sayfasını kullanabilirsiniz.', 'trackify-capi' ); ?></p>
                        </details>
                    </div>
                </div>
                
                <!-- Sağ Kolon -->
                <div>
                    <!-- Video Tutorials -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-video-alt3"></span>
                            <?php esc_html_e( 'Video Eğitimler', 'trackify-capi' ); ?>
                        </h2>
                        
                        <div style="margin-bottom: 15px;">
                            <h4><?php esc_html_e( '1. Kurulum ve İlk Ayarlar', 'trackify-capi' ); ?></h4>
                            <a href="#" class="button" target="_blank">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <?php esc_html_e( 'Videoyu İzle', 'trackify-capi' ); ?>
                            </a>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <h4><?php esc_html_e( '2. WooCommerce Entegrasyonu', 'trackify-capi' ); ?></h4>
                            <a href="#" class="button" target="_blank">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <?php esc_html_e( 'Videoyu İzle', 'trackify-capi' ); ?>
                            </a>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <h4><?php esc_html_e( '3. Event Test Etme', 'trackify-capi' ); ?></h4>
                            <a href="#" class="button" target="_blank">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <?php esc_html_e( 'Videoyu İzle', 'trackify-capi' ); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Yararlı Linkler -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e( 'Yararlı Linkler', 'trackify-capi' ); ?>
                        </h2>
                        
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 10px;">
                                <a href="https://business.facebook.com/events_manager2" target="_blank">
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
                                <a href="https://www.facebook.com/business/help/218844828315224" target="_blank">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e( 'Pixel Kurulum Rehberi', 'trackify-capi' ); ?>
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
                    
                    <!-- Destek -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-sos"></span>
                            <?php esc_html_e( 'Destek Al', 'trackify-capi' ); ?>
                        </h2>
                        
                        <p><?php esc_html_e( 'Sorun mu yaşıyorsunuz? Yardıma mı ihtiyacınız var?', 'trackify-capi' ); ?></p>
                        
                        <p>
                            <a href="https://wordpress.org/support/plugin/trackify-capi/" target="_blank" class="button button-primary">
                                <span class="dashicons dashicons-sos"></span>
                                <?php esc_html_e( 'Destek Forumu', 'trackify-capi' ); ?>
                            </a>
                        </p>
                        
                        <p>
                            <a href="mailto:support@trackify-capi.com" class="button">
                                <span class="dashicons dashicons-email"></span>
                                <?php esc_html_e( 'Email Desteği', 'trackify-capi' ); ?>
                            </a>
                        </p>
                    </div>
                    
                    <!-- Changelog -->
                    <div class="trackify-settings-section">
                        <h2>
                            <span class="dashicons dashicons-list-view"></span>
                            <?php esc_html_e( 'Son Güncellemeler', 'trackify-capi' ); ?>
                        </h2>
                        
                        <h4>v2.0.0 - 2024-01-15</h4>
                        <ul>
                            <li><?php esc_html_e( 'Tamamen yeniden yazıldı', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Gelişmiş event tracking', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'WooCommerce desteği', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Form entegrasyonları', 'trackify-capi' ); ?></li>
                            <li><?php esc_html_e( 'Detaylı analytics', 'trackify-capi' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}