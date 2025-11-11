<?php
/**
 * Ninja Forms Integration
 * 
 * Ninja Forms form gönderimlerini takip eder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Ninja_Forms extends Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = 'ninja_forms';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = 'Ninja Forms';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // Form gönderildiğinde (server-side)
        add_action( 'ninja_forms_after_submission', array( $this, 'track_form_submission' ), 10, 1 );
        
        // Client-side tracking için
        add_action( 'wp_footer', array( $this, 'add_client_side_tracking_script' ) );
    }
    
    /**
     * Check if plugin is active
     * 
     * @return bool
     */
    protected function is_plugin_active() {
        return class_exists( 'Ninja_Forms' ) || function_exists( 'Ninja_Forms' );
    }
    
    /**
     * Form gönderimi track et (server-side)
     * 
     * @param array $form_data Form data
     */
    public function track_form_submission( $form_data ) {
        // Get form ID
        $form_id = isset( $form_data['form_id'] ) ? $form_data['form_id'] : 0;
        
        if ( ! $form_id ) {
            return;
        }
        
        // Get form title
        $form_title = $this->get_form_title( $form_id );
        
        // Form data'yı düzle
        $flat_data = array();
        
        if ( isset( $form_data['fields'] ) && is_array( $form_data['fields'] ) ) {
            foreach ( $form_data['fields'] as $field ) {
                // Get field key and value
                $field_key = isset( $field['key'] ) ? $field['key'] : '';
                $field_value = isset( $field['value'] ) ? $field['value'] : '';
                
                // Skip empty fields
                if ( empty( $field_value ) ) {
                    continue;
                }
                
                // Get field label (try to get from field settings)
                $field_label = $this->get_field_label( $field_key, $form_id );
                
                if ( empty( $field_label ) ) {
                    $field_label = $field_key;
                }
                
                // Array ise string'e çevir
                if ( is_array( $field_value ) ) {
                    $field_value = implode( ', ', $field_value );
                }
                
                // Field name'i normalize et
                $normalized_name = sanitize_key( $field_label );
                $flat_data[ $normalized_name ] = $field_value;
            }
        }
        
        // Lead event gönder
        $this->track_lead( $form_title, $flat_data );
        
        $this->debug_log( 'Ninja Forms submitted', array(
            'form_id' => $form_id,
            'form_title' => $form_title,
        ) );
    }
    
    /**
     * Get form title
     * 
     * @param int $form_id
     * @return string
     */
    private function get_form_title( $form_id ) {
        if ( ! function_exists( 'Ninja_Forms' ) ) {
            return 'Ninja Forms #' . $form_id;
        }
        
        $form = Ninja_Forms()->form( $form_id )->get();
        
        if ( $form && isset( $form->get_settings()['title'] ) ) {
            return $form->get_settings()['title'];
        }
        
        return 'Ninja Forms #' . $form_id;
    }
    
    /**
     * Get field label
     * 
     * @param string $field_key
     * @param int $form_id
     * @return string
     */
    private function get_field_label( $field_key, $form_id ) {
        if ( ! function_exists( 'Ninja_Forms' ) ) {
            return $field_key;
        }
        
        try {
            $form = Ninja_Forms()->form( $form_id )->get();
            
            if ( ! $form ) {
                return $field_key;
            }
            
            $fields = $form->get_fields();
            
            foreach ( $fields as $field ) {
                if ( isset( $field['key'] ) && $field['key'] === $field_key ) {
                    return isset( $field['label'] ) ? $field['label'] : $field_key;
                }
            }
        } catch ( Exception $e ) {
            // Silence is golden
        }
        
        return $field_key;
    }
    
    /**
     * Client-side tracking script ekle
     */
    public function add_client_side_tracking_script() {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        
        // Sadece Ninja Forms var olan sayfalarda
        global $post;
        
        if ( ! $post ) {
            return;
        }
        
        // Check if page has ninja forms shortcode or block
        $has_ninja_form = false;
        
        if ( has_shortcode( $post->post_content, 'ninja_form' ) || 
             has_shortcode( $post->post_content, 'ninja_forms' ) ||
             strpos( $post->post_content, 'wp:ninja-forms/form' ) !== false ) {
            $has_ninja_form = true;
        }
        
        if ( ! $has_ninja_form ) {
            return;
        }
        
        ?>
        <script>
        (function($) {
            'use strict';
            
            // Wait for Ninja Forms to be ready
            var nfCheckInterval = setInterval(function() {
                if (typeof Marionette !== 'undefined' && typeof nfRadio !== 'undefined') {
                    clearInterval(nfCheckInterval);
                    initNinjaFormsTracking();
                }
            }, 100);
            
            function initNinjaFormsTracking() {
                // Listen for form submission success
                var submitChannel = nfRadio.channel('forms');
                
                submitChannel.on('submit:response', function(response, textStatus, jqXHR, formID) {
                    if (typeof fbq === 'undefined') {
                        return;
                    }
                    
                    // Check if submission was successful
                    if (response && response.data && response.data.actions) {
                        var successAction = response.data.actions.success_message || 
                                          response.data.actions.redirect || 
                                          response.data.actions.success;
                        
                        if (successAction) {
                            // Get form title
                            var formTitle = 'Ninja Forms';
                            
                            if (formID) {
                                var formModel = nfRadio.channel('form-' + formID).request('get:form');
                                if (formModel && formModel.get('settings') && formModel.get('settings').title) {
                                    formTitle = formModel.get('settings').title;
                                }
                            }
                            
                            // Track Lead event
                            fbq('track', 'Lead', {
                                content_name: formTitle,
                                content_category: 'form_submission',
                                form_type: 'ninja_forms',
                                form_id: formID || ''
                            });
                            
                            console.log('[Trackify CAPI] Ninja Forms Lead tracked (client-side):', formTitle);
                        }
                    }
                });
            }
            
        })(jQuery);
        </script>
        <?php
    }
}