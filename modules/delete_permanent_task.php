<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = $_POST['id'] ?? null;

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID tugas tidak valid']);
    exit;
}

if (deleteTaskPermanently($id)) {
    echo json_encode(['success' => true, 'message' => 'Tugas dihapus secara permanen']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
}
?>