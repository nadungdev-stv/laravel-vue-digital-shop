<?php
// SVG Placeholder Generator
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

// Get parameters
$text = isset($_GET['text']) ? $_GET['text'] : 'Product';
$width = isset($_GET['w']) ? intval($_GET['w']) : 1070;
$height = isset($_GET['h']) ? intval($_GET['h']) : 500;
$bg = isset($_GET['bg']) ? $_GET['bg'] : '667eea';
$fg = isset($_GET['fg']) ? $_GET['fg'] : 'ffffff';

// Sanitize
$text = htmlspecialchars(substr($text, 0, 50), ENT_QUOTES, 'UTF-8');
$bg = preg_replace('/[^a-fA-F0-9]/', '', $bg);
$fg = preg_replace('/[^a-fA-F0-9]/', '', $fg);

// Generate gradient colors
$gradients = [
    ['667eea', '7387df'], // Purple
    ['f093fb', 'f5576c'], // Pink
    ['4facfe', '00f2fe'], // Blue
    ['43e97b', '38f9d7'], // Green
    ['fa709a', 'fee140'], // Orange
    ['30cfd0', '330867'], // Teal
    ['a8edea', 'fed6e3'], // Pastel
    ['ff9a9e', 'fecfef'], // Rose
];

// Pick gradient based on text hash
$gradientIndex = crc32($text) % count($gradients);
$gradient = $gradients[$gradientIndex];

// Get first letter or icon
$firstChar = mb_substr($text, 0, 1, 'UTF-8');

// Output SVG
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg width="<?= $width ?>" height="<?= $height ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $width ?> <?= $height ?>">
    <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#<?= $gradient[0] ?>;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#<?= $gradient[1] ?>;stop-opacity:1" />
        </linearGradient>
        <filter id="shadow">
            <feDropShadow dx="0" dy="2" stdDeviation="4" flood-opacity="0.2"/>
        </filter>
    </defs>

    <!-- Background with gradient -->
    <rect width="<?= $width ?>" height="<?= $height ?>" fill="url(#grad)"/>

    <!-- Pattern overlay for texture -->
    <rect width="<?= $width ?>" height="<?= $height ?>" fill="url(#grad)" opacity="0.1">
        <animate attributeName="opacity" values="0.1;0.15;0.1" dur="3s" repeatCount="indefinite"/>
    </rect>

    <!-- Icon/Text -->
    <g filter="url(#shadow)">
        <!-- Large letter -->
        <text
            x="50%"
            y="45%"
            font-family="Arial, sans-serif"
            font-size="<?= intval($height * 0.4) ?>"
            font-weight="bold"
            fill="#<?= $fg ?>"
            text-anchor="middle"
            dominant-baseline="middle"
            opacity="0.9">
            <?= strtoupper($firstChar) ?>
        </text>

        <!-- Product name -->
        <text
            x="50%"
            y="70%"
            font-family="Arial, sans-serif"
            font-size="<?= intval($height * 0.08) ?>"
            font-weight="500"
            fill="#<?= $fg ?>"
            text-anchor="middle"
            dominant-baseline="middle"
            opacity="0.8">
            <?= mb_strlen($text) > 20 ? mb_substr($text, 0, 20, 'UTF-8') . '...' : $text ?>
        </text>
    </g>

    <!-- Decorative circle -->
    <circle cx="<?= intval($width * 0.85) ?>" cy="<?= intval($height * 0.15) ?>" r="<?= intval($width * 0.08) ?>" fill="#<?= $fg ?>" opacity="0.15">
        <animate attributeName="r" values="<?= intval($width * 0.08) ?>;<?= intval($width * 0.1) ?>;<?= intval($width * 0.08) ?>" dur="2s" repeatCount="indefinite"/>
    </circle>
    <circle cx="<?= intval($width * 0.15) ?>" cy="<?= intval($height * 0.85) ?>" r="<?= intval($width * 0.06) ?>" fill="#<?= $fg ?>" opacity="0.1">
        <animate attributeName="r" values="<?= intval($width * 0.06) ?>;<?= intval($width * 0.08) ?>;<?= intval($width * 0.06) ?>" dur="2.5s" repeatCount="indefinite"/>
    </circle>
</svg>
