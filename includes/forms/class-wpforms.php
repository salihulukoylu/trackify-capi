<?php
/**
 * WPForms Integration
 * 
 * WPForms form gönderimlerini takip eder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Wpforms extends Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = 'wpforms';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = 'WPForms';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // Form gönderildiğinde (server-side)
        add_action( 'wpforms_process_complete', array( $this, 'track_form_submission' ), 10, 4 );
        
        // Client-side tracking için script ekle
        add_action( 'wp_footer', array( $this, 'add_client_side_tracking_script' ) );
    }
    
    /**
     * Check if plugin is active
     * 
     * @return bool
     */
    protected function is_plugin_active() {
        return defined( 'WPFORMS_VERSION' );
    }
    
    /**
     * Form gönderimi track et (server-side)
     * 
     * @param array $fields Form fields
     * @param array $entry Entry data
     * @param array $form_data Form data
     * @param int $entry_id Entry ID
     */
    public function track_form_submission( $fields, $entry, $form_data, $entry_id ) {
        $form_id = $form_data['id'];
        $form_title = ! empty( $form_data['settings']['form_title'] ) ? 
                      $form_data['settings']['form_title'] : 
                      'WPForms #' . $form_id;
        
        // Form data'yı düzle
        $flat_data = array();
        
        foreach ( $fields as $field_id => $field ) {
            // Field label veya name al
            $field_name = '';
            if ( ! empty( $field['name'] ) ) {
                $field_name = $field['name'];
            } elseif ( ! empty( $field_data['fields'][ $field_id ]['label'] ) ) {
                $field_name = $form_data['fields'][ $field_id ]['label'];
            }
            
            // Field value al
            $field_value = '';
            if ( ! empty( $field['value'] ) ) {
                $field_value = $field['value'];
            }
            
            // Boş değilse ekle
            if ( ! empty( $field_name ) && ! empty( $field_value ) ) {
                // Array ise string'e çevir
                if ( is_array( $field_value ) ) {
                    $field_value = implode( ', ', $field_value );
                }
                
                // Field name'i normalize et
                $normalized_name = sanitize_key( $field_name );
                $flat_data[ $normalized_name ] = $field_value;
            }
        }
        
        // Lead event gönder
        $this->track_lead( $form_title, $flat_data );
        
        $this->debug_log( 'WPForms submitted', array(
            'form_id' => $form_id,
            'form_title' => $form_title,
            'entry_id' => $entry_id,
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
        
        // Sadece WPForms var olan sayfalarda
        global $post;
        
        if ( ! $post ) {
            return;
        }
        
        // Check if page has wpforms shortcode or block
        $has_wpform = false;
        
        if ( has_shortcode( $post->post_content, 'wpforms' ) || 
             strpos( $post->post_content, 'wp:wpforms/form-selector' ) !== false ) {
            $has_wpform = true;
        }
        
        if ( ! $has_wpform ) {
            return;
        }
        
        ?>
        <script>
        (function($) {
            'use strict';
            
            // WPForms submission success event
            $(document).on('wpformsAjaxSubmitSuccess', function(event, data) {
                if (typeof fbq === 'undefined') {
                    return;
                }
                
                // Get form title
                var formTitle = 'WPForms';
                var formId = '';
                
                if (data && data.form_id) {
                    formId = data.form_id;
                    formTitle = 'WPForms #' + formId;
                }
                
                // Track Lead event
                fbq('track', 'Lead', {
                    content_name: formTitle,
                    content_category: 'form_submission',
                    form_type: 'wpforms',
                    form_id: formId
                });
                
                console.log('[Trackify CAPI] WPForms Lead tracked (client-side):', formTitle);
            });
            
            // Alternative: Check for success message insertion
            $(document).on('DOMNodeInserted', function(e) {
                if (typeof fbq === 'undefined') {
                    return;
                }
                
                var $target = $(e.target);
                
                // Check if it's a WPForms success message
                if ($target.hasClass('wpforms-confirmation-container-full')) {
                    var $form = $target.closest('.wpforms-container');
                    var formId = '';
                    
                    if ($form.length) {
                        var formIdMatch = $form.attr('id');
                        if (formIdMatch) {
                            formId = formIdMatch.replace('wpforms-', '');
                        }
                    }
                    
                    var formTitle = formId ? 'WPForms #' + formId : 'WPForms';
                    
                    fbq('track', 'Lead', {
                        content_name: formTitle,
                        content_category: 'form_submission',
                        form_type: 'wpforms',
                        form_id: formId
                    });
                    
                    console.log('[Trackify CAPI] WPForms Lead tracked (client-side, fallback):', formTitle);
                }
            });
            
        })(jQuery);
        </script>
        <?php
    }
}