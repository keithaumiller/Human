<?php

namespace Drupal\gdp_explorer\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for GDP Explorer visualization.
 */
class GdpExplorerController extends ControllerBase {

  /**
   * Display the GDP Explorer page.
   *
   * @return array
   *   A Drupal render array for the GDP Explorer page.
   */
  public function overview(): array {
    return [
      '#theme' => 'gdp_explorer',
      '#attached' => [
        'library' => [
          'gdp_explorer/gdp_explorer',
        ],
      ],
    ];
  }

}