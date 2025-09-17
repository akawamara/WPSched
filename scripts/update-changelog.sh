#!/bin/bash

# WPSched Changelog Management Script
# Usage: ./scripts/update-changelog.sh <version> <type> "<description>"
# Example: ./scripts/update-changelog.sh 1.0.1 "Fixed" "Fixed speaker profile pagination bug"

set -e

VERSION="$1"
TYPE="$2"
DESCRIPTION="$3"

if [ "$#" -ne 3 ]; then
    echo "Usage: $0 <version> <type> \"<description>\""
    echo ""
    echo "Types: Added, Changed, Deprecated, Removed, Fixed, Security"
    echo ""
    echo "Example: $0 1.0.1 Fixed \"Fixed speaker profile pagination bug\""
    exit 1
fi

# Validate type
case "$TYPE" in
    "Added"|"Changed"|"Deprecated"|"Removed"|"Fixed"|"Security")
        ;;
    *)
        echo "Error: Type must be one of: Added, Changed, Deprecated, Removed, Fixed, Security"
        exit 1
        ;;
esac

# Get current date
DATE=$(date +%Y-%m-%d)

echo "Adding changelog entry:"
echo "Version: $VERSION"
echo "Type: $TYPE"
echo "Description: $DESCRIPTION"
echo "Date: $DATE"
echo ""

# Update CHANGELOG.md
if [ -f "CHANGELOG.md" ]; then
    # Check if version section exists
    if ! grep -q "## \[$VERSION\]" CHANGELOG.md; then
        # Add new version section after [Unreleased]
        sed -i '' "/## \[Unreleased\]/a\\
\\
## [$VERSION] - $DATE\\
\\
### $TYPE\\
- $DESCRIPTION
" CHANGELOG.md
    else
        # Add to existing version section
        if grep -q "### $TYPE" CHANGELOG.md; then
            # Add to existing type section
            sed -i '' "/### $TYPE/a\\
- $DESCRIPTION
" CHANGELOG.md
        else
            # Add new type section
            sed -i '' "/## \[$VERSION\]/a\\
\\
### $TYPE\\
- $DESCRIPTION
" CHANGELOG.md
        fi
    fi
    echo "✓ Updated CHANGELOG.md"
else
    echo "⚠ CHANGELOG.md not found"
fi

# Update readme.txt
if [ -f "readme.txt" ]; then
    # WordPress readme format
    WP_TYPE=""
    case "$TYPE" in
        "Added") WP_TYPE="New" ;;
        "Fixed") WP_TYPE="Fix" ;;
        "Changed") WP_TYPE="Update" ;;
        "Removed") WP_TYPE="Remove" ;;
        "Security") WP_TYPE="Security" ;;
        *) WP_TYPE="$TYPE" ;;
    esac
    
    # Check if version section exists in readme.txt
    if ! grep -q "= $VERSION" readme.txt; then
        # Add new version section after == Changelog ==
        sed -i '' "/== Changelog ==/a\\
\\
= $VERSION - $DATE =\\
* **$WP_TYPE:** $DESCRIPTION
" readme.txt
    else
        # Add to existing version section
        sed -i '' "/= $VERSION/a\\
* **$WP_TYPE:** $DESCRIPTION
" readme.txt
    fi
    echo "✓ Updated readme.txt"
else
    echo "⚠ readme.txt not found"
fi

echo ""
echo "Changelog updated successfully!"
echo ""
echo "Next steps:"
echo "1. Review the changes in both files"
echo "2. Update version numbers in plugin files if this is a release"
echo "3. Commit your changes: git add . && git commit -m \"Add changelog entry for v$VERSION\""