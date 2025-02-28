<?php
$apiKeys = [
    'oneAPI' => 'YOUR_ONE_API_KEY',
    'brsAPI' => 'YOUR_BRSAPI_KEY',
];

$apiEndpoints = [
    'oneAPIMain' => 'https://one-api.ir/price/?token='  . $apiKeys['oneAPI'] . "&action=tgju",
    'oneAPICrypto' => 'https://one-api.ir/DigitalCurrency/?token=' . $apiKeys['oneAPI'],
    'brsAPI' => 'https://brsapi.ir/FreeTsetmcBourseApi/TsetmcApi.php?key=' . $apiKeys['brsAPI'],
    'tetherland' => 'https://api.tetherland.com/currencies'
    ];

// Function to fetch data from external APIs
function fetchAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}
?>