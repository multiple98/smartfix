<?php
class EmailNotification {
    private $pdo;
    private $fromEmail = 'noreply@smartfix.com';
    private $fromName = 'SmartFix';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send service request confirmation email to customer
     */
    public function sendServiceRequestConfirmation($customerEmail, $customerName, $serviceDetails) {
        $subject = "Service Request Confirmation - SmartFix";
        
        $message = $this->buildEmailTemplate([
            'title' => 'Service Request Received',
            'greeting' => "Hello " . htmlspecialchars($customerName) . ",",
            'content' => [
                'Thank you for choosing SmartFix! We have received your service request and our team will review it shortly.',
                '',
                '<strong>Service Request Details:</strong>',
                '• Request ID: ' . htmlspecialchars($serviceDetails['request_id']),
                '• Service Type: ' . htmlspecialchars($serviceDetails['service_type']),
                '• Service Option: ' . htmlspecialchars($serviceDetails['service_option'] ?? 'Not specified'),
                '• Request Date: ' . date('F j, Y \a\t g:i A', strtotime($serviceDetails['request_date'])),
                '• Status: ' . ucfirst(htmlspecialchars($serviceDetails['status'])),
                '',
                '<strong>What happens next?</strong>',
                '1. Our team will review your request within 2-4 hours',
                '2. We will assign a qualified technician to your case',
                '3. You will receive a call or email to schedule the service',
                '4. Our technician will arrive at your specified location',
                '',
                'You can track your service request status anytime by visiting our website and using your Request ID: <strong>' . htmlspecialchars($serviceDetails['request_id']) . '</strong>'
            ],
            'cta_text' => 'Track Your Request',
            'cta_link' => 'https://smartfix.com/services/track_service.php?id=' . urlencode($serviceDetails['request_id']),
            'footer_note' => 'If you have any questions, please contact us at info@smartfix.com or call us at +1 (555) 123-4567.'
        ]);
        
        return $this->sendEmail($customerEmail, $subject, $message);
    }
    
    /**
     * Send service request notification to admin
     */
    public function sendServiceRequestNotificationToAdmin($serviceDetails) {
        $adminEmail = 'admin@smartfix.com'; // You can make this configurable
        $subject = "New Service Request - " . $serviceDetails['service_type'];
        
        $message = $this->buildEmailTemplate([
            'title' => 'New Service Request Received',
            'greeting' => "Hello Admin,",
            'content' => [
                'A new service request has been submitted and requires attention.',
                '',
                '<strong>Customer Information:</strong>',
                '• Name: ' . htmlspecialchars($serviceDetails['customer_name']),
                '• Email: ' . htmlspecialchars($serviceDetails['customer_email']),
                '• Phone: ' . htmlspecialchars($serviceDetails['customer_phone']),
                '• Address: ' . htmlspecialchars($serviceDetails['customer_address'] ?? 'Not provided'),
                '',
                '<strong>Service Details:</strong>',
                '• Request ID: ' . htmlspecialchars($serviceDetails['request_id']),
                '• Service Type: ' . htmlspecialchars($serviceDetails['service_type']),
                '• Service Option: ' . htmlspecialchars($serviceDetails['service_option'] ?? 'Not specified'),
                '• Description: ' . htmlspecialchars($serviceDetails['description']),
                '• Priority: ' . ucfirst(htmlspecialchars($serviceDetails['priority'] ?? 'normal')),
                '• Request Date: ' . date('F j, Y \a\t g:i A', strtotime($serviceDetails['request_date'])),
                '',
                'Please assign a technician and respond to the customer promptly.'
            ],
            'cta_text' => 'View in Admin Panel',
            'cta_link' => 'https://smartfix.com/admin/admin_dashboard_new.php',
            'footer_note' => 'This is an automated notification from the SmartFix system.'
        ]);
        
        return $this->sendEmail($adminEmail, $subject, $message);
    }
    
    /**
     * Send technician assignment notification to customer
     */
    public function sendTechnicianAssignmentNotification($customerEmail, $customerName, $serviceDetails, $technicianDetails) {
        $subject = "Technician Assigned - SmartFix Request #" . $serviceDetails['request_id'];
        
        $message = $this->buildEmailTemplate([
            'title' => 'Technician Assigned to Your Request',
            'greeting' => "Hello " . htmlspecialchars($customerName) . ",",
            'content' => [
                'Great news! We have assigned a qualified technician to your service request.',
                '',
                '<strong>Your Technician:</strong>',
                '• Name: ' . htmlspecialchars($technicianDetails['name']),
                '• Specialization: ' . htmlspecialchars($technicianDetails['specialization']),
                '• Phone: ' . htmlspecialchars($technicianDetails['phone']),
                '• Rating: ' . str_repeat('⭐', intval($technicianDetails['rating'] ?? 5)),
                '',
                '<strong>Service Details:</strong>',
                '• Request ID: ' . htmlspecialchars($serviceDetails['request_id']),
                '• Service Type: ' . htmlspecialchars($serviceDetails['service_type']),
                '• Status: Updated to "Assigned"',
                '',
                'Your technician will contact you within 24 hours to schedule the service appointment.',
                '',
                'If you need to contact your technician directly, you can call them at ' . htmlspecialchars($technicianDetails['phone']) . '.'
            ],
            'cta_text' => 'Track Your Request',
            'cta_link' => 'https://smartfix.com/services/track_service.php?id=' . urlencode($serviceDetails['request_id']),
            'footer_note' => 'Thank you for choosing SmartFix for your repair needs!'
        ]);
        
        return $this->sendEmail($customerEmail, $subject, $message);
    }
    
