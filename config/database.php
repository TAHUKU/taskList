<?php


define('DATA_FILE', __DIR__ . '/../data/tasks.json');

/**
 * Membaca semua tugas dari file JSON
 * @return array
 */
function getTasks() {
    if (!file_exists(DATA_FILE)) {
        file_put_contents(DATA_FILE, json_encode([]));
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $tasks = json_decode($json, true);
    return is_array($tasks) ? $tasks : [];
}

/**
 * Menyimpan semua tugas ke file JSON
 * @param array $tasks
 * @return bool
 */
function saveTasks($tasks) {
    $directory = dirname(DATA_FILE);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        return false;
    }

    if (!is_writable($directory) && !chmod($directory, 0777)) {
        return false;
    }

    $result = file_put_contents(DATA_FILE, json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    if ($result === false) {
        return false;
    }

    if (file_exists(DATA_FILE) && !is_writable(DATA_FILE)) {
        chmod(DATA_FILE, 0666);
    }

    return true;
}

/**
 * Mendapatkan tugas berdasarkan ID
 * @param string $id
 * @return array|null
 */
function getTaskById($id) {
    $tasks = getTasks();
    foreach ($tasks as $task) {
        if ($task['id'] === $id) {
            return $task;
        }
    }
    return null;
}

/**
 * Membuat ID unik untuk tugas baru
 * @return string
 */
function generateId() {
    return uniqid('task_', true);
}