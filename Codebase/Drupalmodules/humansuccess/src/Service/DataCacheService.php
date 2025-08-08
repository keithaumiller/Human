<?php

namespace Drupal\humansuccess\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for managing cache operations for Human Success data.
 * 
 * Handles caching of:
 * - H3 geospatial data
 * - Population forecasting results
 * - Quality of life analytics
 * - Real-time monitoring data
 * - Backend API responses
 */
class DataCacheService {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

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
   * Default cache TTL in seconds (24 hours).
   *
   * @var int
   */
  protected $defaultTtl = 86400;

  /**
   * Cache key prefix for this module.
   *
   * @var string
   */
  protected $cachePrefix = 'humansuccess';

  /**
   * Constructs a DataCacheService object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->cache = $cache_backend;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('humansuccess');
    
    $config = $this->configFactory->get('humansuccess.settings');
    $this->defaultTtl = $config->get('cache_ttl') ?: 86400;
  }

  /**
   * Get cached H3 population data.
   *
   * @param int $resolution
   *   H3 resolution level.
   * @param array $bounds
   *   Geographic bounds.
   * @param array $filters
   *   Data filters.
   *
   * @return array|null
   *   Cached data or NULL if not found.
   */
  public function getH3PopulationData(int $resolution, array $bounds = [], array $filters = []): ?array {
    $cache_key = $this->buildCacheKey('h3_population', [
      'resolution' => $resolution,
      'bounds' => $bounds,
      'filters' => $filters,
    ]);
    
    $cached = $this->cache->get($cache_key);
    if ($cached && $cached->valid) {
      $this->logger->debug('H3 population data retrieved from cache: @key', [
        '@key' => $cache_key,
      ]);
      return $cached->data;
    }
    
    return NULL;
  }

  /**
   * Set cached H3 population data.
   *
   * @param array $data
   *   Data to cache.
   * @param int $resolution
   *   H3 resolution level.
   * @param array $bounds
   *   Geographic bounds.
   * @param array $filters
   *   Data filters.
   * @param int $ttl
   *   Cache TTL override.
   */
  public function setH3PopulationData(array $data, int $resolution, array $bounds = [], array $filters = [], int $ttl = NULL): void {
    $cache_key = $this->buildCacheKey('h3_population', [
      'resolution' => $resolution,
      'bounds' => $bounds,
      'filters' => $filters,
    ]);
    
    $expire = $ttl ? time() + $ttl : time() + $this->defaultTtl;
    
    $this->cache->set($cache_key, $data, $expire, [
      'humansuccess:h3_data',
      'humansuccess:population',
    ]);
    
    $this->logger->debug('H3 population data cached: @key', [
      '@key' => $cache_key,
    ]);
  }

  /**
   * Get cached population forecast data.
   *
   * @param string $region_type
   *   Type of region.
   * @param string $region_id
   *   Region identifier.
   * @param int $forecast_years
   *   Number of years forecasted.
   *
   * @return array|null
   *   Cached forecast data or NULL if not found.
   */
  public function getPopulationForecast(string $region_type, string $region_id, int $forecast_years): ?array {
    $cache_key = $this->buildCacheKey('population_forecast', [
      'region_type' => $region_type,
      'region_id' => $region_id,
      'forecast_years' => $forecast_years,
    ]);
    
    $cached = $this->cache->get($cache_key);
    if ($cached && $cached->valid) {
      return $cached->data;
    }
    
    return NULL;
  }

  /**
   * Set cached population forecast data.
   *
   * @param array $data
   *   Forecast data to cache.
   * @param string $region_type
   *   Type of region.
   * @param string $region_id
   *   Region identifier.
   * @param int $forecast_years
   *   Number of years forecasted.
   * @param int $ttl
   *   Cache TTL override (forecasts cache longer).
   */
  public function setPopulationForecast(array $data, string $region_type, string $region_id, int $forecast_years, int $ttl = NULL): void {
    $cache_key = $this->buildCacheKey('population_forecast', [
      'region_type' => $region_type,
      'region_id' => $region_id,
      'forecast_years' => $forecast_years,
    ]);
    
    // Forecasts can be cached longer since they don't change frequently
    $expire = $ttl ? time() + $ttl : time() + (7 * $this->defaultTtl);
    
    $this->cache->set($cache_key, $data, $expire, [
      'humansuccess:forecasts',
      'humansuccess:population',
    ]);
  }

  /**
   * Get cached quality of life optimization data.
   *
   * @param array $metrics
   *   QoL metrics parameters.
   * @param string $optimization_target
   *   Optimization target.
   *
   * @return array|null
   *   Cached optimization data or NULL if not found.
   */
  public function getQualityOfLifeOptimization(array $metrics, string $optimization_target): ?array {
    $cache_key = $this->buildCacheKey('qol_optimization', [
      'metrics' => $metrics,
      'target' => $optimization_target,
    ]);
    
    $cached = $this->cache->get($cache_key);
    if ($cached && $cached->valid) {
      return $cached->data;
    }
    
    return NULL;
  }

