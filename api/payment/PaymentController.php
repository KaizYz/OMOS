<?php
require_once '../config/Database.php';

class PaymentController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function processPayment($orderData, $paymentTypeId) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create order
            $orderQuery = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'confirmed')";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bind_param("id", 
                $orderData['user_id'],
                $orderData['total_amount']
            );
            $orderStmt->execute();
            $orderId = $this->conn->insert_id;

            // Generate mock transaction ID
            $transactionId = 'TXN_' . uniqid();

            // Create payment record
            $paymentQuery = "INSERT INTO payments (order_id, payment_type_id, amount, status, transaction_id) 
                            VALUES (?, ?, ?, 'completed', ?)";
            $paymentStmt = $this->conn->prepare($paymentQuery);
            $paymentStmt->bind_param("iids",
                $orderId,
                $paymentTypeId,
                $orderData['total_amount'],
                $transactionId
            );
            $paymentStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'transaction_id' => $transactionId,
                'status' => 'completed'
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => 'Payment processing failed'
            ];
        }
    }

    public function savePaymentMethod($userId, $cardData) {
        $query = "INSERT INTO payment_types (user_id, card_name, card_number, card_expiry, card_type) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        // Only store last 4 digits of card number
        $maskedCardNumber = 'XXXX-XXXX-XXXX-' . substr($cardData['card_number'], -4);
        
        $stmt->bind_param("issss",
            $userId,
            $cardData['card_name'],
            $maskedCardNumber,
            $cardData['card_expiry'],
            $cardData['card_type']
        );

        if ($stmt->execute()) {
            return [
                'success' => true,
                'payment_type_id' => $this->conn->insert_id
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to save payment method'
        ];
    }
} 