# Custom Event Manager

A professional WordPress plugin for managing events with custom post types, admin dashboard, and shortcodes.

## Features

- **Custom Post Type**: Register and manage events natively within WordPress.
- **Event Categories**: Organize events by category.
- **Admin Settings**: Configurable plugin settings.
- **Shortcodes**: Display events using `[event_list]` and `[event_single]`.
- **Event Metadata**: Store event date, time, location, and capacity.
- **Admin Metabox**: Easy-to-use interface for event details.
- **Pagination**: Built-in pagination for event listings.
- **REST API Support**: Full REST API integration for developers.
- **Security**: Nonce verification, input sanitization, capability checks.

## Installation

1. Download or clone the plugin into `/wp-content/plugins/custom-event-manager/`.
2. Activate the plugin from WordPress Admin → Plugins.
3. Navigate to **Events** in the admin menu to start creating events.

## Quick Start

### Create an Event

1. Go to Admin Dashboard → **Events** → **Add New**
2. Enter event title and description
3. Fill in the **Event Details** metabox:
   - Event Date (required)
   - Event Time (optional)
   - Event Location (required)
   - Event Capacity (optional, 0 = unlimited)
4. Assign to an **Event Category**
5. Click **Publish**

### Display Events on Frontend

#### List All Events
```
[event_list posts_per_page="10"]
```

#### Filter by Category
```
[event_list category="conference" posts_per_page="5"]
```

#### Display Single Event
```
[event_single id="42"]
```

## Shortcode Documentation

### [event_list]

Display a list of events with optional filters and sorting.

**Attributes:**
- `posts_per_page` – Events per page (default: respects admin setting)
- `orderby` – Sort field: `meta_value` (date), `date`, `title`, `modified`
- `meta_key` – Meta field for sorting (default: `_cem_event_date`)
- `order` – Sort direction: `ASC` or `DESC`
- `category` – Filter by category slug
- `paged` – Current page (auto-detected)

**Examples:**
```
[event_list]
[event_list posts_per_page="5"]
[event_list category="webinar" orderby="date" order="DESC"]
[event_list posts_per_page="20" order="ASC"]
```

### [event_single]

Display a single event by ID.

**Attributes:**
- `id` – Event post ID (required)

**Example:**
```
[event_single id="123"]
```

## Admin Settings

Navigate to **Events → Settings** to configure:

| Option | Description | Default |
|--------|-------------|---------|
| Events Per Page | Events to display in listings | 10 |
| Default Currency | Currency for pricing (USD, EUR, GBP, etc.) | USD |
| Enable RSVP | Allow RSVP functionality | Disabled |

## Event Metadata

Each event stores the following via WordPress Post Meta API:

- **Event Date** (`_cem_event_date`) – ISO format (YYYY-MM-DD)
- **Event Time** (`_cem_event_time`) – Time format (HH:MM)
- **Event Location** (`_cem_event_location`) – Venue name/address
- **Event Capacity** (`_cem_event_capacity`) – Max attendees (0 = unlimited)

Data is stored securely in `wp_postmeta` with nonce verification and sanitization.

## Architecture

```
custom-event-manager/
├── custom-event-manager.php          # Main plugin file
├── includes/
│   ├── class-event-post-type.php      # CPT & taxonomy registration
│   ├── class-event-metabox.php        # Admin metabox handler
│   ├── class-event-shortcode.php      # Shortcode handlers
│   ├── class-event-settings.php       # Settings page
│   ├── helpers.php                    # Utility functions
│   └── class-cem.php                  # Core plugin class
├── admin/
│   └── admin-settings-page.php        # Admin UI utilities
└── assets/
    ├── css/
    │   └── event-style.css            # Frontend & admin styles
    └── js/
        └── event-script.js            # Frontend interactions
```

## Security

This plugin follows WordPress security best practices:

✅ **Input Validation** – All user input sanitized before use  
✅ **Output Escaping** – All output escaped (esc_html, esc_attr, wp_kses_post)  
✅ **Nonce Verification** – CSRF protection on forms  
✅ **Capability Checks** – User permissions verified  
✅ **Prepared Statements** – WP_Query prevents SQL injection  
✅ **No Direct DB Queries** – Uses WordPress APIs exclusively  

## Developer Hooks

### Filters

- `cem_event_query_args` – Modify WP_Query arguments for event lists
- `cem_event_query_args` – Extend or modify event query

### Actions

- `cem_before_read_more_link` – Before event "View Details" link
- `cem_after_read_more_link` – After event "View Details" link
- `cem_single_event_footer` – At end of single event display

**Example:**
```php
add_filter( 'cem_event_query_args', function( $args ) {
    $args['posts_per_page'] = 5;
    return $args;
});
```

## Shortcode Execution Flow

```
[event_list posts_per_page="10"]
        ↓
Parse & Sanitize Attributes
        ↓
Build WP_Query with event post type
        ↓
Apply Filters (cem_event_query_args)
        ↓
Execute WP_Query (Prepared Statements)
        ↓
Output Buffering
        ├── Escape all data
        ├── Get metadata safely
        ├── Format dates
        └── Render pagination
        ↓
Return Clean, Escaped HTML
```

## Compatibility

- **WordPress**: 5.0+
- **PHP**: 7.2+
- **Database**: MySQL 5.7+ / MariaDB 10.2+

## License

GPL v2 or later. See [LICENSE](LICENSE) file for details.

## Support

For issues, questions, and feature requests, please visit:  
https://github.com/vizahat36/Custom-WordPress-Plugin-Event-Manager

## Changelog

### v1.0.0 (January 2026)
- ✅ STEP 1: Plugin bootstrap & structure
- ✅ STEP 2: Custom post type & taxonomy
- ✅ STEP 3: Event metabox with date, time, location, capacity
- ✅ STEP 4: Admin settings page (events per page, currency, RSVP)
- ✅ STEP 5: Frontend shortcodes with WP_Query integration
- Production-ready security implementation
