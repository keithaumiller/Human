<?php

namespace Drupal\humansuccess\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * Controller for Human Success module pages.
 */
class HumansuccessController extends ControllerBase {

  /**
   * Returns the main dashboard page.
   *
   * @return array
   *   A render array for the dashboard page.
   */
  public function dashboard() {
    $build = [
      '#theme' => 'humansuccess_dashboard',
      '#data' => $this->getDashboardData(),
      '#attached' => [
        'library' => [
          'humansuccess/dashboard',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Returns the metrics page.
   *
   * @return array
   *   A render array for the metrics page.
   */
  public function metrics() {
    $metrics_data = $this->getMetricsData();

    $build = [
      '#theme' => 'humansuccess_metrics',
      '#metrics' => $metrics_data,
      '#attached' => [
        'library' => [
          'humansuccess/metrics',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Returns the admin dashboard page.
   *
   * @return array
   *   A render array for the admin dashboard.
   */
  public function adminDashboard() {
    $admin_data = $this->getAdminData();

    $build = [
      '#theme' => 'humansuccess_admin',
      '#admin_data' => $admin_data,
      '#attached' => [
        'library' => [
          'humansuccess/admin',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Get dashboard data.
   *
   * @return array
   *   Array of dashboard data.
   */
  private function getDashboardData() {
    // Sample data - replace with actual data retrieval logic
    return [
      'total_users' => 150,
      'active_goals' => 45,
      'completed_achievements' => 320,
      'success_rate' => 78.5,
      'recent_activities' => [
        'John completed "Fitness Goal"',
        'Sarah achieved "Learning Milestone"',
        'Mike started "Career Development"',
      ],
    ];
  }

  /**
   * Get metrics data.
   *
   * @return array
   *   Array of metrics data.
   */
  private function getMetricsData() {
    // Sample metrics data
    return [
      'monthly_progress' => [
        'January' => 65,
        'February' => 72,
        'March' => 68,
        'April' => 81,
        'May' => 79,
        'June' => 85,
      ],
      'category_breakdown' => [
        'Health & Fitness' => 35,
        'Career Development' => 28,
        'Personal Growth' => 22,
        'Education' => 15,
      ],
      'user_engagement' => [
        'daily_active' => 45,
        'weekly_active' => 120,
        'monthly_active' => 150,
      ],
    ];
  }

  /**
   * Get admin data.
   *
   * @return array
   *   Array of admin data.
   */
  private function getAdminData() {
    // Sample admin data
    return [
      'system_status' => 'Operational',
      'pending_reviews' => 12,
      'flagged_content' => 3,
      'system_metrics' => [
        'uptime' => '99.9%',
        'response_time' => '0.25s',
        'error_rate' => '0.1%',
      ],
      'recent_logs' => [
        'User registration spike detected',
        'System backup completed successfully',
        'Performance optimization applied',
      ],
    ];
  }

}