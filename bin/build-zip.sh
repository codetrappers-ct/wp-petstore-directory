#!/usr/bin/env bash
#
# Build an installable plugin zip into dist/.
#
# Produces dist/wp-petstore-directory-<version>.zip with the plugin folder at
# the archive root (the structure WordPress expects), excluding dev-only files
# listed in .distignore.
#
set -euo pipefail

SLUG="wp-petstore-directory"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PARENT_DIR="$(dirname "$PLUGIN_DIR")"

# Read version from the plugin header.
VERSION="$(grep -iE '^\s*\*\s*Version:' "$PLUGIN_DIR/$SLUG.php" | head -1 | sed -E 's/.*Version:\s*//' | tr -d '[:space:]')"
VERSION="${VERSION:-0.0.0}"

DIST_DIR="$PLUGIN_DIR/dist"
ZIP_PATH="$DIST_DIR/$SLUG-$VERSION.zip"

mkdir -p "$DIST_DIR"
rm -f "$ZIP_PATH"

# Exclusions (dev-only files; kept in the repo, not in the shipped plugin).
EXCLUDES=(
	"$SLUG/.git/*"
	"$SLUG/.github/*"
	"$SLUG/.gitignore"
	"$SLUG/.distignore"
	"$SLUG/.DS_Store"
	"*/.DS_Store"
	"$SLUG/dist/*"
	"$SLUG/bin/*"
	"$SLUG/PLAN.md"
	"$SLUG/DECISION_LOG.md"
	"$SLUG/docs/*"
)

cd "$PARENT_DIR"
zip -rq "$ZIP_PATH" "$SLUG" -x "${EXCLUDES[@]}"

echo "Built: $ZIP_PATH"
echo "Contents:"
unzip -l "$ZIP_PATH"
