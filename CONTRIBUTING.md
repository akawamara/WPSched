# Contributing to WPSched

## Changelog Management Workflow

This project maintains changelogs in two formats:
- `CHANGELOG.md` - Detailed changelog following [Keep a Changelog](https://keepachangelog.com/) format
- `readme.txt` - WordPress-formatted changelog for the plugin directory

### During Development

When working on features or fixes, add entries to the `[Unreleased]` section in `CHANGELOG.md`:

```markdown
## [Unreleased]

### Added
- New speaker bio field support

### Fixed
- Fixed pagination issue on speakers page
```

### Using the Changelog Script

Use the provided script to add changelog entries:

```bash
# Add a bug fix
./scripts/update-changelog.sh 1.0.1 "Fixed" "Fixed speaker profile pagination bug"

# Add a new feature  
./scripts/update-changelog.sh 1.1.0 "Added" "Added speaker bio field support"

# Add a security fix
./scripts/update-changelog.sh 1.0.2 "Security" "Fixed XSS vulnerability in admin settings"
```

### Release Process

1. **Prepare Release**
   ```bash
   # Move unreleased items to version section
   ./scripts/update-changelog.sh 1.0.1 "Fixed" "All your bug fixes here"
   ```

2. **Update Version Numbers**
   - Update `Version:` in plugin header
   - Update `SCHED_PLUGIN_VERSION` constant
   - Update `Stable tag:` in readme.txt

3. **Commit and Tag**
   ```bash
   git add .
   git commit -m "Release version 1.0.1"
   git tag v1.0.1
   git push origin main --tags
   ```

### Changelog Entry Types

| Type | WordPress Format | Description |
|------|------------------|-------------|
| **Added** | **New:** | New features |
| **Changed** | **Update:** | Changes in existing functionality |
| **Deprecated** | **Deprecated:** | Soon-to-be removed features |
| **Removed** | **Remove:** | Now removed features |
| **Fixed** | **Fix:** | Bug fixes |
| **Security** | **Security:** | Vulnerability fixes |

### Best Practices

#### Writing Good Changelog Entries
- ✅ "Fixed speaker profile pagination showing incorrect page numbers"
- ✅ "Added support for custom speaker bio fields"
- ❌ "Fixed bug"
- ❌ "Updated code"

#### Version Numbering
- **Patch** (1.0.1): Bug fixes, security patches
- **Minor** (1.1.0): New features, backward compatible
- **Major** (2.0.0): Breaking changes, API changes

#### When to Update Changelog
- Every bug fix
- Every new feature
- Every security patch
- Every breaking change
- Before each release

### File Structure
```
/scripts/
  └── update-changelog.sh     # Changelog management script
CHANGELOG.md                  # Detailed changelog
readme.txt                   # WordPress plugin readme with changelog
CONTRIBUTING.md              # This file
```

### Manual Updates

If you prefer manual updates:

1. **CHANGELOG.md**: Follow Keep a Changelog format
2. **readme.txt**: Use WordPress format with `= Version =` headers

Always keep both files in sync for consistency.