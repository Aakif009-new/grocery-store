<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $conn;
    private string $table = 'users';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function register(array $data): bool
    {
        try {
            $query = "INSERT INTO {$this->table} (email, password_hash, full_name, phone) 
                      VALUES (:email, :password_hash, :full_name, :phone)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password_hash', $data['password_hash']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':phone', $data['phone']);

            return $stmt->execute();
        } catch (PDOException $e) {
            logError("Register user error: " . $e->getMessage());
            return false;
        }
    }

    public function getByEmail(string $email): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (PDOException $e) {
            logError("Get user by email error: " . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (PDOException $e) {
            logError("Get user by id error: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile(int $id, array $data): bool
    {
        try {
            $query = "UPDATE {$this->table} SET
                        full_name = :full_name,
                        phone = :phone,
                        address = :address,
                        city = :city,
                        state = :state,
                        zip_code = :zip_code
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':city', $data['city']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':zip_code', $data['zip_code']);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            logError("Update profile error: " . $e->getMessage());
            return false;
        }
    }
}