  /**
   * Set cached quality of life optimization data.
   *
   * @param array $data
   *   Optimization data to cache.
   * @param array $metrics
   *   QoL metrics parameters.
   * @param string $optimization_target
   *   Optimization target.
   * @param int $ttl
   *   Cache TTL override.
   */
  public function setQualityOfLifeOptimization(array $data, array $metrics, string $optimization_target, int $ttl = NULL): void {
    $cache_key = $this->buildCacheKey('qol_optimization', [
      'metrics' => $metrics,
      'target' => $optimization_target,
    ]);
    
    $expire = $ttl ? time() + $ttl : time() + $this->defaultTtl;
    
    $this->cache->set($cache_key, $data, $expire, [
      'humansuccess:qol_data',
      'humansuccess:optimization',
    ]);
  }

  /**
   * Get cached global trends data.
   *
   * @param string $timeframe
   *   Time period for trends.
   * @param array $metrics
   *   Specific metrics included.
   *
   * @return array|null
   *   Cached trends data or NULL if not found.
   */
  public function getGlobalTrends(string $timeframe, array $metrics = []): ?array {
    $cache_key = $this->buildCacheKey('global_trends', [
      'timeframe' => $timeframe,
      'metrics' => $metrics,
    ]);
    
    $cached = $this->cache->get($cache_key);
    if ($cached && $cached->valid) {
      return $cached->data;
    }
    
    return NULL;
  }

  /**
   * Set cached global trends data.
   *
   * @param array $data
   *   Trends data to cache.
   * @param string $timeframe
   *   Time period for trends.
   * @param array $metrics
   *   Specific metrics included.
   * @param int $ttl
   *   Cache TTL override.
   */
  public function setGlobalTrends(array $data, string $timeframe, array $metrics = [], int $ttl = NULL): void {
    $cache_key = $this->buildCacheKey('global_trends', [
      'timeframe' => $timeframe,
      'metrics' => $metrics,
    ]);
    
    $expire = $ttl ? time() + $ttl : time() + $this->defaultTtl;
    
    $this->cache->set($cache_key, $data, $expire, [
      'humansuccess:trends',
      'humansuccess:analytics',
    ]);
  }

  /**
   * Get cached real-time monitoring data.
   *
   * @param array $indicators
   *   Monitoring indicators.
   *
   * @return array|null
   *   Cached monitoring data or NULL if not found.
   */
  public function getRealTimeMonitoring(array $indicators = []): ?array {
    $cache_key = $this->buildCacheKey('realtime_monitoring', [
      'indicators' => $indicators,
    ]);
    
    $cached = $this->cache->get($cache_key);
    if ($cached && $cached->valid) {
      return $cached->data;
    }
    
    return NULL;
  }

  /**
   * Set cached real-time monitoring data.
   *
   * @param array $data
   *   Monitoring data to cache.
   * @param array $indicators
   *   Monitoring indicators.
   * @param int $ttl
   *   Cache TTL override (short for real-time data).
   */
  public function setRealTimeMonitoring(array $data, array $indicators = [], int $ttl = NULL): void {
    $cache_key = $this->buildCacheKey('realtime_monitoring', [
      'indicators' => $indicators,
    ]);
    
    // Real-time data should have shorter cache TTL
    $expire = $ttl ? time() + $ttl : time() + 300; // 5 minutes default
    
    $this->cache->set($cache_key, $data, $expire, [
      'humansuccess:realtime',
      'humansuccess:monitoring',
    ]);
  }

  /**
   * Invalidate cached data by tags.
   *
   * @param array $tags
   *   Cache tags to invalidate.
   */
  public function invalidateByTags(array $tags): void {
    $this->cache->invalidateTags($tags);
    
    $this->logger->info('Cache invalidated for tags: @tags', [
      '@tags' => implode(', ', $tags),
    ]);
  }

  /**
   * Clear all module cache data.
   */
  public function clearAll(): void {
    $this->cache->deleteAll();
    
    $this->logger->info('All Human Success cache data cleared');
  }

  /**
   * Get cache statistics.
   *
   * @return array
   *   Cache usage statistics.
   */
  public function getCacheStats(): array {
    // This would need to be implemented based on the cache backend
    // For now, return basic info
    return [
      'cache_prefix' => $this->cachePrefix,
      'default_ttl' => $this->defaultTtl,
      'backend_class' => get_class($this->cache),
    ];
  }

  /**
   * Build a cache key from components.
   *
   * @param string $type
   *   Data type identifier.
   * @param array $parameters
   *   Parameters that affect the cached data.
   *
   * @return string
   *   Generated cache key.
   */
  protected function buildCacheKey(string $type, array $parameters = []): string {
    $key_parts = [$this->cachePrefix, $type];
    
    if (!empty($parameters)) {
      $key_parts[] = md5(serialize($parameters));
    }
    
    return implode(':', $key_parts);
  }

  /**
   * Check if data should be cached based on configuration.
   *
   * @param string $data_type
   *   Type of data being cached.
   *
   * @return bool
   *   TRUE if data should be cached, FALSE otherwise.
   */
  protected function shouldCache(string $data_type): bool {
    $config = $this->configFactory->get('humansuccess.settings');
    $cache_config = $config->get('cache_settings') ?: [];
    
    // Default to TRUE, allow per-data-type configuration
    return $cache_config[$data_type] ?? TRUE;
  }

}
