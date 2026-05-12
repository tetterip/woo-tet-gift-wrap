#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="woo-tet-gift-wrap"
PLUGIN_FILE="woo-tet-gift-wrap.php"

# ---------------------------------------------------------------------------
# Derive version from the plugin header
# ---------------------------------------------------------------------------
VERSION=$(grep -m1 '^ \* Version:' "$PLUGIN_FILE" | awk '{print $3}')

if [[ -z "$VERSION" ]]; then
    echo "ERROR: Could not read version from $PLUGIN_FILE" >&2
    exit 1
fi

DIST_DIR="dist"
STAGING="${DIST_DIR}/${PLUGIN_SLUG}"
ZIP_NAME="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

echo "==> Building ${PLUGIN_SLUG} v${VERSION}"

# ---------------------------------------------------------------------------
# Build block checkout JS
# ---------------------------------------------------------------------------
echo "==> Compiling block JS..."
npm run build

# ---------------------------------------------------------------------------
# Prepare staging directory
# ---------------------------------------------------------------------------
echo "==> Staging runtime files..."
rm -rf "$STAGING"
mkdir -p \
    "${STAGING}/includes" \
    "${STAGING}/assets/css" \
    "${STAGING}/assets/js"

# Main files
cp "$PLUGIN_FILE"        "$STAGING/"
cp "update-checker.php"  "$STAGING/"

# PHP classes
cp includes/*.php "${STAGING}/includes/"

# Assets
cp assets/css/gift-wrap.css                 "${STAGING}/assets/css/"
cp assets/js/gift-wrap.js                   "${STAGING}/assets/js/"
cp assets/js/gift-wrap-blocks.js            "${STAGING}/assets/js/"
cp assets/js/gift-wrap-blocks.asset.php     "${STAGING}/assets/js/"
cp assets/ttrp-logo.svg                     "${STAGING}/assets/"

# Languages (optional)
if [[ -d "languages" ]]; then
    cp -r languages "${STAGING}/"
fi

# ---------------------------------------------------------------------------
# Create ZIP
# ---------------------------------------------------------------------------
echo "==> Creating ${ZIP_NAME}..."
rm -f "$ZIP_NAME"
(cd "$DIST_DIR" && zip -r "../${ZIP_NAME}" "$PLUGIN_SLUG")

# Clean up staging directory
rm -rf "$STAGING"

echo ""
echo "Release ready: ${ZIP_NAME}"
