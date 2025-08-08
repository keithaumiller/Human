<?php

namespace Drupal\humansuccess\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\humansuccess\Service\BackendApiService;
use Drupal\humansuccess\Service\DataCacheService;

/**
 * Service for H3 geospatial operations and hexagonal mapping.
 * 
 * Provides functionality for:
 * - H3 hexagonal grid generation
 * - Geospatial population mapping
 * - Coordinate to H3 index conversion
 * - Spatial aggregation and analysis
 * - Geographic boundary handling
 */
class H3GeospatialService {

  /**
   * The backend API service.
   *
   * @var \Drupal\humansuccess\Service\BackendApiService
   */
  protected $backendApi;

  /**
   * The data cache service.
   *
   * @var \Drupal\humansuccess\Service\DataCacheService
   */
  protected $dataCache;

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
   * Default H3 resolution for population mapping.
   *
   * @var int
   */
  protected $defaultResolution = 6;

  /**
   * Constructs an H3GeospatialService object.
   *
   * @param \Drupal\humansuccess\Service\BackendApiService $backend_api
   *   The backend API service.
   * @param \Drupal\humansuccess\Service\DataCacheService $data_cache
   *   The data cache service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    BackendApiService $backend_api,
    DataCacheService $data_cache,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->backendApi = $backend_api;
    $this->dataCache = $data_cache;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('humansuccess');
    
    $config = $this->configFactory->get('humansuccess.settings');
    $this->defaultResolution = $config->get('h3_default_resolution') ?: 6;
  }

  /**
   * Get H3 population mapping data for a specific region.
   *
   * @param array $bounds
   *   Geographic bounds [min_lat, min_lng, max_lat, max_lng].
   * @param int $resolution
   *   H3 resolution level (0-15). Higher = more detailed.
   * @param array $filters
   *   Optional filters for population data.
   * @param bool $use_cache
   *   Whether to use cached data if available.
   *
   * @return array
   *   H3 hexagon population data with GeoJSON structure.
   */
  public function getPopulationMapping(array $bounds = [], int $resolution = NULL, array $filters = [], bool $use_cache = TRUE): array {
    $resolution = $resolution ?: $this->defaultResolution;
    
    // Try to get from cache first
    if ($use_cache) {
      $cached = $this->dataCache->getH3PopulationData($resolution, $bounds, $filters);
      if ($cached) {
        return $cached;
      }
    }
    
    // Get fresh data from backend
    $data = $this->backendApi->getH3PopulationData($resolution, $bounds, $filters);
    
    if ($data) {
      // Process and format the data
      $processed = $this->processH3PopulationData($data, $resolution);
      
      // Cache the processed data
      if ($use_cache) {
        $this->dataCache->setH3PopulationData($processed, $resolution, $bounds, $filters);
      }
      
      return $processed;
    }
    
    // Return empty structure if no data available
    return $this->getEmptyH3Structure();
  }

  /**
   * Get global H3 population overview at multiple resolutions.
   *
   * @param array $resolutions
   *   Array of H3 resolutions to include.
   *
   * @return array
   *   Multi-resolution population data.
   */
  public function getGlobalPopulationOverview(array $resolutions = [4, 6, 8]): array {
    $overview = [
      'total_population' => 0,
      'populated_hexagons' => 0,
      'resolutions' => [],
    ];
    
    foreach ($resolutions as $resolution) {
      $data = $this->getPopulationMapping([], $resolution);
      
      $overview['resolutions'][$resolution] = [
        'resolution' => $resolution,
        'hexagon_count' => count($data['features'] ?? []),
        'population_sum' => $this->calculateTotalPopulation($data),
        'avg_population_density' => $this->calculateAveragePopulationDensity($data),
      ];
      
      // Use highest resolution for total
      if ($resolution === max($resolutions)) {
        $overview['total_population'] = $overview['resolutions'][$resolution]['population_sum'];
        $overview['populated_hexagons'] = $overview['resolutions'][$resolution]['hexagon_count'];
      }
    }
    
    return $overview;
  }

