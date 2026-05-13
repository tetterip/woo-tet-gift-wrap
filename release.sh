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
# Create ZIP (use PowerShell on Windows if zip is unavailable)
# Compress-Archive stores Windows backslash paths which break extraction on
# Linux servers (PHP's ZipArchive treats '\' as a literal char, not a
# directory separator). Use the .NET ZipFile API instead so entry names
# always use forward slashes.
# ---------------------------------------------------------------------------
echo "==> Creating ${ZIP_NAME}..."
rm -f "$ZIP_NAME"
if command -v zip &>/dev/null; then
    (cd "$DIST_DIR" && zip -r "../${ZIP_NAME}" "$PLUGIN_SLUG")
else
    # Write a temp PS1 so we avoid multi-line quoting headaches in -Command.
    cat > "${DIST_DIR}/make-zip.ps1" << 'PSEOF'
param([string]$Staging, [string]$ZipDest, [string]$Slug)
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($ZipDest, 'Create')
Get-ChildItem -Recurse -File $Staging | ForEach-Object {
    $rel = $_.FullName.Substring($Staging.Length + 1) -replace '\\', '/'
    [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zip, $_.FullName, "$Slug/$rel", 'Optimal')
}
$zip.Dispose()
PSEOF
    # Convert Unix-style paths (Git Bash) to Windows paths for PowerShell.
    if command -v cygpath &>/dev/null; then
        STAGING_WIN=$(cygpath -w "${STAGING}")
        ZIP_WIN=$(cygpath -w "${ZIP_NAME}")
        PS1_WIN=$(cygpath -w "${DIST_DIR}/make-zip.ps1")
    else
        STAGING_WIN="${STAGING}"
        ZIP_WIN="${ZIP_NAME}"
        PS1_WIN="${DIST_DIR}/make-zip.ps1"
    fi
    powershell.exe -NoProfile -ExecutionPolicy Bypass \
        -File "${PS1_WIN}" \
        -Staging "${STAGING_WIN}" \
        -ZipDest "${ZIP_WIN}" \
        -Slug "${PLUGIN_SLUG}"
    rm -f "${DIST_DIR}/make-zip.ps1"
fi

# Clean up staging directory
rm -rf "$STAGING"

echo ""
echo "Release ready: ${ZIP_NAME}"