    /**
     * Send service completion notification
     */
    public function sendServiceCompletionNotification($customerEmail, $customerName, $serviceDetails) {
        $subject = "Service Completed - SmartFix Request #" . $serviceDetails['request_id'];
        
        $message = $this->buildEmailTemplate([
            'title' => 'Your Service Has Been Completed!',
            'greeting' => "Hello " . htmlspecialchars($customerName) . ",",
            'content' => [
                'We are pleased to inform you that your service request has been completed successfully!',
                '',
                '<strong>Service Summary:</strong>',
                '• Request ID: ' . htmlspecialchars($serviceDetails['request_id']),
                '• Service Type: ' . htmlspecialchars($serviceDetails['service_type']),
                '• Completion Date: ' . date('F j, Y \a\t g:i A', strtotime($serviceDetails['completion_date'])),
                '• Technician: ' . htmlspecialchars($serviceDetails['technician_name'] ?? 'SmartFix Team'),
                '',
                'We hope you are satisfied with our service. Your feedback is important to us!',
                '',
                'If you experience any issues or have questions about the completed work, please don\'t hesitate to contact us within 7 days for warranty support.'
            ],
            'cta_text' => 'Leave a Review',
            'cta_link' => 'https://smartfix.com/leave_review.php?request_id=' . urlencode($serviceDetails['request_id']),
            'footer_note' => 'Thank you for choosing SmartFix. We look forward to serving you again!'
        ]);
        
        return $this->sendEmail($customerEmail, $subject, $message);
    }
    
    /**
     * Build HTML email template
     */
    private function buildEmailTemplate($data) {
        $content_html = '';
        foreach ($data['content'] as $line) {
            if (empty($line)) {
                $content_html .= '<br>';
            } else {
                $content_html .= '<p>' . $line . '</p>';
            }
        }
        
        $cta_button = '';
        if (isset($data['cta_text']) && isset($data['cta_link'])) {
            $cta_button = '
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . htmlspecialchars($data['cta_link']) . '" 
                       style="display: inline-block; padding: 12px 30px; background-color: #007bff; 
                              color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        ' . htmlspecialchars($data['cta_text']) . '
                    </a>
                </div>';
        }
        
        return "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background-color: #007bff; color: white; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 30px 20px; background-color: #ffffff; }
                .content h2 { color: #007bff; margin-top: 0; }
                .content p { margin: 10px 0; }
                .footer { padding: 20px; text-align: center; background-color: #f8f9fa; color: #666; font-size: 14px; }
                .logo { font-size: 24px; font-weight: bold; }
                a { color: #007bff; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🔧 SmartFix</div>
                    <h1>" . htmlspecialchars($data['title']) . "</h1>
                </div>
                <div class='content'>
                    <h2>" . $data['greeting'] . "</h2>
                    " . $content_html . "
                    " . $cta_button . "
                </div>
                <div class='footer'>
                    <p>" . htmlspecialchars($data['footer_note']) . "</p>
                    <p>SmartFix - Your Trusted Repair Service<br>
                    Email: info@smartfix.com | Phone: +1 (555) 123-4567</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send email using PHP mail function
     */
    private function sendEmail($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->fromName . " <" . $this->fromEmail . ">" . "\r\n";
        $headers .= "Reply-To: info@smartfix.com" . "\r\n";
        
        // Log email attempt
        error_log("Sending email to: $to, Subject: $subject");
        
        try {
            $result = mail($to, $subject, $message, $headers);
            if ($result) {
                error_log("Email sent successfully to: $to");
                return true;
            } else {
                error_log("Failed to send email to: $to");
                return false;
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log email activity to database
     */
    public function logEmailActivity($recipient, $subject, $type, $status, $request_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (recipient, subject, email_type, status, request_id, sent_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$recipient, $subject, $type, $status, $request_id]);
        } catch (PDOException $e) {
            error_log("Failed to log email activity: " . $e->getMessage());
        }
    }
}
?>