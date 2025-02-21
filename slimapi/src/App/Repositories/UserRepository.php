<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

class UserRepository
{

    public function __construct(private Database $database)
    {
    }

    public function findByEmail(string $usernameOrEmail)
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindParam(':email', $usernameOrEmail, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);

    }

    public function findByUsername(string $usernameOrEmail)
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindParam(':username', $usernameOrEmail, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}