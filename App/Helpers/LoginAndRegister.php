<?php

class LoginAndRegister {
    public static function createSignUpTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_title VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            website_url VARCHAR(255),
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        return mysqli_query($conn, $sql);
    }
}