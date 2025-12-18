<?php
// Suppress warnings in production to prevent HTML in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

//Database Configuration File (Handles API and DB connection)
// Database Configuration - Support environment variables for production
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root'); 
define('DB_PASS', getenv('DB_PASS') ?: '');     
define('DB_NAME', getenv('DB_NAME') ?: 'weather_dashboard');

// OpenWeatherMap API Configuration (MY API KEY)
define('API_KEY', getenv('API_KEY') ?: 'a1de693852ced7b108a00f23aa51f665');
define('API_BASE_URL', 'https://api.openweathermap.org/data/2.5/weather');

// Security Settings - Allow all origins
define('ALLOWED_ORIGINS', ['*']);


/**
 * Get database connection
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Set charset to UTF-8
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $conn;
}


//Set CORS headers for API responses (make js talk to php)
function setCORSHeaders() {
    // Always allow all origins for public weather API
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json; charset=UTF-8");
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}