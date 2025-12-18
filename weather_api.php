<?php

//Weather API Proxy
//Handles weather data requests and stores search history

require_once './db_config.php';

// Set CORS headers
setCORSHeaders();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
$city = trim($input['city'] ?? '');

if (empty($city)) {
    http_response_code(400);
    echo json_encode(['error' => 'City name is required']);
    exit();
}

// Validate city name (alphanumeric, spaces, hyphens only)
if (!preg_match('/^[a-zA-Z\s\-]+$/', $city)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid city name format']);
    exit();
}

try {
    // Build API URL
    $apiUrl = API_BASE_URL . '?' . http_build_query([
        'q' => $city,
        'appid' => API_KEY,
        'units' => 'metric'
    ]);
    
    // Fetch weather data from OpenWeatherMap
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'User-Agent: WeatherDashboard/1.0'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch weather data');
    }
    
    $weatherData = json_decode($response, true);
    
    // Check if API returned an error
    if (isset($weatherData['cod']) && $weatherData['cod'] != 200) {
        $errorMessage = $weatherData['message'] ?? 'City not found';
        http_response_code(404);
        echo json_encode(['error' => ucfirst($errorMessage)]);
        exit();
    }
    
    // Extract relevant data
    $cityName = $weatherData['name'];
    $countryCode = $weatherData['sys']['country'];
    $temperature = $weatherData['main']['temp'];
    $feelsLike = $weatherData['main']['feels_like'];
    $humidity = $weatherData['main']['humidity'];
    $pressure = $weatherData['main']['pressure'];
    $windSpeed = $weatherData['wind']['speed'];
    $visibility = isset($weatherData['visibility']) ? $weatherData['visibility'] / 1000 : 0; // Convert to km
    $weatherDescription = $weatherData['weather'][0]['description'];
    $weatherMain = $weatherData['weather'][0]['main'];
    $weatherIcon = $weatherData['weather'][0]['icon'];
    
    // Store search in database
    $conn = getDBConnection();
    
    $stmt = $conn->prepare(
        "INSERT INTO searches (city_name, country_code, temperature, weather_description) 
        VALUES (?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssds", $cityName, $countryCode, $temperature, $weatherDescription);
    
    if (!$stmt->execute()) {
        error_log("Failed to save search: " . $stmt->error);
    }
    
    $stmt->close();
    
    // Prepare response
    $responseData = [
        'success' => true,
        'data' => [
            'city' => $cityName,
            'country' => $countryCode,
            'temperature' => round($temperature, 1),
            'feelsLike' => round($feelsLike, 1),
            'humidity' => $humidity,
            'pressure' => $pressure,
            'windSpeed' => round($windSpeed, 1),
            'visibility' => round($visibility, 1),
            'description' => $weatherDescription,
            'weatherMain' => $weatherMain,
            'icon' => $weatherIcon,
            'timestamp' => time()
        ]
    ];
    
    http_response_code(200);
    echo json_encode($responseData);
    
} catch (Exception $e) {
    error_log("Weather API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch weather data. Please try again.']);
}