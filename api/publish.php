<?php
header('Content-Type: application/json; charset=utf-8');

// Apenas aceitar requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use POST.']);
    exit;
}

// Ler dados enviados em JSON
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload JSON inválido.']);
    exit;
}

$title = trim($data['title'] ?? '');
$content = trim($data['content'] ?? '');
$status = trim($data['status'] ?? 'draft');

// Validar status permitido
if (!in_array($status, ['draft', 'publish', 'pending'])) {
    $status = 'draft';
}

if (empty($title) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Título e conteúdo são obrigatórios.']);
    exit;
}

// Credenciais e URL da API do WordPress
$wpApiUrl = 'https://noticiabare.com/wp-json/wp/v2/posts';
$username = 'mariozinhocs@gmail.com';
$appPassword = 'wYtzOmu06zaFsTi7tYg7NLZN';

$authHeader = 'Basic ' . base64_encode($username . ':' . $appPassword);

$postPayload = json_encode([
    'title'   => $title,
    'content' => $content,
    'status'  => $status
]);

// Realizar requisição HTTP
if (function_exists('curl_init')) {
    $ch = curl_init($wpApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postPayload);
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
    // Fallback com stream context
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: " . $authHeader . "\r\n",
            'content' => $postPayload,
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

if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['id'])) {
    echo json_encode([
        'success'  => true,
        'post_id'  => $responseData['id'],
        'link'     => $responseData['link'] ?? null,
        'status'   => $responseData['status'] ?? $status,
        'message'  => 'Matéria enviada com sucesso!'
    ]);
} else {
    $errMsg = $responseData['message'] ?? ('HTTP Status ' . $httpCode);
    echo json_encode([
        'success' => false,
        'message' => 'Falha na resposta do WordPress: ' . $errMsg,
        'raw'     => $responseData
    ]);
}
