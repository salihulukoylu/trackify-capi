/**
 * Trackify CAPI - Admin JavaScript
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    // Document ready
    $(document).ready(function() {
        TrackifyCAPIAdmin.init();
    });
    
    /**
     * Admin JavaScript Module
     */
    const TrackifyCAPIAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.pixelCards();
            this.eventToggles();
            this.testEvent();
            this.clearLogs();
            this.exportSettings();
            this.importSettings();
            this.toggleAdvanced();
        },
        
        /**
         * Pixel card interactions
         */
        pixelCards: function() {
            // Add new pixel
            $('.trackify-add-pixel').on('click', function(e) {
                e.preventDefault();
                // Clone pixel card template
                const template = $('.trackify-pixel-card-template').clone();
                template.removeClass('trackify-pixel-card-template').show();
                $('.trackify-pixel-cards').append(template);
            });
            
            // Remove pixel
            $(document).on('click', '.trackify-remove-pixel', function(e) {
                e.preventDefault();
                if (confirm('Bu pixel\'i silmek istediğinizden emin misiniz?')) {
                    $(this).closest('.trackify-pixel-card').remove();
                }
            });
            
            // Toggle pixel
            $(document).on('change', '.trackify-pixel-toggle', function() {
                const card = $(this).closest('.trackify-pixel-card');
                if ($(this).is(':checked')) {
                    card.addClass('active').removeClass('inactive');
                } else {
                    card.removeClass('active').addClass('inactive');
                }
            });
        },
        
        /**
         * Event toggle interactions
         */
        eventToggles: function() {
            $('.trackify-event-master-toggle').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.trackify-event-item input[type="checkbox"]').prop('checked', isChecked);
            });
            
            // Update master toggle
            $(document).on('change', '.trackify-event-item input[type="checkbox"]', function() {
                const total = $('.trackify-event-item input[type="checkbox"]').length;
                const checked = $('.trackify-event-item input[type="checkbox"]:checked').length;
                $('.trackify-event-master-toggle').prop('checked', total === checked);
            });
        },
        
        /**
         * Test event gönder
         */
        testEvent: function() {
            $('.trackify-send-test-event').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const originalText = $button.text();
                
                $button.prop('disabled', true).text('Gönderiliyor...');
                
                $.ajax({
                    url: trackifyCAPIAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'trackify_send_test_event',
                        nonce: trackifyCAPIAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Test event başarıyla gönderildi!\n\nEvent ID: ' + response.data.event_id + '\n\nMeta Events Manager\'da kontrol edebilirsiniz.');
                        } else {
                            alert('❌ Hata: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('❌ AJAX hatası oluştu.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text(originalText);
                    }
                });
            });
        },
        
        /**
         * Clear logs
         */
        clearLogs: function() {
            $('.trackify-clear-logs').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Tüm logları silmek istediğinizden emin misiniz?')) {
                    return;
                }
                
                const $button = $(this);
                $button.prop('disabled', true);
                
                $.ajax({
                    url: trackifyCAPIAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'trackify_clear_logs',
                        nonce: trackifyCAPIAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Hata oluştu!');
                            $button.prop('disabled', false);
                        }
                    }
                });
            });
        },
        
        /**
         * Export settings
         */
        exportSettings: function() {
            $('.trackify-export-settings').on('click', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: trackifyCAPIAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'trackify_export_settings',
                        nonce: trackifyCAPIAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // JSON dosyası olarak indir
                            const blob = new Blob([response.data.json], {type: 'application/json'});
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'trackify-capi-settings-' + Date.now() + '.json';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            window.URL.revokeObjectURL(url);
                        } else {
                            alert('Export hatası!');
                        }
                    }
                });
            });
        },
        
        /**
         * Import settings
         */
        importSettings: function() {
            $('.trackify-import-settings').on('click', function(e) {
                e.preventDefault();
                $('#trackify-import-file').click();
            });
            
            $('#trackify-import-file').on('change', function(e) {
                const file = e.target.files[0];
                
                if (!file) {
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const json = e.target.result;
                    
                    $.ajax({
                        url: trackifyCAPIAdmin.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'trackify_import_settings',
                            nonce: trackifyCAPIAdmin.nonce,
                            json: json
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ Ayarlar başarıyla içe aktarıldı!');
                                location.reload();
                            } else {
                                alert('❌ Import hatası: ' + response.data.message);
                            }
                        }
                    });
                };
                
                reader.readAsText(file);
            });
        },
        
        /**
         * Toggle advanced settings
         */
        toggleAdvanced: function() {
            $('.trackify-toggle-advanced').on('click', function(e) {
                e.preventDefault();
                $('.trackify-advanced-settings').slideToggle();
                $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
            });
        }
    };
    
})(jQuery);