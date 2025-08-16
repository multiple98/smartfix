<?php
// Create a placeholder image for products without images
$width = 300;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Set background color (light gray)
$bg_color = imagecolorallocate($image, 238, 238, 238);
imagefill($image, 0, 0, $bg_color);

// Set text color (dark gray)
$text_color = imagecolorallocate($image, 153, 153, 153);

// Add text
$text = "No Image";
$font = 5; // Built-in font
$font_width = imagefontwidth($font);
$font_height = imagefontheight($font);
$text_width = $font_width * strlen($text);
$text_x = ($width - $text_width) / 2;
$text_y = ($height - $font_height) / 2;

imagestring($image, $font, $text_x, $text_y, $text, $text_color);

// Save the image
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

imagepng($image, 'uploads/no-image.jpg');
imagedestroy($image);

echo "Placeholder image created successfully at uploads/no-image.jpg";
?>