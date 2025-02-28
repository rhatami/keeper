<?php
header('Content-Type: application/json');
// APIs info
require_once('api.php');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
// API Endpoint and Keys
ini_set('display_errors', 1);

// Validate input
$valid_assets = ['USD','EUR','USDT','BTC','ETH', 'Silver', 'Gold18', 'Gold24' , 'Coin', 'HCoin', 'QCoin' , 'Stock'];


/*  
    *********** HANDLING GET REQUEST *********** 
*/

// Get the asset parameter from URL
$asset = $_GET['asset'] ?? '';
$type = $_GET['type'] ?? '';
$asset = trim($asset);
$type = trim($type);

// empty parameter error
if (empty($asset)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Asset parameter is required',
        'valid_assets' => $valid_assets
    ]);
    exit;
}

// out of scope assets error
if (!in_array($asset, $valid_assets)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid asset type',
        'valid_assets' => $valid_assets
    ]);
    exit;
}

// empty type error
if (isset($asset) && $asset != "None" && empty($type)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'type parameter is required'
    ]);
    exit;
}

// Get the price
if($asset == "Stock"){
   $price = get_stock_price($type); 
}
else{
   $price = get_price($asset);  
}

// Prepare response
$response = [
    'status' => 'success',
    'data' => [
        $type ? 'type' : 'asset' => $type ? $type : $asset,
        'price' => $price
        ]
];

// Send response
echo json_encode($response);


/*  
    *********** Functions *********** 
*/

  //////////////////
 // API Functions /
//////////////////

function get_price($asset) {
    global $apiEndpoints; 
    switch ($asset) {
        case 'USD':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['currencies']['dollar']['p'] ?? 0) / 10;
         case 'EUR':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['currencies']['eur']['p'] ?? 0) / 10;
        case 'USDT':
            return (int) fetchAPI($apiEndpoints['tetherland'])['data']['currencies']['USDT']['price'] ?? 0;
        case 'BTC':
            return USD_to_IRT((int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPICrypto'])['result']['0']['price'] ?? 0));
        case 'ETH':
            return USD_to_IRT((int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPICrypto'])['result']['1']['price'] ?? 0));
        case 'Silver':
            return USD_to_IRT((int) (str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['gold']['silver']['p'] ?? 0) / 28.35));
        case 'Gold18':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['gold']['geram18']['p'] ?? 0) / 10;
        case 'Gold24':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['gold']['geram24']['p'] ?? 0) / 10;
        case 'Coin':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['coin_retail']['sekee']['p'] ?? 0) / 10;
        case 'HCoin':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['coin_retail']['nim']['p'] ?? 0) / 10;
        case 'QCoin':
            return (int) str_replace(',', '', fetchAPI($apiEndpoints['oneAPIMain'])['result']['coin_retail']['rob']['p'] ?? 0) / 10;
        default:
            return 0;
    }
}

function get_stock_price($type) {
    global $apiEndpoints;
    $cacheFile = 'stocks.json';
    $cacheDuration = 3600; // 1 hour in seconds

    // Check if cache exists and is still valid
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheDuration)) {
        $data = json_decode(file_get_contents($cacheFile), true);
    } else {
        $data = fetch_and_cache_data($cacheFile, $apiEndpoints['brsAPI']);
    }

    // Ensure valid data
    if (!is_array($data)) {
        error_log("Invalid data format from API or cache.");
        return 0;
    }

    // Search for the stock price
    foreach ($data as $item) {
        if (isset($item['l18']) && $item['l18'] === $type) {
            return isset($item['pf']) ? (int)$item['pf'] / 10 : 0;
        }
    }

    return 0; // Stock not found
}

  //////////////////////
 // Useful Functions //
//////////////////////

function fetch_and_cache_data($cacheFile, $apiUrl) {
    $lockFile = $cacheFile . '.lock';

    // Prevent simultaneous API calls
    $lock = fopen($lockFile, 'w');
    if ($lock && !flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return json_decode(file_get_contents($cacheFile), true); // Use last known data
    }

    // Fetch data from API
    $data = fetchAPI($apiUrl);
    
    if (is_array($data)) {
        file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    } else {
        error_log("Failed to fetch valid data from API.");
        $data = [];
    }

    // Release lock
    if ($lock) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    return $data;
}

// convert USD to Toman
function USD_to_IRT($usd_value){
    $usd_price = get_price("USD");
    return $usd_value * $usd_price;
}
?>