<?php

namespace Drupal\humansuccess\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

/**
 * Service for communicating with the Human Success Python FastAPI backend.
 * 
 * Handles all HTTP communication with the backend services including:
 * - H3 geospatial processing
 * - Population forecasting with Meta Prophet
 * - Quality of life optimization
 * - Real-time data synchronization
 */
class BackendApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The backend API base URL.
   *
   * @var string
   */
  protected $apiBaseUrl;

  /**
   * Constructs a BackendApiService object.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The HTTP client factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    ClientFactory $http_client_factory,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client_factory->fromOptions([
      'timeout' => 30,
      'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Drupal-HumanSuccess/1.0',
      ],
    ]);
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('humansuccess');
    
    $config = $this->configFactory->get('humansuccess.settings');
    $this->apiBaseUrl = $config->get('backend_api_url') ?: 'http://localhost:8000/api/v1';
  }

  /**
   * Get H3 population mapping data for specified resolution and region.
   *
   * @param int $resolution
   *   H3 resolution level (0-15).
   * @param array $bounds
   *   Geographic bounds [min_lat, min_lng, max_lat, max_lng].
   * @param array $filters
   *   Optional filters for data processing.
   *
   * @return array|null
   *   H3 hexagon data with population metrics or NULL on failure.
   */
  public function getH3PopulationData(int $resolution = 6, array $bounds = [], array $filters = []): ?array {
    try {
      $params = [
        'resolution' => $resolution,
        'filters' => $filters,
      ];
      
      if (!empty($bounds)) {
        $params['bounds'] = $bounds;
      }

      $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/h3/population', [
        'query' => $params,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('H3 population data retrieved successfully for resolution @resolution', [
        '@resolution' => $resolution,
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to retrieve H3 population data: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get population forecasting data using Meta Prophet.
   *
   * @param string $region_type
   *   Type of region (country, state, h3_hex, etc.).
   * @param string $region_id
   *   Identifier for the specific region.
   * @param int $forecast_years
   *   Number of years to forecast ahead.
   * @param array $external_regressors
   *   External variables for multivariate forecasting.
   *
   * @return array|null
   *   Forecasting results or NULL on failure.
   */
  public function getPopulationForecast(string $region_type, string $region_id, int $forecast_years = 10, array $external_regressors = []): ?array {
    try {
      $payload = [
        'region_type' => $region_type,
        'region_id' => $region_id,
        'forecast_years' => $forecast_years,
        'external_regressors' => $external_regressors,
      ];

      $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/forecasting/population', [
        'json' => $payload,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('Population forecast generated for @region_type:@region_id', [
        '@region_type' => $region_type,
        '@region_id' => $region_id,
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to generate population forecast: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get quality of life optimization analysis.
   *
   * @param array $metrics
   *   QoL metrics to analyze (education, healthcare, economy, environment).
   * @param string $optimization_target
   *   Target for optimization (maximize_wellbeing, balance_resources, etc.).
   * @param array $constraints
   *   Resource or policy constraints.
   *
   * @return array|null
   *   Optimization analysis results or NULL on failure.
   */
  public function getQualityOfLifeOptimization(array $metrics, string $optimization_target = 'maximize_wellbeing', array $constraints = []): ?array {
    try {
      $payload = [
        'metrics' => $metrics,
        'optimization_target' => $optimization_target,
        'constraints' => $constraints,
      ];

      $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/optimization/quality-of-life', [
        'json' => $payload,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('Quality of life optimization analysis completed for target: @target', [
        '@target' => $optimization_target,
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to perform QoL optimization: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get global population trends and analytics.
   *
   * @param string $timeframe
   *   Time period for analysis (1year, 5years, 10years, historical).
   * @param array $metrics
   *   Specific metrics to include in trends.
   *
   * @return array|null
   *   Global trends data or NULL on failure.
   */
  public function getGlobalTrends(string $timeframe = '5years', array $metrics = []): ?array {
    try {
      $params = [
        'timeframe' => $timeframe,
        'metrics' => $metrics,
      ];

      $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/analytics/global-trends', [
        'query' => $params,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('Global trends data retrieved for timeframe: @timeframe', [
        '@timeframe' => $timeframe,
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to retrieve global trends: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get regional analysis comparing multiple areas.
   *
   * @param array $regions
   *   List of regions to compare.
   * @param array $comparison_metrics
   *   Metrics to use for comparison.
   *
   * @return array|null
   *   Regional comparison data or NULL on failure.
   */
  public function getRegionalAnalysis(array $regions, array $comparison_metrics = []): ?array {
    try {
      $payload = [
        'regions' => $regions,
        'comparison_metrics' => $comparison_metrics,
      ];

      $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/analytics/regional-comparison', [
        'json' => $payload,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('Regional analysis completed for @count regions', [
        '@count' => count($regions),
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to perform regional analysis: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Run scenario modeling with different parameters.
   *
   * @param array $scenarios
   *   List of scenarios with parameters to model.
   * @param string $model_type
   *   Type of modeling (population, economic, environmental).
   *
   * @return array|null
   *   Scenario modeling results or NULL on failure.
   */
  public function runScenarioModeling(array $scenarios, string $model_type = 'population'): ?array {
    try {
      $payload = [
        'scenarios' => $scenarios,
        'model_type' => $model_type,
      ];

      $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/modeling/scenarios', [
        'json' => $payload,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('Scenario modeling completed for @count scenarios', [
        '@count' => count($scenarios),
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to run scenario modeling: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get real-time monitoring data.
   *
   * @param array $indicators
   *   List of indicators to monitor.
   *
   * @return array|null
   *   Real-time monitoring data or NULL on failure.
   */
  public function getRealTimeMonitoring(array $indicators = []): ?array {
    try {
      $params = [
        'indicators' => $indicators,
      ];

      $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/monitoring/realtime', [
        'query' => $params,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to retrieve real-time monitoring data: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get demographic transition analysis data.
   *
   * @param string $region_id
   *   Region identifier (global, country code, etc.).
   * @param string $timeframe
   *   Time period for analysis (10years, 25years, 50years, historical).
   * @param string $transition_type
   *   Type of transition to analyze (all, fertility, mortality, migration).
   *
   * @return array|null
   *   Demographic transition analysis data or NULL on failure.
   */
  public function getDemographicTransitions(string $region_id = 'global', string $timeframe = '50years', string $transition_type = 'all'): ?array {
    try {
      $params = [
        'region_id' => $region_id,
        'timeframe' => $timeframe,
        'transition_type' => $transition_type,
      ];

      $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/demographics/transitions', [
        'query' => $params,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      $this->logger->info('Demographic transitions data retrieved for region: @region_id', [
        '@region_id' => $region_id,
      ]);
      
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to retrieve demographic transitions data: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Test backend API connectivity.
   *
   * @return bool
   *   TRUE if backend is accessible, FALSE otherwise.
   */
  public function testConnection(): bool {
    try {
      $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/health');
      return $response->getStatusCode() === 200;
    }
    catch (RequestException $e) {
      $this->logger->error('Backend API health check failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Get API status and version information.
   *
   * @return array|null
   *   API status information or NULL on failure.
   */
  public function getApiStatus(): ?array {
    try {
      $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/status');
      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to retrieve API status: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
