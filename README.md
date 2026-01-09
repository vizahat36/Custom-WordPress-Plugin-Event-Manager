# Custom Event Manager

A professional WordPress plugin for managing events with custom post types, admin dashboard, and shortcodes.

## Overview

- Headless-ready event manager with CPT, taxonomy, REST API, and AJAX RSVP
- Frontend shortcodes for listings, single event view, RSVP, and filters
- Admin metabox for event details (date, time, location, capacity)
- Settings page for pagination, currency, RSVP toggle
- Advanced filtering: date range, location, keyword search (preserves pagination)

## Screenshots

- Admin – Events list & metabox: [assets/screenshots/admin-events.png](assets/screenshots/admin-events.png)
- Frontend – Event list with filters: [assets/screenshots/frontend-events.png](assets/screenshots/frontend-events.png)

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

## REST API Endpoints (STEP 8)

Custom Event Manager exposes REST API endpoints for headless WordPress applications and third-party integrations.

### Base URL

```
https://yoursite.com/wp-json/cem/v1/
```

### Endpoints

#### GET /events

List all published events with pagination support.

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Current page number |
| per_page | integer | 10 | Events per page (1-100) |
| orderby | string | date | Sort by: `date`, `title`, `event_date` |
| order | string | DESC | Sort order: `ASC` or `DESC` |
| category | string | - | Filter by event category slug |

**Example Request:**
```bash
GET /wp-json/cem/v1/events?per_page=5&orderby=event_date&order=ASC
```

**Example Response:**
```json
[
  {
    "id": 123,
    "title": {
      "rendered": "Tech Conference 2026",
      "raw": "Tech Conference 2026"
    },
    "content": {
      "rendered": "<p>Join us for the biggest tech event...</p>",
      "raw": "Join us for the biggest tech event..."
    },
    "excerpt": {
      "rendered": "Join us for the biggest tech event...",
      "raw": ""
    },
    "date": "2026-03-15",
    "time": "09:00",
    "location": "Convention Center, San Francisco",
    "capacity": 500,
    "rsvp_count": 247,
    "rsvp_available": 253,
    "categories": [
      {
        "id": 5,
        "name": "Conference",
        "slug": "conference"
      }
    ],
    "link": "https://yoursite.com/event/tech-conference-2026/",
    "featured_image": {
      "id": 456,
      "alt": "Conference venue",
      "thumbnail": {
        "url": "https://yoursite.com/wp-content/uploads/event-thumb.jpg",
        "width": 150,
        "height": 150
      },
      "medium": {
        "url": "https://yoursite.com/wp-content/uploads/event-medium.jpg",
        "width": 300,
        "height": 300
      },
      "large": {
        "url": "https://yoursite.com/wp-content/uploads/event-large.jpg",
        "width": 1024,
        "height": 768
      },
      "full": {
        "url": "https://yoursite.com/wp-content/uploads/event-full.jpg",
        "width": 1920,
        "height": 1080
      }
    },
    "author": {
      "id": 1,
      "name": "Admin"
    },
    "published_date": "2026-01-10 14:30:00",
    "modified_date": "2026-01-10 15:45:00"
  }
]
```

**Response Headers:**
```
X-WP-Total: 42
X-WP-TotalPages: 9
Link: <https://yoursite.com/wp-json/cem/v1/events?page=2>; rel="next"
```

#### GET /events/{id}

Get a single event by ID.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Event post ID |

**Example Request:**
```bash
GET /wp-json/cem/v1/events/123
```

**Example Response:**
```json
{
  "id": 123,
  "title": {
    "rendered": "Tech Conference 2026",
    "raw": "Tech Conference 2026"
  },
  "content": {
    "rendered": "<p>Join us for the biggest tech event of the year...</p>",
    "raw": "Join us for the biggest tech event of the year..."
  },
  "date": "2026-03-15",
  "time": "09:00",
  "location": "Convention Center, San Francisco",
  "capacity": 500,
  "rsvp_count": 247,
  "rsvp_available": 253,
  "categories": [
    {
      "id": 5,
      "name": "Conference",
      "slug": "conference"
    }
  ],
  "link": "https://yoursite.com/event/tech-conference-2026/",
  "featured_image": {...},
  "author": {
    "id": 1,
    "name": "Admin"
  }
}
```

