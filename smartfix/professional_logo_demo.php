<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Logo Design - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .demo-header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .demo-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .demo-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .logo-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .logo-demo-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-demo-card h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            text-align: center;
        }
        
        .logo-preview {
            background: linear-gradient(to right, #004080, #0066cc);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Professional Logo Styles */
        .professional-logo {
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .professional-logo:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .professional-logo-icon {
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #004080;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .professional-logo:hover .professional-logo-icon {
            transform: rotate(5deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 204, 0, 0.4);
        }
        
        .professional-logo-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .professional-logo-text {
            font-size: 24px;
            font-weight: 700;
            color: white;
            line-height: 1;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }
        
        .professional-logo-tagline {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1;
        }
        
        /* Old Logo Styles for comparison */
        .old-logo {
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }
        
        .old-logo:hover {
            transform: scale(1.05);
        }
        
        .old-logo-text {
            margin-right: 5px;
        }
        
        .old-logo-highlight {
            color: #ffcc00;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            color: #333;
        }
        
        .features-list li i {
            color: #28a745;
            margin-right: 10px;
            width: 20px;
        }
        
        .comparison-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }
        
        .comparison-table h3 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .comparison-item {
            text-align: center;
        }
        
        .comparison-item h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        
        @media (max-width: 768px) {
            .demo-header h1 {
                font-size: 2rem;
            }
            
            .logo-showcase {
                grid-template-columns: 1fr;
            }
            
            .comparison-grid {
                grid-template-columns: 1fr;
            }
            
            .navigation-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1><i class="fas fa-palette"></i> Professional Logo Design</h1>
            <p>Enhanced SmartFix branding with modern, professional appearance</p>
        </div>
        
        <div class="comparison-table">
            <h3>Before vs After Comparison</h3>
            <div class="comparison-grid">
                <div class="comparison-item">
                    <h4>❌ Old Design</h4>
                    <div class="logo-preview">
                        <a href="#" class="old-logo">
                            <span class="old-logo-text">SmartFix</span>
                            <span class="old-logo-highlight">Zed</span>
                        </a>
                    </div>
                    <ul class="features-list">
                        <li><i class="fas fa-times"></i> Simple text only</li>
                        <li><i class="fas fa-times"></i> No visual identity</li>
                        <li><i class="fas fa-times"></i> Basic hover effect</li>
                        <li><i class="fas fa-times"></i> Limited branding</li>
                    </ul>
                </div>
                
                <div class="comparison-item">
                    <h4>✅ New Professional Design</h4>
                    <div class="logo-preview">
                        <a href="#" class="professional-logo">
                            <div class="professional-logo-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="professional-logo-content">
                                <span class="professional-logo-text">SmartFix</span>
                                <span class="professional-logo-tagline">Professional Repair Services</span>
                            </div>
                        </a>
                    </div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i> Professional icon design</li>
                        <li><i class="fas fa-check"></i> Clear brand identity</li>
                        <li><i class="fas fa-check"></i> Advanced animations</li>
                        <li><i class="fas fa-check"></i> Descriptive tagline</li>
                        <li><i class="fas fa-check"></i> Glass morphism effect</li>
                        <li><i class="fas fa-check"></i> Gradient backgrounds</li>
                        <li><i class="fas fa-check"></i> Mobile responsive</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="logo-showcase">
            <div class="logo-demo-card">
                <h3><i class="fas fa-desktop"></i> Desktop View</h3>
                <div class="logo-preview">
                    <a href="#" class="professional-logo">
                        <div class="professional-logo-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="professional-logo-content">
                            <span class="professional-logo-text">SmartFix</span>
                            <span class="professional-logo-tagline">Professional Repair Services</span>
                        </div>
                    </a>
                </div>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i> Full size with tagline</li>
                    <li><i class="fas fa-check"></i> Prominent icon display</li>
                    <li><i class="fas fa-check"></i> Smooth hover animations</li>
                </ul>
            </div>
            
            <div class="logo-demo-card">
                <h3><i class="fas fa-mobile-alt"></i> Mobile View</h3>
                <div class="logo-preview">
                    <a href="#" class="professional-logo" style="padding: 6px 10px;">
                        <div class="professional-logo-icon" style="width: 38px; height: 38px; margin-right: 10px; font-size: 18px;">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="professional-logo-content">
                            <span class="professional-logo-text" style="font-size: 20px;">SmartFix</span>
                            <span class="professional-logo-tagline" style="font-size: 10px;">Professional Repair Services</span>
                        </div>
                    </a>
                </div>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i> Responsive sizing</li>
                    <li><i class="fas fa-check"></i> Optimized for touch</li>
                    <li><i class="fas fa-check"></i> Maintains readability</li>
                </ul>
            </div>
        </div>
        
        <div class="logo-demo-card">
            <h3><i class="fas fa-star"></i> Key Improvements</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <ul class="features-list">
                    <li><i class="fas fa-palette"></i> <strong>Visual Identity:</strong> Professional icon with gradient</li>
                    <li><i class="fas fa-magic"></i> <strong>Modern Effects:</strong> Glass morphism and backdrop blur</li>
                    <li><i class="fas fa-mobile-alt"></i> <strong>Responsive:</strong> Adapts to all screen sizes</li>
                </ul>
                <ul class="features-list">
                    <li><i class="fas fa-rocket"></i> <strong>Animations:</strong> Smooth hover and transform effects</li>
                    <li><i class="fas fa-tag"></i> <strong>Branding:</strong> Clear service description</li>
                    <li><i class="fas fa-shield-alt"></i> <strong>Professional:</strong> Enterprise-grade appearance</li>
                </ul>
            </div>
        </div>
        
        <div class="navigation-buttons">
            <a href="index.php" class="btn btn-success">
                <i class="fas fa-home"></i> View Live Homepage
            </a>
            <a href="transport_gps_integration.php" class="btn btn-info">
                <i class="fas fa-truck"></i> Transport Dashboard
            </a>
            <a href="system_health_dashboard.php" class="btn">
                <i class="fas fa-heartbeat"></i> System Health
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 2rem; padding: 2rem; background: rgba(255, 255, 255, 0.1); border-radius: 15px; color: white;">
            <h3><i class="fas fa-info-circle"></i> Implementation Status</h3>
            <p><strong>✅ Successfully Implemented</strong></p>
            <p>The professional logo has been integrated into the main homepage with responsive design and modern animations.</p>
            <p><strong>Features:</strong> Glass morphism effect, gradient icon, professional tagline, mobile optimization</p>
        </div>
    </div>
    
    <script>
        // Add some interactive effects
        document.querySelectorAll('.professional-logo').forEach(logo => {
            logo.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            logo.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>