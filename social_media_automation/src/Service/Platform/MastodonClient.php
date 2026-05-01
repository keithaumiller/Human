<?php

namespace Drupal\social_media_automation\Service\Platform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_media_automation\Traits\ConfigurableLoggingTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Mastodon platform client for social media automation.
 * 
 * Handles authentication and communication with Mastodon API.
 */
class MastodonClient implements PlatformInterface {

  use ConfigurableLoggingTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('social_media_automation');
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'Mastodon';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string {
    return 'mastodon';
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured(): bool {
    $config = $this->configFactory->get('social_media_automation.settings');
    $credentials = $this->getRequiredCredentials();
    
    $this->logDebug('Checking Mastodon configuration...');
    
    foreach ($credentials as $credential) {
      $value = $config->get("mastodon.{$credential}");
      if (empty($value)) {
        $this->logDebug('Missing credential: mastodon.@credential', ['@credential' => $credential]);
        return FALSE;
      } else {
        $this->logDebug('Credential present: mastodon.@credential (@length chars)', [
          '@credential' => $credential,
          '@length' => strlen($value),
        ]);
      }
    }
    
    $this->logDebug('All Mastodon credentials present');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(): bool {
    $this->logInfo('=== Starting Mastodon connection test ===');
    
    // Step 1: Check if configured
    if (!$this->isConfigured()) {
      $config = $this->configFactory->get('social_media_automation.settings');
      $server_url = $config->get('mastodon.server_url');
      $access_token = $config->get('mastodon.access_token');
      
      $this->logError('Mastodon not configured. Missing credentials:');
      $this->logError('- server_url: @server_url', ['@server_url' => $server_url ? 'Present' : 'MISSING']);
      $this->logError('- access_token: @access_token', ['@access_token' => $access_token ? 'Present (' . strlen($access_token) . ' chars)' : 'MISSING']);
      
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    $this->logInfo('Step 1: Configuration check passed');
    $this->logInfo('- Server URL: @server', ['@server' => $server_url]);
    $this->logInfo('- Access Token: @token_length chars, ends with @token_end', [
      '@token_length' => strlen($access_token),
      '@token_end' => substr($access_token, -8),
    ]);

    // Step 2: Validate URL format
    if (!filter_var($server_url, FILTER_VALIDATE_URL)) {
      $this->logger->error('Step 2 FAILED: Invalid server URL format: @url', ['@url' => $server_url]);
      return FALSE;
    }
    $this->logger->info('Step 2: URL format validation passed');

    // Step 3: Check if URL is accessible
    $test_url = rtrim($server_url, '/') . '/api/v1/accounts/verify_credentials';
    $this->logger->info('Step 3: Testing API endpoint: @url', ['@url' => $test_url]);

    try {
      $this->logger->info('Step 4: Making HTTP request...');
      
      $response = $this->httpClient->get($test_url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Accept' => 'application/json',
          'User-Agent' => 'TruthPerspective/1.0 (+https://thetruthperspective.org)',
        ],
        'timeout' => 10,
        'allow_redirects' => true,
      ]);

      $status = $response->getStatusCode();
      $this->logger->info('Step 5: HTTP response received');
      $this->logger->info('- Status Code: @status', ['@status' => $status]);
      $this->logger->info('- Content Type: @type', ['@type' => $response->getHeaderLine('Content-Type')]);
      
      if ($status === 200) {
        // Try to decode the response to verify it's valid JSON
        try {
          $body = $response->getBody()->getContents();
          $data = json_decode($body, true);
          
          if (json_last_error() === JSON_ERROR_NONE && isset($data['id'])) {
            $this->logger->info('Step 6: SUCCESS! Valid account data received');
            $this->logger->info('- Account ID: @id', ['@id' => $data['id']]);
            $this->logger->info('- Username: @username', ['@username' => $data['username'] ?? 'Unknown']);
            $this->logger->info('- Display Name: @name', ['@name' => $data['display_name'] ?? 'Unknown']);
            return TRUE;
          } else {
            $this->logger->error('Step 6 FAILED: Invalid JSON response or missing account ID');
            $this->logger->error('- JSON Error: @error', ['@error' => json_last_error_msg()]);
            $this->logger->error('- Response body (first 200 chars): @body', ['@body' => substr($body, 0, 200)]);
            return FALSE;
          }
        } catch (\Exception $e) {
          $this->logger->error('Step 6 FAILED: Error parsing response: @message', ['@message' => $e->getMessage()]);
          return FALSE;
        }
      } else {
        $this->logger->error('Step 5 FAILED: HTTP error status @status', ['@status' => $status]);
        
        // Try to get error details from response
        try {
          $body = $response->getBody()->getContents();
          $error_data = json_decode($body, true);
          if (json_last_error() === JSON_ERROR_NONE && isset($error_data['error'])) {
            $this->logger->error('- Mastodon error: @error', ['@error' => $error_data['error']]);
          } else {
            $this->logger->error('- Response body: @body', ['@body' => substr($body, 0, 500)]);
          }
        } catch (\Exception $e) {
          $this->logger->error('- Could not parse error response: @message', ['@message' => $e->getMessage()]);
        }
        
        return FALSE;
      }

    } catch (RequestException $e) {
      $this->logger->error('Step 4 FAILED: HTTP request exception');
      $this->logger->error('- Exception type: @type', ['@type' => get_class($e)]);
      $this->logger->error('- Message: @message', ['@message' => $e->getMessage()]);
      
      if ($e->hasResponse()) {
        $response = $e->getResponse();
        $this->logger->error('- Response status: @status', ['@status' => $response->getStatusCode()]);
        $this->logger->error('- Response headers: @headers', ['@headers' => json_encode($response->getHeaders())]);
        
        try {
          $body = $response->getBody()->getContents();
          $this->logger->error('- Response body: @body', ['@body' => substr($body, 0, 500)]);
        } catch (\Exception $body_error) {
          $this->logger->error('- Could not read response body: @error', ['@error' => $body_error->getMessage()]);
        }
      } else {
        $this->logger->error('- No response received (connection/DNS issue?)');
      }
      
      return FALSE;
      
    } catch (\Exception $e) {
      $this->logger->error('Step 4 FAILED: Unexpected exception');
      $this->logger->error('- Exception type: @type', ['@type' => get_class($e)]);
      $this->logger->error('- Message: @message', ['@message' => $e->getMessage()]);
      $this->logger->error('- File: @file:@line', ['@file' => $e->getFile(), '@line' => $e->getLine()]);
      
      return FALSE;
    } finally {
      $this->logger->info('=== Mastodon connection test completed ===');
    }
  }

  /**
   * Post to Mastodon using AI-generated social media content.
   * 
   * @return bool
   *   TRUE if the post was successful, FALSE otherwise.
   */
  public function testPost(): bool {
    $this->logger->info('=== Starting Mastodon post with AI-generated content ===');
    
    // Step 1: Test connection first
    if (!$this->testConnection()) {
      $this->logger->error('Connection test failed, cannot proceed with post');
      return FALSE;
    }
    
    // Step 2: Generate AI social media content
    $this->logger->info('Step 2: Attempting to generate AI content...');
    $test_message = $this->generateTestContent();
    
    $this->logger->info('AI content generation result: @result', [
      '@result' => empty($test_message) ? 'EMPTY/FAILED' : 'SUCCESS (' . strlen($test_message) . ' chars)'
    ]);
    
    if (empty($test_message)) {
      // No fallback - if AI generation fails, don't post
      $this->logger->warning('AI content generation failed, cannot create test post without article content');
      return FALSE;
    }

    $this->logger->info('Using AI-generated content for post');

    // Ensure message fits within character limit
    $char_limit = $this->getInstanceCharacterLimit();
    if (strlen($test_message) > $char_limit) {
      // Truncate without adding test markers
      $test_message = substr($test_message, 0, $char_limit - 3) . '...';
      $this->logger->warning('Truncated message to fit character limit of @limit chars', [
        '@limit' => $char_limit
      ]);
    }
    
    $this->logger->info('Step 2: Posting message (length: @length chars)', [
      '@length' => strlen($test_message)
    ]);
    
    // Step 3: Post the message
    $result = $this->postContent($test_message, [
      'visibility' => 'public'
    ]);
    
    if ($result === FALSE) {
      $this->logger->error('Post failed');
      return FALSE;
    }
    
    // Step 4: Success
    $this->logger->info('✅ Post successful!');
    if (isset($result['url'])) {
      $this->logger->info('Post URL: @url', ['@url' => $result['url']]);
    }
    if (isset($result['id'])) {
      $this->logger->info('Post ID: @id', ['@id' => $result['id']]);
    }
    
    $this->logger->info('=== Mastodon post completed successfully ===');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacterLimit(): int {
    // Standard Mastodon limit, but some instances have much lower limits
    // This returns the theoretical maximum, actual validation happens in postContent()
    return 500;
  }

  /**
   * Get the actual character limit for this Mastodon instance.
   * 
   * @return int The actual character limit, or fallback to conservative value.
   */
  protected function getInstanceCharacterLimit(): int {
    static $cached_limit = null;
    
    if ($cached_limit !== null) {
      return $cached_limit;
    }

    try {
      $config = $this->configFactory->get('social_media_automation.settings');
      $server_url = $config->get('mastodon.server_url');
      
      if (empty($server_url)) {
        $cached_limit = 50; // Conservative fallback
        return $cached_limit;
      }

      // Query instance info
      $response = $this->httpClient->get($server_url . '/api/v1/instance', [
        'timeout' => 10,
        'http_errors' => false,
      ]);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody()->getContents(), true);
        if (isset($data['configuration']['statuses']['max_characters'])) {
          $cached_limit = (int) $data['configuration']['statuses']['max_characters'];
          $this->logger->info('Detected Mastodon instance character limit: @limit', ['@limit' => $cached_limit]);
          return $cached_limit;
        }
      }
    } catch (\Exception $e) {
      $this->logger->warning('Could not detect instance character limit: @message', ['@message' => $e->getMessage()]);
    }

    // Fallback to very conservative limit if we can't detect
    $cached_limit = 50;
    $this->logger->info('Using conservative character limit fallback: @limit', ['@limit' => $cached_limit]);
    return $cached_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function postContent(string $content, array $options = []): array|false {
    if (!$this->isConfigured()) {
      $this->logger->error('Mastodon not configured for posting');
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    // Prepare post data
    $post_data = [
      'status' => $content,
    ];

    // Add optional parameters
    if (!empty($options['visibility'])) {
      $post_data['visibility'] = $options['visibility']; // public, unlisted, private, direct
    } else {
      $post_data['visibility'] = 'public';
    }

    if (!empty($options['reply_to'])) {
      $post_data['in_reply_to_id'] = $options['reply_to'];
    }

    if (!empty($options['sensitive'])) {
      $post_data['sensitive'] = $options['sensitive'];
      if (!empty($options['spoiler_text'])) {
        $post_data['spoiler_text'] = $options['spoiler_text'];
      }
    }

    try {
      $response = $this->httpClient->post($server_url . '/api/v1/statuses', [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Content-Type' => 'application/json',
        ],
        'json' => $post_data,
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($data['id'])) {
        $this->logger->info('Successfully posted to Mastodon: @post_id', ['@post_id' => $data['id']]);
        
        // Store post content for future context to avoid repetition
        $this->storePostContext($content);
        
        return $data;
      } else {
        $this->logger->error('Mastodon API returned unexpected response: @response', ['@response' => print_r($data, TRUE)]);
        return FALSE;
      }

    } catch (RequestException $e) {
      $error_response = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
      
      // Check for character limit error
      if (strpos($error_response, 'character limit') !== false || 
          strpos($error_response, 'Validation failed') !== false) {
        $this->logger->error('Mastodon post failed due to character limit. Content length: @length chars. Error: @message', [
          '@length' => strlen($content),
          '@message' => $e->getMessage()
        ]);
      } else {
        $this->logger->error('Failed to post to Mastodon: @message. Response: @response', [
          '@message' => $e->getMessage(),
          '@response' => $error_response
        ]);
      }
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatContent(string $content, array $context = []): string {
    // Check if this content contains article data that should use AI generation
    if (preg_match('/Random Deep Dive:|Featured Analysis:|AI Spotlight:|Story Breakdown:|Random Pick:/', $content) && 
        preg_match('/"([^"]+)"/', $content, $title_matches)) {
      
      $this->logger->info('Detected article content for AI generation: @title', [
        '@title' => $title_matches[1] ?? 'unknown'
      ]);
      
      // Try to generate AI content using the detected article title
      $ai_content = $this->generateAIContentFromTitle($title_matches[1]);
      
      if (!empty($ai_content)) {
        $this->logger->info('Successfully generated AI content, using instead of template');
        $formatted = $ai_content;
      } else {
        $this->logger->warning('AI generation failed, falling back to original template content');
        $formatted = $content;
      }
    } else {
      // For non-article content, use as-is
      $formatted = $content;
    }
    
    // Ensure content fits within character limit using instance-specific limit
    $limit = $this->getInstanceCharacterLimit();
    if (strlen($formatted) > $limit) {
      // Check if the content contains a URL that should be preserved
      $url_pattern = '/(https?:\/\/[^\s]+)/';
      if (preg_match($url_pattern, $formatted, $matches)) {
        $url = $matches[1];
        $url_length = strlen($url);
        
        // Reserve space for the URL plus some buffer
        $available_for_content = $limit - $url_length - 10; // 10 chars buffer for spacing and "..."
        
        if ($available_for_content > 50) { // Only truncate if we have reasonable content space
          // Find the position of the URL
          $url_position = strpos($formatted, $url);
          
          if ($url_position !== false) {
            // Extract content before and after URL
            $content_before = substr($formatted, 0, $url_position);
            $content_after = substr($formatted, $url_position + $url_length);
            
            // Truncate the content before the URL if needed
            if (strlen($content_before) > $available_for_content - strlen($content_after)) {
              $content_before = substr($content_before, 0, $available_for_content - strlen($content_after) - 3) . '...';
            }
            
            $formatted = trim($content_before) . "\n\n" . $url . $content_after;
          } else {
            // Fallback: simple truncation but preserve URL at end
            $content_without_url = str_replace($url, '', $formatted);
            $truncated_content = substr($content_without_url, 0, $available_for_content - 3) . '...';
            $formatted = trim($truncated_content) . "\n\n" . $url;
          }
        }
        // If not enough space for reasonable content, keep the post as-is and let it be long
      } else {
        // No URL found, safe to truncate normally
        $formatted = substr($formatted, 0, $limit - 3) . '...';
      }
    }
    
    // Add context-specific hashtags
    if (!empty($context['hashtags'])) {
      $hashtags = is_array($context['hashtags']) ? $context['hashtags'] : [$context['hashtags']];
      $hashtag_string = ' ' . implode(' ', array_map(function($tag) {
        return '#' . preg_replace('/[^a-zA-Z0-9]/', '', $tag);
      }, $hashtags));
      
      // Add hashtags if they fit
      if (strlen($formatted . $hashtag_string) <= $limit) {
        $formatted .= $hashtag_string;
      }
    }
    
    return $formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl(): string {
    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url', 'https://mastodon.social');
    
    return $server_url . '/settings/applications';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredCredentials(): array {
    return [
      'server_url',
      'access_token',
    ];
  }

  /**
   * Get optional credentials for extended functionality.
   */
  public function getOptionalCredentials(): array {
    return [
      'username', // For mention monitoring and interaction responses
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateCredentials(array $credentials): array {
    $errors = [];
    
    if (empty($credentials['server_url'])) {
      $errors[] = 'Server URL is required';
    } elseif (!filter_var($credentials['server_url'], FILTER_VALIDATE_URL)) {
      $errors[] = 'Server URL must be a valid URL';
    }
    
    if (empty($credentials['client_id'])) {
      $errors[] = 'Client ID is required';
    }
    
    if (empty($credentials['client_secret'])) {
      $errors[] = 'Client Secret is required';
    }
    
    if (empty($credentials['access_token'])) {
      $errors[] = 'Access Token is required';
    }
    
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures(): array {
    return [
      'hashtags' => TRUE,
      'mentions' => TRUE,
      'media' => TRUE,
      'visibility_levels' => TRUE,
      'content_warnings' => TRUE,
      'replies' => TRUE,
      'threads' => TRUE,
      'polls' => TRUE,
    ];
  }

  /**
   * Generate AI-powered social media content for posting.
   *
   * @return string
   *   The generated social media content, or empty string if generation fails.
   */
  protected function generateTestContent(): string {
    $this->logger->info('Starting AI test content generation...');
    
    try {
      // Get most recent article
      $this->logger->info('Querying for most recent article...');
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        $this->logger->warning('No published articles found for AI test content generation');
        return '';
      }
      
      $nid = reset($nids);
      $this->logger->info('Found article @nid for content generation', ['@nid' => $nid]);
      
      $article = \Drupal\node\Entity\Node::load($nid);
      
      if (!$article) {
        $this->logger->warning('Could not load article @nid for test content', ['@nid' => $nid]);
        return '';
      }
      
      $this->logger->info('Article loaded successfully: @title', ['@title' => $article->getTitle()]);
      
      // Extract motivation analysis
      $motivation_analysis = '';
      if ($article->hasField('field_motivation_analysis') && !$article->get('field_motivation_analysis')->isEmpty()) {
        $motivation_analysis = $article->get('field_motivation_analysis')->value;
        $this->logger->info('Found motivation analysis field: @length characters', [
          '@length' => strlen($motivation_analysis)
        ]);
      } else {
        $this->logger->warning('No motivation analysis field or field is empty for article @nid', ['@nid' => $nid]);
        
        // Debug: Check what fields are actually available
        $field_definitions = $article->getFieldDefinitions();
        $available_fields = array_keys($field_definitions);
        $this->logger->info('Available fields on article: @fields', [
          '@fields' => implode(', ', $available_fields)
        ]);
        
        return '';
      }
      
      if (empty($motivation_analysis)) {
        $this->logger->warning('Motivation analysis field exists but is empty for article @nid', ['@nid' => $nid]);
        return '';
      }
      
      // Get article details
      $article_title = $article->getTitle();
      $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
      
      $this->logger->info('Article details extracted: title=@title, url=@url', [
        '@title' => $article_title,
        '@url' => $article_url
      ]);
      
      // Check if AI service is available
      $container = \Drupal::getContainer();
      if (!$container->has('news_extractor.ai_processing')) {
        $this->logger->warning('AI processing service not available for test content');
        return '';
      }
      
      $ai_service = $container->get('news_extractor.ai_processing');
      $this->logger->info('AI processing service loaded successfully');
      
      // Build prompt for social media generation
      $prompt = $this->buildContentPrompt($article_title, $article_url, $motivation_analysis);
      $this->logger->info('Built prompt for AI: @length characters', ['@length' => strlen($prompt)]);
      
      // Call AI service
      $this->logger->info('Calling AI service for content generation...');
      $social_media_post = $ai_service->generateAnalysis($prompt, $article_title);
      
      if (empty($social_media_post)) {
        $this->logger->warning('AI service returned empty response for test content');
        return '';
      }
      
      $this->logger->info('AI service returned response: @length characters', [
        '@length' => strlen($social_media_post)
      ]);
      
      // Parse AI response if it's JSON
      if (is_string($social_media_post) && (strpos($social_media_post, '{') === 0)) {
        $this->logger->info('Parsing JSON response from AI...');
        $parsed = json_decode($social_media_post, TRUE);
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['content'])) {
          $social_media_post = $parsed['content'];
          $this->logger->info('Extracted content from JSON response');
        } else {
          $this->logger->warning('Failed to parse AI JSON response or missing content field');
        }
      }
      
      // Ensure the article URL is included in the test post
      if (strpos($social_media_post, $article_url) === false && strpos($social_media_post, 'thetruthperspective.org') === false) {
        // Add the URL if it's missing
        $social_media_post = trim($social_media_post) . "\n\n" . $article_url;
        $this->logger->info('Added missing article URL to AI-generated test content');
      }
      
      $this->logger->info('Successfully generated AI test content: @length characters', [
        '@length' => strlen($social_media_post)
      ]);
      
      // Log first 100 chars for debugging
      $this->logger->info('Generated content preview: @preview...', [
        '@preview' => substr($social_media_post, 0, 100)
      ]);
      
      return $social_media_post;
      
    } catch (\Exception $e) {
      $this->logger->error('AI test content generation failed with exception: @error', [
        '@error' => $e->getMessage()
      ]);
      $this->logger->error('Exception trace: @trace', [
        '@trace' => $e->getTraceAsString()
      ]);
      return '';
    }
  }

  /**
   * Get recent posts context to avoid repetition.
   *
   * @return array
   *   Array of recent post content for context.
   */
  protected function getRecentPostsContext(): array {
    $state = \Drupal::state();
    $recent_posts = $state->get('social_media_automation.mastodon.recent_posts', []);
    
    // Return last 10 posts
    return array_slice($recent_posts, -10);
  }

  /**
   * Store post content for future context.
   *
   * @param string $content
   *   The post content to store.
   */
  protected function storePostContext(string $content): void {
    $state = \Drupal::state();
    $recent_posts = $state->get('social_media_automation.mastodon.recent_posts', []);
    
    // Add new post to the list
    $recent_posts[] = $content;
    
    // Keep only last 15 posts (we use 10 for context, keep 5 extra for buffer)
    if (count($recent_posts) > 15) {
      $recent_posts = array_slice($recent_posts, -15);
    }
    
    $state->set('social_media_automation.mastodon.recent_posts', $recent_posts);
    
    $this->logger->info('Stored post context, now tracking @count recent posts', [
      '@count' => count($recent_posts)
    ]);
  }

  /**
   * Generate AI content from article title.
   *
   * @param string $article_title
   *   The article title to find and generate content for.
   *
   * @return string
   *   Generated AI content or empty string if failed.
   */
  protected function generateAIContentFromTitle(string $article_title): string {
    try {
      // Find article by title
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $query = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('title', $article_title)
        ->accessCheck(FALSE)
        ->range(0, 1);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        $this->logger->warning('Article not found by title: @title', ['@title' => $article_title]);
        return '';
      }
      
      $nid = reset($nids);
      $article = $node_storage->load($nid);
      
      if (!$article) {
        $this->logger->warning('Failed to load article @nid', ['@nid' => $nid]);
        return '';
      }
      
      // Get motivation analysis
      if (!$article->hasField('field_motivation_analysis') || $article->get('field_motivation_analysis')->isEmpty()) {
        $this->logger->warning('Article @nid has no motivation analysis data', ['@nid' => $nid]);
        return '';
      }
      
      $motivation_analysis = $article->get('field_motivation_analysis')->value;
      $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
      
      // Fix URL if it shows as default
      if (strpos($article_url, 'http://default') !== false) {
        $article_url = str_replace('http://default', 'https://thetruthperspective.org', $article_url);
      }
      
      // Check if AI service is available
      $container = \Drupal::getContainer();
      if (!$container->has('news_extractor.ai_processing')) {
        $this->logger->warning('AI processing service not available');
        return '';
      }
      
      $ai_service = $container->get('news_extractor.ai_processing');
      
      // Build prompt for social media generation
      $prompt = $this->buildContentPrompt($article_title, $article_url, $motivation_analysis);
      
      // Call AI service
      $social_media_post = $ai_service->generateAnalysis($prompt, $article_title);
      
      if (empty($social_media_post)) {
        $this->logger->warning('AI service returned empty response');
        return '';
      }
      
      // Parse AI response if it's JSON
      if (is_string($social_media_post) && (strpos($social_media_post, '{') === 0)) {
        $parsed = json_decode($social_media_post, TRUE);
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['content'])) {
          $social_media_post = $parsed['content'];
        }
      }
      
      // Ensure the article URL is included in the post
      if (strpos($social_media_post, $article_url) === false && strpos($social_media_post, 'thetruthperspective.org') === false) {
        // Add the URL if it's missing
        $social_media_post = trim($social_media_post) . "\n\n" . $article_url;
        $this->logger->info('Added missing article URL to AI-generated content');
      }
      
      $this->logger->info('Successfully generated AI content for article: @title', ['@title' => $article_title]);
      return trim($social_media_post);
      
    } catch (\Exception $e) {
      $this->logger->error('Failed to generate AI content from title: @error', [
        '@error' => $e->getMessage()
      ]);
      return '';
    }
  }

  /**
   * Build content generation prompt for social media post.
   *
   * @param string $article_title
   *   The article title.
   * @param string $article_url
   *   The article URL.
   * @param string $motivation_analysis
   *   The motivation analysis data.
   *
   * @return string
   *   The prompt for AI.
   */
  protected function buildContentPrompt($article_title, $article_url, $motivation_analysis): string {
    $prompt = "Create a compelling social media post for Mastodon based on the motivation analysis below. This is for The Truth Perspective platform. Focus heavily on SPORTS angles and trending sports topics.\n\n";
    
    $prompt .= "ARTICLE TITLE: {$article_title}\n\n";
    $prompt .= "ARTICLE URL: {$article_url}\n\n";
    $prompt .= "MOTIVATION ANALYSIS DATA:\n{$motivation_analysis}\n\n";
    
    // Add recent posts context to avoid repetition
    $recent_posts = $this->getRecentPostsContext();
    if (!empty($recent_posts)) {
      $prompt .= "RECENT POSTS CONTEXT (to avoid repetition):\n";
      $prompt .= "You have recently posted the following content. Please create something different in style, opening, and sports angle:\n\n";
      foreach ($recent_posts as $index => $post) {
        $prompt .= "Post " . ($index + 1) . ": " . substr($post, 0, 150) . "...\n";
      }
      $prompt .= "\n";
    }
    
    $instance_limit = $this->getInstanceCharacterLimit();
    $url_length = strlen($article_url);
    $content_limit = $instance_limit - $url_length - 50; // Reserve space for URL, hashtags, and spacing
    
    $prompt .= "REQUIREMENTS:\n";
    $prompt .= "- Write in a bold, passionate, and emphatic broadcasting style\n";
    $prompt .= "- Use dramatic emphasis, strong opinions, and sports commentary energy\n";
    $prompt .= "- Connect the story to SPORTS themes, athletes, teams, or sporting culture whenever possible\n";
    $prompt .= "- Focus on trending sports topics, major athletes, or sports business angles\n";
    $prompt .= "- Keep the main content under {$content_limit} characters (leaving room for URL and hashtags)\n";
    $prompt .= "- Total post must be under {$instance_limit} characters (Mastodon instance limit)\n";
    $prompt .= "- NO ICONS, EMOJIS, OR SYMBOLS - use only text\n";
    $prompt .= "- Use phrases like 'This is RIDICULOUS', 'I'm telling you right now', 'Listen up', 'Check this out'\n";
    $prompt .= "- DO NOT start posts with 'Let me tell you something' - use other dramatic openings\n";
    $prompt .= "- AVOID repeating the style, opening phrases, or sports angles from recent posts shown above\n";
    $prompt .= "- Create fresh, unique content that stands out from previous posts\n";
    $prompt .= "- Include 2-4 relevant hashtags focusing on sports (e.g., #Sports #NBA #NFL #Athletics)\n";
    $prompt .= "- MUST include the full article URL: {$article_url}\n";
    $prompt .= "- Make bold declarations and strong takes on the sports implications\n\n";
    
    $prompt .= "TONE: Passionate sports broadcaster with dramatic emphasis, strong opinions, and energetic delivery\n\n";
    
    $prompt .= "IMPORTANT: Your post MUST include the full article URL ({$article_url}) and must fit within {$instance_limit} total characters. Keep content concise to ensure the full URL is preserved.\n\n";
    
    $prompt .= "Please respond with ONLY the social media post text, ready to publish. Do not include any additional commentary, explanation, or JSON formatting.";
    
    return $prompt;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateLimits(): array {
    return [
      'posts_per_hour' => 300,  // Most Mastodon instances
      'posts_per_day' => 1000,
      'burst_limit' => 5,       // Posts per minute
    ];
  }

  /**
   * Check for mentions and boosts, respond appropriately.
   * 
   * This method should be called periodically by cron to monitor
   * for mentions and boosts of our posts.
   */
  public function processInteractions(): void {
    if (!$this->isConfigured()) {
      $this->logError('Mastodon not configured for interaction processing');
      return;
    }

    try {
      // Get notifications (mentions, boosts, favorites)
      $notifications = $this->getNotifications();
      
      foreach ($notifications as $notification) {
        if ($this->shouldRespondToNotification($notification)) {
          $this->respondToNotification($notification);
        }
      }
    } catch (\Exception $e) {
      $this->logError('Failed to process Mastodon interactions: @message', ['@message' => $e->getMessage()]);
    }
  }

  /**
   * Get recent notifications from Mastodon API.
   * 
   * @return array
   *   Array of notification objects from Mastodon API.
   */
  protected function getNotifications(): array {
    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    try {
      // Get notifications for mentions, boosts, and favorites
      $response = $this->httpClient->get($server_url . '/api/v1/notifications', [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
        'query' => [
          'types' => ['mention', 'reblog', 'favourite'], // Only get interactions we care about
          'limit' => 40, // Check last 40 notifications
        ],
        'timeout' => 15,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (is_array($data)) {
        $this->logInfo('Retrieved @count Mastodon notifications', ['@count' => count($data)]);
        return $data;
      }
    } catch (RequestException $e) {
      $this->logError('Failed to get Mastodon notifications: @message', ['@message' => $e->getMessage()]);
    }

    return [];
  }

  /**
   * Check if we should respond to this notification.
   * 
   * @param array $notification
   *   The notification data from Mastodon API.
   * 
   * @return bool
   *   TRUE if we should respond, FALSE otherwise.
   */
  protected function shouldRespondToNotification(array $notification): bool {
    // Only respond to mentions and boosts (reblogs)
    if (!in_array($notification['type'], ['mention', 'reblog'])) {
      return FALSE;
    }

    // Check if we've already responded to this notification
    $notification_id = $notification['id'];
    $last_processed = \Drupal::state()->get('social_media_automation.last_processed_notification', 0);
    
    if ((int) $notification_id <= $last_processed) {
      return FALSE; // Already processed
    }

    // Don't respond to our own posts
    if (isset($notification['account']['acct']) && $this->isOurAccount($notification['account']['acct'])) {
      return FALSE;
    }

    // For boosts, we always respond with thanks
    if ($notification['type'] === 'reblog') {
      return TRUE;
    }

    // For mentions, check if it's a direct reply or mention of our content
    if ($notification['type'] === 'mention') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Generate and send response to a notification.
   * 
   * @param array $notification
   *   The notification data from Mastodon API.
   */
  protected function respondToNotification(array $notification): void {
    try {
      $response_content = $this->generateResponseContent($notification);
      
      if (!empty($response_content)) {
        // Determine reply parameters
        $reply_options = [
          'visibility' => 'public',
        ];

        // For mentions, reply to the original status
        if ($notification['type'] === 'mention' && isset($notification['status']['id'])) {
          $reply_options['reply_to'] = $notification['status']['id'];
        }

        // Post the response
        $result = $this->postContent($response_content, $reply_options);
        
        if ($result) {
          $this->logInfo('Successfully responded to Mastodon @type: @content', [
            '@type' => $notification['type'],
            '@content' => substr($response_content, 0, 100) . '...',
          ]);
        }

        // Update the last processed notification ID
        \Drupal::state()->set('social_media_automation.last_processed_notification', (int) $notification['id']);
      }
    } catch (\Exception $e) {
      $this->logError('Failed to respond to notification: @message', ['@message' => $e->getMessage()]);
    }
  }

  /**
   * Generate appropriate response content based on notification type and sentiment.
   * 
   * @param array $notification
   *   The notification data from Mastodon API.
   * 
   * @return string
   *   The response content, or empty string if no response needed.
   */
  protected function generateResponseContent(array $notification): string {
    $account_name = $notification['account']['display_name'] ?: $notification['account']['username'];
    $account_handle = '@' . $notification['account']['acct'];
    
    // For boosts/reblogs, always respond positively
    if ($notification['type'] === 'reblog') {
      return $this->generateBoostResponse($account_name, $account_handle);
    }

    // For mentions, analyze sentiment and respond accordingly
    if ($notification['type'] === 'mention' && isset($notification['status']['content'])) {
      $mention_text = strip_tags($notification['status']['content']);
      $sentiment = $this->analyzeSentiment($mention_text);
      
      return $this->generateMentionResponse($account_name, $account_handle, $mention_text, $sentiment);
    }

    return '';
  }

  /**
   * Generate a positive response for boosts/shares.
   * 
   * @param string $account_name
   *   The display name of the account.
   * @param string $account_handle
   *   The @handle of the account.
   * 
   * @return string
   *   The response content.
   */
  protected function generateBoostResponse(string $account_name, string $account_handle): string {
    $positive_responses = [
      "{$account_handle} YESSIR! Now THAT'S what I'm talking about! You see the vision! Appreciate you sharing the TRUTH with your people!",
      "{$account_handle} This is EXACTLY what we need! You're spreading the real analysis!",
      "{$account_handle} BOOM! You just became part of the solution! That boost shows you UNDERSTAND what real media analysis looks like!",
      "{$account_handle} Hold up, hold up! Did you just boost our content? RESPECT! You're helping expose the truth!",
      "{$account_handle} I see you! THAT is how we build a community that values REAL transparency! Thank you for the share!",
      "{$account_handle} Can I get a WITNESS?! This person just amplified the TRUTH! That's the kind of support we need!",
      "{$account_handle} OUTSTANDING move! You just helped more people see through the media noise! Appreciate the boost!",
    ];
    
    return $positive_responses[array_rand($positive_responses)];
  }

  /**
   * Generate a response for mentions based on sentiment.
   * 
   * @param string $account_name
   *   The display name of the account.
   * @param string $account_handle
   *   The @handle of the account.
   * @param string $mention_text
   *   The content of the mention.
   * @param string $sentiment
   *   The detected sentiment: 'positive', 'negative', or 'neutral'.
   * 
   * @return string
   *   The response content.
   */
  protected function generateMentionResponse(string $account_name, string $account_handle, string $mention_text, string $sentiment): string {
    if ($sentiment === 'positive') {
      $positive_responses = [
        "{$account_handle} Now THAT is the kind of energy we need! You GET IT! Thank you for engaging with real analysis!",
        "{$account_handle} EXACTLY! Finally someone who appreciates what REAL media transparency looks like! Keep asking the tough questions!",
        "{$account_handle} I see you! You're part of the solution! This is what happens when people start thinking critically!",
        "{$account_handle} YES! That comment shows you understand the game! Keep spreading awareness!",
        "{$account_handle} This right here - THIS is the engagement that moves the needle! Appreciate you being part of the conversation!",
      ];
      return $positive_responses[array_rand($positive_responses)];
    }
    
    if ($sentiment === 'negative') {
      $sarcastic_responses = [
        "{$account_handle} Oh, I see we got ourselves a CRITIC! Look, I respect the passion, but maybe check out our methodology before you swing at the fences!",
        "{$account_handle} Hold up now! You're coming in HOT! But let me ask you this - did you actually READ our analysis or just the headline?",
        "{$account_handle} Whoa there, tiger! I appreciate the energy, but maybe direct that fire toward asking better questions about media bias!",
        "{$account_handle} Look look look - I hear you, but let's channel that energy into something PRODUCTIVE! What specifically bothers you about transparency?",
        "{$account_handle} Oh we got a SKEPTIC! I LOVE that! But instead of throwing shots, why don't you engage with the actual data we're presenting?",
        "{$account_handle} I see the passion! But before you come for us, maybe understand what we're actually DOING here - exposing media manipulation!",
        "{$account_handle} That's some SERIOUS energy you got there! Now imagine if you used that same fire to question mainstream media narratives!",
      ];
      return $sarcastic_responses[array_rand($sarcastic_responses)];
    }
    
    // Neutral responses
    $neutral_responses = [
      "{$account_handle} Thanks for engaging! Check out our methodology at thetruthperspective.org - we're all about transparency!",
      "{$account_handle} I appreciate you taking the time to comment! What aspects of media analysis interest you most?",
      "{$account_handle} Thanks for being part of the conversation! We're always looking to improve our approach!",
      "{$account_handle} Good to see you engaging with the content! Let us know what questions you have about our analysis!",
    ];
    return $neutral_responses[array_rand($neutral_responses)];
  }

  /**
   * Analyze sentiment of text using AI service.
   * 
   * @param string $text
   *   The text to analyze.
   * 
   * @return string
   *   The sentiment: 'positive', 'negative', or 'neutral'.
   */
  protected function analyzeSentiment(string $text): string {
    try {
      // Use the existing AI service for sentiment analysis
      $ai_service = \Drupal::service('news_extractor.ai_processing');
      
      $prompt = "Analyze the sentiment of this social media comment/mention. Respond with ONLY one word: 'positive', 'negative', or 'neutral'.\n\n";
      $prompt .= "Comment: \"{$text}\"\n\n";
      $prompt .= "Consider the overall tone, intent, and emotional content. Is this comment supportive/appreciative (positive), critical/hostile (negative), or asking questions/neutral engagement (neutral)?\n\n";
      $prompt .= "Response (one word only):";
      
      $response = $ai_service->generateAnalysis($prompt, 'Sentiment Analysis');
      
      // Clean up the response and validate
      $sentiment = strtolower(trim($response));
      
      if (in_array($sentiment, ['positive', 'negative', 'neutral'])) {
        $this->logInfo('Sentiment analysis result: @sentiment for text: @text', [
          '@sentiment' => $sentiment,
          '@text' => substr($text, 0, 100) . '...',
        ]);
        return $sentiment;
      }
      
      $this->logWarning('Invalid sentiment analysis result: @response', ['@response' => $response]);
    } catch (\Exception $e) {
      $this->logError('Sentiment analysis failed: @message', ['@message' => $e->getMessage()]);
    }
    
    // Default to neutral if analysis fails
    return 'neutral';
  }

  /**
   * Check if an account is our own account.
   * 
   * @param string $account_acct
   *   The account acct field (username@domain).
   * 
   * @return bool
   *   TRUE if this is our account, FALSE otherwise.
   */
  protected function isOurAccount(string $account_acct): bool {
    // This would need to be configured based on your actual Mastodon account
    // For now, we'll implement a basic check
    $config = $this->configFactory->get('social_media_automation.settings');
    $our_username = $config->get('mastodon.username');
    
    if (empty($our_username)) {
      return FALSE;
    }
    
    // Check if the account matches our configured username
    return strpos($account_acct, $our_username) === 0;
  }

}
