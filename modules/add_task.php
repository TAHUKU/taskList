<?php
/**
 * Module: Menambahkan tugas baru
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['deadline'])) {
    $name = trim($_POST['name']);
    $deadline = trim($_POST['deadline']);
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama tugas tidak boleh kosong!']);
        exit;
    }
    
    if (empty($deadline)) {
        echo json_encode(['success' => false, 'message' => 'Deadline harus diisi!']);
        exit;
    }
    
    $task = addTask($name, $deadline);
    if ($task) {
        echo json_encode(['success' => true, 'message' => 'Tugas berhasil ditambahkan!', 'task' => $task]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan tugas. Silakan cek izin folder data/ dan file tasks.json.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);