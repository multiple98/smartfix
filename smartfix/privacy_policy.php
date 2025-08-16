<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: #28a745;
            color: white;
            padding: 1rem 0;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #28a745;
            text-decoration: none;
            margin-bottom: 30px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #1e7e34;
        }

        .back-link i {
            margin-right: 8px;
        }

        h2 {
            color: #28a745;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 20px;
        }

        h3 {
            color: #333;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 16px;
        }

        p {
            margin-bottom: 15px;
            text-align: justify;
        }

        ul, ol {
            margin-bottom: 15px;
            padding-left: 30px;
        }

        li {
            margin-bottom: 8px;
        }

        .highlight {
            background: #e8f5e8;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
            border-radius: 4px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .contact-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .last-updated {
            font-style: italic;
            color: #666;
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            background: #e8f5e8;
            color: #28a745;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin: 10px 0;
        }

        .security-badge i {
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-shield-alt"></i> Privacy Policy</h1>
        <p>SmartFix Service Management Platform</p>
    </div>

    <div class="container">
        <a href="javascript:history.back()" class="back-link">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <div class="highlight">
            <strong>Your Privacy Matters:</strong> This Privacy Policy explains how SmartFix collects, uses, and protects your personal information when you use our services.
        </div>

        <div class="security-badge">
            <i class="fas fa-lock"></i> SSL Encrypted & Secure
        </div>

        <h2>1. Information We Collect</h2>
        <p>
            We collect information you provide directly to us, information we obtain automatically when you use our services, and information from third parties.
        </p>

        <h3>1.1 Information You Provide</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data Type</th>
                    <th>Examples</th>
                    <th>Purpose</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Account Information</td>
                    <td>Name, email, username, password</td>
                    <td>Account creation and authentication</td>
                </tr>
                <tr>
                    <td>Contact Information</td>
                    <td>Phone number, address, city, province</td>
                    <td>Service delivery and communication</td>
                </tr>
                <tr>
                    <td>Service Requests</td>
                    <td>Service type, description, location, photos</td>
                    <td>Matching with appropriate technicians</td>
                </tr>
                <tr>
                    <td>Payment Information</td>
                    <td>Billing address, payment method details</td>
                    <td>Processing payments securely</td>
                </tr>
                <tr>
                    <td>Communication Data</td>
                    <td>Messages, reviews, ratings</td>
                    <td>Platform communication and quality assurance</td>
                </tr>
            </tbody>
        </table>

        <h3>1.2 Automatically Collected Information</h3>
        <ul>
            <li><strong>Device Information:</strong> IP address, browser type, operating system</li>
            <li><strong>Usage Data:</strong> Pages visited, time spent, features used</li>
            <li><strong>Location Data:</strong> GPS coordinates for service delivery (with permission)</li>
            <li><strong>Cookies:</strong> Session data, preferences, authentication tokens</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the collected information for the following purposes:</p>

        <h3>2.1 Service Provision</h3>
        <ul>
            <li>Creating and managing your account</li>
            <li>Processing service requests and bookings</li>
            <li>Matching customers with qualified technicians</li>
            <li>Processing payments and managing orders</li>
            <li>Providing customer support</li>
        </ul>

        <h3>2.2 Communication</h3>
        <ul>
            <li>Sending service updates and notifications</li>
            <li>Responding to inquiries and support requests</li>
            <li>Sending promotional materials (with consent)</li>
            <li>Two-factor authentication codes</li>
        </ul>

        <h3>2.3 Platform Improvement</h3>
        <ul>
            <li>Analyzing usage patterns to improve our services</li>
            <li>Developing new features and functionality</li>
            <li>Ensuring platform security and preventing fraud</li>
            <li>Conducting research and analytics</li>
        </ul>

        <h2>3. Information Sharing and Disclosure</h2>
        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>

        <h3>3.1 Service Providers</h3>
        <ul>
            <li><strong>Technicians:</strong> Contact information and service details for service completion</li>
            <li><strong>Payment Processors:</strong> Billing information for payment processing</li>
            <li><strong>Delivery Services:</strong> Address information for product delivery</li>
        </ul>

        <h3>3.2 Legal Requirements</h3>
        <ul>
            <li>When required by law or legal process</li>
            <li>To protect our rights, property, or safety</li>
            <li>To prevent fraud or security threats</li>
            <li>With your explicit consent</li>
        </ul>

        <h2>4. Data Security</h2>
        <p>We implement appropriate technical and organizational measures to protect your personal information:</p>

        <div class="highlight">
            <h3>Security Measures</h3>
            <ul>
                <li><i class="fas fa-lock"></i> <strong>Encryption:</strong> All data transmission is encrypted using SSL/TLS</li>
                <li><i class="fas fa-key"></i> <strong>Password Protection:</strong> Passwords are hashed using industry-standard algorithms</li>
                <li><i class="fas fa-shield-alt"></i> <strong>Access Control:</strong> Limited access to personal data on a need-to-know basis</li>
                <li><i class="fas fa-server"></i> <strong>Secure Storage:</strong> Data stored on secure servers with regular backups</li>
                <li><i class="fas fa-user-shield"></i> <strong>Two-Factor Authentication:</strong> Additional security layer for account access</li>
            </ul>
        </div>

        <h2>5. Your Rights and Choices</h2>
        <p>You have the following rights regarding your personal information:</p>

        <h3>5.1 Access and Control</h3>
        <ul>
            <li><strong>Access:</strong> Request a copy of your personal data</li>
            <li><strong>Correction:</strong> Update or correct inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your personal data</li>
            <li><strong>Portability:</strong> Request transfer of your data to another service</li>
        </ul>

        <h3>5.2 Communication Preferences</h3>
        <ul>
            <li>Opt-out of promotional emails</li>
            <li>Manage notification settings</li>
            <li>Control location sharing permissions</li>
        </ul>

        <h2>6. Cookies and Tracking</h2>
        <p>We use cookies and similar technologies to enhance your experience:</p>

        <h3>6.1 Types of Cookies</h3>
        <ul>
            <li><strong>Essential Cookies:</strong> Required for basic platform functionality</li>
            <li><strong>Performance Cookies:</strong> Help us understand how you use our platform</li>
            <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
            <li><strong>Marketing Cookies:</strong> Used for targeted advertising (with consent)</li>
        </ul>

        <h2>7. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to provide our services and comply with legal obligations:</p>
        <ul>
            <li><strong>Account Data:</strong> Retained while your account is active</li>
            <li><strong>Service Records:</strong> Kept for 7 years for warranty and legal purposes</li>
            <li><strong>Communication Data:</strong> Retained for 3 years for quality assurance</li>
            <li><strong>Payment Data:</strong> Retained according to financial regulations</li>
        </ul>

        <h2>8. International Data Transfers</h2>
        <p>
            Your information may be transferred to and processed in countries other than Rwanda. We ensure appropriate safeguards are in place to protect your data during such transfers.
        </p>

        <h2>9. Children's Privacy</h2>
        <p>
            Our services are not intended for children under 18 years of age. We do not knowingly collect personal information from children under 18. If we become aware that we have collected such information, we will take steps to delete it.
        </p>

        <h2>10. Changes to This Privacy Policy</h2>
        <p>
            We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.
        </p>

        <h2>11. Third-Party Services</h2>
        <p>Our platform may contain links to third-party websites or services. This Privacy Policy does not apply to those third-party services. We encourage you to read their privacy policies.</p>

        <div class="contact-info">
            <h3><i class="fas fa-envelope"></i> Contact Us About Privacy</h3>
            <p>
                If you have any questions about this Privacy Policy or our data practices, please contact us:
            </p>
            <ul>
                <li><strong>Privacy Officer:</strong> privacy@smartfixzed.com</li>
                <li><strong>General Support:</strong> info@smartfixzed.com</li>
                <li><strong>Phone:</strong> +250 788 123456</li>
                <li><strong>Address:</strong> Kigali, Rwanda</li>
            </ul>
            <p>
                <strong>Response Time:</strong> We will respond to privacy-related inquiries within 30 days.
            </p>
        </div>

        <div class="last-updated">
            Last updated: <?php echo date('F j, Y'); ?>
        </div>
    </div>
</body>
</html>