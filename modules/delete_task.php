<?php
/**
 * Module: Menghapus tugas (soft delete)
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = trim($_POST['id']);
    
    $result = deleteTask($id);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil dipindahkan ke sampah!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);