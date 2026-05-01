# Social Media Automation Module

## Overview

Unified social media automation system for The Truth Perspective platform. Provides automated content generation and posting across multiple social media platforms with a single configuration interface and shared scheduling logic.

## 🎉 Current Status: Mastodon Integration WORKING!

**✅ Fully Functional:**
- Mastodon platform integration
- Form-based credential configuration 
- Test posting with "Hello World" messages
- Database persistence of settings
- Enhanced debugging and logging
- Complete end-to-end posting pipeline

**📍 Current Focus:** Mastodon is the primary platform (free, decentralized, no API costs)

**🔄 Next Steps:** LinkedIn and Facebook integration (when needed)

## Quick Start

1. **Install the module** (already done)
2. **Configure Mastodon credentials**: See `docs/MASTODON_SETUP.md`
3. **Test the integration**: Use the "Test Mastodon (Send Hello World Post)" button
4. **Verify**: Check your Mastodon account for the test post
5. **Enable automation**: Check the "Enable Mastodon" box and save

## Architecture

### Core Design Principles

1. **Platform Agnostic**: Common content generation and scheduling logic
2. **Modular Services**: Separate service for each platform with unified interface
3. **Unified Configuration**: Single admin interface for all platform credentials
4. **Flexible Content**: Adaptive content generation based on platform constraints
5. **Reliable Posting**: Queue-based system with retry logic and error handling

### Module Structure

```
social_media_automation/
├── src/
│   ├── Controller/
│   │   └── SocialMediaController.php         # Main dashboard and management
│   ├── Form/
│   │   └── SocialMediaAutomationSettingsForm.php  # Unified platform configuration
│   ├── Service/
│   │   ├── ContentGenerator.php              # Platform-agnostic content creation
│   │   ├── SocialMediaScheduler.php          # Unified scheduling and queue management
│   │   ├── Platform/
│   │   │   ├── PlatformInterface.php         # Common interface for all platforms
│   │   │   ├── MastodonClient.php            # ✅ Mastodon API integration (WORKING)
│   │   │   ├── LinkedInClient.php            # LinkedIn API integration
│   │   │   ├── FacebookClient.php            # Facebook API integration
│   │   │   └── TwitterClient.php             # Twitter API integration (legacy/paid)
│   │   └── PlatformManager.php               # Platform registry and factory
│   ├── Commands/
│   │   └── SocialMediaCommands.php           # Drush commands for testing and management
│   └── EventSubscriber/
│       └── CronSubscriber.php                # Automated posting scheduler
├── docs/
│   ├── MASTODON_SETUP.md                     # ✅ Complete Mastodon setup guide
│   └── README.md                             # This file
├── scripts/
│   ├── debug_mastodon.sh                     # Mastodon debugging utilities
│   ├── enhanced_debug.sh                     # Form submission debugging
│   └── test_form_submission.sh               # Form testing scripts
├── templates/
│   └── (template files for admin interface)
├── css/
│   └── (styling for admin interface)
├── js/
│   └── (JavaScript for admin interface)
└── social_media_automation.info.yml
```

## Platform Integration

### Supported Platforms

| Platform | Status | API Cost | Authentication | Character Limit | Features |
|----------|--------|----------|---------------|-----------------|----------|
| **Mastodon** | ✅ **WORKING** | Free | OAuth 2.0 | 500 chars | Full automation, mention/boost monitoring, AI responses |
| **LinkedIn** | 🔄 Planned | Free (Personal) | OAuth 2.0 | 3000 chars | Personal posts |
| **Facebook** | 🔄 Planned | Free (with review) | OAuth 2.0 | 63,206 chars | Page posts |
| **Twitter** | 💰 Optional | $100/month | OAuth 1.0a | 280 chars | Full features |
| **Reddit** | 🔄 Future | Free | OAuth 2.0 | 40,000 chars | Subreddit posts |

### Platform Service Interface

```php
interface PlatformInterface {
  public function getName(): string;
  public function isConfigured(): bool;
  public function testConnection(): bool;
  public function getCharacterLimit(): int;
  public function postContent(string $content, array $options = []): array|false;
  public function formatContent(string $content, array $context = []): string;
  public function getAuthenticationUrl(): string;
  public function getRequiredCredentials(): array;
}
```

## Content Generation System

### Content Types

1. **Analytics Summary** (Morning Posts 8AM-12PM)
   - Daily metrics from newsmotivationmetrics
   - Article processing statistics
   - Source diversity reports
   - Platform-optimized formatting

2. **Trending Topics** (Evening Posts 6PM-10PM)
   - Most frequent themes from recent articles
   - AI-detected narrative trends
   - Cross-platform engagement optimization

3. **Bias Insights** (Educational Content)
   - AI limitation explanations
   - Methodology transparency
   - Critical thinking promotion

4. **Manual Content** (On-demand)
   - Admin-generated posts
   - Immediate or scheduled posting
   - Multi-platform distribution

