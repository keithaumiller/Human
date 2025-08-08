/**
 * @file
 * JavaScript for the Human Success metrics page.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Metrics page functionality.
   */
  Drupal.behaviors.humansuccessMetrics = {
    attach: function (context, settings) {
      $('.humansuccess-metrics', context).once('metrics-init').each(function () {
        var $metrics = $(this);
        
        // Animate chart bars
        $('.chart-container .bar', $metrics).each(function (index) {
          var $bar = $(this);
          var height = $bar.css('height');
          $bar.css('height', '0');
          
          setTimeout(function () {
            $bar.animate({
              height: height
            }, 800, 'easeOutQuart');
          }, index * 100);
        });

        // Animate progress bars
        $('.category-bar .progress', $metrics).each(function (index) {
          var $progress = $(this);
          var width = $progress.css('width');
          $progress.css('width', '0');
          
          setTimeout(function () {
            $progress.animate({
              width: width
            }, 1000, 'easeOutQuart');
          }, 500 + (index * 150));
        });

        // Add tooltips to chart bars
        $('.chart-bar', $metrics).each(function () {
          var $bar = $(this);
          var value = $('.bar-value', $bar).text();
          var label = $('.bar-label', $bar).text();
          
          $bar.attr('title', label + ': ' + value);
        });

        // Add click handlers for interactive elements
        $('.engagement-item', $metrics).on('click', function () {
          var metric = $('.engagement-label', this).text();
          var value = $('.engagement-value', this).text();
          
          console.log('Engagement metric clicked: ' + metric + ' = ' + value);
          
          // You could show a detailed view or drill-down here
          if (typeof Drupal.dialog !== 'undefined') {
            // Example of showing additional details
            var $dialog = $('<div><p>Detailed view for ' + metric + ': ' + value + '</p></div>');
            Drupal.dialog($dialog, {
              title: metric + ' Details',
              width: 400,
              height: 300
            }).showModal();
          }
        });

        // Initialize any chart libraries here
        initializeCharts($metrics);
      });
    }
  };

  /**
   * Initialize chart libraries (placeholder for future chart integration).
   */
  function initializeCharts($container) {
    // If using Chart.js, D3, or other charting libraries,
    // initialize them here
    console.log('Initializing charts for metrics page');
    
    // Example: If Chart.js is available
    if (typeof Chart !== 'undefined') {
      // Initialize charts
      $('.chart-container', $container).each(function () {
        // Chart initialization code would go here
      });
    }
  }

  /**
   * Export functionality.
   */
  Drupal.behaviors.humansuccessExport = {
    attach: function (context, settings) {
      $('.btn[href*="export"]', context).once('export-handler').on('click', function (e) {
        e.preventDefault();
        
        // Show loading state
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Exporting...').addClass('loading');
        
        // Simulate export process (replace with actual AJAX call)
        setTimeout(function () {
          $btn.text(originalText).removeClass('loading');
          
          // In a real implementation, trigger the download
          console.log('Export completed');
          
          // You could trigger an actual file download here
          // window.location.href = '/human-success/export/download';
        }, 2000);
      });
    }
  };

})(jQuery, Drupal);
