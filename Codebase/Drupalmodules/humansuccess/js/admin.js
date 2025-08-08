/**
 * @file
 * JavaScript for the Human Success admin dashboard.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Admin dashboard functionality.
   */
  Drupal.behaviors.humansuccessAdmin = {
    attach: function (context, settings) {
      $('.humansuccess-admin', context).once('admin-init').each(function () {
        var $admin = $(this);
        
        // Initialize status monitoring
        initializeStatusMonitoring($admin);
        
        // Auto-refresh system metrics every 30 seconds
        setInterval(function () {
          refreshSystemMetrics($admin);
        }, 30000);

        // Add confirmation dialogs for critical actions
        $('.admin-action[href*="delete"], .admin-action[href*="reset"]', $admin).on('click', function (e) {
          e.preventDefault();
          
          var $action = $(this);
          var actionName = $('h3', $action).text();
          
          if (confirm('Are you sure you want to ' + actionName.toLowerCase() + '? This action cannot be undone.')) {
            window.location.href = $action.attr('href');
          }
        });

        // Add loading states for admin actions
        $('.admin-action', $admin).on('click', function () {
          var $action = $(this);
          $action.addClass('loading');
          
          // Add a small delay to show the loading state
          setTimeout(function () {
            // The page will navigate, so this might not execute
            $action.removeClass('loading');
          }, 500);
        });

        // Initialize real-time log monitoring
        initializeLogMonitoring($admin);
      });
    }
  };

  /**
   * Initialize status monitoring.
   */
  function initializeStatusMonitoring($container) {
    var $statusCard = $('.status-card', $container);
    
    // Check status every minute
    setInterval(function () {
      // In a real implementation, make an AJAX call to check system status
      checkSystemStatus($statusCard);
    }, 60000);
  }

  /**
   * Check system status via AJAX.
   */
  function checkSystemStatus($statusCard) {
    // Placeholder for AJAX status check
    $.ajax({
      url: '/admin/human-success/status',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        var $statusValue = $('.status-value', $statusCard);
        $statusValue.text(data.status);
        
        // Update status class
        $statusCard.removeClass('status-good status-warning status-error');
        if (data.status === 'Operational') {
          $statusCard.addClass('status-good');
        } else if (data.status === 'Warning') {
          $statusCard.addClass('status-warning');
        } else {
          $statusCard.addClass('status-error');
        }
      },
      error: function () {
        console.log('Failed to check system status');
      }
    });
  }

  /**
   * Refresh system metrics.
   */
  function refreshSystemMetrics($container) {
    var $metrics = $('.system-metrics', $container);
    
    // Placeholder for AJAX metrics refresh
    $.ajax({
      url: '/admin/human-success/metrics',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        $('.metric-item', $metrics).each(function () {
          var $metric = $(this);
          var label = $('.metric-label', $metric).text();
          var key = label.toLowerCase().replace(/\s+/g, '_');
          
          if (data[key]) {
            $('.metric-value', $metric).text(data[key]);
          }
        });
      },
      error: function () {
        console.log('Failed to refresh system metrics');
      }
    });
  }

  /**
   * Initialize log monitoring.
   */
  function initializeLogMonitoring($container) {
    var $logList = $('.log-list', $container);
    
    // Check for new logs every 2 minutes
    setInterval(function () {
      // In a real implementation, fetch new logs via AJAX
      fetchNewLogs($logList);
    }, 120000);
  }

  /**
   * Fetch new logs via AJAX.
   */
  function fetchNewLogs($logList) {
    $.ajax({
      url: '/admin/human-success/logs',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        if (data.logs && data.logs.length > 0) {
          // Add new logs to the top of the list
          data.logs.forEach(function (log) {
            var $newLog = $('<li class="log-item new-log">' + log + '</li>');
            $logList.prepend($newLog);
            
            // Highlight new log briefly
            setTimeout(function () {
              $newLog.removeClass('new-log');
            }, 3000);
          });
          
          // Keep only the latest 10 logs
          $('.log-item:gt(9)', $logList).remove();
        }
      },
      error: function () {
        console.log('Failed to fetch new logs');
      }
    });
  }

  /**
   * Notification system for admin alerts.
   */
  Drupal.behaviors.humansuccessNotifications = {
    attach: function (context, settings) {
      // Check for admin notifications on page load
      if (settings.humansuccess && settings.humansuccess.notifications) {
        settings.humansuccess.notifications.forEach(function (notification) {
          showNotification(notification.message, notification.type);
        });
      }
    }
  };

  /**
   * Show notification message.
   */
  function showNotification(message, type) {
    type = type || 'info';
    
    var $notification = $('<div class="admin-notification ' + type + '">' + message + '</div>');
    $('body').append($notification);
    
    // Auto-remove after 5 seconds
    setTimeout(function () {
      $notification.fadeOut(function () {
        $notification.remove();
      });
    }, 5000);
  }

})(jQuery, Drupal);
