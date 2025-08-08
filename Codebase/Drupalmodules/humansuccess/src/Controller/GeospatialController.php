<?php

namespace Drupal\humansuccess\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\humansuccess\Service\H3GeospatialService;
use Drupal\humansuccess\Service\PopulationDataService;
use Drupal\humansuccess\Service\QualityOfLifeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for geospatial and H3 mapping functionality.
 */
class GeospatialController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The H3 geospatial service.
   *
   * @var \Drupal\humansuccess\Service\H3GeospatialService
   */
  protected $h3Service;

  /**
   * The population data service.
   *
   * @var \Drupal\humansuccess\Service\PopulationDataService
   */
  protected $populationService;

  /**
   * The quality of life service.
   *
   * @var \Drupal\humansuccess\Service\QualityOfLifeService
   */
  protected $qolService;

  /**
   * Constructs a GeospatialController object.
   *
   * @param \Drupal\humansuccess\Service\H3GeospatialService $h3_service
   *   The H3 geospatial service.
   * @param \Drupal\humansuccess\Service\PopulationDataService $population_service
   *   The population data service.
   * @param \Drupal\humansuccess\Service\QualityOfLifeService $qol_service
   *   The quality of life service.
   */
  public function __construct(
    H3GeospatialService $h3_service,
    PopulationDataService $population_service,
    QualityOfLifeService $qol_service
  ) {
    $this->h3Service = $h3_service;
    $this->populationService = $population_service;
    $this->qolService = $qol_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('humansuccess.h3_geospatial'),
      $container->get('humansuccess.population_data'),
      $container->get('humansuccess.quality_of_life')
    );
  }

  /**
   * Displays the H3 population mapping interface.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for the H3 population map.
   */
  public function h3PopulationMap(Request $request) {
    // Get query parameters for map configuration
    $resolution = $request->query->get('resolution', 6);
    $bounds = $request->query->get('bounds');
    
    // Parse bounds if provided
    $parsed_bounds = [];
    if ($bounds) {
      $bounds_array = explode(',', $bounds);
      if (count($bounds_array) === 4) {
        $parsed_bounds = array_map('floatval', $bounds_array);
      }
    }

    // Get global population overview for initial display
    $population_overview = $this->h3Service->getGlobalPopulationOverview([4, 6, 8]);

    $build = [
      '#theme' => 'humansuccess_h3_map',
      '#population_overview' => $population_overview,
      '#default_resolution' => $resolution,
      '#default_bounds' => $parsed_bounds,
      '#attached' => [
        'library' => [
          'humansuccess/h3_mapping',
          'humansuccess/leaflet',
          'humansuccess/h3_js',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'h3Map' => [
              'apiEndpoint' => '/human-success/api/h3-data',
              'defaultResolution' => $resolution,
              'defaultBounds' => $parsed_bounds,
              'populationOverview' => $population_overview,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for H3 population data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with H3 population data.
   */
  public function getH3Data(Request $request) {
    $resolution = $request->query->get('resolution', 6);
    $bounds = $request->query->get('bounds');
    $filters = $request->query->get('filters', []);

    // Parse bounds
    $parsed_bounds = [];
    if ($bounds) {
      $bounds_array = explode(',', $bounds);
      if (count($bounds_array) === 4) {
        $parsed_bounds = array_map('floatval', $bounds_array);
      }
    }

    // Parse filters if JSON string
    if (is_string($filters)) {
      $filters = json_decode($filters, TRUE) ?: [];
    }

    // Get H3 population data
    $h3_data = $this->h3Service->getPopulationMapping($parsed_bounds, $resolution, $filters);

    // Generate visualization data
    $visualization_data = $this->h3Service->generateVisualizationData($h3_data, [
      'color_scheme' => $request->query->get('color_scheme', 'YlOrRd'),
      'legend_steps' => $request->query->get('legend_steps', 7),
    ]);

    return new JsonResponse($visualization_data);
  }

  /**
   * Displays the population forecasting interface.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for population forecasting.
   */
  public function populationForecasting(Request $request) {
    // Get initial forecast data for global view
    $global_forecast = $this->populationService->generateForecast('global', 'world', 10);
    $global_trends = $this->populationService->getGlobalTrends('10years');

    $build = [
      '#theme' => 'humansuccess_population_forecasting',
      '#global_forecast' => $global_forecast,
      '#global_trends' => $global_trends,
      '#attached' => [
        'library' => [
          'humansuccess/time_series_charts',
          'humansuccess/d3',
          'humansuccess/chart_js',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'forecasting' => [
              'apiEndpoint' => '/human-success/api/forecast-data',
              'globalForecast' => $global_forecast,
              'globalTrends' => $global_trends,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for forecast data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with forecast data.
   */
  public function getForecastData(Request $request) {
    $region_type = $request->query->get('region_type', 'country');
    $region_id = $request->query->get('region_id', '');
    $forecast_years = $request->query->get('forecast_years', 10);
    $external_regressors = $request->query->get('regressors', []);

    if (is_string($external_regressors)) {
      $external_regressors = json_decode($external_regressors, TRUE) ?: [];
    }

    $forecast_data = $this->populationService->generateForecast(
      $region_type,
      $region_id,
      $forecast_years,
      $external_regressors
    );

    return new JsonResponse($forecast_data);
  }

  /**
   * Displays the quality of life optimization interface.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for QoL optimization.
   */
  public function qualityOfLifeOptimization(Request $request) {
    // Get initial optimization analysis for global view
    $global_qol = $this->qolService->calculateQualityOfLifeIndex('global', 'world');
    
    $optimization_analysis = $this->qolService->performOptimizationAnalysis(
      [], // Empty metrics will use defaults
      'maximize_wellbeing',
      [],
      'global',
      'world'
    );

    $build = [
      '#theme' => 'humansuccess_qol_optimization',
      '#global_qol' => $global_qol,
      '#optimization_analysis' => $optimization_analysis,
      '#attached' => [
        'library' => [
          'humansuccess/qol_analysis',
          'humansuccess/d3',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'qolOptimization' => [
              'apiEndpoint' => '/human-success/api/qol-data',
              'globalQoL' => $global_qol,
              'optimizationAnalysis' => $optimization_analysis,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for QoL optimization data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with QoL optimization data.
   */
  public function getQoLData(Request $request) {
    $region_type = $request->query->get('region_type', 'global');
    $region_id = $request->query->get('region_id', 'world');
    $optimization_target = $request->query->get('target', 'maximize_wellbeing');
    $metrics = $request->query->get('metrics', []);
    $constraints = $request->query->get('constraints', []);

    if (is_string($metrics)) {
      $metrics = json_decode($metrics, TRUE) ?: [];
    }
    if (is_string($constraints)) {
      $constraints = json_decode($constraints, TRUE) ?: [];
    }

    $qol_data = $this->qolService->performOptimizationAnalysis(
      $metrics,
      $optimization_target,
      $constraints,
      $region_type,
      $region_id
    );

    return new JsonResponse($qol_data);
  }

  /**
   * Displays the global monitoring dashboard.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for global monitoring.
   */
  public function globalMonitoring(Request $request) {
    // Get real-time monitoring data
    $monitoring_indicators = [
      'population_growth',
      'quality_of_life_index',
      'resource_utilization',
      'environmental_health',
      'economic_stability',
    ];

    // This would typically come from a real-time data service
    $monitoring_data = [
      'indicators' => $monitoring_indicators,
      'last_updated' => date('c'),
      'global_status' => 'stable',
      'alerts' => [],
      'trends' => [],
    ];

    $build = [
      '#theme' => 'humansuccess_global_monitoring',
      '#monitoring_data' => $monitoring_data,
      '#attached' => [
        'library' => [
          'humansuccess/realtime_monitoring',
          'humansuccess/websocket',
          'humansuccess/d3',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'globalMonitoring' => [
              'apiEndpoint' => '/human-success/api/monitoring-data',
              'websocketEndpoint' => 'ws://localhost:8080/monitoring',
              'indicators' => $monitoring_indicators,
              'monitoringData' => $monitoring_data,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for real-time monitoring data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with monitoring data.
   */
  public function getMonitoringData(Request $request) {
    $indicators = $request->query->get('indicators', []);
    
    if (is_string($indicators)) {
      $indicators = json_decode($indicators, TRUE) ?: [];
    }

    // Get real-time data from backend service
    // For now, return mock data structure
    $monitoring_data = [
      'timestamp' => date('c'),
      'indicators' => $indicators,
      'data' => [],
      'alerts' => [],
      'status' => 'active',
    ];

    return new JsonResponse($monitoring_data);
  }

}
