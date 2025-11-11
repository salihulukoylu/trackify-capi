/**
 * Trackify CAPI - Analytics Page JavaScript
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Analytics Module
     */
    const TrackifyCAPIAnalytics = {
        
        /**
         * Chart instances
         */
        charts: {},
        
        /**
         * Initialize
         */
        init: function() {
            this.initDateRangePicker();
            this.loadCharts();
            this.bindEvents();
            this.initRefresh();
        },
        
        /**
         * Init date range picker
         */
        initDateRangePicker: function() {
            // Auto-submit on change
            $('select[name="days"]').on('change', function() {
                $(this).closest('form').submit();
            });
        },
        
        /**
         * Load charts
         */
        loadCharts: function() {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.warn('[Trackify CAPI] Chart.js not loaded');
                return;
            }
            
            // Event trend chart
            this.loadEventTrendChart();
            
            // Event distribution pie chart
            this.loadEventDistributionChart();
            
            // Success rate chart
            this.loadSuccessRateChart();
        },
        
        /**
         * Load event trend chart
         */
        loadEventTrendChart: function() {
            const canvas = document.getElementById('trackify-events-chart');
            
            if (!canvas) {
                return;
            }
            
            // Chart is already initialized in HTML
            // This function can be used for dynamic updates
        },
        
        /**
         * Load event distribution chart
         */
        loadEventDistributionChart: function() {
            const canvas = document.getElementById('trackify-distribution-chart');
            
            if (!canvas) {
                return;
            }
            
            // Get data from table
            const labels = [];
            const data = [];
            const colors = [
                '#0073aa', '#28a745', '#dc3545', '#ffc107', 
                '#17a2b8', '#6f42c1', '#fd7e14', '#20c997'
            ];
            
            $('.trackify-event-breakdown tbody tr').each(function(index) {
                const eventName = $(this).find('td:first strong').text();
                const total = parseInt($(this).find('td:eq(1)').text().replace(/,/g, '')) || 0;
                
                if (total > 0) {
                    labels.push(eventName);
                    data.push(total);
                }
            });
            
            if (data.length === 0) {
                return;
            }
            
            this.charts.distribution = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value.toLocaleString() + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Load success rate chart
         */
        loadSuccessRateChart: function() {
            const canvas = document.getElementById('trackify-success-chart');
            
            if (!canvas) {
                return;
            }
            
            // Get data from table
            const labels = [];
            const successData = [];
            const failData = [];
            
            $('.trackify-event-breakdown tbody tr').each(function() {
                const eventName = $(this).find('td:first strong').text();
                const successful = parseInt($(this).find('td:eq(2)').text().replace(/,/g, '')) || 0;
                const failed = parseInt($(this).find('td:eq(3)').text().replace(/,/g, '')) || 0;
                
                labels.push(eventName);
                successData.push(successful);
                failData.push(failed);
            });
            
            this.charts.success = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Başarılı',
                            data: successData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: '#28a745',
                            borderWidth: 2
                        },
                        {
                            label: 'Başarısız',
                            data: failData,
                            backgroundColor: 'rgba(220, 53, 69, 0.7)',
                            borderColor: '#dc3545',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    }
                }
            });
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Export button
            $('.trackify-export-analytics').on('click', this.exportAnalytics.bind(this));
            
            // Print button
            $('.trackify-print-analytics').on('click', function() {
                window.print();
            });
            
            // Chart type toggle
            $('.trackify-chart-toggle').on('click', function(e) {
                e.preventDefault();
                const chartType = $(this).data('chart-type');
                TrackifyCAPIAnalytics.toggleChartType(chartType);
            });
        },
        
        /**
         * Export analytics
         */
        exportAnalytics: function() {
            // Create CSV content
            let csv = 'Event,Total,Successful,Failed,Success Rate\n';
            
            $('.trackify-event-breakdown tbody tr').each(function() {
                const eventName = $(this).find('td:first strong').text();
                const total = $(this).find('td:eq(1)').text();
                const successful = $(this).find('td:eq(2)').text();
                const failed = $(this).find('td:eq(3)').text();
                const successRate = $(this).find('td:eq(4) .trackify-success-badge').text();
                
                csv += `"${eventName}",${total},${successful},${failed},${successRate}\n`;
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'trackify-analytics-' + Date.now() + '.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        /**
         * Toggle chart type
         */
        toggleChartType: function(chartType) {
            const canvas = document.getElementById('trackify-events-chart');
            
            if (!canvas || !this.charts.trend) {
                return;
            }
            
            // Destroy existing chart
            this.charts.trend.destroy();
            
            // Create new chart with different type
            const currentData = this.charts.trend.data;
            
            this.charts.trend = new Chart(canvas, {
                type: chartType,
                data: currentData,
                options: this.getChartOptions(chartType)
            });
        },
        
        /**
         * Get chart options by type
         */
        getChartOptions: function(chartType) {
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            };
            
            if (chartType === 'bar') {
                baseOptions.scales = {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                };
            }
            
            return baseOptions;
        },
        
        /**
         * Init auto refresh
         */
        initRefresh: function() {
            // Auto refresh every 5 minutes
            setInterval(function() {
                location.reload();
            }, 300000); // 5 minutes
        },
        
        /**
         * Format number
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        
        /**
         * Calculate percentage
         */
        calculatePercentage: function(value, total) {
            if (total === 0) {
                return 0;
            }
            return Math.round((value / total) * 100 * 10) / 10;
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('.trackify-capi-admin').length && window.location.href.indexOf('trackify-capi-analytics') > -1) {
            TrackifyCAPIAnalytics.init();
        }
    });
    
    /**
     * Expose to global scope
     */
    window.TrackifyCAPIAnalytics = TrackifyCAPIAnalytics;
    
})(jQuery);