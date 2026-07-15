<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Menambahkan tugas baru
 * @param string $nama
 * @param string $deadline
 * @param string $status
 * @return array|null
 */
function addTask($nama, $deadline, $status = 'in_progress') {
    $tasks = getTasks();
    $newTask = [
        'id' => generateId(),
        'name' => htmlspecialchars(trim($nama)),
        'deadline' => $deadline,
        'completed' => false,
        'deleted' => false,
        'status' => $status, // 'in_progress', 'completed', 'not_completed'
        'created_at' => date('Y-m-d H:i:s')
    ];
    $tasks[] = $newTask;

    if (!saveTasks($tasks)) {
        return null;
    }
    return $newTask;
}

/**
 * Mengedit nama tugas
 * @param string $id
 * @param string $newName
 * @return bool
 */
function editTask($id, $newName) {
    $tasks = getTasks();
    foreach ($tasks as &$task) {
        if ($task['id'] === $id) {
            $task['name'] = htmlspecialchars(trim($newName));
            saveTasks($tasks);
            return true;
        }
    }
    return false;
}

/**
 * Menandai tugas selesai / belum selesai (toggle)
 * @param string $id
 * @return bool
 */
function toggleComplete($id) {
    $tasks = getTasks();
    foreach ($tasks as &$task) {
        if ($task['id'] === $id) {
            $task['completed'] = !$task['completed'];
            // Update status based on completed state
            $task['status'] = $task['completed'] ? 'completed' : 'not_completed';
            saveTasks($tasks);
            return true;
        }
    }
    return false;
}

/**
 * Menghapus tugas (soft delete)
 * @param string $id
 * @return bool
 */
function deleteTask($id) {
    $tasks = getTasks();
    foreach ($tasks as &$task) {
        if ($task['id'] === $id) {
            $task['deleted'] = true;
            saveTasks($tasks);
            return true;
        }
    }
    return false;
}

/**
 * Mengembalikan tugas dari sampah
 * @param string $id
 * @return bool
 */
function restoreTask($id) {
    $tasks = getTasks();
    foreach ($tasks as &$task) {
        if ($task['id'] === $id) {
            $task['deleted'] = false;
            saveTasks($tasks);
            return true;
        }
    }
    return false;
}

/**
 * Menghapus tugas secara permanen
 * @param string $id
 * @return bool
 */
function deleteTaskPermanently($id) {
    $tasks = getTasks();
    $taskFound = false;
    
    foreach ($tasks as $index => $task) {
        if ($task['id'] === $id) {
            $taskFound = true;
            unset($tasks[$index]);
            break;
        }
    }
    
    if ($taskFound) {
        saveTasks($tasks);
        return true;
    }
    return false;
}

/**
 * Mendapatkan status warna berdasarkan deadline dan completed
 * @param array $task
 * @return string (color hex)
 */
function getTaskColor($task) {
    if ($task['completed']) {
        return '#3b82f6'; // Biru - Selesai
    }
    
    $deadline = strtotime($task['deadline']);
    $now = time();
    $diff = $deadline - $now;
    $daysLeft = floor($diff / (60 * 60 * 24));
    
    if ($diff < 0) {
        return '#ef4444'; // Merah - Lewat deadline
    } elseif ($daysLeft <= 3) {
        return '#f59e0b'; // Kuning - Deadline mendekat (H-3)
    } else {
        return '#10b981'; // Hijau - Masih jauh
    }
}

/**
 * Mendapatkan label status deadline dalam Bahasa Indonesia
 * @param array $task
 * @return string
 */
function getDeadlineStatus($task) {
    if ($task['completed']) {
        return 'Selesai';
    }
    
    $deadline = strtotime($task['deadline']);
    $now = time();
    $diff = $deadline - $now;
    $daysLeft = floor($diff / (60 * 60 * 24));
    
    if ($diff < 0) {
        $daysPast = abs($daysLeft);
        return "Terlambat {$daysPast} hari";
    } elseif ($daysLeft == 0) {
        return 'Deadline hari ini!';
    } elseif ($daysLeft == 1) {
        return 'Batas akhir besok';
    } else {
        return "Sisa {$daysLeft} hari";
    }
}

/**
 * Mendapatkan daftar tugas yang deadline-nya H-3 (urgent)
 * @return array
 */
function getDeadlineUrgentTasks() {
    $tasks = getTasks();
    $urgentTasks = [];
    $now = time();
    $threeDays = 3 * 24 * 60 * 60; // 3 hari dalam detik
    
    foreach ($tasks as $task) {
        if ($task['deleted'] || $task['completed']) {
            continue;
        }
        $deadline = strtotime($task['deadline']);
        $diff = $deadline - $now;
        // Deadline dalam rentang 0 - 3 hari ke depan (belum lewat)
        if ($diff >= 0 && $diff <= $threeDays) {
            $urgentTasks[] = $task;
        }
    }
    
    return $urgentTasks;
}

/**
 * Format tanggal Indonesia
 * @param string $date
 * @return string
 */
function formatDateIndonesia($date) {
    $hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $bulan = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];
    
    $timestamp = strtotime($date);
    $dayName = $hari[date('l', $timestamp)];
    $day = date('j', $timestamp);
    $month = $bulan[date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "{$dayName}, {$day} {$month} {$year}";
}

/**
 * Mendapatkan statistik tugas
 * @return array
 */
function getTaskStats() {
    $tasks = getTasks();
    $stats = [
        'total' => 0,
        'active' => 0,
        'completed' => 0,
        'overdue' => 0,
        'deleted' => 0,
        'deadline_urgent' => 0 // Tambah statistik deadline urgent
    ];
    
    foreach ($tasks as $task) {
        if ($task['deleted']) {
            $stats['deleted']++;
            continue;
        }
        $stats['total']++;
        
        if ($task['completed']) {
            $stats['completed']++;
        } else {
            $stats['active']++;
            $deadline = strtotime($task['deadline']);
            if ($deadline < time()) {
                $stats['overdue']++;
            }
            // Cek deadline urgent (H-3, belum lewat)
            $diff = $deadline - time();
            if ($diff >= 0 && $diff <= 3 * 24 * 60 * 60) {
                $stats['deadline_urgent']++;
            }
        }
    }
    
    return $stats;
}