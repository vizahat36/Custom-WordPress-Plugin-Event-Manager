# Custom Event Manager

A professional WordPress plugin for managing events with custom post types, admin dashboard, and shortcodes.

## Features

- **Custom Post Type**: Register and manage events natively within WordPress.
- **Event Categories**: Organize events by category.
- **Admin Settings**: Configurable plugin settings.
- **Shortcodes**: Display events using `[cem_events]` and `[cem_event_single]`.
- **Event Metadata**: Store event date, time, and location.
- **RSVP Support**: Optional RSVP functionality.
- **REST API Support**: Full REST API integration for developers.

## Installation

1. Download or clone the plugin into `/wp-content/plugins/custom-event-manager/`.
2. Activate the plugin from WordPress Admin → Plugins.
3. Navigate to Events in the admin menu to start creating events.

## Usage

### Create an Event

1. Go to Admin Dashboard → Events → Add New.
2. Enter the event title and description.
3. Fill in the event metadata (date, time, location).
4. Set the event category.
5. Publish the event.

### Display Events with Shortcodes

#### All Events
```
[cem_events posts_per_page="10" orderby="date" order="DESC"]
```

#### Single Event
```
[cem_event_single id="123"]
```

### Plugin Settings

Navigate to Events → Settings to configure:
- Events per page
- Enable/Disable RSVP

## File Structure

```
custom-event-manager/
├── custom-event-manager.php          # Main plugin file
├── readme.md                          # This file
├── includes/
│   ├── class-event-post-type.php      # CPT registration
│   ├── class-event-shortcode.php      # Shortcode handlers
│   ├── class-event-settings.php       # Settings page
│   └── helpers.php                    # Helper functions
├── admin/
│   └── admin-settings-page.php        # Admin metabox rendering
└── assets/
    ├── css/
    │   └── event-style.css            # Frontend styles
    └── js/
        └── event-script.js            # Frontend scripts
```

## Code Standards

- Follows WordPress Coding Standards.
- Security: Nonce verification, capability checks, input sanitization.
- Hooks: Action/filter-based extensibility.
- OOP: Class-based architecture for maintainability.

## Hooks

### Actions
- `cem_before_event_save` – Before saving event metadata.
- `cem_after_event_save` – After saving event metadata.

### Filters
- `cem_event_query_args` – Modify event query arguments.
- `cem_event_display_html` – Modify event HTML output.

## Contributing

Contributions are welcome. Please follow WordPress Coding Standards and submit pull requests.

## License

GPL v2 or later.

## Support

For issues and feature requests, please visit the GitHub repository.
