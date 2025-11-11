<?php
/**
 * Abstract Form Class
 * 
 * Tüm form entegrasyon sınıfları için base class
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = '';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = '';
    
    /**
     * Settings instance
     * 
     * @var Trackify_CAPI_Settings
     */
    protected $settings;
    
    /**
     * CAPI instance
     * 
     * @var Trackify_CAPI_CAPI
     */
    protected $capi;
    
    /**
     * Logger instance
     * 
     * @var Trackify_CAPI_Logger
     */
    protected $logger;
    
    /**
     * Constructor
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->settings = trackify_capi()->get_component( 'settings' );
        $this->capi = trackify_capi()->get_component( 'capi' );
        $this->logger = trackify_capi()->get_component( 'logger' );
        
        // Form tracking etkin mi?
        if ( ! $this->is_enabled() ) {
            return;
        }
        
        // Hook'ları başlat
        $this->init_hooks();
    }
    
    /**
     * Hook'ları başlat (her form plugin override etmeli)
     * 
     * @abstract
     */
    abstract protected function init_hooks();
    
    /**
     * Form ID'yi al
     * 
     * @return string
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Form adını al
     * 
     * @return string
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Form tracking etkin mi?
     * 
     * @return bool
     */
    public function is_enabled() {
        return $this->settings->get( 'integrations.forms.enabled', true );
    }
    
    /**
     * Lead event gönder
     * 
     * @param string $form_name Form adı
     * @param array $form_data Form verisi (opsiyonel)
     * @param array $user_data User data (opsiyonel)
     * @return array|WP_Error
     */
    protected function track_lead( $form_name, $form_data = array(), $user_data = array() ) {
        // Lead event etkin mi?
        if ( ! $this->settings->is_event_enabled( 'Lead', 'capi' ) ) {
            return new WP_Error( 'event_disabled', __( 'Lead event devre dışı', 'trackify-capi' ) );
        }
        
        $event_id = trackify_capi_generate_event_id( 'lead', sanitize_key( $form_name ) );
        
        $custom_data = array(
            'content_name' => $form_name,
            'content_category' => 'form_submission',
            'form_type' => $this->id,
        );
        
        // Form data'dan user data çıkar
        if ( empty( $user_data ) && ! empty( $form_data ) ) {
            $user_data = $this->extract_user_data( $form_data );
        }
        
        $result = $this->capi->send_event( 'Lead', $custom_data, $user_data, $event_id );
        
        $this->debug_log( 'Lead tracked', array(
            'form_name' => $form_name,
            'form_type' => $this->id,
            'event_id' => $event_id,
        ) );
        
        return $result;
    }
    
    /**
     * Form data'dan user data çıkar ve hash'le
     * 
     * @param array $form_data
     * @return array
     */
    protected function extract_user_data( $form_data ) {
        $hasher = new Trackify_CAPI_Data_Hasher();
        $user_data = array();
        
        // Email bul (yaygın field isimleri)
        $email_fields = array( 'email', 'e-mail', 'e_mail', 'user_email', 'your-email', 'your_email' );
        foreach ( $email_fields as $field ) {
            if ( ! empty( $form_data[ $field ] ) ) {
                $user_data['em'] = $hasher->hash_email( $form_data[ $field ] );
                break;
            }
        }
        
        // Telefon bul
        $phone_fields = array( 'phone', 'telephone', 'tel', 'mobile', 'your-phone', 'your_phone' );
        foreach ( $phone_fields as $field ) {
            if ( ! empty( $form_data[ $field ] ) ) {
                $user_data['ph'] = $hasher->hash_phone( $form_data[ $field ] );
                break;
            }
        }
        
        // İsim bul
        $name_fields = array( 'first_name', 'firstname', 'first-name', 'your-name' );
        foreach ( $name_fields as $field ) {
            if ( ! empty( $form_data[ $field ] ) ) {
                $user_data['fn'] = $hasher->hash_text( $form_data[ $field ] );
                break;
            }
        }
        
        // Soyisim bul
        $lastname_fields = array( 'last_name', 'lastname', 'last-name', 'surname' );
        foreach ( $lastname_fields as $field ) {
            if ( ! empty( $form_data[ $field ] ) ) {
                $user_data['ln'] = $hasher->hash_text( $form_data[ $field ] );
                break;
            }
        }
        
        return $user_data;
    }
    
    /**
     * Log kaydet
     * 
     * @param string $message
     * @param array $data
     * @param string $level
     */
    protected function log( $message, $data = array(), $level = 'info' ) {
        $data['form_plugin'] = $this->id;
        $data['message'] = $message;
        
        $this->logger->log( $data, $level );
    }
    
    /**
     * Debug log
     * 
     * @param string $message
     * @param array $data
     */
    protected function debug_log( $message, $data = array() ) {
        if ( $this->settings->is_debug_mode() ) {
            $this->log( $message, $data, 'debug' );
        }
    }
}