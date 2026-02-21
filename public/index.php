<?php

require __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

header("Content-Type: application/json");

$firestore = new FirestoreClient([
    'projectId' => getenv('GOOGLE_CLOUD_PROJECT')
]);

$collection = $firestore->collection('notes');

$method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

if ($uri[0] === 'health') {
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($uri[0] === 'notes') {

    // POST /notes
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['title']) || !isset($data['content'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid payload"]);
            exit;
        }

        $docRef = $collection->add([
            'title' => $data['title'],
            'content' => $data['content'],
            'created_at' => new DateTime()
        ]);

        echo json_encode(["id" => $docRef->id()]);
        exit;
    }

    // GET /notes
    if ($method === 'GET' && count($uri) === 1) {
        $documents = $collection->documents();
        $result = [];

        foreach ($documents as $document) {
            if ($document->exists()) {
                $result[] = [
                    'id' => $document->id(),
                    'data' => $document->data()
                ];
            }
        }

        echo json_encode($result);
        exit;
    }

    // GET /notes/{id}
    if ($method === 'GET' && count($uri) === 2) {
        $snapshot = $collection->document($uri[1])->snapshot();

        if (!$snapshot->exists()) {
            http_response_code(404);
            echo json_encode(["error" => "Not found"]);
            exit;
        }

        echo json_encode([
            'id' => $snapshot->id(),
            'data' => $snapshot->data()
        ]);
        exit;
    }

    // DELETE /notes/{id}
    if ($method === 'DELETE' && count($uri) === 2) {
        $collection->document($uri[1])->delete();
        echo json_encode(["deleted" => true]);
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Route not found"]);