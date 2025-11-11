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
        add_action( 'wpforms_frontend_output_success', array( $this, 'add_client_side_tracking' ), 10, 3 );
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
        $form_title = ! empty( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : 'WPForms #' . $form_id;
        
        // Form data'yı düzle
        $flat_data = array();
        
        foreach ( $fields as $field_id => $field ) {
            if ( ! empty( $field['name'] ) && ! empty( $field['value'] ) ) {
                // Field ismini normalize et
                $field_name = sanitize_key( $field['name'] );
                $flat_data[ $field_name ] = $field['value'];
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
     * 
     * @param array $form_data
     * @param array $fields
     * @param int $entry_id
     */
    public function add_client_side_tracking( $form_data, $fields, $entry_id ) {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        
        $form_title = ! empty( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : 'WPForms #' . $form_data['id'];
        ?>
        <script>
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Lead', {
                content_name: '<?php echo esc_js( $form_title ); ?>',
                content_category: 'form_submission',
                form_type: 'wpforms'
            });
            
            console.log('[Trackify CAPI] WPForms Lead tracked (client-side):', '<?php echo esc_js( $form_title ); ?>');
        }
        </script>
        <?php
    }
}