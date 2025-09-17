# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- New features that haven't been released yet

### Changed
- Changes in existing functionality

### Deprecated
- Soon-to-be removed features

### Removed
- Now removed features

### Fixed
- Bug fixes

### Security
- Vulnerability fixes

## [1.0.0] - 2024-09-17

### Added
- Initial release of WPSched
- Automatic speaker profile pages at `/speakers/username` URLs
- Session and speaker management with Sched.com API integration
- Color customization system for event types and subtypes
- Rate limiting protection (25 calls per minute)
- Security features with SQL injection protection
- Mobile responsive design
- Internationalization support with complete .pot file
- Admin settings page with API configuration
- Shortcodes for displaying sessions and speakers
- Built-in pagination and filtering options
- CSRF protection with nonce verification
- Professional conference management features

### Security
- All database queries use prepared statements
- User inputs are properly sanitized
- Outputs are properly escaped
- Admin functions require proper capabilities

## Guidelines for Changelog Entries

### Categories
- **Added** for new features
- **Changed** for changes in existing functionality
- **Deprecated** for soon-to-be removed features
- **Removed** for now removed features
- **Fixed** for any bug fixes
- **Security** for vulnerability fixes

### Writing Style
- Use simple past tense ("Added new feature" not "Add new feature")
- Be specific and user-focused
- Include issue/PR numbers when applicable
- Group related changes together
- Keep entries concise but descriptive

### Version Format
- Use semantic versioning (MAJOR.MINOR.PATCH)
- Include release date in YYYY-MM-DD format
- Link versions to GitHub releases when available