### Content Adaptation

```php
class ContentGenerator {
  public function generateContent(string $type, array $context = []): array {
    // Generate base content
    $baseContent = $this->createBaseContent($type, $context);
    
    // Adapt for each enabled platform
    $platformContent = [];
    foreach ($this->getEnabledPlatforms() as $platform) {
      $platformContent[$platform] = $this->adaptForPlatform($baseContent, $platform);
    }
    
    return $platformContent;
  }
}
```

## 🔄 New: Interaction Monitoring & Response System

### Stephen A. Smith Style AI Responses

The system now automatically monitors and responds to:
- **Mentions**: Direct replies and mentions of your account
- **Boosts/Shares**: People sharing your content
- **Sentiment Analysis**: AI determines positive, negative, or neutral tone

### Response Styles

#### Positive Mentions/Boosts
```
"@user YESSIR! Now THAT'S what I'm talking about! You see the vision! 
Appreciate you sharing the TRUTH with your people!"
```

#### Negative Mentions (Sarcastic but Positive)
```
"@user Oh, I see we got ourselves a CRITIC! Look, I respect the passion, 
but maybe check out our methodology before you swing at the fences!"
```

#### Neutral Mentions
```
"@user Thanks for engaging! Check out our methodology at 
thetruthperspective.org - we're all about transparency!"
```

### Configuration Requirements

For interaction monitoring, add your **Username** in Mastodon settings:
- Server URL: `https://your-mastodon-instance.com`
- Username: `yourusername` (without @)
- Access Token: Your OAuth token

### Processing Frequency
- **Cron-based**: Every time Drupal cron runs
- **Responsive**: Usually processes interactions within 15-60 minutes
- **Rate Limited**: Respects Mastodon API limits

## Configuration System

### Unified Settings Structure

```yaml
social_media_automation.settings:
  global:
    enabled: true
    posting_schedule:
      morning_start: '08:00'
      morning_end: '12:00'
      evening_start: '18:00'
      evening_end: '22:00'
    content_types:
      analytics_summary: true
      trending_topics: true
      bias_insights: true
  
  platforms:
    mastodon:
      enabled: true
      server_url: 'https://mastodon.social'
      client_id: 'xxx'
      client_secret: 'xxx'
      access_token: 'xxx'
      character_limit: 500
    
    linkedin:
      enabled: true
      client_id: 'xxx'
      client_secret: 'xxx'
      access_token: 'xxx'
      character_limit: 3000
    
    facebook:
      enabled: false
      app_id: 'xxx'
      app_secret: 'xxx'
      access_token: 'xxx'
      page_id: 'xxx'
    
    twitter:
      enabled: false
      api_key: 'xxx'
      api_secret: 'xxx'
      access_token: 'xxx'
      access_secret: 'xxx'
```

## Queue System

### Posting Workflow

1. **Cron Trigger**: Check if current time falls within posting windows
2. **Content Generation**: Create platform-specific content
3. **Queue Items**: Add posts to Drupal queue system
4. **Queue Processing**: Worker processes posts with retry logic
5. **Status Tracking**: Log success/failure for each platform
6. **Error Handling**: Retry failed posts with backoff strategy

### Queue Item Structure

```php
class SocialMediaPostItem {
  public string $platform;
  public string $content;
  public array $options;
  public int $retry_count;
  public int $scheduled_time;
  public string $content_type;
}
```

## Admin Interface

### Dashboard Features

- **Platform Status**: Real-time connection status for each platform
- **Recent Posts**: Last 10 posts across all platforms with engagement metrics
- **Pending Queue**: Upcoming scheduled posts and retry queue
- **Content Preview**: Live preview of generated content for each platform
- **Manual Posting**: Create and send immediate posts
- **Analytics**: Basic posting statistics and platform performance

### Configuration Interface

- **Platform Credentials**: Tabbed interface for each platform setup
- **Posting Schedule**: Visual time range selectors
- **Content Settings**: Enable/disable content types per platform
- **Test Connections**: Individual platform testing with detailed feedback
- **Bulk Operations**: Enable/disable multiple platforms at once

## Installation & Setup

### 1. Enable Module ✅ DONE
```bash
drush en social_media_automation
```

### 2. Configure Mastodon ✅ WORKING
1. Go to `/admin/config/services/social-media-automation/settings`
2. Follow the complete setup guide: `social_media_automation/docs/MASTODON_SETUP.md`
3. Test connection using "Test Mastodon (Send Hello World Post)" button
4. Save configuration to enable automated posting

### 3. Debugging Tools ✅ AVAILABLE
Use the provided debugging scripts in `social_media_automation/scripts/`:
- `debug_mastodon.sh` - Monitor Mastodon operations
- `enhanced_debug.sh` - Form submission debugging  
- `test_form_submission.sh` - Form testing utilities

### 4. Platform-Specific Setup

