<?php

header("Content-Type: application/json");

$projectId = getenv('PROJECT_ID');
$baseUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/notes";

function getAccessToken() {
    $metadataUrl = "http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token";
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Metadata-Flavor: Google"
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($metadataUrl, false, $context);
    $data = json_decode($response, true);
    return $data['access_token'];
}

function firestoreRequest($method, $url, $body = null) {
    $token = getAccessToken();

    $opts = [
        "http" => [
            "method" => $method,
            "header" => "Authorization: Bearer $token\r\nContent-Type: application/json",
        ]
    ];

    if ($body) {
        $opts["http"]["content"] = json_encode($body);
    }

    $context = stream_context_create($opts);
    return file_get_contents($url, false, $context);
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

if ($uri[0] === 'health') {
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($uri[0] === 'notes') {

    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $payload = [
            "fields" => [
                "title" => ["stringValue" => $data["title"]],
                "content" => ["stringValue" => $data["content"]]
            ]
        ];

        $response = firestoreRequest("POST", $baseUrl, $payload);
        echo $response;
        exit;
    }

    if ($method === 'GET') {
        $response = firestoreRequest("GET", $baseUrl);
        echo $response;
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Not found"]);