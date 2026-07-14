#!/bin/bash
# Theme CSS Rebuild Script
# This script rebuilds the Tailwind CSS and ensures theme colors are properly applied

echo "🔄 Rebuilding theme CSS files..."

# Navigate to project directory
cd "$(dirname "$0")"

# Check if Tailwind CLI is available
if ! command -v npx &> /dev/null; then
    echo "❌ Error: npx (Node.js) is not available. Please install Node.js to rebuild CSS."
    exit 1
fi

# Clear Tailwind CSS cache to ensure a fresh build
echo "🧹 Clearing Tailwind CSS cache..."
rm -rf node_modules/.cache/tailwindcss

# Build Tailwind CSS
echo "📦 Building Tailwind CSS..."
npx tailwindcss -i ./src/input.css -o ./public/css/tailwind.css --watch=false

if [ $? -eq 0 ]; then
    echo "✅ Tailwind CSS built successfully"

    # Set proper permissions
    chmod 644 ./public/css/tailwind.css
    chmod 644 ./public/css/dynamic-theme.php

    echo "🎨 Theme CSS rebuild completed!"
    echo ""
    echo "📋 Summary:"
    echo "  - Tailwind CSS: ./public/css/tailwind.css"
    echo "  - Dynamic Theme: ./public/css/dynamic-theme.php"
    echo ""
    echo "💡 Note: The dynamic theme CSS automatically uses colors from the database."
    echo "   No manual rebuild is needed when changing theme colors in the admin panel."

else
    echo "❌ Error: Failed to build Tailwind CSS"
    exit 1
fi
