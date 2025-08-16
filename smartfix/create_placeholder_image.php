<?php
// Create placeholder image for products without images
$image_path = 'uploads/no-image.jpg';

// Create directory if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

// Create a simple placeholder image
$width = 300;
$height = 200;
$image = imagecreate($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 240, 240);  // Light gray background
$text_color = imagecolorallocate($image, 100, 100, 100); // Dark gray text
$border_color = imagecolorallocate($image, 200, 200, 200); // Border

// Draw border
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);

// Add text
$text = "No Image Available";
$font_size = 3;

// Calculate text position to center it
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Add icon-like element
$icon_size = 30;
$icon_x = $width / 2 - $icon_size / 2;
$icon_y = $height / 2 - $icon_size - 20;

// Simple camera icon representation
imagerectangle($image, $icon_x, $icon_y, $icon_x + $icon_size, $icon_y + $icon_size - 5, $text_color);
imagerectangle($image, $icon_x + 5, $icon_y - 3, $icon_x + $icon_size - 5, $icon_y + 3, $text_color);

// Save the image
if (imagejpeg($image, $image_path, 85)) {
    echo "✓ Placeholder image created successfully at: $image_path<br>";
    echo "<img src='$image_path' alt='Placeholder' style='border:1px solid #ccc;'><br>";
    echo "<a href='shop.php'>Visit Shop</a> | <a href='fix_shop_errors.php'>Run Error Fix</a>";
} else {
    echo "✗ Failed to create placeholder image<br>";
}

// Clean up
imagedestroy($image);

// Set proper permissions
chmod('uploads', 0755);
if (file_exists($image_path)) {
    chmod($image_path, 0644);
}
?>