**Error Response (404):**
```json
{
  "code": "cem_event_not_found",
  "message": "Event not found.",
  "data": {
    "status": 404
  }
}
```

### REST API Request Flow

```
HTTP Request → /wp-json/cem/v1/events
        ↓
WordPress REST API Router
        ↓
Permission Check (public access)
        ↓
Sanitize Parameters
   ├── absint() for IDs and integers
   ├── sanitize_text_field() for strings
   └── Validate enum values
        ↓
Build WP_Query Arguments
   ├── post_type = 'event'
   ├── post_status = 'publish'
   ├── Apply pagination (page, per_page)
   ├── Apply sorting (orderby, order)
   └── Apply filtering (tax_query)
        ↓
Execute WP_Query (Prepared Statements)
        ↓
Retrieve Event Meta Data
   ├── _cem_event_date
   ├── _cem_event_time
   ├── _cem_event_location
   └── _cem_event_capacity
        ↓
Get RSVP Count
   ├── Query event_rsvp CPT
   └── Count records for event ID
        ↓
Escape All Output
   ├── esc_html() for text
   ├── esc_url() for URLs
   ├── esc_attr() for attributes
   └── absint() for integers
        ↓
Format JSON Response
   ├── Standard WordPress REST format
   ├── Pagination headers (X-WP-Total)
   └── HATEOAS links (prev, next)
        ↓
Return JSON Response (200 OK)
```

### REST API Security

- **Permission Callbacks**: All endpoints require permission checks
- **Public Access**: Events are public by default (customizable)
- **Parameter Sanitization**: All input sanitized before use
- **Output Escaping**: All data escaped before response
- **Prepared Statements**: WP_Query handles SQL safely
- **Rate Limiting**: Use WordPress caching plugins
- **Authentication**: WordPress REST API authentication (JWT, OAuth)

### REST API Customization

**Filter event REST response:**
```php
add_filter( 'cem_rest_prepare_event', function( $event_data, $event ) {
    // Add custom field
    $event_data['custom_field'] = get_post_meta( $event->ID, '_custom_meta', true );
    
    // Remove sensitive data
    unset( $event_data['author'] );
    
    return $event_data;
}, 10, 2 );
```

**Restrict API access to authenticated users:**
```php
add_filter( 'rest_pre_dispatch', function( $result, $server, $request ) {
    if ( strpos( $request->get_route(), '/cem/v1/' ) === 0 ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Authentication required.' ),
                array( 'status' => 401 )
            );
        }
    }
    return $result;
}, 10, 3 );
```

**Usage Examples:**

**Fetch events with JavaScript:**
```javascript
fetch('https://yoursite.com/wp-json/cem/v1/events?per_page=5')
  .then(response => response.json())
  .then(events => {
    events.forEach(event => {
      console.log(`${event.title.rendered} - ${event.date}`);
    });
  });
```

**Fetch with pagination:**
```javascript
async function getAllEvents() {
  let page = 1;
  let allEvents = [];
  
  while (true) {
    const response = await fetch(`/wp-json/cem/v1/events?page=${page}`);
    const events = await response.json();
    
    if (events.length === 0) break;
    
    allEvents = allEvents.concat(events);
    page++;
  }
  
  return allEvents;
}
```

**cURL example:**
```bash
curl -X GET "https://yoursite.com/wp-json/cem/v1/events?per_page=10&orderby=event_date&order=ASC" \
     -H "Content-Type: application/json"
```

**Python example:**
```python
import requests

response = requests.get('https://yoursite.com/wp-json/cem/v1/events', params={
    'per_page': 10,
    'orderby': 'event_date',
    'order': 'ASC'
})

events = response.json()
for event in events:
    print(f"{event['title']['rendered']} - {event['date']}")
```

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

## Email Notifications (STEP 7)

Automatic emails are sent when users RSVP to events.

### Email Trigger Logic

