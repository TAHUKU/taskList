<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = trim($_POST['id']);
    
    $result = restoreTask($id);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil dikembalikan!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);