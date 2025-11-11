/**
 * Trackify CAPI - Enhanced Frontend Pixel Events
 * 
 * @package Trackify_CAPI
 * @version 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Main Trackify CAPI Frontend Module
     */
    const TrackifyCAPIPixel = {
        
        /**
         * Config
         */
        config: {
            pixelId: trackifyCAPI.pixelId || '',
            pixels: trackifyCAPI.pixels || [],
            restUrl: trackifyCAPI.restUrl || '',
            nonce: trackifyCAPI.nonce || '',
            debug: trackifyCAPI.debug || false,
            hasWooCommerce: trackifyCAPI.hasWooCommerce === 'yes',
        },
        
        /**
         * Initialize
         */
        init: function() {
            this.log('Trackify CAPI Frontend initialized', this.config);
            
            // Wait for fbq to load
            this.waitForPixel(() => {
                this.log('Meta Pixel loaded successfully');
                
                // Initialize WooCommerce tracking
                if (this.config.hasWooCommerce) {
                    this.initWooCommerceTracking();
                }
                
                // Initialize form tracking
                this.initFormTracking();
                
                // Initialize scroll tracking
                this.initScrollTracking();
                
                // Initialize click tracking
                this.initClickTracking();
            });
        },
        
        /**
         * Wait for pixel to load
         */
        waitForPixel: function(callback) {
            let attempts = 0;
            const maxAttempts = 30; // 6 seconds
            
            const check = setInterval(() => {
                attempts++;
                
                if (typeof fbq !== 'undefined') {
                    clearInterval(check);
                    callback();
                } else if (attempts >= maxAttempts) {
                    clearInterval(check);
                    this.log('Meta Pixel failed to load', 'error');
                }
            }, 200);
        },
        
        /**
         * Initialize WooCommerce tracking
         */
        initWooCommerceTracking: function() {
            this.log('WooCommerce tracking initialized');
            
            // AJAX Add to Cart
            $(document.body).on('added_to_cart', (event, fragments, cart_hash, $button) => {
                this.trackAddToCart($button);
            });
            
            // Single product Add to Cart
            $('.single_add_to_cart_button').on('click', (e) => {
                const $button = $(e.currentTarget);
                
                if (!$button.hasClass('disabled')) {
                    setTimeout(() => {
                        this.trackAddToCart($button);
                    }, 500);
                }
            });
            
            // Remove from cart
            $(document.body).on('click', '.remove_from_cart_button', (e) => {
                const $button = $(e.currentTarget);
                this.trackRemoveFromCart($button);
            });
        },
        
        /**
         * Track Add to Cart
         */
        trackAddToCart: function($button) {
            const productId = $button.data('product_id') || $('input[name="product_id"]').val();
            const productName = $button.data('product_name') || $('.product_title').text().trim();
            const productPrice = parseFloat($button.data('product_price') || $('.woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, ''));
            const quantity = parseInt($button.data('quantity') || $('input[name="quantity"]').val() || 1);
            
            if (!productId) {
                this.log('AddToCart: Product ID not found', 'warn');
                return;
            }
            
            const eventData = {
                content_ids: [String(productId)],
                content_type: 'product',
                content_name: productName,
                value: productPrice * quantity,
                currency: this.getCurrency(),
                num_items: quantity
            };
            
            const eventId = this.generateEventId('atc', productId);
            
            this.trackEvent('AddToCart', eventData, eventId);
        },
        
        /**
         * Track Remove from Cart
         */
        trackRemoveFromCart: function($button) {
            const productId = $button.data('product_id');
            
            if (!productId) return;
            
            const eventData = {
                content_ids: [String(productId)],
                content_type: 'product'
            };
            
            const eventId = this.generateEventId('rfc', productId);
            
            this.trackCustomEvent('RemoveFromCart', eventData, eventId);
        },
        
        /**
         * Initialize form tracking
         */
        initFormTracking: function() {
            this.log('Form tracking initialized');
            
            // Contact Form 7
            document.addEventListener('wpcf7mailsent', (event) => {
                this.trackLead('Contact Form 7', event.detail.contactFormId);
            });
            
            // WPForms
            $(document).on('wpformsAjaxSubmitSuccess', (event, data) => {
                this.trackLead('WPForms', data.form_id);
            });
            
            // Gravity Forms
            $(document).on('gform_confirmation_loaded', (event, formId) => {
                this.trackLead('Gravity Forms', formId);
            });
            
            // Generic forms (fallback)
            $('form').on('submit', (e) => {
                const $form = $(e.currentTarget);
                const formId = $form.attr('id') || 'generic_form';
                
                // Skip if already tracked by specific plugin
                if (!$form.hasClass('wpcf7-form') && !$form.hasClass('wpforms-form') && !$form.hasClass('gform')) {
                    setTimeout(() => {
                        this.trackLead('Generic Form', formId);
                    }, 1000);
                }
            });
        },
        
        /**
         * Track Lead
         */
        trackLead: function(formName, formId) {
            const eventData = {
                content_name: formName + (formId ? ' #' + formId : ''),
                content_category: 'form_submission'
            };
            
            const eventId = this.generateEventId('lead', formId || 'form');
            
            this.trackEvent('Lead', eventData, eventId);
        },
        
        /**
         * Initialize scroll tracking
         */
        initScrollTracking: function() {
            const milestones = [25, 50, 75, 100];
            const tracked = [];
            
            let ticking = false;
            
            $(window).on('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        const scrollPercent = this.getScrollPercent();
                        
                        milestones.forEach(milestone => {
                            if (scrollPercent >= milestone && !tracked.includes(milestone)) {
                                tracked.push(milestone);
                                this.trackCustomEvent('ScrollDepth', {
                                    scroll_depth: milestone,
                                    page_url: window.location.href
                                }, this.generateEventId('scroll', milestone));
                            }
                        });
                        
                        ticking = false;
                    });
                    
                    ticking = true;
                }
            });
        },
        
        /**
         * Get scroll percentage
         */
        getScrollPercent: function() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const trackLength = documentHeight - windowHeight;
            const percent = Math.floor((scrollTop / trackLength) * 100);
            
            return Math.min(percent, 100);
        },
        
        /**
         * Initialize click tracking
         */
        initClickTracking: function() {
            // Track important CTA clicks
            $('a[href^="tel:"], a[href^="mailto:"]').on('click', (e) => {
                const $link = $(e.currentTarget);
                const href = $link.attr('href');
                const type = href.startsWith('tel:') ? 'phone' : 'email';
                
                this.trackCustomEvent('Contact', {
                    contact_type: type,
                    contact_value: href
                }, this.generateEventId('contact', type));
            });
            
            // Track external links
            $('a[target="_blank"]').not('[href*="' + location.hostname + '"]').on('click', (e) => {
                const $link = $(e.currentTarget);
                const href = $link.attr('href');
                
                this.trackCustomEvent('OutboundClick', {
                    link_url: href,
                    link_text: $link.text().trim()
                }, this.generateEventId('outbound', href));
            });
        },
        
        /**
         * Track standard event
         */
        trackEvent: function(eventName, eventData, eventId) {
            if (typeof fbq === 'undefined') {
                this.log('fbq not available', 'error');
                return;
            }
            
            try {
                fbq('track', eventName, eventData, {
                    eventID: eventId
                });
                
                this.log('Event tracked: ' + eventName, {eventId, eventData});
            } catch (error) {
                this.log('Error tracking event: ' + error.message, 'error');
            }
        },
        
        /**
         * Track custom event
         */
        trackCustomEvent: function(eventName, eventData, eventId) {
            if (typeof fbq === 'undefined') {
                this.log('fbq not available', 'error');
                return;
            }
            
            try {
                fbq('trackCustom', eventName, eventData, {
                    eventID: eventId
                });
                
                this.log('Custom event tracked: ' + eventName, {eventId, eventData});
            } catch (error) {
                this.log('Error tracking custom event: ' + error.message, 'error');
            }
        },
        
        /**
         * Generate event ID
         */
        generateEventId: function(prefix, identifier) {
            const timestamp = Date.now();
            const random = Math.floor(Math.random() * 10000);
            return prefix + '_' + (identifier || '') + '_' + timestamp + '_' + random;
        },
        
        /**
         * Get currency
         */
        getCurrency: function() {
            return trackifyCAPI.currency || 'USD';
        },
        
        /**
         * Debug log
         */
        log: function(message, data, level = 'info') {
            if (this.config.debug || window.trackifyCAPIDebug) {
                const styles = {
                    info: 'color: #0073aa',
                    warn: 'color: #f0b849',
                    error: 'color: #dc3232'
                };
                
                console.log(
                    '%c[Trackify CAPI] ' + message,
                    styles[level] || styles.info,
                    data || ''
                );
            }
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        TrackifyCAPIPixel.init();
    });
    
    /**
     * Expose to global scope for manual usage
     */
    window.TrackifyCAPI = window.TrackifyCAPI || {};
    window.TrackifyCAPI.trackEvent = TrackifyCAPIPixel.trackEvent.bind(TrackifyCAPIPixel);
    window.TrackifyCAPI.trackCustomEvent = TrackifyCAPIPixel.trackCustomEvent.bind(TrackifyCAPIPixel);
    window.TrackifyCAPI.generateEventId = TrackifyCAPIPixel.generateEventId.bind(TrackifyCAPIPixel);
    
})(jQuery);