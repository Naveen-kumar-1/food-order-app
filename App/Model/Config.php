<?php

class Config {
    public static function getConnection() {
        $conn = mysqli_connect('localhost', 'root', '', 'food_order_app');

        if (!$conn) {
            return null;
        }

        return $conn;
    }
}   