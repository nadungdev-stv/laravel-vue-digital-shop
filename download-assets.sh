#!/bin/bash

# Download Bootstrap CSS
echo "Downloading Bootstrap CSS..."
curl -L "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" -o public/vendor/bootstrap.min.css

# Download Bootstrap JS
echo "Downloading Bootstrap JS..."
curl -L "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" -o public/vendor/bootstrap.bundle.min.js

# Download Font Awesome (subset - only common icons)
echo "Downloading Font Awesome..."
curl -L "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" -o public/vendor/fontawesome.min.css
mkdir -p public/vendor/webfonts
curl -L "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2" -o public/vendor/webfonts/fa-solid-900.woff2
curl -L "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2" -o public/vendor/webfonts/fa-regular-400.woff2
curl -L "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2" -o public/vendor/webfonts/fa-brands-400.woff2

# Download Inter font
echo "Downloading Inter font..."
mkdir -p public/fonts/inter
curl -L "https://fonts.bunny.net/inter/files/inter-latin-400-normal.woff2" -o public/fonts/inter/inter-400.woff2
curl -L "https://fonts.bunny.net/inter/files/inter-latin-500-normal.woff2" -o public/fonts/inter/inter-500.woff2
curl -L "https://fonts.bunny.net/inter/files/inter-latin-600-normal.woff2" -o public/fonts/inter/inter-600.woff2
curl -L "https://fonts.bunny.net/inter/files/inter-latin-700-normal.woff2" -o public/fonts/inter/inter-700.woff2

echo "Done! Assets downloaded to public/vendor/ and public/fonts/"
