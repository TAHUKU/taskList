<?php
/**
 * Module: Menandai tugas selesai / belum selesai (toggle)
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = trim($_POST['id']);
    
    $result = toggleComplete($id);
    if ($result) {
        $task = getTaskById($id);
        $status = $task['completed'] ? 'Selesai' : 'Belum selesai';
        echo json_encode(['success' => true, 'message' => "Tugas ditandai: {$status}!", 'completed' => $task['completed']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);