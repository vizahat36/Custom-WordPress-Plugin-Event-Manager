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

#### List All Events
```
[event_list posts_per_page="10" orderby="meta_value" order="ASC"]
```

**Attributes:**
- `posts_per_page` – Number of events per page (respects admin setting if not specified)
- `orderby` – Sort field: `meta_value` (default, sorts by date), `date` (post date), `title`, `modified`
- `meta_key` – Meta field for sorting (default: `_cem_event_date`)
- `order` – Sort direction: `ASC` (ascending), `DESC` (descending)
- `category` – Filter by event category slug (e.g., `category="conference"`)
- `paged` – Current page (auto-detected from URL)

**Examples:**
```
[event_list posts_per_page="5"]
[event_list category="conference" orderby="date"]
[event_list posts_per_page="20" order="DESC"]
```

#### Single Event
```
[event_single id="123"]
```

**Attributes:**
- `id` – Event post ID (required)

**Example:**
```
[event_single id="42"]
```

#### Backward Compatibility
- `[cem_events]` → Alias for `[event_list]`
- `[cem_event_single]` → Alias for `[event_single]`

### Shortcode Execution Flow (STEP 5)

**[event_list] Execution:**
```
1. User adds shortcode to page/post content
   ↓
2. WordPress parses shortcode attributes
   [event_list posts_per_page="10" category="webinar"]
   ↓
3. display_events_list() handler called
   ├── Sanitize attributes (intval, sanitize_text_field)
   ├── Retrieve default from admin setting if not specified
   └── Validate order direction (ASC/DESC)
   ↓
4. Build WP_Query arguments
   ├── post_type = 'event'
   ├── posts_per_page = sanitized value
   ├── orderby = meta_value (default)
   ├── meta_key = _cem_event_date
   └── tax_query = category filter (if provided)
   ↓
5. apply_filters('cem_event_query_args') for extensibility
   ↓
6. Execute WP_Query (NO DIRECT DB ACCESS)
   ├── WordPress handles sanitization
   ├── Prepared statements used internally
   └── Results retrieved safely
   ↓
7. Output Buffering Started
   ├── Check if posts exist
   ├── Loop through posts
   ├── For each post:
   │   ├── Escape all data (esc_html, esc_attr)
   │   ├── Get metadata safely (get_post_meta)
   │   ├── Format dates using wp_date()
   │   └── Render HTML fragment
   ├── Render pagination if needed
   └── Reset post data
   ↓
**Security Features:**
- ✅ All input sanitized before use
- ✅ All output escaped (esc_html, esc_attr, wp_kses_post)
- ✅ Metadata retrieved via safe API (get_post_meta)
- ✅ WP_Query handles prepared statements
- ✅ Action hooks for extensibility (cem_before_read_more_link, cem_after_read_more_link)
- ✅ Post status verification (only published posts shown to non-admins)
- ✅ No direct SQL queries

### Plugin Settings

Navigate to Events → Settings to configure:
- Events per page
- Enable/Disable RSVP

## Event Metadata

Each event stores the following metadata using WordPress Post Meta API:

- **Event Date** (`_cem_event_date`) – ISO date format (YYYY-MM-DD)
- **Event Time** (`_cem_event_time`) – Time format (HH:MM)
- **Event Location** (`_cem_event_location`) – Venue name or address
- **Event Capacity** (`_cem_event_capacity`) – Maximum attendees (0 = unlimited)

All metadata is stored securely in the `wp_postmeta` table with nonce verification and sanitization during save.

## Plugin Settings

Navigate to **Events → Settings** to configure:

| Option | Description | Default |
|--------|-------------|---------|
| Events Per Page | Number of events to display in listings | 10 |
| Default Currency | Currency for event pricing | USD |
| Enable RSVP | Allow RSVP functionality | Disabled |

Settings are stored in `wp_options` table using the WordPress Settings API.

```
custom-event-manager/
├── custom-event-manager.php          # Main plugin file
├── readme.md                          # This file
├── includes/
│   ├── class-event-post-type.php      # CPT registration
│   ├── class-event-metabox.php        # Event metabox & meta fields
│   ├── class-event-shortcode.php      # Shortcode handlers
│   ├── class-event-settings.php       # Settings page
│   └── helpers.php                    # Helper functions
├── admin/
│   └── admin-settings-page.php        # Admin metabox rendering
└── assets/
    ├── css/
    │   └── event-style.css            # Frontend & admin styles
    └── js/
        └── event-script.js            # Frontend scripts
```

## Security Implementation

### STEP 3: Event Meta Fields
- **Nonce Verification**: `wp_verify_nonce()` checks on save to prevent CSRF attacks.
- **Input Sanitization**: `sanitize_text_field()`, `intval()` for type casting.
- **Capability Checks**: Only users with `edit_post` capability can save events.
- **Autosave Skip**: Metadata skipped during WordPress autosave to prevent duplicate saves.
- **Format Validation**: Regex validation for date (YYYY-MM-DD) and time (HH:MM) formats.
- **Output Escaping**: `esc_attr()` and `esc_html()` when rendering meta in admin.

**Data Flow (Admin → DB):**
1. Admin fills metabox fields on event edit page.
2. Nonce is generated and embedded in form.
3. On save, `save_event_meta()` hook fires after post save.
4. Nonce is verified; if invalid, save is aborted.
5. User capability is checked; only editors+ can save.
6. Input is sanitized and validated per field type.
7. Data stored in `wp_postmeta` table with prefixed keys (`_cem_*`).
8. On display, values are escaped to prevent XSS.

### STEP 4: Admin Settings Page
- **Settings API**: Uses `register_setting()` and `add_settings_section()` for structured registration.
- **Sanitization Callbacks**: Each setting has a dedicated sanitization function.
- **Nonce Protection**: Built-in by `settings_fields()` in Settings API.
- **Capability Checks**: Only admins can access settings via `manage_options` capability.
- **Options Table**: Settings stored in `wp_options` table, not serialized PHP.

**Settings Lifecycle:**
1. Admin navigates to Events → Settings.
2. `register_settings()` loads saved values from `wp_options`.
3. Form displays with sanitized values.
4. Admin modifies and submits.
5. `settings_fields()` includes nonce for CSRF protection.
6. `register_setting()` sanitizes input before saving.
7. Values stored in `wp_options` table.
8. On display, `get_option()` retrieves with fallback defaults.

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