#### ✅ Mastodon Setup (WORKING)
See complete guide: `social_media_automation/docs/MASTODON_SETUP.md`

1. Choose a Mastodon server (mastodon.social, mastodon.world, etc.)
2. Create account on chosen server
3. Go to Preferences > Development > New Application
4. Copy Access Token
5. Enter server URL and token in module configuration
6. Test with "Hello World" post

#### 🔄 LinkedIn Setup (Future)
1. Create LinkedIn Developer App
2. Set up OAuth 2.0 with appropriate scopes
3. Generate access token for personal posting
4. Configure in module settings

#### 🔄 Facebook Setup (Future)
1. Create Facebook App in Meta Developers
2. Request pages_manage_posts permission
3. Complete app review process
4. Configure page access token

## Features

### Automated Posting
- ✅ **Smart Scheduling**: Posts during optimal time windows
- ✅ **Content Adaptation**: Platform-specific formatting and character limits
- ✅ **Queue Management**: Reliable posting with retry logic
- ✅ **Error Recovery**: Automatic retry for failed posts

### Content Management
- ✅ **Multi-Type Content**: Analytics, trends, insights, and manual posts
- ✅ **Live Preview**: See how content appears on each platform
- ✅ **Manual Override**: Create and post custom content immediately
- ✅ **Scheduling**: Queue posts for specific times

### Platform Support
- ✅ **Mastodon**: Full automation with free API
- ✅ **LinkedIn**: Personal profile posting
- 🔄 **Facebook**: Page posting (requires app review)
- 💰 **Twitter**: Full features with paid API
- 🔄 **Reddit**: Community posting (future)

### Administration
- ✅ **Unified Dashboard**: All platforms in one interface
- ✅ **Status Monitoring**: Real-time platform health checks
- ✅ **Activity Logs**: Detailed posting history and error tracking
- ✅ **Performance Metrics**: Basic engagement and reach statistics

## Dependencies

- **newsmotivationmetrics**: For analytics data and content generation
- **Drupal Core**: Queue, cron, config, state, logging, HTTP client
- **Platform APIs**: OAuth libraries and HTTP clients for each platform

## Security & Privacy

- ✅ **Encrypted Storage**: All API credentials stored in Drupal configuration
- ✅ **Permission-Based**: Admin access required for configuration
- ✅ **Audit Trail**: Complete logging of all posting activities
- ✅ **No Personal Data**: Only public analytics data is shared
- ✅ **Platform Compliance**: Follows each platform's API terms of service

## Performance Considerations

- **Lightweight Cron**: Minimal impact on site performance
- **Queue-Based**: Prevents timeouts and allows retry logic
- **Efficient Content Generation**: Cached expensive operations
- **Rate Limiting**: Respects platform API limits
- **Error Handling**: Graceful degradation when platforms are unavailable

## Future Enhancements

### Phase 2
- **Reddit Integration**: Subreddit posting for relevant communities
- **Image Support**: Automated image generation and posting
- **Analytics Integration**: Platform-specific engagement metrics
- **Content Templates**: Customizable post templates

### Phase 3
- **AI-Powered Timing**: Optimal posting time prediction
- **Cross-Platform Analytics**: Unified performance dashboard
- **A/B Testing**: Content variation testing across platforms
- **Advanced Scheduling**: Campaign-based posting strategies

## Troubleshooting

### Common Issues

1. **Platform Connection Failed**
   - Verify API credentials are correct
   - Check platform-specific rate limits
   - Ensure proper OAuth scopes/permissions

2. **Posts Not Sending**
   - Confirm automation is enabled globally and per-platform
   - Check cron is running regularly
   - Review queue worker status

3. **Content Generation Errors**
   - Verify newsmotivationmetrics module is functioning
   - Check article data availability
   - Review content type configurations

### Debug Commands

```bash
# Test platform connections
drush sma:test-connections

# Generate test content
drush sma:generate-content analytics_summary

# Process posting queue manually
drush sma:process-queue

# Show platform status
drush sma:status

# Test interaction monitoring and responses
drush sma:interactions

# Manual cron execution (posts + interactions)
drush sma:cron
```

## API Documentation

### Content Generation API
```php
// Generate content for all platforms
$content = \Drupal::service('social_media_automation.content_generator')
  ->generateContent('analytics_summary');

// Post to specific platform
$result = \Drupal::service('social_media_automation.platform_manager')
  ->getPlatform('mastodon')
  ->postContent($content['mastodon']);
```

### Platform Integration API
```php
// Register new platform
class CustomPlatformClient implements PlatformInterface {
  // Implementation...
}

// Add to platform manager
\Drupal::service('social_media_automation.platform_manager')
  ->registerPlatform('custom', CustomPlatformClient::class);
```

This architecture provides a robust, extensible foundation for multi-platform social media automation while maintaining the proven content generation and scheduling logic from the original Twitter module.
