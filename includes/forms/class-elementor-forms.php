<?php
/**
 * Elementor Forms Integration
 * 
 * Elementor Pro form gönderimlerini takip eder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Elementor_Forms extends Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = 'elementor_forms';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = 'Elementor Forms';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // Form gönderildiğinde (server-side)
        add_action( 'elementor_pro/forms/new_record', array( $this, 'track_form_submission' ), 10, 2 );
        
        // Client-side tracking için
        add_action( 'elementor_pro/forms/process/success', array( $this, 'add_client_side_tracking' ), 10, 2 );
    }
    
    /**
     * Check if plugin is active
     * 
     * @return bool
     */
    protected function is_plugin_active() {
        // Check if Elementor Pro is active with Forms module
        if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
            return false;
        }
        
        // Check if Forms module is active
        if ( ! class_exists( '\ElementorPro\Modules\Forms\Module' ) ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Form gönderimi track et (server-side)
     * 
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function track_form_submission( $record, $ajax_handler ) {
        // Get form name
        $form_name = $record->get_form_settings( 'form_name' );
        
        if ( empty( $form_name ) ) {
            $form_name = 'Elementor Form';
        }
        
        // Get form ID
        $form_id = $record->get_form_settings( 'id' );
        
        // Get form fields
        $raw_fields = $record->get( 'fields' );
        
        // Form data'yı düzle
        $flat_data = array();
        
        foreach ( $raw_fields as $field_id => $field ) {
            $field_label = isset( $field['title'] ) ? $field['title'] : $field_id;
            $field_value = isset( $field['value'] ) ? $field['value'] : '';
            
            if ( ! empty( $field_value ) ) {
                // Field label'ı normalize et
                $field_name = sanitize_key( $field_label );
                
                // Array ise string'e çevir
                if ( is_array( $field_value ) ) {
                    $field_value = implode( ', ', $field_value );
                }
                
                $flat_data[ $field_name ] = $field_value;
            }
        }
        
        // Lead event gönder
        $this->track_lead( $form_name, $flat_data );
        
        $this->debug_log( 'Elementor Form submitted', array(
            'form_id' => $form_id,
            'form_name' => $form_name,
        ) );
    }
    
    /**
     * Client-side tracking script ekle
     * 
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function add_client_side_tracking( $record, $ajax_handler ) {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        
        // Get form name
        $form_name = $record->get_form_settings( 'form_name' );
        
        if ( empty( $form_name ) ) {
            $form_name = 'Elementor Form';
        }
        
        // Get form ID
        $form_id = $record->get_form_settings( 'id' );
        
        // Add inline script to footer
        add_action( 'wp_footer', function() use ( $form_name, $form_id ) {
            ?>
            <script>
            if (typeof fbq !== 'undefined') {
                fbq('track', 'Lead', {
                    content_name: '<?php echo esc_js( $form_name ); ?>',
                    content_category: 'form_submission',
                    form_type: 'elementor_forms',
                    form_id: '<?php echo esc_js( $form_id ); ?>'
                });
                
                console.log('[Trackify CAPI] Elementor Form Lead tracked (client-side):', '<?php echo esc_js( $form_name ); ?>');
            }
            </script>
            <?php
        } );
    }
    
    /**
     * Get form name from settings
     * 
     * @param array $settings
     * @return string
     */
    private function get_form_name( $settings ) {
        if ( ! empty( $settings['form_name'] ) ) {
            return $settings['form_name'];
        }
        
        if ( ! empty( $settings['id'] ) ) {
            return 'Elementor Form #' . $settings['id'];
        }
        
        return 'Elementor Form';
    }
}