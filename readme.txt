=== WPSched ===
Contributors: Alan Kawamara
Tags: conference, events, sched, speakers, sessions, api
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync and display conference data from Sched.com with automatic speaker profiles and professional conference management features.

== Description ==

WPSched integrates your WordPress site with Sched.com to create a professional conference website with automatic speaker profiles, session management, and real-time data synchronization.

= Key Features =

* **Automatic Speaker Profiles**: Creates `/speakers/username` URLs automatically
* **Session Management**: Display and filter conference sessions
* **Data Synchronization**: Real-time import from Sched.com API
* **Color Customization**: Match your conference branding
* **Rate Limiting**: Built-in API protection (25 calls/minute)
* **Security**: SQL injection protection and proper sanitization
* **Mobile Responsive**: Works on all devices
* **Translation Ready**: Full internationalization support

= Professional Conference Features =

* SEO-friendly speaker profile URLs
* Automatic linking between sessions and speakers
* Color-coded event types and subtypes
* Advanced filtering and pagination
* Theme integration with any WordPress theme
* Zero maintenance speaker profiles

= Perfect For =

* Academic conferences
* Business events
* Technology summits
* Professional associations
* Multi-day conferences
* Speaker showcases

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wpsched/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → WPSched to configure API settings
4. Enter your Sched.com API key and conference URL
5. Click "Sync Data" to import your conference data
6. Use shortcodes to display content on your pages

== Frequently Asked Questions ==

= Do I need a Sched.com account? =

Yes, you need an active Sched.com conference with API access. The plugin syncs data from your Sched.com conference.

= How do speaker profile URLs work? =

Once you sync speakers, they automatically get profile pages at `/speakers/username`. No manual page creation needed!

= Can I customize the colors? =

Yes! The plugin includes a color management system where you can manually set colors for different event types and subtypes to match your conference branding.

= Is there rate limiting? =

Yes, the plugin includes built-in rate limiting (25 calls per minute) to ensure you never exceed Sched.com's API limits.

= Can I translate the plugin? =

Absolutely! The plugin is fully translation-ready with a complete .pot file included.

== Shortcodes ==

= Display Sessions =
`[sched_sessions limit="30" pagination="true" show_filter="true"]`

= Display Speakers =
`[sched_speakers limit="100" pagination="true"]`

= Display Single Speaker =
`[sched_single_speaker username="speaker-username"]`

== Screenshots ==

1. Admin settings page with API configuration
2. Color customization interface for event types
3. Sessions display with filtering options
4. Speakers listing with automatic profile links
5. Individual speaker profile page
6. Mobile responsive design

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic speaker profile pages
* Session and speaker management
* Color customization system
* Rate limiting and security features
* Internationalization support
* Mobile responsive design

== Upgrade Notice ==

= 1.0.0 =
Initial release of WPSched with professional conference management features.

== Security ==

This plugin follows WordPress security best practices:

* All database queries use prepared statements
* User inputs are properly sanitized
* Outputs are properly escaped
* Admin functions require proper capabilities
* CSRF protection with nonce verification
* Built-in API rate limiting

== Support ==

For support and documentation, please visit the plugin's GitHub repository or WordPress.org support forums.