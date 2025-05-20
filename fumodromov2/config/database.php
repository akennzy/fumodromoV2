<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'fumodromov2');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set MySQL timezone to match PHP timezone (São Paulo, UTC-3)
mysqli_query($conn, "SET time_zone = '-03:00'");

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, DB_NAME);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        quit_date DATETIME NOT NULL,
        cigarettes_per_day INT NOT NULL,
        cost_per_day DECIMAL(10,2) NOT NULL,
        points INT DEFAULT 0,
        level INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Create checklist table    
    $sql = "CREATE TABLE IF NOT EXISTS daily_checklist (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        date DATE NOT NULL,
        hydration BOOLEAN DEFAULT FALSE,
        conscious_breathing BOOLEAN DEFAULT FALSE,
        trigger_avoidance BOOLEAN DEFAULT FALSE,
        completed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        UNIQUE KEY unique_user_date (user_id, date)
    )";
    mysqli_query($conn, $sql);    // Create relapses table
    $sql = "CREATE TABLE IF NOT EXISTS relapses (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        relapse_date DATETIME(6) NOT NULL COMMENT 'Data e hora exata da recaída com precisão de microssegundos',
        abstinence_duration INT NOT NULL COMMENT 'Duração da abstinência em minutos',
        level_reached INT NOT NULL COMMENT 'Nível alcançado antes da recaída',
        points_lost INT NOT NULL COMMENT 'Pontos acumulados perdidos',
        created_at TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP(6),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    mysqli_query($conn, $sql);
} else {
    echo "Error creating database: " . mysqli_error($conn);
}
?>
