<?php
class SimpleCaptcha {
    
    public static function generateCaptcha() {
        // Generate a simple math captcha
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operations = ['+', '-', '*'];
        $operation = $operations[array_rand($operations)];
        
        switch($operation) {
            case '+':
                $answer = $num1 + $num2;
                break;
            case '-':
                // Ensure positive result
                if ($num1 < $num2) {
                    $temp = $num1;
                    $num1 = $num2;
                    $num2 = $temp;
                }
                $answer = $num1 - $num2;
                break;
            case '*':
                $answer = $num1 * $num2;
                break;
        }
        
        $question = "$num1 $operation $num2 = ?";
        
        $_SESSION['captcha_answer'] = $answer;
        $_SESSION['captcha_question'] = $question;
        
        return $question;
    }
    
    public static function verifyCaptcha($userAnswer) {
        if (!isset($_SESSION['captcha_answer'])) {
            return false;
        }
        
        $isValid = (int)$userAnswer === (int)$_SESSION['captcha_answer'];
        
        // Clear captcha after verification
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_question']);
        
        return $isValid;
    }
    
    public static function generateImageCaptcha() {
        // Generate a simple text-based captcha
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $captcha_code = '';
        
        for ($i = 0; $i < 5; $i++) {
            $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $_SESSION['image_captcha'] = $captcha_code;
        
        // Create image
        $width = 120;
        $height = 40;
        $image = imagecreate($width, $height);
        
        // Colors
        $bg_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $line_color = imagecolorallocate($image, 200, 200, 200);
        
        // Add some noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
        }
        
        // Add text
        imagestring($image, 5, 25, 10, $captcha_code, $text_color);
        
        // Output image
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    
    public static function verifyImageCaptcha($userInput) {
        if (!isset($_SESSION['image_captcha'])) {
            return false;
        }
        
        $isValid = strtoupper($userInput) === strtoupper($_SESSION['image_captcha']);
        
        // Clear captcha after verification
        unset($_SESSION['image_captcha']);
        
        return $isValid;
    }
}
?>