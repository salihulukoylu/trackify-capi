<?php
/**
 * Fluent Forms Integration
 * 
 * Fluent Forms form gönderimlerini takip eder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Fluent_Forms extends Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = 'fluent_forms';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = 'Fluent Forms';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // Form gönderildiğinde (server-side)
        add_action( 'fluentform_submission_inserted', array( $this, 'track_form_submission' ), 10, 3 );
        
        // Client-side tracking için
        add_action( 'wp_footer', array( $this, 'add_client_side_tracking_script' ) );
    }
    
    /**
     * Check if plugin is active
     * 
     * @return bool
     */
    protected function is_plugin_active() {
        return defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' );
    }
    
    /**
     * Form gönderimi track et (server-side)
     * 
     * @param int $entryId Entry ID
     * @param array $formData Form data
     * @param object $form Form object
     */
    public function track_form_submission( $entryId, $formData, $form ) {
        // Get form ID
        $form_id = $form->id;
        
        // Get form title
        $form_title = ! empty( $form->title ) ? $form->title : 'Fluent Forms #' . $form_id;
        
        // Form data'yı düzle
        $flat_data = array();
        
        if ( is_array( $formData ) ) {
            foreach ( $formData as $field_name => $field_value ) {
                // Skip internal fields
                if ( strpos( $field_name, '__' ) === 0 || strpos( $field_name, '_' ) === 0 ) {
                    continue;
                }
                
                if ( ! empty( $field_value ) ) {
                    // Array ise string'e çevir
                    if ( is_array( $field_value ) ) {
                        $field_value = implode( ', ', $field_value );
                    }
                    
                    // Field name'i normalize et
                    $normalized_name = sanitize_key( $field_name );
                    $flat_data[ $normalized_name ] = $field_value;
                }
            }
        }
        
        // Lead event gönder
        $this->track_lead( $form_title, $flat_data );
        
        $this->debug_log( 'Fluent Forms submitted', array(
            'form_id' => $form_id,
            'form_title' => $form_title,
            'entry_id' => $entryId,
        ) );
    }
    
    /**
     * Client-side tracking script ekle
     */
    public function add_client_side_tracking_script() {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        
        // Sadece Fluent Forms var olan sayfalarda
        global $post;
        
        if ( ! $post || ! has_shortcode( $post->post_content, 'fluentform' ) ) {
            return;
        }
        
        ?>
        <script>
        (function($) {
            'use strict';
            
            // Fluent Forms jQuery event
            $(document).on('fluentform_submission_success', function(event, data, form) {
                if (typeof fbq === 'undefined') {
                    return;
                }
                
                // Get form title
                var formTitle = 'Fluent Forms';
                var formId = '';
                
                if (data && data.form) {
                    formTitle = data.form.title || formTitle;
                    formId = data.form.id || '';
                }
                
                // Track Lead event
                fbq('track', 'Lead', {
                    content_name: formTitle,
                    content_category: 'form_submission',
                    form_type: 'fluent_forms',
                    form_id: formId
                });
                
                console.log('[Trackify CAPI] Fluent Forms Lead tracked (client-side):', formTitle);
            });
            
            // Alternative: native JS event
            document.addEventListener('fluentform_submission_success', function(event) {
                if (typeof fbq === 'undefined') {
                    return;
                }
                
                var detail = event.detail || {};
                var formTitle = 'Fluent Forms';
                var formId = '';
                
                if (detail.form) {
                    formTitle = detail.form.title || formTitle;
                    formId = detail.form.id || '';
                }
                
                fbq('track', 'Lead', {
                    content_name: formTitle,
                    content_category: 'form_submission',
                    form_type: 'fluent_forms',
                    form_id: formId
                });
                
                console.log('[Trackify CAPI] Fluent Forms Lead tracked (client-side):', formTitle);
            });
            
        })(jQuery);
        </script>
        <?php
    }
}