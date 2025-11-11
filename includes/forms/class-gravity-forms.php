<?php
/**
 * Gravity Forms Integration
 * 
 * Gravity Forms form gönderimlerini takip eder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Gravity_Forms extends Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = 'gravity_forms';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = 'Gravity Forms';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // Form gönderildiğinde (server-side)
        add_action( 'gform_after_submission', array( $this, 'track_form_submission' ), 10, 2 );
        
        // Client-side tracking için
        add_filter( 'gform_confirmation', array( $this, 'add_client_side_tracking' ), 10, 4 );
    }
    
    /**
     * Form gönderimi track et (server-side)
     * 
     * @param array $entry Entry data
     * @param array $form Form data
     */
    public function track_form_submission( $entry, $form ) {
        $form_id = $form['id'];
        $form_title = ! empty( $form['title'] ) ? $form['title'] : 'Gravity Forms #' . $form_id;
        
        // Form data'yı düzle
        $flat_data = array();
        
        foreach ( $form['fields'] as $field ) {
            $field_id = $field->id;
            $field_label = $field->label;
            $field_value = rgar( $entry, $field_id );
            
            if ( ! empty( $field_value ) ) {
                // Field label'ı normalize et
                $field_name = sanitize_key( $field_label );
                $flat_data[ $field_name ] = $field_value;
            }
        }
        
        // Lead event gönder
        $this->track_lead( $form_title, $flat_data );
        
        $this->debug_log( 'Gravity Forms submitted', array(
            'form_id' => $form_id,
            'form_title' => $form_title,
            'entry_id' => $entry['id'],
        ) );
    }
    
    /**
     * Client-side tracking script ekle
     * 
     * @param string|array $confirmation
     * @param array $form
     * @param array $entry
     * @param bool $ajax
     * @return string|array
     */
    public function add_client_side_tracking( $confirmation, $form, $entry, $ajax ) {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return $confirmation;
        }
        
        $form_id = $form['id'];
        $form_title = ! empty( $form['title'] ) ? $form['title'] : 'Gravity Forms #' . $form_id;
        
        // Script oluştur
        $script = sprintf(
            '<script>
            if (typeof fbq !== "undefined") {
                fbq("track", "Lead", {
                    content_name: %s,
                    content_category: "form_submission",
                    form_type: "gravity_forms",
                    form_id: %s
                });
                
                console.log("[Trackify CAPI] Gravity Forms Lead tracked (client-side):", %s);
            }
            </script>',
            wp_json_encode( $form_title ),
            wp_json_encode( $form_id ),
            wp_json_encode( $form_title )
        );
        
        // Confirmation mesajına script ekle
        if ( is_array( $confirmation ) ) {
            // Redirect confirmation
            if ( isset( $confirmation['redirect'] ) ) {
                // Redirect'e script eklenemez, sadece server-side tracking yapılır
                return $confirmation;
            }
            
            // Page confirmation
            if ( isset( $confirmation['pageId'] ) ) {
                // Page'e script ekle
                add_action( 'wp_footer', function() use ( $script ) {
                    echo $script;
                } );
                return $confirmation;
            }
        }
        
        // String confirmation (default)
        if ( is_string( $confirmation ) ) {
            $confirmation .= $script;
        }
        
        return $confirmation;
    }
}