<?php

namespace Drupal\humansuccess\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\humansuccess\Service\PopulationDataService;
use Drupal\humansuccess\Service\QualityOfLifeService;
use Drupal\humansuccess\Service\BackendApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for population analytics and forecasting interfaces.
 */
class PopulationAnalyticsController extends ControllerBase implements ContainerInjectionInterface {

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
   * The backend API service.
   *
   * @var \Drupal\humansuccess\Service\BackendApiService
   */
  protected $backendApi;

  /**
   * Constructs a PopulationAnalyticsController object.
   *
   * @param \Drupal\humansuccess\Service\PopulationDataService $population_service
   *   The population data service.
   * @param \Drupal\humansuccess\Service\QualityOfLifeService $qol_service
   *   The quality of life service.
   * @param \Drupal\humansuccess\Service\BackendApiService $backend_api
   *   The backend API service.
   */
  public function __construct(
    PopulationDataService $population_service,
    QualityOfLifeService $qol_service,
    BackendApiService $backend_api
  ) {
    $this->populationService = $population_service;
    $this->qolService = $qol_service;
    $this->backendApi = $backend_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('humansuccess.population_data'),
      $container->get('humansuccess.quality_of_life'),
      $container->get('humansuccess.backend_api')
    );
  }

  /**
   * Displays global population trends dashboard.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for global trends.
   */
  public function globalTrends(Request $request) {
    $timeframe = $request->query->get('timeframe', '10years');
    $metrics = $request->query->get('metrics', []);
    
    if (is_string($metrics)) {
      $metrics = explode(',', $metrics);
    }

    // Get global trends data
    $trends_data = $this->populationService->getGlobalTrends($timeframe, $metrics);
    
    // Get comparative QoL trends
    $qol_trends = $this->qolService->getWellbeingTrends('global', 'world', $timeframe);

    $build = [
      '#theme' => 'humansuccess_global_trends',
      '#trends_data' => $trends_data,
      '#qol_trends' => $qol_trends,
      '#timeframe' => $timeframe,
      '#available_metrics' => $this->getAvailableMetrics(),
      '#attached' => [
        'library' => [
          'humansuccess/population_analysis',
          'humansuccess/d3',
          'humansuccess/chart_js',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'globalTrends' => [
              'apiEndpoint' => '/human-success/api/trends-data',
              'timeframe' => $timeframe,
              'trendsData' => $trends_data,
              'qolTrends' => $qol_trends,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for trends data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with trends data.
   */
  public function getTrendsData(Request $request) {
    $timeframe = $request->query->get('timeframe', '5years');
    $metrics = $request->query->get('metrics', []);
    $region_type = $request->query->get('region_type', 'global');
    $region_id = $request->query->get('region_id', 'world');

    if (is_string($metrics)) {
      $metrics = json_decode($metrics, TRUE) ?: explode(',', $metrics);
    }

    // Get population trends
    $population_trends = $this->populationService->getGlobalTrends($timeframe, $metrics);
    
    // Get QoL trends for comparison
    $qol_trends = $this->qolService->getWellbeingTrends($region_type, $region_id, $timeframe, $metrics);

    $response_data = [
      'population_trends' => $population_trends,
      'qol_trends' => $qol_trends,
      'timeframe' => $timeframe,
      'region' => [
        'type' => $region_type,
        'id' => $region_id,
      ],
      'metrics' => $metrics,
    ];

    return new JsonResponse($response_data);
  }

  /**
   * Displays regional analysis comparison interface.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for regional analysis.
   */
  public function regionalAnalysis(Request $request) {
    // Default regions for comparison
    $default_regions = [
      ['type' => 'country', 'id' => 'US', 'name' => 'United States'],
      ['type' => 'country', 'id' => 'CN', 'name' => 'China'],
      ['type' => 'country', 'id' => 'IN', 'name' => 'India'],
      ['type' => 'country', 'id' => 'BR', 'name' => 'Brazil'],
    ];

    $regions = $request->query->get('regions', $default_regions);
    $comparison_metrics = $request->query->get('metrics', [
      'population_density',
      'growth_rate',
      'quality_of_life_index',
      'economic_indicators',
    ]);

    // Get regional comparison data
    $regional_comparison = $this->populationService->getRegionalComparison($regions, $comparison_metrics, TRUE);
    
    // Get QoL comparison
    $qol_comparison = $this->qolService->compareRegionalQoL($regions, $comparison_metrics, TRUE);

    $build = [
      '#theme' => 'humansuccess_regional_analysis',
      '#regional_comparison' => $regional_comparison,
      '#qol_comparison' => $qol_comparison,
      '#regions' => $regions,
      '#comparison_metrics' => $comparison_metrics,
      '#attached' => [
        'library' => [
          'humansuccess/population_analysis',
          'humansuccess/d3',
          'humansuccess/chart_js',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'regionalAnalysis' => [
              'apiEndpoint' => '/human-success/api/regional-data',
              'regions' => $regions,
              'comparisonMetrics' => $comparison_metrics,
              'regionalComparison' => $regional_comparison,
              'qolComparison' => $qol_comparison,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for regional comparison data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with regional data.
   */
  public function getRegionalData(Request $request) {
    $regions = $request->query->get('regions', []);
    $metrics = $request->query->get('metrics', []);
    $include_forecasts = $request->query->get('include_forecasts', FALSE);

    if (is_string($regions)) {
      $regions = json_decode($regions, TRUE) ?: [];
    }
    if (is_string($metrics)) {
      $metrics = json_decode($metrics, TRUE) ?: [];
    }

    // Get regional analysis
    $regional_analysis = $this->populationService->getRegionalComparison($regions, $metrics, $include_forecasts);
    
    // Get QoL comparison
    $qol_analysis = $this->qolService->compareRegionalQoL($regions, $metrics, TRUE);

    $response_data = [
      'regional_analysis' => $regional_analysis,
      'qol_analysis' => $qol_analysis,
      'regions' => $regions,
      'metrics' => $metrics,
      'include_forecasts' => $include_forecasts,
    ];

    return new JsonResponse($response_data);
  }

  /**
   * Displays multivariate forecasting interface.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for multivariate forecasting.
   */
  public function multivariateForecasting(Request $request) {
    $region_type = $request->query->get('region_type', 'global');
    $region_id = $request->query->get('region_id', 'world');
    $forecast_years = $request->query->get('forecast_years', 10);

    // Default external regressors for multivariate analysis
    $default_regressors = [
      'economic_indicators' => ['gdp_growth', 'unemployment_rate', 'inflation'],
      'environmental_factors' => ['climate_change_index', 'pollution_levels'],
      'social_indicators' => ['education_index', 'healthcare_access'],
      'policy_factors' => ['migration_policy', 'family_planning_policy'],
    ];

    // Get multivariate forecast
    $multivariate_forecast = $this->populationService->getMultivariateForecast(
      $region_type,
      $region_id,
      $default_regressors,
      $forecast_years
    );

    // Get standard forecast for comparison
    $standard_forecast = $this->populationService->generateForecast($region_type, $region_id, $forecast_years);

    $build = [
      '#theme' => 'humansuccess_multivariate_forecasting',
      '#multivariate_forecast' => $multivariate_forecast,
      '#standard_forecast' => $standard_forecast,
      '#regressors' => $default_regressors,
      '#region_type' => $region_type,
      '#region_id' => $region_id,
      '#forecast_years' => $forecast_years,
      '#attached' => [
        'library' => [
          'humansuccess/time_series_charts',
          'humansuccess/d3',
          'humansuccess/chart_js',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'multivariateForecasting' => [
              'apiEndpoint' => '/human-success/api/multivariate-forecast',
              'regionType' => $region_type,
              'regionId' => $region_id,
              'forecastYears' => $forecast_years,
              'regressors' => $default_regressors,
              'multivariateData' => $multivariate_forecast,
              'standardData' => $standard_forecast,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for multivariate forecast data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with multivariate forecast data.
   */
  public function getMultivariateForecastData(Request $request) {
    $region_type = $request->query->get('region_type', 'global');
    $region_id = $request->query->get('region_id', 'world');
    $forecast_years = $request->query->get('forecast_years', 10);
    $regressors = $request->query->get('regressors', []);

    if (is_string($regressors)) {
      $regressors = json_decode($regressors, TRUE) ?: [];
    }

    // Get multivariate forecast
    $multivariate_forecast = $this->populationService->getMultivariateForecast(
      $region_type,
      $region_id,
      $regressors,
      $forecast_years
    );

    return new JsonResponse($multivariate_forecast);
  }

  /**
   * Displays scenario modeling interface.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array for scenario modeling.
   */
  public function scenarioModeling(Request $request) {
    // Default scenarios for demonstration
    $default_scenarios = [
      'baseline' => [
        'name' => 'Baseline Scenario',
        'description' => 'Current trends continue unchanged',
        'parameters' => ['growth_rate' => 'current', 'policy_changes' => 'none'],
      ],
      'optimistic' => [
        'name' => 'Optimistic Growth',
        'description' => 'Improved economic conditions and policies',
        'parameters' => ['growth_rate' => 'increased', 'economic_boost' => 'high'],
      ],
      'conservative' => [
        'name' => 'Conservative Growth',
        'description' => 'Economic challenges and resource constraints',
        'parameters' => ['growth_rate' => 'decreased', 'resource_constraints' => 'high'],
      ],
      'climate_impact' => [
        'name' => 'Climate Change Impact',
        'description' => 'Significant environmental challenges',
        'parameters' => ['climate_stress' => 'high', 'migration_pressure' => 'increased'],
      ],
    ];

    $scenarios = $request->query->get('scenarios', $default_scenarios);
    $model_type = $request->query->get('model_type', 'population');

    // Run scenario modeling
    $scenario_results = $this->backendApi->runScenarioModeling($scenarios, $model_type);

    $build = [
      '#theme' => 'humansuccess_scenario_modeling',
      '#scenarios' => $scenarios,
      '#scenario_results' => $scenario_results,
      '#model_type' => $model_type,
      '#attached' => [
        'library' => [
          'humansuccess/scenario_interface',
          'humansuccess/time_series_charts',
          'humansuccess/d3',
        ],
        'drupalSettings' => [
          'humanSuccess' => [
            'scenarioModeling' => [
              'apiEndpoint' => '/human-success/api/scenario-data',
              'scenarios' => $scenarios,
              'modelType' => $model_type,
              'scenarioResults' => $scenario_results,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * AJAX endpoint for scenario modeling data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with scenario modeling data.
   */
  public function getScenarioData(Request $request) {
    $scenarios = $request->query->get('scenarios', []);
    $model_type = $request->query->get('model_type', 'population');

    if (is_string($scenarios)) {
      $scenarios = json_decode($scenarios, TRUE) ?: [];
    }

    // Run scenario modeling
    $scenario_results = $this->backendApi->runScenarioModeling($scenarios, $model_type);

    $response_data = [
      'scenarios' => $scenarios,
      'model_type' => $model_type,
      'results' => $scenario_results,
      'generated_at' => date('c'),
    ];

    return new JsonResponse($response_data);
  }

  /**
   * Get available metrics for analysis.
   *
   * @return array
   *   Array of available metrics.
   */
  protected function getAvailableMetrics(): array {
    return [
      'population_total' => 'Total Population',
      'population_density' => 'Population Density',
      'growth_rate' => 'Population Growth Rate',
      'birth_rate' => 'Birth Rate',
      'death_rate' => 'Death Rate',
      'migration_rate' => 'Net Migration Rate',
      'age_median' => 'Median Age',
      'dependency_ratio' => 'Dependency Ratio',
      'urbanization_rate' => 'Urbanization Rate',
      'life_expectancy' => 'Life Expectancy',
      'fertility_rate' => 'Fertility Rate',
      'infant_mortality' => 'Infant Mortality Rate',
    ];
  }

}
