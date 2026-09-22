<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use GET.']);
    exit;
}

$wpApiUrl = 'https://noticiabare.com/wp-json/wp/v2/categories?per_page=100&hide_empty=false';
$username = 'mariozinhocs@gmail.com';
$appPassword = 'wYtzOmu06zaFsTi7tYg7NLZN';

$authHeader = 'Basic ' . base64_encode($username . ':' . $appPassword);

if (function_exists('curl_init')) {
    $ch = curl_init($wpApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: ' . $authHeader
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Erro cURL: ' . $curlError]);
        exit;
    }
} else {
    $opts = [
        'http' => [
            'method'  => 'GET',
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: " . $authHeader . "\r\n",
            'timeout' => 15
        ]
    ];
    $context  = stream_context_create($opts);
    $response = @file_get_contents($wpApiUrl, false, $context);
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m);
        $httpCode = (int)($m[1] ?? 500);
    }
}

$responseData = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && is_array($responseData)) {
    $categories = [];
    foreach ($responseData as $cat) {
        if (isset($cat['id']) && isset($cat['name'])) {
            $categories[] = [
                'id' => $cat['id'],
                'name' => html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8')
            ];
        }
    }
    
    usort($categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} else {
    $errMsg = $responseData['message'] ?? ('HTTP Status ' . $httpCode);
    echo json_encode([
        'success' => false,
        'message' => 'Falha na resposta do WordPress: ' . $errMsg
    ]);
}
