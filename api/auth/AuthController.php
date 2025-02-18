<?php
require_once '../config/Database.php';
require_once '../utils/JWT.php';

class AuthController {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function register($data) {
        // Validate input
        if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
            return ['error' => 'Missing required fields'];
        }

        // Check if email exists
        $query = "SELECT id FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['error' => 'Email already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        // Insert user
        $query = "INSERT INTO " . $this->table . " (name, email, password, address, phone, role) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssss", 
            $data['name'],
            $data['email'],
            $hashedPassword,
            $data['address'],
            $data['phone'],
            $data['role']
        );

        if ($stmt->execute()) {
            $userId = $this->conn->insert_id;
            $token = JWT::generate(['user_id' => $userId, 'role' => $data['role']]);
            return [
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role']
                ]
            ];
        }

        return ['error' => 'Registration failed'];
    }

    public function login($email, $password) {
        $query = "SELECT id, name, email, password, role FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $token = JWT::generate([
                    'user_id' => $user['id'],
                    'role' => $user['role']
                ]);
                
                unset($user['password']);
                return [
                    'token' => $token,
                    'user' => $user
                ];
            }
        }

        return ['error' => 'Invalid credentials'];
    }
} 