<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ... kode web kamu yang lain di bawah sini ...
require_once __DIR__ . '/includes/functions.php';

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get all tasks
$allTasks = getTasks();

// Filter tasks based on selection
$tasks = [];
foreach ($allTasks as $task) {
    switch ($filter) {
        case 'active':
            if (!$task['deleted'] && !$task['completed']) {
                $tasks[] = $task;
            }
            break;
        case 'completed':
            if (!$task['deleted'] && $task['completed']) {
                $tasks[] = $task;
            }
            break;
        case 'deadline':
            // Tugas yang deadline H-3 dan belum selesai
            if (!$task['deleted'] && !$task['completed']) {
                $deadline = strtotime($task['deadline']);
                $diff = $deadline - time();
                if ($diff >= 0 && $diff <= 3 * 24 * 60 * 60) {
                    $tasks[] = $task;
                }
            }
            break;
        case 'trash':
            if ($task['deleted']) {
                $tasks[] = $task;
            }
            break;
        default: // 'all'
            if (!$task['deleted']) {
                $tasks[] = $task;
            }
            break;
    }
}

// Sort tasks: incomplete first (by deadline ASC), then completed
usort($tasks, function($a, $b) {
    if ($a['completed'] !== $b['completed']) {
        return $a['completed'] ? 1 : -1;
    }
    return strtotime($a['deadline']) - strtotime($b['deadline']);
});

// Get stats
$stats = getTaskStats();

// Get urgent tasks for notification
$urgentTasks = getDeadlineUrgentTasks();

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
        <div class="modal-title" id="modalTitle"></div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<!-- ===== STATISTICS CARDS ===== -->
<section class="stats-grid">
    <div class="stat-card total">
        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Tugas</div>
    </div>
    <div class="stat-card active-task">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?php echo $stats['active']; ?></div>
        <div class="stat-label">Belum Selesai</div>
    </div>
    <div class="stat-card completed">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?php echo $stats['completed']; ?></div>
        <div class="stat-label">Selesai</div>
    </div>
    <div class="stat-card overdue">
        <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="stat-value"><?php echo $stats['overdue']; ?></div>
        <div class="stat-label">Terlambat</div>
    </div>
    <div class="stat-card deleted">
        <div class="stat-icon"><i class="fas fa-trash"></i></div>
        <div class="stat-value"><?php echo $stats['deleted']; ?></div>
        <div class="stat-label">Di Sampah</div>
    </div>
    <?php if ($stats['deadline_urgent'] > 0): ?>
    <div class="stat-card deadline-urgent">
        <div class="stat-icon"><i class="fas fa-bell"></i></div>
        <div class="stat-value"><?php echo $stats['deadline_urgent']; ?></div>
        <div class="stat-label">Deadline H-3</div>
    </div>
    <?php endif; ?>
</section>

<!-- ===== DEADLINE NOTIFICATION BANNER ===== -->
<div id="deadlineNotificationContainer">
<?php if (!empty($urgentTasks) && $filter !== 'trash'): ?>
<div class="deadline-notification">
    <div class="deadline-notif-header">
        <i class="fas fa-bell"></i>
        <span>⚠️ Peringatan Deadline!</span>
        <a href="?filter=deadline" class="deadline-notif-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="deadline-notif-list">
        <?php foreach (array_slice($urgentTasks, 0, 5) as $urgent): ?>
            <div class="deadline-notif-item">
                <i class="fas fa-clock"></i>
                <span class="deadline-notif-name"><?php echo htmlspecialchars($urgent['name']); ?></span>
                <span class="deadline-notif-date"><?php echo formatDateIndonesia($urgent['deadline']); ?></span>
                <span class="deadline-notif-badge">H-<?php echo floor((strtotime($urgent['deadline']) - time()) / (60 * 60 * 24)); ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (count($urgentTasks) > 5): ?>
            <div class="deadline-notif-item">
                <span style="color: var(--text-muted); font-size: 0.85rem;">...dan <?php echo count($urgentTasks) - 5; ?> tugas lainnya</span>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>

