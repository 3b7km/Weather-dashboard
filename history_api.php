<?php
// handles retrieval and deletion of search history
require_once './db_config.php';

setCORSHeaders();

try {
    $conn = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Retrieve search history
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $limit = min($limit, 50); // Max 50 records
        
        $stmt = $conn->prepare(
            "SELECT id, city_name, country_code, temperature, weather_description, searched_at 
            FROM searches 
            ORDER BY searched_at asc 
            LIMIT ?"
        );
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $searches = [];
        while ($row = $result->fetch_assoc()) {
            $searches[] = [
                'id' => $row['id'],
                'city' => $row['city_name'],
                'country' => $row['country_code'],
                'temperature' => $row['temperature'] ? round($row['temperature'], 1) : null,
                'description' => $row['weather_description'],
                'timestamp' => strtotime($row['searched_at']),
                'timeAgo' => getTimeAgo($row['searched_at'])
            ];
        }
        
        $stmt->close();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $searches,
            'count' => count($searches)
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Clear all search history
        $result = $conn->query("DELETE FROM searches");
        
        if (!$result) {
            throw new Exception("Delete failed: " . $conn->error);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Search history cleared',
            'deleted' => $conn->affected_rows
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("History API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process request']);
}

/**
 * Convert timestamp to human-readable time ago format
 * @param string $datetime MySQL datetime string
 * @return string Human-readable time ago
 */
function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>