```
1. User submits RSVP via [event_single] form
                    ↓
2. RSVP validated and saved to database
                    ↓
3. Action hook fires: do_action('cem_rsvp_created', $rsvp_id, $event_id, $name, $email)
                    ↓
4. Email handler listens to cem_rsvp_created
                    ↓
5. Retrieve event details from database
   ├── Event title (get_the_title)
   ├── Event date (get_post_meta)
   ├── Event time (get_post_meta)
   ├── Event location (get_post_meta)
   └── Event URL (get_permalink)
                    ↓
6. FORMAT & ESCAPE all data
   ├── esc_html() for text content
   ├── esc_url() for URLs
   ├── sanitize_email() for email addresses
   └── wp_date() for date formatting
                    ↓
7. Build confirmation email (to attendee)
   ├── Subject: "RSVP Confirmation: {Event Title}"
   ├── Body: Personalized message with event details
   ├── Headers: From site admin, Content-Type UTF-8
   └── Apply filters for customization
                    ↓
8. Send via wp_mail() to attendee
   ├── wp_mail($to, $subject, $message, $headers)
   ├── Returns: true on success, false on failure
   └── Log error if failed
                    ↓
9. Build admin notification email
   ├── Subject: "New RSVP: {Event} - {Attendee}"
   ├── Body: Event details + attendee info
   ├── Headers: Reply-To attendee email
   └── Apply filters for customization
                    ↓
10. Send via wp_mail() to site admin
    ├── To: get_option('admin_email')
    ├── Log error if failed
    └── Fires after-email action hooks
                    ↓
11. Return to AJAX handler (success)
```

### Email Customization via Filters

**Customize confirmation email subject:**
```php
add_filter( 'cem_rsvp_confirmation_subject', function( $subject, $event, $name ) {
    return sprintf( 'You\'re registered for %s!', $event );
}, 10, 3 );
```

**Customize confirmation email body:**
```php
add_filter( 'cem_rsvp_confirmation_message', function( $message, $event, $name, $date, $time, $location ) {
    $custom = "Dear {$name},\r\n\r\n";
    $custom .= "Your RSVP for {$event} on {$date} has been confirmed!\r\n";
    $custom .= "We'll see you at {$location}.\r\n\r\n";
    $custom .= "Cheers!";
    return $custom;
}, 10, 6 );
```

**Customize admin notification subject:**
```php
add_filter( 'cem_admin_notification_subject', function( $subject, $event, $name, $email ) {
    return "[New RSVP] {$event} - {$name}";
}, 10, 4 );
```

**Customize email headers (add CC/BCC):**
```php
add_filter( 'cem_rsvp_confirmation_headers', function( $headers ) {
    $headers[] = 'Cc: manager@example.com';
    return $headers;
});
```

**Hook into email send events:**
```php
// After confirmation email sent
add_action( 'cem_after_confirmation_email', function( $sent, $to, $event ) {
    if ( $sent ) {
        error_log( "Confirmation sent to {$to} for {$event}" );
    }
}, 10, 3 );

// After admin notification sent
add_action( 'cem_after_admin_notification', function( $sent, $admin_email, $event ) {
    // Log or trigger external webhook
}, 10, 3 );
```

### Email Content

**Confirmation Email (to Attendee):**
```
Subject: RSVP Confirmation: Tech Conference 2026

Hi John Doe,

Thank you for your RSVP! You have successfully registered for:

Event: Tech Conference 2026
Date: March 15, 2026
Time: 10:00 AM
Location: Convention Center

View event details: https://example.com/event/tech-conference

We look forward to seeing you there!

Best regards,
My Event Site
```

**Admin Notification:**
```
Subject: New RSVP: Tech Conference 2026 - John Doe

A new RSVP has been submitted for your event:

Event: Tech Conference 2026
Date: March 15, 2026
Time: 10:00 AM
Location: Convention Center

Attendee Details:
Name: John Doe
Email: john@example.com

View event RSVPs: https://example.com/wp-admin/edit.php?post_type=event_rsvp&event_id=5
```

### Error Handling

