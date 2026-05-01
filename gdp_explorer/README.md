# GDP Explorer Module

Interactive D3.js visualization of economic indicators and their relationships through a comprehensive 4-tier system.

## Features

- **4-Tier System**: Core Indicators → Supporting → Foundation → Raw Inputs
- **Progressive Discovery**: Start with overview, click nodes to explore neighborhoods
- **Interactive Filters**: Filter by organization, relationship strength, view modes
- **Real-time Updates**: D3.js force-directed network with smooth animations
- **Professional Presentation**: Suitable for public-facing analytics dashboard

## Access

- **URL**: `/gdp-explorer`
- **Public Access**: No authentication required
- **Mobile Responsive**: Optimized for desktop and mobile viewing

## Technical Stack

- **Drupal 11**: Modern module architecture with proper theming
- **D3.js v7**: Interactive network visualization
- **Force Simulation**: Physics-based node positioning
- **External Libraries**: D3.js loaded via CDN

## Installation

1. Enable the module in Drupal admin
2. Clear cache if needed
3. Visit `/gdp-explorer` to view the visualization

## Development

- **Controller**: `GdpExplorerController::overview()`
- **Template**: `templates/gdp-explorer.html.twig`
- **Assets**: Managed via `gdpexplorer.libraries.yml`
- **Theme Hook**: Defined in `gdpexplorer.module`