/**
 * Trackify CAPI - Setup Wizard JavaScript
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Setup Wizard Module
     */
    const TrackifySetupWizard = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.validateForms();
            this.animateSteps();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Site type selection
            $('.site-type-card input[type="radio"]').on('change', function() {
                $('.site-type-card').removeClass('selected');
                $(this).closest('.site-type-card').addClass('selected');
            });
            
            // Event selection
            $('.event-item input[type="checkbox"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).closest('.event-item').addClass('selected');
                } else {
                    $(this).closest('.event-item').removeClass('selected');
                }
            });
            
            // Form validation
            $('form').on('submit', this.handleFormSubmit.bind(this));
            
            // Pixel ID validation
            $('input[name="pixel_id"]').on('blur', this.validatePixelId);
            
            // Access Token validation
            $('input[name="access_token"]').on('blur', this.validateAccessToken);
        },
        
        /**
         * Handle form submit
         */
        handleFormSubmit: function(e) {
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('button[type="submit"]');
            
            // Check if form is valid
            if (!$form[0].checkValidity()) {
                return true; // Let browser handle validation
            }
            
            // Disable submit button
            $submitButton.prop('disabled', true);
            
            // Show loading state
            const originalText = $submitButton.html();
            $submitButton.html('<span class="dashicons dashicons-update spin"></span> ' + this.getLoadingText());
            
            // Allow form to submit
            return true;
        },
        
        /**
         * Get loading text
         */
        getLoadingText: function() {
            const texts = {
                '2': 'Kaydediliyor...',
                '3': 'Pixel doğrulanıyor...',
                '4': 'Event\'ler yapılandırılıyor...',
                '5': 'Kurulum tamamlanıyor...'
            };
            
            const step = $('input[name="trackify_setup_step"]').val();
            return texts[step] || 'Yükleniyor...';
        },
        
        /**
         * Validate pixel ID
         */
        validatePixelId: function() {
            const $input = $(this);
            const value = $input.val().trim();
            
            // Remove any non-digit characters
            const pixelId = value.replace(/\D/g, '');
            
            if (pixelId && pixelId.length >= 15) {
                $input.val(pixelId);
                $input.removeClass('invalid').addClass('valid');
                TrackifySetupWizard.showFieldSuccess($input, 'Pixel ID geçerli görünüyor');
            } else if (value) {
                $input.addClass('invalid').removeClass('valid');
                TrackifySetupWizard.showFieldError($input, 'Pixel ID 15 haneli bir sayı olmalı');
            }
        },
        
        /**
         * Validate access token
         */
        validateAccessToken: function() {
            const $input = $(this);
            const value = $input.val().trim();
            
            if (value && value.length > 20 && value.startsWith('EA')) {
                $input.removeClass('invalid').addClass('valid');
                TrackifySetupWizard.showFieldSuccess($input, 'Access Token formatı doğru');
            } else if (value) {
                $input.addClass('invalid').removeClass('valid');
                TrackifySetupWizard.showFieldError($input, 'Access Token "EA" ile başlamalı ve yeterince uzun olmalı');
            }
        },
        
        /**
         * Show field error
         */
        showFieldError: function($input, message) {
            // Remove existing messages
            $input.next('.field-message').remove();
            
            // Add error message
            $input.after('<div class="field-message error" style="color: #dc3545; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        },
        
        /**
         * Show field success
         */
        showFieldSuccess: function($input, message) {
            // Remove existing messages
            $input.next('.field-message').remove();
            
            // Add success message
            $input.after('<div class="field-message success" style="color: #28a745; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        },
        
        /**
         * Validate forms
         */
        validateForms: function() {
            // Add real-time validation
            $('input[required]').on('input', function() {
                const $input = $(this);
                
                if ($input.val().trim()) {
                    $input.removeClass('invalid');
                }
            });
        },
        
        /**
         * Animate steps
         */
        animateSteps: function() {
            // Fade in current step
            $('.trackify-setup-step').hide().fadeIn(600);
            
            // Animate features
            $('.trackify-features .feature').each(function(index) {
                $(this).css('opacity', 0).delay(index * 100).animate({
                    opacity: 1
                }, 400);
            });
            
            // Animate site type cards
            $('.site-type-card').each(function(index) {
                $(this).css('opacity', 0).delay(index * 80).animate({
                    opacity: 1
                }, 400);
            });
            
            // Animate events
            $('.event-item').each(function(index) {
                $(this).css('opacity', 0).delay(index * 60).animate({
                    opacity: 1
                }, 400);
            });
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('.trackify-setup').length) {
            TrackifySetupWizard.init();
        }
    });
    
    /**
     * CSS for spinning icon
     */
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        input.valid {
            border-color: #28a745 !important;
        }
        input.invalid {
            border-color: #dc3545 !important;
        }
    `;
    document.head.appendChild(style);
    
})(jQuery);