**Graceful Failures:**
- If wp_mail() fails, error is logged (not shown to user)
- RSVP is still saved (email failure doesn't prevent registration)
- Logged to PHP error_log for debugging
- Action hooks fire regardless of email success

**WordPress Mail Configuration:**
- Uses site's configured mail server (SMTP if configured)
- Respects WordPress mail settings (from name, from address)
- Compatible with WP Mail SMTP plugins
- Falls back to PHP mail() function if no SMTP

**Testing Emails:**
```php
// Test confirmation email manually
do_action( 'cem_rsvp_created', 123, 5, 'Test User', 'test@example.com' );
```

When RSVP is enabled in **Events → Settings**, visitors can RSVP to events on the frontend.

### RSVP Data Flow

```
1. Visitor views event using [event_single id="123"]
   ↓
2. RSVP form rendered (if RSVP enabled)
   ├── Name field
   ├── Email field
   └── Submit button (RSVP Now)
   ↓
3. Visitor fills form and submits
   ↓
4. Client-side validation (required fields)
   ↓
5. AJAX request sent to server
   ├── Action: cem_rsvp_submit
   ├── Data: event_id, name, email
   └── Nonce: Security token (wp_create_nonce)
   ↓
6. Server validates AJAX request
   ├── Verify nonce (wp_verify_nonce)
   ├── Check RSVP enabled
   ├── Sanitize inputs (sanitize_text_field, sanitize_email)
   ├── Validate email format (is_email)
   ├── Verify event exists and is published
   └── Check for duplicate RSVP by email
   ↓
7. Duplicate Check Query
   ├── Query: event_rsvp posts with email match
   ├── Uses WP_Query (no direct DB access)
   ├── Returns: true if exists, false if new
   └── Prevents: Multiple RSVPs from same email
   ↓
8. Capacity Check (if event has limit)
   ├── Get event capacity (_cem_event_capacity)
   ├── Count existing RSVPs (WP_Query)
   ├── Compare: rsvp_count >= capacity
   └── Reject: If at limit
   ↓
9. Create RSVP Record
   ├── Insert new event_rsvp post
   ├── Set post parent = event_id
   ├── Store metadata:
   │   ├── _cem_rsvp_event_id (event ID)
   │   ├── _cem_rsvp_name (attendee name)
   │   ├── _cem_rsvp_email (attendee email)
   │   └── _cem_rsvp_date (timestamp)
   └── Trigger: cem_rsvp_created action hook
   ↓
10. Response sent to client
    ├── Success: "Thank you for your RSVP!"
    ├── Error: Specific error message
    └── Form reset on success
    ↓
11. Dynamic message display
    ├── Slide down animation
    ├── Success: Green (auto-hide after 5s)
    └── Error: Red (persistent)
```

### Duplicate Prevention

**How it works:**
1. When RSVP submitted, email is extracted and sanitized
2. Query searches event_rsvp posts for matching email AND event_id
3. If found, returns error: "You have already RSVP'd to this event"
4. If not found, RSVP record is created

**Database Query:**
```sql
SELECT ID FROM wp_posts 
WHERE post_type = 'event_rsvp' 
AND post_parent = {event_id}
AND post_meta._cem_rsvp_email = '{email}'
```

**Security:**
- ✅ Email sanitized via `sanitize_email()`
- ✅ Query uses WP_Query (prepared statements)
- ✅ No direct SQL queries

### RSVP Data Storage

RSVPs are stored as a custom post type `event_rsvp` under **Events → Event RSVPs** in admin.

**Each RSVP post contains:**
- `post_title` – Event name + attendee name
- `post_parent` – Event post ID (relationship)
- `post_status` – published
- `post_type` – event_rsvp

**Metadata stored:**
- `_cem_rsvp_event_id` – Event post ID
- `_cem_rsvp_name` – Attendee name
- `_cem_rsvp_email` – Attendee email
- `_cem_rsvp_date` – RSVP timestamp

**Example SQL:**
```sql
-- Get all RSVPs for event ID 5
SELECT pm.*
FROM wp_postmeta pm
WHERE pm.meta_key = '_cem_rsvp_event_id' 
AND pm.meta_value = '5'
```

### AJAX Security

✅ **Nonce Verification** – `wp_verify_nonce()` prevents CSRF attacks
✅ **Input Sanitization** – `sanitize_text_field()`, `sanitize_email()`
✅ **Email Validation** – `is_email()` ensures valid format
✅ **Prepared Statements** – WP_Query handles SQL escaping
✅ **Permission Checks** – Event must be published
✅ **No page reload** – Smooth UX with AJAX
✅ **Dynamic feedback** – Success/error messages
✅ **Rate limiting** – Nonce is one-time use

### Enable/Disable RSVP

Navigate to **Events → Settings** and toggle "Enable RSVP":
- ✅ Enabled: RSVP form appears on [event_single]
- ❌ Disabled: No RSVP form, no event_rsvp posts created

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
- ✅ STEP 6: RSVP functionality with AJAX and duplicate prevention
- ✅ STEP 7: Email notifications with wp_mail() and filters
- ✅ STEP 8: REST API endpoints for headless WordPress
- Production-ready security implementation
