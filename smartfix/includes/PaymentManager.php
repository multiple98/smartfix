<?php
/**
 * Enhanced Payment Manager for SmartFix
 * Supports multiple payment methods and mobile-optimized processing
 */

class PaymentManager {
    private $pdo;
    private $config;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = [
            'currency' => 'ZMW',
            'currency_symbol' => 'K',
            'tax_rate' => 0.16, // 16% VAT for Zambia
            'payment_methods' => [
                'mobile_money' => [
                    'name' => 'Mobile Money',
                    'providers' => ['MTN', 'Airtel', 'Zamtel'],
                    'enabled' => true
                ],
                'bank_transfer' => [
                    'name' => 'Bank Transfer',
                    'enabled' => true
                ],
                'cash_on_delivery' => [
                    'name' => 'Cash on Delivery',
                    'enabled' => true
                ],
                'card_payment' => [
                    'name' => 'Card Payment',
                    'enabled' => false // Will be enabled with Stripe/PayPal integration
                ]
            ]
        ];
        
        $this->initializePaymentTables();
    }
    
    private function initializePaymentTables() {
        try {
            // Create payment_transactions table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS payment_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    transaction_id VARCHAR(100) UNIQUE,
                    payment_method VARCHAR(50) NOT NULL,
                    provider VARCHAR(50),
                    amount DECIMAL(10,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'ZMW',
                    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                    reference_number VARCHAR(100),
                    customer_phone VARCHAR(20),
                    metadata JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_order_id (order_id),
                    INDEX idx_transaction_id (transaction_id),
                    INDEX idx_status (status)
                )
            ");
            
            // Create payment_methods table for customer saved methods
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS payment_methods (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    method_type VARCHAR(50) NOT NULL,
                    provider VARCHAR(50),
                    account_number VARCHAR(100),
                    account_name VARCHAR(100),
                    is_default BOOLEAN DEFAULT FALSE,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id)
                )
            ");
            
        } catch (PDOException $e) {
            error_log("Error creating payment tables: " . $e->getMessage());
        }
    }
    
    public function processPayment($order_id, $payment_data) {
        try {
            $this->pdo->beginTransaction();
            
            // Validate payment data
            $validation = $this->validatePaymentData($payment_data);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Get order details
            $order = $this->getOrderDetails($order_id);
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            // Generate transaction ID
            $transaction_id = $this->generateTransactionId();
            
            // Create payment transaction record
            $payment_id = $this->createPaymentTransaction([
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
                'payment_method' => $payment_data['method'],
                'provider' => $payment_data['provider'] ?? null,
                'amount' => $order['total_amount'],
                'currency' => $this->config['currency'],
                'reference_number' => $payment_data['reference'] ?? null,
                'customer_phone' => $payment_data['phone'] ?? null,
                'metadata' => json_encode($payment_data)
            ]);
            
            // Process based on payment method
            $result = $this->processPaymentByMethod($payment_id, $payment_data);
            
            if ($result['success']) {
                // Update order status
                $this->updateOrderPaymentStatus($order_id, 'paid');
                $this->pdo->commit();
                
                return [
                    'success' => true,
                    'transaction_id' => $transaction_id,
                    'payment_id' => $payment_id,
                    'message' => 'Payment processed successfully',
                    'data' => $result
                ];
            } else {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Payment processing failed'
                ];
            }
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function processPaymentByMethod($payment_id, $payment_data) {
        switch ($payment_data['method']) {
            case 'mobile_money':
                return $this->processMobileMoney($payment_id, $payment_data);
                
            case 'bank_transfer':
                return $this->processBankTransfer($payment_id, $payment_data);
                
            case 'cash_on_delivery':
                return $this->processCashOnDelivery($payment_id, $payment_data);
                
            case 'card_payment':
                return $this->processCardPayment($payment_id, $payment_data);
                
            default:
                return ['success' => false, 'message' => 'Invalid payment method'];
        }
    }
    
    private function processMobileMoney($payment_id, $payment_data) {
        // Update payment status to processing
        $this->updatePaymentStatus($payment_id, 'processing');
        
        // In a real implementation, you would integrate with mobile money APIs
        // For now, we'll simulate the process
        
        $provider = $payment_data['provider'] ?? 'MTN';
        $phone = $payment_data['phone'] ?? '';
        
        // Simulate API call delay
        sleep(1);
        
        // For demo purposes, assume successful payment
        $this->updatePaymentStatus($payment_id, 'completed');
        
        return [
            'success' => true,
            'provider' => $provider,
            'phone' => $phone,
            'message' => "Mobile Money payment via {$provider} completed successfully"
        ];
    }
    
    private function processBankTransfer($payment_id, $payment_data) {
        // Bank transfers are typically manual verification
        $this->updatePaymentStatus($payment_id, 'pending');
        
        return [
            'success' => true,
            'message' => 'Bank transfer details recorded. Payment pending verification.',
            'instructions' => $this->getBankTransferInstructions()
        ];
    }
    
    private function processCashOnDelivery($payment_id, $payment_data) {
        // COD payments are completed on delivery
        $this->updatePaymentStatus($payment_id, 'pending');
        
        return [
            'success' => true,
            'message' => 'Cash on delivery selected. Payment will be collected upon delivery.'
        ];
    }
    
    private function processCardPayment($payment_id, $payment_data) {
        // This would integrate with Stripe, PayPal, or local payment processor
        return [
            'success' => false,
            'message' => 'Card payments not yet available. Coming soon!'
        ];
    }
    
    private function validatePaymentData($data) {
        if (!isset($data['method'])) {
            return ['valid' => false, 'message' => 'Payment method is required'];
        }
        
        if (!array_key_exists($data['method'], $this->config['payment_methods'])) {
            return ['valid' => false, 'message' => 'Invalid payment method'];
        }
        
        if (!$this->config['payment_methods'][$data['method']]['enabled']) {
            return ['valid' => false, 'message' => 'Payment method is not available'];
        }
        
        // Method-specific validation
        switch ($data['method']) {
            case 'mobile_money':
                if (empty($data['phone'])) {
                    return ['valid' => false, 'message' => 'Phone number is required for mobile money'];
                }
                if (empty($data['provider'])) {
                    return ['valid' => false, 'message' => 'Mobile money provider is required'];
                }
                break;
                
            case 'bank_transfer':
                if (empty($data['reference'])) {
                    return ['valid' => false, 'message' => 'Bank reference number is required'];
                }
                break;
        }
        
        return ['valid' => true];
    }
    
    private function generateTransactionId() {
        return 'SF-TXN-' . strtoupper(uniqid()) . '-' . date('Ymd');
    }
    
    private function createPaymentTransaction($data) {
        $sql = "INSERT INTO payment_transactions 
                (order_id, transaction_id, payment_method, provider, amount, currency, 
                 reference_number, customer_phone, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['order_id'],
            $data['transaction_id'],
            $data['payment_method'],
            $data['provider'],
            $data['amount'],
            $data['currency'],
            $data['reference_number'],
            $data['customer_phone'],
            $data['metadata']
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    private function updatePaymentStatus($payment_id, $status) {
        $sql = "UPDATE payment_transactions SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status, $payment_id]);
    }
    
    private function updateOrderPaymentStatus($order_id, $payment_status) {
        $sql = "UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$payment_status, $order_id]);
    }
    
    private function getOrderDetails($order_id) {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$order_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getBankTransferInstructions() {
        return [
            'bank_name' => 'Standard Chartered Bank Zambia',
            'account_name' => 'SmartFix Services Limited',
            'account_number' => '0100123456789',
            'swift_code' => 'SCBLZMLU',
            'reference' => 'Include your order number as reference'
        ];
    }
    
    public function getPaymentMethods() {
        return array_filter($this->config['payment_methods'], function($method) {
            return $method['enabled'];
        });
    }
    
    public function getPaymentHistory($user_id, $limit = 10) {
        $sql = "SELECT pt.*, o.tracking_number, o.total_amount as order_total
                FROM payment_transactions pt
                JOIN orders o ON pt.order_id = o.id
                WHERE o.user_id = ?
                ORDER BY pt.created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function formatAmount($amount) {
        return $this->config['currency_symbol'] . number_format($amount, 2);
    }
    
    public function calculateTax($amount) {
        return $amount * $this->config['tax_rate'];
    }
    
    public function calculateTotal($subtotal) {
        $tax = $this->calculateTax($subtotal);
        return $subtotal + $tax;
    }
}
?>