<!-- ===== ADD TASK FORM (only show when not in trash) ===== -->
<?php if ($filter !== 'trash'): ?>
<section class="add-task-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-plus-circle"></i> Tambah Tugas Baru
        </h2>
    </div>
    <form id="addTaskForm" class="add-task-form" method="POST">
        <div class="form-group">
            <label for="taskName">Nama Tugas</label>
            <input type="text" id="taskName" name="name" class="form-input" 
                   placeholder="Masukkan nama tugas..." required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="taskDeadline">Deadline</label>
            <input type="date" id="taskDeadline" name="deadline" class="form-input" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Tugas
        </button>
    </form>
</section>
<?php endif; ?>

<!-- ===== TASK LIST ===== -->
<section class="task-list-section">
    <div class="task-list-header">
        <h2 class="section-title">
            <?php if ($filter === 'all'): ?>
                <i class="fas fa-th-large"></i> Semua Tugas
            <?php elseif ($filter === 'active'): ?>
                <i class="fas fa-clock"></i> Tugas Aktif
            <?php elseif ($filter === 'completed'): ?>
                <i class="fas fa-check-circle"></i> Tugas Selesai
            <?php elseif ($filter === 'deadline'): ?>
                <i class="fas fa-bell"></i> Deadline H-3
            <?php elseif ($filter === 'trash'): ?>
                <i class="fas fa-trash"></i> Sampah
            <?php endif; ?>
        </h2>
        <span class="task-count"><?php echo count($tasks); ?> tugas</span>
        
        <!-- Bulk Action Toolbar (only show when not in trash) -->
        <?php if ($filter !== 'trash'): ?>
        <div class="bulk-action-toolbar" id="bulkActionToolbar" style="display: none;">
            <div class="bulk-selection-info">
                <span id="selectedCount">0</span> tugas dipilih
            </div>
            <div class="bulk-actions">
                <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn">
                    <i class="fas fa-trash"></i> Hapus Terpilih
                </button>
                <button type="button" class="btn btn-sm btn-success" id="bulkCompleteBtn">
                    <i class="fas fa-check"></i> Tandai Selesai
                </button>
                <button type="button" class="btn btn-sm btn-warning" id="bulkNotCompleteBtn">
                    <i class="fas fa-times"></i> Tandai Belum Selesai
                </button>
                <button type="button" class="btn btn-sm btn-secondary" id="bulkCancelBtn">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Status Filter Dropdown -->
    <div class="status-filter-container">
        <div class="status-filter">
            <label for="statusFilter">Tampilkan Tugas:</label>
            <select id="statusFilter" class="form-select">
                <option value="all" <?php echo ($filter === 'all') ? 'selected' : ''; ?>>📋 Semua Tugas</option>
                <option value="active" <?php echo ($filter === 'active') ? 'selected' : ''; ?>>⏳ Belum Selesai</option>
                <option value="completed" <?php echo ($filter === 'completed') ? 'selected' : ''; ?>>✅ Selesai</option>
                <option value="deadline" <?php echo ($filter === 'deadline') ? 'selected' : ''; ?>>🔔 Deadline H-3</option>
                <option value="trash" <?php echo ($filter === 'trash') ? 'selected' : ''; ?>>🗑️ Di Sampah</option>
            </select>
        </div>
    </div>

    <div id="taskListContainer">
        <?php if (!empty($tasks) && $filter !== 'trash'): ?>
        <div class="select-all-container">
            <input type="checkbox" id="selectAllCheckbox" class="select-all-checkbox">
            <label for="selectAllCheckbox">Pilih Semua</label>
        </div>
        <?php endif; ?>
        
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <?php if ($filter === 'trash'): ?>
                    <i class="fas fa-trash-restore"></i>
                    <h3>Sampah masih kosong</h3>
                    <p>Tugas yang dihapus akan muncul di sini</p>
                <?php elseif ($filter === 'completed'): ?>
                    <i class="fas fa-check-double"></i>
                    <h3>Belum ada tugas selesai</h3>
                    <p>Centang tugas yang sudah kamu kerjakan</p>
                <?php elseif ($filter === 'active'): ?>
                    <i class="fas fa-inbox"></i>
                    <h3>Semua tugas sudah selesai! 🎉</h3>
                    <p>Tambahkan tugas baru untuk melanjutkan</p>
                <?php elseif ($filter === 'deadline'): ?>
                    <i class="fas fa-check-circle"></i>
                    <h3>Tidak ada deadline mendesak</h3>
                    <p>Semua tugas masih jauh dari deadline</p>
                <?php else: ?>
                    <i class="fas fa-inbox"></i>
                    <h3>Belum ada tugas</h3>
                    <p>Tambahkan tugas pertama kamu sekarang!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="task-list">
                <?php foreach ($tasks as $task): 
                    $color = getTaskColor($task);
                    $colorClass = 'color-blue';
                    if ($color === '#10b981') $colorClass = 'color-green';
                    elseif ($color === '#f59e0b') $colorClass = 'color-yellow';
                    elseif ($color === '#ef4444') $colorClass = 'color-red';
                    elseif ($color === '#3b82f6') $colorClass = 'color-blue';
                    
                    $deadlineStatus = getDeadlineStatus($task);
                    $deadlineLabel = '';
                    if ($task['completed']) {
                        $deadlineLabel = '<span class="deadline-status" style="background: rgba(59,130,246,0.15); color: #3b82f6;">✓ Selesai</span>';
                    } else {
                        $deadline = strtotime($task['deadline']);
                        $now = time();
                        $diff = $deadline - $now;
                        if ($diff < 0) {
                            $deadlineLabel = '<span class="deadline-status" style="background: rgba(239,68,68,0.15); color: #ef4444;">⚠ ' . $deadlineStatus . '</span>';
                        } elseif ($diff <= 3 * 24 * 60 * 60) {
                            $deadlineLabel = '<span class="deadline-status" style="background: rgba(245,158,11,0.15); color: #f59e0b;">⏰ ' . $deadlineStatus . '</span>';
                        } else {
                            $deadlineLabel = '<span class="deadline-status" style="background: rgba(16,185,129,0.15); color: #10b981;">✓ ' . $deadlineStatus . '</span>';
                        }
                    }
                ?>
                    <div class="task-item <?php echo $colorClass; ?>" data-status="<?php echo $task['completed'] ? 'completed' : ($task['deleted'] ? 'deleted' : 'in_progress'); ?>">
                    <div class="task-check <?php echo $task['completed'] ? 'completed' : ''; ?>">
                        <?php if ($filter !== 'trash'): ?>
                            <label class="toggle-complete" data-id="<?php echo $task['id']; ?>">
                                <input type="checkbox" class="task-checkbox" data-id="<?php echo $task['id']; ?>" 
                                    <?php echo $task['completed'] ? 'checked' : ''; ?>>
                                <div class="checkbox-custom">
                                    <?php if ($task['completed']): ?>
                                        <i class="fas fa-check"></i>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endif; ?>
                    </div>
                        
                        <div class="task-content">
                            <div class="task-name <?php echo $task['completed'] ? 'completed-text' : ''; ?>">
                                <?php echo htmlspecialchars($task['name']); ?>
                            </div>
                            <div class="task-meta">
                                <span class="task-deadline">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo formatDateIndonesia($task['deadline']); ?>
                                </span>
                                <?php echo $deadlineLabel; ?>
                            </div>
                        </div>
                        
                        <div class="task-actions">
                            <?php if ($filter === 'trash'): ?>
                                <button class="btn btn-sm btn-cyan restore-task" 
                                        data-id="<?php echo $task['id']; ?>"
                                        title="Kembalikan tugas">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button class="btn btn-sm btn-dark permanent-delete-task" 
                                        data-id="<?php echo $task['id']; ?>"
                                        title="Hapus secara permanen">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-warning edit-task" 
                                        data-id="<?php echo $task['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($task['name']); ?>"
                                        title="Edit nama tugas">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-task" 
                                        data-id="<?php echo $task['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($task['name']); ?>"
                                        title="Hapus tugas">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>