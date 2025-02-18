<?php
require_once '../config/Database.php';
require_once '../utils/Auth.php';

class OrderController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function createOrder($userId, $orderData) {
        try {
            $this->conn->begin_transaction();

            // Create the order
            $orderQuery = "INSERT INTO orders (user_id, restaurant_id, total_amount, status) 
                          VALUES (?, ?, ?, 'pending')";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bind_param("iid",
                $userId,
                $orderData['restaurant_id'],
                $orderData['total_amount']
            );
            $orderStmt->execute();
            $orderId = $this->conn->insert_id;

            // Insert order items
            $itemQuery = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal, notes) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $itemStmt = $this->conn->prepare($itemQuery);

            foreach ($orderData['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $itemStmt->bind_param("iiidds",
                    $orderId,
                    $item['menu_item_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $subtotal,
                    $item['notes']
                );
                $itemStmt->execute();
            }

            // Record initial status
            $statusQuery = "INSERT INTO order_status_history (order_id, status) VALUES (?, 'pending')";
            $statusStmt = $this->conn->prepare($statusQuery);
            $statusStmt->bind_param("i", $orderId);
            $statusStmt->execute();

            $this->conn->commit();
            return ['success' => true, 'order_id' => $orderId];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => 'Failed to create order'];
        }
    }

    public function updateOrderStatus($orderId, $status, $notes = '') {
        try {
            $this->conn->begin_transaction();

            // Update order status
            $orderQuery = "UPDATE orders SET status = ? WHERE id = ?";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bind_param("si", $status, $orderId);
            $orderStmt->execute();

            // Record status history
            $historyQuery = "INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)";
            $historyStmt = $this->conn->prepare($historyQuery);
            $historyStmt->bind_param("iss", $orderId, $status, $notes);
            $historyStmt->execute();

            $this->conn->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => 'Failed to update order status'];
        }
    }

    public function getOrderDetails($orderId) {
        $query = "SELECT o.*, 
                         oi.quantity, oi.unit_price, oi.subtotal, oi.notes,
                         mi.name as item_name, mi.description as item_description,
                         r.name as restaurant_name
                  FROM orders o
                  JOIN order_items oi ON o.id = oi.order_id
                  JOIN menu_items mi ON oi.menu_item_id = mi.id
                  JOIN restaurants r ON o.restaurant_id = r.id
                  WHERE o.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $orderDetails = [
            'items' => [],
            'status_history' => []
        ];

        while ($row = $result->fetch_assoc()) {
            if (empty($orderDetails['order_info'])) {
                $orderDetails['order_info'] = [
                    'id' => $row['id'],
                    'status' => $row['status'],
                    'total_amount' => $row['total_amount'],
                    'restaurant_name' => $row['restaurant_name'],
                    'created_at' => $row['created_at']
                ];
            }

            $orderDetails['items'][] = [
                'name' => $row['item_name'],
                'description' => $row['item_description'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'subtotal' => $row['subtotal'],
                'notes' => $row['notes']
            ];
        }

        // Get status history
        $historyQuery = "SELECT status, notes, created_at 
                        FROM order_status_history 
                        WHERE order_id = ? 
                        ORDER BY created_at DESC";
        $historyStmt = $this->conn->prepare($historyQuery);
        $historyStmt->bind_param("i", $orderId);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();

        while ($row = $historyResult->fetch_assoc()) {
            $orderDetails['status_history'][] = $row;
        }

        return $orderDetails;
    }
} 