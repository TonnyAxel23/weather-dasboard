<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Replace with your actual OpenWeatherMap API key
$apiKey = 'YOUR_API_KEY_HERE';

if (!isset($_GET['city'])) {
    echo json_encode(['error' => 'City parameter is required']);
    exit;
}

$city = urlencode($_GET['city']);

// API URLs
$currentWeatherUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&units=metric&appid={$apiKey}";
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&units=metric&appid={$apiKey}";

// Function to fetch data with error handling
function fetchApiData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return ['error' => 'CURL Error: ' . curl_error($ch)];
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $data = json_decode($response, true);
        return ['error' => $data['message'] ?? "API request failed with status $httpCode"];
    }
    
    return json_decode($response, true);
}

// Get current weather
$currentData = fetchApiData($currentWeatherUrl);
if (isset($currentData['error'])) {
    echo json_encode(['error' => $currentData['error']]);
    exit;
}

// Get forecast
$forecastData = fetchApiData($forecastUrl);
if (isset($forecastData['error'])) {
    echo json_encode(['error' => $forecastData['error']]);
    exit;
}

// Process forecast data to get daily forecasts
$dailyForecasts = [];
$processedDays = [];

foreach ($forecastData['list'] as $forecast) {
    $date = date('Y-m-d', $forecast['dt']);
    
    if (!in_array($date, $processedDays)) {
        $processedDays[] = $date;
        
        // Find midday forecast for better representation
        $middayForecast = null;
        foreach ($forecastData['list'] as $f) {
            if (date('Y-m-d', $f['dt']) === $date && date('H', $f['dt']) >= 12) {
                $middayForecast = $f;
                break;
            }
        }
        
        if (!$middayForecast) {
            $middayForecast = $forecast;
        }
        
        // Find max and min temps for the day
        $temps = array_filter(array_map(function($f) use ($date) {
            return date('Y-m-d', $f['dt']) === $date ? $f['main']['temp'] : null;
        }, $forecastData['list']));
        
        $dailyForecasts[] = [
            'dt' => $middayForecast['dt'],
            'temp' => [
                'max' => max($temps),
                'min' => min($temps),
            ],
            'weather' => $middayForecast['weather'],
        ];
        
        if (count($dailyForecasts) >= 5) {
            break;
        }
    }
}

// Prepare response
echo json_encode([
    'city' => $currentData['name'],
    'country' => $currentData['sys']['country'] ?? '',
    'current' => [
        'temp' => $currentData['main']['temp'],
        'humidity' => $currentData['main']['humidity'],
        'wind_speed' => $currentData['wind']['speed'],
        'weather' => $currentData['weather'],
    ],
    'forecast' => $dailyForecasts
]);
?>
