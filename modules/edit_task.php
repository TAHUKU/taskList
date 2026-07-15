<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['name'])) {
    $id = trim($_POST['id']);
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama tugas tidak boleh kosong!']);
        exit;
    }
    
    $result = editTask($id, $name);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Nama tugas berhasil diupdate!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);