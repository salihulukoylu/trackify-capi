<?php
/**
 * Contact Form 7 Integration
 * 
 * Contact Form 7 form gönderimlerini takip eder
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Cf7 extends Trackify_CAPI_Abstract_Form {
    
    /**
     * Form plugin ID
     * 
     * @var string
     */
    protected $id = 'cf7';
    
    /**
     * Form plugin name
     * 
     * @var string
     */
    protected $name = 'Contact Form 7';
    
    /**
     * Hook'ları başlat
     * 
     * @since 2.0.0
     */
    protected function init_hooks() {
        // Form gönderildiğinde
        add_action( 'wpcf7_mail_sent', array( $this, 'track_form_submission' ) );
        
        // Client-side tracking için script ekle
        add_action( 'wp_footer', array( $this, 'add_client_side_tracking' ) );
    }
    
    /**
     * Form gönderimi track et (server-side)
     * 
     * @param WPCF7_ContactForm $contact_form
     */
    public function track_form_submission( $contact_form ) {
        $form_id = $contact_form->id();
        $form_title = $contact_form->title();
        
        // Form data'yı al
        $submission = WPCF7_Submission::get_instance();
        
        if ( ! $submission ) {
            return;
        }
        
        $form_data = $submission->get_posted_data();
        
        // Lead event gönder
        $this->track_lead( $form_title, $form_data );
        
        $this->debug_log( 'CF7 form submitted', array(
            'form_id' => $form_id,
            'form_title' => $form_title,
        ) );
    }
    
    /**
     * Client-side tracking script ekle
     */
    public function add_client_side_tracking() {
        // Pixel etkin değilse çık
        if ( ! $this->settings->get( 'pixels.0.enabled' ) ) {
            return;
        }
        ?>
        <script>
        document.addEventListener('wpcf7mailsent', function(event) {
            if (typeof fbq !== 'undefined') {
                var formTitle = event.detail.contactFormId ? 'CF7 Form ' + event.detail.contactFormId : 'Contact Form 7';
                
                fbq('track', 'Lead', {
                    content_name: formTitle,
                    content_category: 'form_submission'
                });
                
                console.log('[Trackify CAPI] CF7 Lead tracked (client-side):', formTitle);
            }
        }, false);
        </script>
        <?php
    }
}