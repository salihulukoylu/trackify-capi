<?php
/**
 * Settings API
 * 
 * Plugin ayarlarını yönetir ve erişim sağlar
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Settings {
    
    /**
     * Ayarlar option key
     * 
     * @var string
     */
    private $option_key = 'trackify_capi_settings';
    
    /**
     * Ayarlar cache
     * 
     * @var array
     */
    private $settings = null;
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        // Ayarları yükle
        $this->load_settings();
        
        // Hook'lar
        add_action( 'init', array( $this, 'register_settings' ) );
    }
    
    /**
     * Ayarları kaydet
     * 
     * @since 2.0.0
     */
    public function register_settings() {
        register_setting(
            'trackify_capi_settings_group',
            $this->option_key,
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
            )
        );
    }
    
    /**
     * Ayarları yükle
     * 
     * @since 2.0.0
     */
    private function load_settings() {
        if ( is_null( $this->settings ) ) {
            $this->settings = get_option( $this->option_key, array() );
        }
    }
    
    /**
     * Tüm ayarları getir
     * 
     * @return array
     */
    public function get_all() {
        return $this->settings;
    }
    
    /**
     * Ayar getir
     * 
     * @param string $key Ayar anahtarı (nokta notasyonu destekler: 'pixels.0.pixel_id')
     * @param mixed $default Varsayılan değer
     * @return mixed
     */
    public function get( $key, $default = null ) {
        // Nokta notasyonu ile nested array erişimi
        if ( strpos( $key, '.' ) !== false ) {
            $keys = explode( '.', $key );
            $value = $this->settings;
            
            foreach ( $keys as $k ) {
                if ( ! isset( $value[ $k ] ) ) {
                    return $default;
                }
                $value = $value[ $k ];
            }
            
            return $value;
        }
        
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }
    
    /**
     * Ayar kaydet
     * 
     * @param string $key Ayar anahtarı
     * @param mixed $value Değer
     * @return bool
     */
    public function set( $key, $value ) {
        // Nokta notasyonu ile nested array
        if ( strpos( $key, '.' ) !== false ) {
            $keys = explode( '.', $key );
            $settings = &$this->settings;
            
            foreach ( $keys as $k ) {
                if ( ! isset( $settings[ $k ] ) ) {
                    $settings[ $k ] = array();
                }
                $settings = &$settings[ $k ];
            }
            
            $settings = $value;
        } else {
            $this->settings[ $key ] = $value;
        }
        
        return update_option( $this->option_key, $this->settings );
    }
    
    /**
     * Birden fazla ayarı kaydet
     * 
     * @param array $settings
     * @return bool
     */
    public function update( $settings ) {
        $this->settings = array_merge( $this->settings, $settings );
        return update_option( $this->option_key, $this->settings );
    }
    
    /**
     * Ayarları temizle ve doğrula
     * 
     * @param array $settings
     * @return array
     */
    public function sanitize_settings( $settings ) {
        $sanitized = array();
        
        // Genel ayarlar
        $sanitized['enabled'] = ! empty( $settings['enabled'] );
        $sanitized['debug_mode'] = ! empty( $settings['debug_mode'] );
        $sanitized['test_mode'] = ! empty( $settings['test_mode'] );
        
        // Pixel'ler
        if ( ! empty( $settings['pixels'] ) && is_array( $settings['pixels'] ) ) {
            $sanitized['pixels'] = array();
            
            foreach ( $settings['pixels'] as $pixel ) {
                $sanitized_pixel = array(
                    'name' => sanitize_text_field( $pixel['name'] ?? '' ),
                    'pixel_id' => sanitize_text_field( $pixel['pixel_id'] ?? '' ),
                    'access_token' => sanitize_text_field( $pixel['access_token'] ?? '' ),
                    'test_event_code' => sanitize_text_field( $pixel['test_event_code'] ?? '' ),
                    'enabled' => ! empty( $pixel['enabled'] ),
                );
                
                // Boş pixel_id varsa ekleme
                if ( ! empty( $sanitized_pixel['pixel_id'] ) ) {
                    $sanitized['pixels'][] = $sanitized_pixel;
                }
            }
        }
        
        // CAPI ayarları
        $sanitized['capi_enabled'] = ! empty( $settings['capi_enabled'] );
        $sanitized['api_version'] = sanitize_text_field( $settings['api_version'] ?? 'v18.0' );
        
        // Event ayarları
        if ( ! empty( $settings['events'] ) && is_array( $settings['events'] ) ) {
            foreach ( $settings['events'] as $event_name => $event_settings ) {
                $sanitized['events'][ $event_name ] = array(
                    'enabled' => ! empty( $event_settings['enabled'] ),
                    'pixel' => ! empty( $event_settings['pixel'] ),
                    'capi' => ! empty( $event_settings['capi'] ),
                );
            }
        }
        
        // Advanced matching
        if ( ! empty( $settings['advanced_matching'] ) ) {
            $sanitized['advanced_matching'] = array(
                'enabled' => ! empty( $settings['advanced_matching']['enabled'] ),
                'hash_email' => ! empty( $settings['advanced_matching']['hash_email'] ),
                'hash_phone' => ! empty( $settings['advanced_matching']['hash_phone'] ),
                'hash_name' => ! empty( $settings['advanced_matching']['hash_name'] ),
                'hash_address' => ! empty( $settings['advanced_matching']['hash_address'] ),
                'capture_fbp' => ! empty( $settings['advanced_matching']['capture_fbp'] ),
                'capture_fbc' => ! empty( $settings['advanced_matching']['capture_fbc'] ),
            );
        }
        
        // Performance ayarları
        if ( ! empty( $settings['performance'] ) ) {
            $sanitized['performance'] = array(
                'use_queue' => ! empty( $settings['performance']['use_queue'] ),
                'queue_size' => absint( $settings['performance']['queue_size'] ?? 10 ),
                'batch_sending' => ! empty( $settings['performance']['batch_sending'] ),
            );
        }
        
        // Logging ayarları
        if ( ! empty( $settings['logging'] ) ) {
            $sanitized['logging'] = array(
                'enabled' => ! empty( $settings['logging']['enabled'] ),
                'log_level' => sanitize_text_field( $settings['logging']['log_level'] ?? 'info' ),
                'retention_days' => absint( $settings['logging']['retention_days'] ?? 30 ),
                'database_logging' => ! empty( $settings['logging']['database_logging'] ),
                'file_logging' => ! empty( $settings['logging']['file_logging'] ),
            );
        }
        
        // Entegrasyonlar
        if ( ! empty( $settings['integrations'] ) ) {
            $sanitized['integrations'] = $settings['integrations'];
        }
        
        return apply_filters( 'trackify_capi_sanitize_settings', $sanitized, $settings );
    }
    
    /**
     * Plugin etkin mi?
     * 
     * @return bool
     */
    public function is_enabled() {
        return ! empty( $this->settings['enabled'] );
    }
    
    /**
     * Debug mode aktif mi?
     * 
     * @return bool
     */
    public function is_debug_mode() {
        return ! empty( $this->settings['debug_mode'] );
    }
    
    /**
     * Test mode aktif mi?
     * 
     * @return bool
     */
    public function is_test_mode() {
        return ! empty( $this->settings['test_mode'] );
    }
    
    /**
     * CAPI etkin mi?
     * 
     * @return bool
     */
    public function is_capi_enabled() {
        return ! empty( $this->settings['capi_enabled'] );
    }
    
    /**
     * Event etkin mi?
     * 
     * @param string $event_name
     * @param string $type 'pixel' veya 'capi'
     * @return bool
     */
    public function is_event_enabled( $event_name, $type = null ) {
        if ( empty( $this->settings['events'][ $event_name ]['enabled'] ) ) {
            return false;
        }
        
        if ( $type === 'pixel' ) {
            return ! empty( $this->settings['events'][ $event_name ]['pixel'] );
        }
        
        if ( $type === 'capi' ) {
            return ! empty( $this->settings['events'][ $event_name ]['capi'] );
        }
        
        return true;
    }
    
    /**
     * Aktif pixel'leri getir
     * 
     * @return array
     */
    public function get_active_pixels() {
        $pixels = $this->get( 'pixels', array() );
        
        return array_filter( $pixels, function( $pixel ) {
            return ! empty( $pixel['enabled'] ) && ! empty( $pixel['pixel_id'] );
        } );
    }
    
    /**
     * İlk pixel'i getir (varsayılan)
     * 
     * @return array|null
     */
    public function get_primary_pixel() {
        $pixels = $this->get_active_pixels();
        return ! empty( $pixels ) ? reset( $pixels ) : null;
    }
    
    /**
     * Advanced matching etkin mi?
     * 
     * @return bool
     */
    public function is_advanced_matching_enabled() {
        return ! empty( $this->settings['advanced_matching']['enabled'] );
    }
    
    /**
     * Export ayarlar (JSON)
     * 
     * @return string
     */
    public function export() {
        $export_data = array(
            'version' => TRACKIFY_CAPI_VERSION,
            'exported_at' => current_time( 'mysql' ),
            'settings' => $this->settings,
        );
        
        return wp_json_encode( $export_data, JSON_PRETTY_PRINT );
    }
    
    /**
     * Import ayarlar (JSON)
     * 
     * @param string $json
     * @return bool|WP_Error
     */
    public function import( $json ) {
        $data = json_decode( $json, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', __( 'Geçersiz JSON formatı', 'trackify-capi' ) );
        }
        
        if ( empty( $data['settings'] ) ) {
            return new WP_Error( 'no_settings', __( 'Ayarlar bulunamadı', 'trackify-capi' ) );
        }
        
        // Ayarları sanitize et ve kaydet
        $sanitized = $this->sanitize_settings( $data['settings'] );
        $this->settings = $sanitized;
        
        return update_option( $this->option_key, $this->settings );
    }
    
    /**
     * Ayarları sıfırla
     * 
     * @return bool
     */
    public function reset() {
        return delete_option( $this->option_key );
    }
}