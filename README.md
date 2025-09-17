# WPSched

A WordPress plugin that integrates with Sched.com to display conference sessions and speakers with automatic speaker profile pages.

## Features

- **Session Management**: Display and filter conference sessions
- **Speaker Profiles**: Automatic speaker profile pages at `/speakers/username` URLs
- **Data Synchronization**: Import data from Sched.com API with rate limiting
- **Color Customization**: Manual color matching for event types and subtypes
- **Security**: Rate limiting, SQL injection protection, and proper sanitization
- **Responsive Design**: Mobile-friendly interface
- **Internationalization**: Translation-ready with text domain support

## Shortcodes

### Sessions
```php
[sched_sessions limit="32" pagination="true" show_filter="true"]
```
Displays conference sessions with filtering and pagination options.

### Speakers
```php  
[sched_speakers limit="100" pagination="true"]
```
Shows speakers list with automatic links to individual speaker profiles.

### Single Speaker Profile
```php
[sched_single_speaker username="speaker-username" back_url="/speakers/"]
```
Displays detailed speaker information (used internally by virtual pages).

## Speaker Profile System

The plugin automatically creates speaker profile pages:

- **Automatic URLs**: `/speakers/username` format
- **SEO-friendly**: Clean URL structure
- **Zero maintenance**: Updates automatically with API sync
- **Professional design**: Avatar, bio, sessions, and contact info
- **Theme integration**: Works with any WordPress theme

### How It Works
1. Sync speakers from Sched.com
2. Speaker profiles become available at `/speakers/username`
3. Speaker names automatically link to their profiles
4. No manual page creation required

## Installation

1. Upload the plugin files to `/wp-content/plugins/sched-conference-plugin/`
2. Activate the plugin through the WordPress admin
3. Configure your Sched.com API settings in Settings → Sched Conference
4. Sync data from Sched.com
5. Use shortcodes to display content on pages

## Configuration

### API Settings
- **API Key**: Your Sched.com API key
- **Conference URL**: Your Sched.com conference URL (e.g., https://yourconf.sched.com)
- **Pagination**: Number of items per page

### Color Settings
- **Event Type Colors**: Customize colors for different event types
- **Event Subtype Colors**: Customize colors for event subtypes
- **Auto-discovery**: New event types are automatically detected during sync

### Data Management
- **Auto Sync**: Automatic data synchronization options
- **Rate Limiting**: Built-in API rate limiting (25 calls/minute)
- **Cleanup**: Optional complete data removal on plugin deletion

## Security Features

- **Rate Limiting**: Prevents API limit violations (25 calls/minute)
- **SQL Protection**: All queries use prepared statements
- **Input Sanitization**: All user inputs properly sanitized
- **Output Escaping**: All outputs properly escaped
- **Capability Checks**: Admin functions require proper permissions
- **CSRF Protection**: Nonce verification for admin actions

## Architecture

This plugin follows WordPress coding standards:

- ✅ **Template System**: Theme override support for all templates
- ✅ **Virtual Pages**: Professional speaker profile URLs
- ✅ **Database Optimization**: Efficient queries with N+1 prevention
- ✅ **Security**: Comprehensive security measures
- ✅ **Internationalization**: Translation-ready
- ✅ **Performance**: Rate limiting and caching

## File Structure

```
wpsched/
├── README.md                               # Project documentation
├── sched-conference-plugin.php            # Main plugin file
├── uninstall.php                          # Plugin uninstall handler
├── admin/                                 # Admin interface files
│   ├── class-sched-admin.php             # Admin class with color settings
│   ├── css/sched-admin.css               # Admin styles with color picker
│   ├── js/sched-admin.js                 # Admin JavaScript with rate limiting
│   └── partials/sched-admin-display.php  # Admin template
├── includes/                              # Core plugin files
│   ├── class-sched.php                   # Main plugin class with i18n
│   ├── class-sched-loader.php            # Hook loader
│   ├── class-sched-activator.php         # Activation handler with cleanup
│   └── class-sched-api.php               # Sched.com API with rate limiting
├── public/                                # Public-facing files
│   ├── class-sched-public.php            # Public class with virtual pages
│   ├── css/sched-public.css              # Public styles
│   ├── js/sched-public.js                # Public JavaScript
│   └── partials/                         # Template partials
│       ├── sched-sessions-display.php    # Sessions template
│       ├── sched-speakers-display.php    # Speakers template
│       ├── sched-session-card.php        # Individual session card
│       ├── sched-speaker-card.php        # Individual speaker card
│       ├── sched-single-speaker-display.php # Single speaker template
│       └── speaker-page-template.php     # Virtual speaker page template
└── languages/                            # Translation files
    └── sched-conference-plugin.pot       # Translation template
```