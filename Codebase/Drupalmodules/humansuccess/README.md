# Human Success Module

A Drupal 11 module for tracking and displaying human success metrics and achievements.

## Overview

The Human Success module provides a comprehensive dashboard system for monitoring and analyzing human success metrics. It includes public-facing metrics pages and administrative tools for system management.

## Features

### Public Features
- **Dashboard** (`/human-success`) - Overview of success metrics and recent activities
- **Metrics Page** (`/human-success/metrics`) - Detailed analytics and insights
- Mobile-responsive design
- Interactive charts and visualizations

### Administrative Features
- **Admin Dashboard** (`/admin/human-success`) - System management and monitoring
- Real-time system status monitoring
- User engagement analytics
- System performance metrics
- Activity logging and monitoring

## Installation

1. Place the module in your Drupal site's `modules/custom/` directory
2. Enable the module via Drush or the admin interface:
   ```bash
   drush en humansuccess
   ```
3. Clear the cache:
   ```bash
   drush cr
   ```

## Configuration

### Permissions
The module defines the following permissions:
- **View Human Success Data** - Access to public dashboards and metrics
- **Administer Human Success** - Access to administrative functions

### Routing
- `/human-success` - Main dashboard (public)
- `/human-success/metrics` - Metrics page (public)
- `/admin/human-success` - Admin dashboard (restricted)

## File Structure

```
humansuccess/
├── css/                          # Stylesheets
│   ├── dashboard.css            # Dashboard styles
│   ├── metrics.css              # Metrics page styles
│   └── admin.css                # Admin dashboard styles
├── js/                          # JavaScript files
│   ├── dashboard.js             # Dashboard functionality
│   ├── metrics.js               # Metrics page functionality
│   └── admin.js                 # Admin dashboard functionality
├── src/
│   └── Controller/
│       └── HumansuccessController.php  # Main controller
├── templates/                   # Twig templates
│   ├── humansuccess-dashboard.html.twig
│   ├── humansuccess-metrics.html.twig
│   └── humansuccess-admin.html.twig
├── humansuccess.info.yml        # Module definition
├── humansuccess.libraries.yml   # Asset libraries
├── humansuccess.module          # Module hooks and theme definitions
├── humansuccess.permissions.yml # Permission definitions
├── humansuccess.routing.yml     # Route definitions
└── README.md                    # This file
```

## Usage

### Accessing the Dashboard
Visit `/human-success` to view the main dashboard with:
- User statistics
- Active goals tracking
- Success rate metrics
- Recent activity feed

### Viewing Metrics
Visit `/human-success/metrics` for detailed analytics including:
- Monthly progress charts
- Category breakdowns
- User engagement statistics

### Administration
Administrators can access `/admin/human-success` for:
- System status monitoring
- Performance metrics
- Activity logs
- Administrative actions

## Customization

### Styling
The module includes comprehensive CSS files that can be customized:
- `css/dashboard.css` - Main dashboard styling
- `css/metrics.css` - Metrics page styling  
- `css/admin.css` - Admin interface styling

### Templates
Twig templates can be overridden in your theme:
- `humansuccess-dashboard.html.twig`
- `humansuccess-metrics.html.twig`
- `humansuccess-admin.html.twig`

### Data Sources
The controller methods can be modified to connect to real data sources:
- `getDashboardData()` - Dashboard statistics
- `getMetricsData()` - Metrics and analytics
- `getAdminData()` - Administrative data

## Development

### Adding New Pages
1. Add route to `humansuccess.routing.yml`
2. Add controller method to `HumansuccessController.php`
3. Create corresponding template in `templates/`
4. Add CSS/JS assets to `humansuccess.libraries.yml`

### Extending Functionality
The module is designed to be extensible:
- Add new controller methods for additional pages
- Create custom services for data processing
- Implement entity types for data storage
- Add configuration forms for admin settings

## Requirements

- Drupal 11.x
- PHP 8.1+
- Modern web browser with JavaScript enabled

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

The module is optimized for performance with:
- Efficient CSS and JavaScript loading
- Responsive design for mobile devices
- Optimized database queries (when connected to real data)
- Proper caching implementation

## Security

- Proper permission-based access control
- Input sanitization and validation
- CSRF protection via Drupal core
- XSS prevention through template escaping

## Contributing

When contributing to this module:
1. Follow Drupal coding standards
2. Add proper documentation for new functions
3. Include appropriate error handling
4. Test on multiple browsers and devices
5. Update this README for significant changes

## License

This module is licensed under the GPL v2 license, consistent with Drupal core.

## Support

For issues and feature requests, please use the project's issue queue or contact the development team.
