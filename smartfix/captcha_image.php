<?php
session_start();
require_once 'includes/SimpleCaptcha.php';

SimpleCaptcha::generateImageCaptcha();
?>