  /**
   * Convert geographic coordinates to H3 index.
   *
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longitude.
   * @param int $resolution
   *   H3 resolution level.
   *
   * @return string|null
   *   H3 index string or NULL on failure.
   */
  public function coordinatesToH3(float $lat, float $lng, int $resolution = NULL): ?string {
    $resolution = $resolution ?: $this->defaultResolution;
    
    // This would typically be handled by the backend API
    // For now, we'll make a simple API call
    try {
      $data = $this->backendApi->getH3PopulationData($resolution, [$lat, $lng, $lat, $lng]);
      
      if ($data && !empty($data['features'])) {
        return $data['features'][0]['properties']['h3_index'] ?? NULL;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to convert coordinates to H3: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    
    return NULL;
  }

  /**
   * Get H3 hexagons within a geographic area.
   *
   * @param array $polygon
   *   GeoJSON polygon coordinates.
   * @param int $resolution
   *   H3 resolution level.
   *
   * @return array
   *   Array of H3 indexes within the polygon.
   */
  public function getH3WithinPolygon(array $polygon, int $resolution = NULL): array {
    $resolution = $resolution ?: $this->defaultResolution;
    
    // Extract bounds from polygon
    $bounds = $this->extractBoundsFromPolygon($polygon);
    
    // Get H3 data for the bounds
    $data = $this->getPopulationMapping($bounds, $resolution, [], FALSE);
    
    // Filter hexagons that are actually within the polygon
    // (This would be done more precisely in the backend)
    $within_polygon = [];
    
    if (!empty($data['features'])) {
      foreach ($data['features'] as $feature) {
        $h3_index = $feature['properties']['h3_index'] ?? NULL;
        if ($h3_index && $this->isH3WithinPolygon($feature['geometry'], $polygon)) {
          $within_polygon[] = $h3_index;
        }
      }
    }
    
    return $within_polygon;
  }

  /**
   * Calculate population density statistics for H3 data.
   *
   * @param array $h3_data
   *   H3 GeoJSON data.
   *
   * @return array
   *   Population density statistics.
   */
  public function calculatePopulationDensityStats(array $h3_data): array {
    $densities = [];
    
    if (!empty($h3_data['features'])) {
      foreach ($h3_data['features'] as $feature) {
        $population = $feature['properties']['population'] ?? 0;
        $area = $feature['properties']['area_km2'] ?? 1;
        $densities[] = $population / $area;
      }
    }
    
    if (empty($densities)) {
      return [
        'min' => 0,
        'max' => 0,
        'mean' => 0,
        'median' => 0,
        'std_dev' => 0,
      ];
    }
    
    sort($densities);
    $count = count($densities);
    
    return [
      'min' => min($densities),
      'max' => max($densities),
      'mean' => array_sum($densities) / $count,
      'median' => $count % 2 === 0 
        ? ($densities[$count / 2 - 1] + $densities[$count / 2]) / 2 
        : $densities[intval($count / 2)],
      'std_dev' => $this->calculateStandardDeviation($densities),
    ];
  }

  /**
   * Get H3 hexagons for administrative boundaries.
   *
   * @param string $boundary_type
   *   Type of boundary (country, state, city, etc.).
   * @param string $boundary_id
   *   Identifier for the specific boundary.
   * @param int $resolution
   *   H3 resolution level.
   *
   * @return array
   *   H3 population data for the boundary.
   */
  public function getPopulationForBoundary(string $boundary_type, string $boundary_id, int $resolution = NULL): array {
    $resolution = $resolution ?: $this->defaultResolution;
    
    // This would involve getting the boundary polygon from a boundaries service
    // For now, we'll use the backend API with boundary parameters
    $filters = [
      'boundary_type' => $boundary_type,
      'boundary_id' => $boundary_id,
    ];
    
    return $this->getPopulationMapping([], $resolution, $filters);
  }

  /**
   * Generate visualization data for H3 population mapping.
   *
   * @param array $h3_data
   *   H3 GeoJSON data.
   * @param array $options
   *   Visualization options (color_scheme, legend_steps, etc.).
   *
   * @return array
   *   Visualization configuration and data.
   */
  public function generateVisualizationData(array $h3_data, array $options = []): array {
    $color_scheme = $options['color_scheme'] ?? 'YlOrRd';
    $legend_steps = $options['legend_steps'] ?? 7;
    
    // Calculate population statistics for color mapping
    $stats = $this->calculatePopulationDensityStats($h3_data);
    
    // Generate color breaks
    $color_breaks = $this->generateColorBreaks($stats, $legend_steps);
    
    // Add color properties to features
    if (!empty($h3_data['features'])) {
      foreach ($h3_data['features'] as &$feature) {
        $population = $feature['properties']['population'] ?? 0;
        $area = $feature['properties']['area_km2'] ?? 1;
        $density = $population / $area;
        
        $feature['properties']['density'] = $density;
        $feature['properties']['color'] = $this->getColorForDensity($density, $color_breaks);
        $feature['properties']['opacity'] = $this->getOpacityForDensity($density, $stats);
      }
    }
    
    return [
      'data' => $h3_data,
      'legend' => [
        'title' => 'Population Density (per km²)',
        'color_scheme' => $color_scheme,
        'breaks' => $color_breaks,
      ],
      'stats' => $stats,
    ];
  }

  /**
   * Process raw H3 population data from backend.
   *
   * @param array $raw_data
   *   Raw data from backend API.
   * @param int $resolution
   *   H3 resolution level.
   *
   * @return array
   *   Processed GeoJSON structure.
   */
  protected function processH3PopulationData(array $raw_data, int $resolution): array {
    // Ensure proper GeoJSON structure
    $processed = [
      'type' => 'FeatureCollection',
      'properties' => [
        'h3_resolution' => $resolution,
        'generated_at' => date('c'),
        'feature_count' => count($raw_data['features'] ?? []),
      ],
      'features' => [],
    ];
    
    if (!empty($raw_data['features'])) {
      foreach ($raw_data['features'] as $feature) {
        $processed_feature = [
          'type' => 'Feature',
          'geometry' => $feature['geometry'],
          'properties' => array_merge($feature['properties'], [
            'h3_resolution' => $resolution,
            'area_km2' => $this->calculateH3AreaKm2($resolution),
          ]),
        ];
        
        $processed['features'][] = $processed_feature;
      }
    }
    
    return $processed;
  }

  /**
   * Calculate total population from H3 data.
   *
   * @param array $h3_data
   *   H3 GeoJSON data.
   *
   * @return int
   *   Total population.
   */
  protected function calculateTotalPopulation(array $h3_data): int {
    $total = 0;
    
    if (!empty($h3_data['features'])) {
      foreach ($h3_data['features'] as $feature) {
        $total += $feature['properties']['population'] ?? 0;
      }
    }
    
    return $total;
  }

  /**
   * Calculate average population density from H3 data.
   *
   * @param array $h3_data
   *   H3 GeoJSON data.
   *
   * @return float
   *   Average population density per km².
   */
  protected function calculateAveragePopulationDensity(array $h3_data): float {
    $densities = [];
    
    if (!empty($h3_data['features'])) {
      foreach ($h3_data['features'] as $feature) {
        $population = $feature['properties']['population'] ?? 0;
        $area = $feature['properties']['area_km2'] ?? 1;
        if ($population > 0) {
          $densities[] = $population / $area;
        }
      }
    }
    
    return empty($densities) ? 0 : array_sum($densities) / count($densities);
  }

  /**
   * Get empty H3 structure for when no data is available.
   *
   * @return array
   *   Empty GeoJSON structure.
   */
  protected function getEmptyH3Structure(): array {
    return [
      'type' => 'FeatureCollection',
      'properties' => [
        'h3_resolution' => $this->defaultResolution,
        'generated_at' => date('c'),
        'feature_count' => 0,
      ],
      'features' => [],
    ];
  }

  /**
   * Calculate H3 hexagon area in km² for a given resolution.
   *
   * @param int $resolution
   *   H3 resolution level.
   *
   * @return float
   *   Area in km².
   */
  protected function calculateH3AreaKm2(int $resolution): float {
    // Approximate areas for H3 resolutions
    $areas = [
      0 => 4250546.848,
      1 => 607220.9782,
      2 => 86745.85403,
      3 => 12392.26486,
      4 => 1770.323552,
      5 => 252.9033645,
      6 => 36.1290521,
      7 => 5.1612932,
      8 => 0.7373276,
      9 => 0.1053325,
      10 => 0.0150475,
      11 => 0.0021496,
      12 => 0.0003071,
      13 => 0.0000439,
      14 => 0.0000063,
      15 => 0.0000009,
    ];
    
    return $areas[$resolution] ?? 36.1290521; // Default to resolution 6
  }

  /**
   * Calculate standard deviation of an array of numbers.
   *
   * @param array $values
   *   Array of numeric values.
   *
   * @return float
   *   Standard deviation.
   */
  protected function calculateStandardDeviation(array $values): float {
    $count = count($values);
    if ($count === 0) {
      return 0;
    }
    
    $mean = array_sum($values) / $count;
    $sum_of_squares = 0;
    
    foreach ($values as $value) {
      $sum_of_squares += pow($value - $mean, 2);
    }
    
    return sqrt($sum_of_squares / $count);
  }

  /**
   * Generate color breaks for density visualization.
   *
   * @param array $stats
   *   Population density statistics.
   * @param int $steps
   *   Number of color steps.
   *
   * @return array
   *   Array of color break values.
   */
  protected function generateColorBreaks(array $stats, int $steps): array {
    $min = $stats['min'];
    $max = $stats['max'];
    $range = $max - $min;
    
    $breaks = [];
    for ($i = 0; $i <= $steps; $i++) {
      $breaks[] = $min + ($range * $i / $steps);
    }
    
    return $breaks;
  }

  /**
   * Get color for a specific density value.
   *
   * @param float $density
   *   Population density.
   * @param array $color_breaks
   *   Color break values.
   *
   * @return string
   *   Hex color code.
   */
  protected function getColorForDensity(float $density, array $color_breaks): string {
    // Simple color mapping - would be more sophisticated in production
    $step = 0;
    for ($i = 0; $i < count($color_breaks) - 1; $i++) {
      if ($density >= $color_breaks[$i] && $density <= $color_breaks[$i + 1]) {
        $step = $i;
        break;
      }
    }
    
    // YlOrRd color scheme
    $colors = [
      '#FFFFCC', '#FFEDA0', '#FED976', '#FEB24C',
      '#FD8D3C', '#FC4E2A', '#E31A1C', '#B10026'
    ];
    
    return $colors[$step] ?? '#FFFFCC';
  }

  /**
   * Get opacity for a specific density value.
   *
   * @param float $density
   *   Population density.
   * @param array $stats
   *   Population density statistics.
   *
   * @return float
   *   Opacity value between 0.1 and 1.0.
   */
  protected function getOpacityForDensity(float $density, array $stats): float {
    if ($stats['max'] === $stats['min']) {
      return 0.7;
    }
    
    $normalized = ($density - $stats['min']) / ($stats['max'] - $stats['min']);
    return 0.1 + ($normalized * 0.9); // Between 0.1 and 1.0
  }

  /**
   * Extract geographic bounds from polygon coordinates.
   *
   * @param array $polygon
   *   GeoJSON polygon coordinates.
   *
   * @return array
   *   Bounds [min_lat, min_lng, max_lat, max_lng].
   */
  protected function extractBoundsFromPolygon(array $polygon): array {
    $lats = [];
    $lngs = [];
    
    if (!empty($polygon[0])) {
      foreach ($polygon[0] as $coordinate) {
        $lngs[] = $coordinate[0];
        $lats[] = $coordinate[1];
      }
    }
    
    return [
      min($lats),
      min($lngs),
      max($lats),
      max($lngs),
    ];
  }

  /**
   * Check if H3 hexagon is within polygon (simplified).
   *
   * @param array $h3_geometry
   *   H3 hexagon geometry.
   * @param array $polygon
   *   Polygon coordinates.
   *
   * @return bool
   *   TRUE if hexagon is within polygon.
   */
  protected function isH3WithinPolygon(array $h3_geometry, array $polygon): bool {
    // Simplified check - in production would use proper polygon intersection
    // For now, just check if center point is within bounds
    if (!empty($h3_geometry['coordinates'][0])) {
      $center_lng = 0;
      $center_lat = 0;
      $vertex_count = count($h3_geometry['coordinates'][0]);
      
      foreach ($h3_geometry['coordinates'][0] as $vertex) {
        $center_lng += $vertex[0];
        $center_lat += $vertex[1];
      }
      
      $center_lng /= $vertex_count;
      $center_lat /= $vertex_count;
      
      $bounds = $this->extractBoundsFromPolygon($polygon);
      
      return $center_lat >= $bounds[0] && $center_lat <= $bounds[2] &&
             $center_lng >= $bounds[1] && $center_lng <= $bounds[3];
    }
    
    return FALSE;
  }

}
