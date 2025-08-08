/**
 * @file
 * JavaScript for the Human Success dashboard.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Dashboard functionality.
   */
  Drupal.behaviors.humansuccessDashboard = {
    attach: function (context, settings) {
      $('.humansuccess-dashboard', context).once('dashboard-init').each(function () {
        var $dashboard = $(this);
        
        // Animate stat cards on load
        $('.stat-card', $dashboard).each(function (index) {
          var $card = $(this);
          setTimeout(function () {
            $card.addClass('animated');
          }, index * 100);
        });

        // Add click tracking for quick actions
        $('.btn', $dashboard).on('click', function () {
          var action = $(this).text();
          console.log('Dashboard action clicked: ' + action);
          
          // You can add analytics tracking here
          if (typeof gtag !== 'undefined') {
            gtag('event', 'dashboard_action', {
              'action_name': action
            });
          }
        });

        // Auto-refresh recent activities every 5 minutes
        setInterval(function () {
          // In a real implementation, this would make an AJAX call
          // to refresh the activities list
          console.log('Refreshing recent activities...');
        }, 300000); // 5 minutes

        // Add hover effects to activity items
        $('.activity-item', $dashboard).hover(
          function () {
            $(this).addClass('highlighted');
          },
          function () {
            $(this).removeClass('highlighted');
          }
        );
      });
    }
  };

  /**
   * Initialize dashboard on document ready.
   */
  $(document).ready(function () {
    // Add any initialization code here
    console.log('Human Success Dashboard initialized');
  });

})(jQuery, Drupal);
