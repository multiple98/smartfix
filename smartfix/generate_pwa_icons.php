<?php
// Generate PWA Icons Script
// Run this script once to generate all PWA icons

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    die('GD library is not installed. Please enable GD extension to generate icons.');
}

// Icon sizes needed for PWA
$icon_sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Create base icon (you can replace this with your logo)
function createBaseIcon($size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Create gradient background
    $bg_start = imagecolorallocate($image, 0, 123, 255);
    $bg_end = imagecolorallocate($image, 0, 86, 179);
    
    // Fill with gradient
    imagefill($image, 0, 0, $bg_start);
    
    // Add tool icon (simple wrench representation)
    $white = imagecolorallocate($image, 255, 255, 255);
    $tool_size = $size * 0.6;
    $center_x = $size / 2;
    $center_y = $size / 2;
    
    // Draw simple wrench shape
    $thickness = max(2, $size / 32);
    imagesetthickness($image, $thickness);
    
    // Wrench handle
    imageline($image, $center_x - $tool_size/4, $center_y + $tool_size/4, 
              $center_x + $tool_size/4, $center_y - $tool_size/4, $white);
    
    // Wrench head (circle)
    $circle_size = $tool_size / 6;
    imagefilledellipse($image, $center_x - $tool_size/4, $center_y + $tool_size/4, 
                      $circle_size, $circle_size, $white);
    
    // Add text for larger icons
    if ($size >= 128) {
        $font_size = max(12, $size / 20);
        $text = "SF";
        $text_color = imagecolorallocate($image, 255, 255, 255);
        
        // Calculate text position
        $text_box = imagettfbbox($font_size, 0, __DIR__ . '/arial.ttf', $text);
        if ($text_box === false) {
            // Fallback to imagestring if ttf font not available
            $text_x = $center_x - (strlen($text) * 10) / 2;
            $text_y = $center_y + $tool_size/3;
            imagestring($image, 5, $text_x, $text_y, $text, $text_color);
        }
    }
    
    return $image;
}

// Create icons directory if it doesn't exist
$icons_dir = __DIR__ . '/img';
if (!is_dir($icons_dir)) {
    mkdir($icons_dir, 0755, true);
}

$generated_count = 0;
$errors = [];

foreach ($icon_sizes as $size) {
    $filename = "icon-{$size}x{$size}.png";
    $filepath = $icons_dir . '/' . $filename;
    
    try {
        $image = createBaseIcon($size);
        
        if (imagepng($image, $filepath)) {
            echo "✓ Generated: {$filename} ({$size}x{$size})\n";
            $generated_count++;
        } else {
            $errors[] = "Failed to save: {$filename}";
        }
        
        imagedestroy($image);
    } catch (Exception $e) {
        $errors[] = "Error generating {$filename}: " . $e->getMessage();
    }
}

// Create favicon.ico
try {
    $favicon = createBaseIcon(32);
    $favicon_path = __DIR__ . '/favicon.ico';
    if (imagepng($favicon, $favicon_path)) {
        echo "✓ Generated: favicon.ico (32x32)\n";
        $generated_count++;
    }
    imagedestroy($favicon);
} catch (Exception $e) {
    $errors[] = "Error generating favicon: " . $e->getMessage();
}

// Generate apple-touch-icon
try {
    $apple_icon = createBaseIcon(180);
    $apple_path = $icons_dir . '/apple-touch-icon.png';
    if (imagepng($apple_icon, $apple_path)) {
        echo "✓ Generated: apple-touch-icon.png (180x180)\n";
        $generated_count++;
    }
    imagedestroy($apple_icon);
} catch (Exception $e) {
    $errors[] = "Error generating Apple touch icon: " . $e->getMessage();
}

echo "\n";
echo "=== PWA Icons Generation Complete ===\n";
echo "Generated: {$generated_count} icons\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "✗ {$error}\n";
    }
}

echo "\nNext steps:\n";
echo "1. Add PWA meta tags to your pages\n";
echo "2. Register the service worker\n";
echo "3. Test the PWA functionality\n";
echo "\nIcons saved in: {$icons_dir}/\n";
?>