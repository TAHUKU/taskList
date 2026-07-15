/**
 * Todo List - JavaScript Interactivity
 * Mengelola AJAX requests, modal, toast, dan interaksi pengguna
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== Toast Notification System =====
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);

    /**
     * Menampilkan toast notification
     * @param {string} message - Pesan yang ditampilkan
     * @param {string} type - Tipe: 'success' | 'error' | 'info'
     */
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconMap = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        };
        
        toast.innerHTML = `
            <i class="fas ${iconMap[type] || iconMap.info}"></i>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto remove after 3.5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100px)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 3500);
    };

    // ===== Modal System =====
    const modalOverlay = document.getElementById('modalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalClose = document.getElementById('modalClose');

    /**
     * Membuka modal dengan konten tertentu
     * @param {string} title - Judul modal
     * @param {string} content - HTML konten
     */
    window.openModal = function(title, content) {
        modalTitle.innerHTML = title;
        modalBody.innerHTML = content;
        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    /**
     * Menutup modal
     */
    window.closeModal = function() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeModal();
        }
    });

    // ===== Add Task Form =====
    const addTaskForm = document.getElementById('addTaskForm');
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('modules/add_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    refreshDeadlineNotification();
                    this.reset();
                    // Set default date to tomorrow
                    const dateInput = this.querySelector('input[type="date"]');
                    if (dateInput) {
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        dateInput.value = tomorrow.toISOString().split('T')[0];
                    }
                    refreshTaskList();
                    refreshStats();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Terjadi kesalahan saat menambah tugas', 'error');
                console.error('Error:', error);
            });
        });
    }

    // Set default date to tomorrow for add form
    const addDateInput = document.querySelector('#addTaskForm input[type="date"]');
    if (addDateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        addDateInput.value = tomorrow.toISOString().split('T')[0];
    }

    // ===== Task Actions (Event Delegation) =====
    document.addEventListener('click', function(e) {
        // Toggle Complete - Optimistic UI
        const toggleBtn = e.target.closest('.toggle-complete');
        if (toggleBtn) {
            e.preventDefault();
            const taskId = toggleBtn.dataset.id;
            toggleComplete(taskId, toggleBtn);
            return;
        }
        
        // Edit Task
        const editBtn = e.target.closest('.edit-task');
        if (editBtn) {
            e.preventDefault();
            const taskId = editBtn.dataset.id;
            const taskName = editBtn.dataset.name;
            showEditModal(taskId, taskName);
            return;
        }
        
        // Delete Task
        const deleteBtn = e.target.closest('.delete-task');
        if (deleteBtn) {
            e.preventDefault();
            const taskId = deleteBtn.dataset.id;
            const taskName = deleteBtn.dataset.name;
            confirmDelete(taskId, taskName);
            return;
        }
        
        // Permanent Delete Task
        const permanentDeleteBtn = e.target.closest('.permanent-delete-task');
        if (permanentDeleteBtn) {
            e.preventDefault();
            const taskId = permanentDeleteBtn.dataset.id;
            confirmPermanentDelete(taskId);
            return;
        }
        
        // Restore Task
        const restoreBtn = e.target.closest('.restore-task');
        if (restoreBtn) {
            e.preventDefault();
            const taskId = restoreBtn.dataset.id;
            restoreTask(taskId);
            return;
        }
    });

    // ===== Toggle Complete (Optimistic UI) =====
    function toggleComplete(id, btnElement) {
        // 1. Optimistic update: toggle visual state IMMEDIATELY
        const taskItem = btnElement.closest('.task-item');
        const taskCheck = taskItem.querySelector('.task-check');
        const checkCustom = taskItem.querySelector('.checkbox-custom');
        const taskName = taskItem.querySelector('.task-name');
        const checkbox = taskItem.querySelector('.task-checkbox');
        const currentlyCompleted = taskItem.classList.contains('completed');
        
        // Toggle immediately (update both visual AND checkbox state for bulk selection)
        if (!currentlyCompleted) {
            taskItem.classList.remove('color-green', 'color-yellow', 'color-red');
            taskItem.classList.add('color-blue', 'completed');
            taskName.classList.add('completed-text');
            checkCustom.innerHTML = '<i class="fas fa-check"></i>';
            taskCheck?.classList.add('completed');
            if (checkbox) checkbox.checked = true;
        } else {
            taskItem.classList.remove('color-blue', 'completed');
            taskName.classList.remove('completed-text');
            checkCustom.innerHTML = '';
            taskCheck?.classList.remove('completed');
            if (checkbox) checkbox.checked = false;
        }
        
        // Update bulk selection state
        updateBulkSelection();
        
        // 2. Send AJAX request
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('modules/complete_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                refreshStats();
                refreshDeadlineNotification();
            } else {
                // Rollback on failure
                if (currentlyCompleted) {
                    taskItem.classList.remove('color-blue', 'completed');
                    taskName.classList.remove('completed-text');
                    checkCustom.innerHTML = '';
                    taskCheck?.classList.remove('completed');
                    if (checkbox) checkbox.checked = false;
                } else {
                    taskItem.classList.remove('color-blue', 'completed');
                    taskItem.classList.add(getOriginalColorClass(taskItem));
                    taskName.classList.remove('completed-text');
                    checkCustom.innerHTML = '';
                    taskCheck?.classList.remove('completed');
                    if (checkbox) checkbox.checked = false;
                }
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            // Rollback on error
            if (currentlyCompleted) {
                taskItem.classList.remove('color-blue', 'completed');
                taskName.classList.remove('completed-text');
                checkCustom.innerHTML = '';
                taskCheck?.classList.remove('completed');
                if (checkbox) checkbox.checked = false;
            } else {
                taskItem.classList.remove('completed');
                taskName.classList.remove('completed-text');
                checkCustom.innerHTML = '';
                taskCheck?.classList.remove('completed');
                if (checkbox) checkbox.checked = false;
            }
            showToast('Terjadi kesalahan', 'error');
            console.error('Error:', error);
        });
    }
    
    function getOriginalColorClass(taskItem) {
        const deadlineEl = taskItem.querySelector('.deadline-status');
        if (deadlineEl) {
            const text = deadlineEl.textContent || '';
            if (text.includes('Terlambat') || text.includes('⚠')) return 'color-red';
            if (text.includes('⏰')) return 'color-yellow';
            if (text.includes('✓') && !text.includes('Selesai')) return 'color-green';
        }
        return 'color-blue';
    }

    // ===== Edit Task =====
    function showEditModal(id, currentName) {
        const content = `
            <form id="editTaskForm">
                <input type="hidden" name="id" value="${id}">
                <div class="form-group">
                    <label for="editName">Nama Tugas</label>
                    <input type="text" id="editName" name="name" class="form-input" 
                           value="${escapeHtml(currentName)}" placeholder="Masukkan nama tugas" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        `;
        
        openModal('<i class="fas fa-edit"></i> Edit Tugas', content);
        
        const editForm = document.getElementById('editTaskForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('modules/edit_task.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeModal();
                        refreshTaskList();
                        refreshStats();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Terjadi kesalahan', 'error');
                    console.error('Error:', error);
                });
            });
            
            setTimeout(() => document.getElementById('editName')?.focus(), 100);
        }
    }

    // ===== Delete Task (with confirmation) =====
    function confirmDelete(id, name) {
        const content = `
            <div style="text-align: center; padding: 10px 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--color-yellow); margin-bottom: 16px; display: block;"></i>
                <p style="color: var(--text-secondary); margin-bottom: 8px; font-size: 1rem;">
                    Apakah kamu yakin ingin menghapus tugas:
                </p>
                <p style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary); margin-bottom: 20px;">
                    "${escapeHtml(name)}"
                </p>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">
                    Tugas akan dipindahkan ke sampah dan bisa dikembalikan nanti.
                </p>
                <div style="display: flex; gap: 10px;">
                    <button onclick="closeModal()" class="btn" 
                            style="flex: 1; background: var(--bg-card-hover); color: var(--text-secondary);">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button onclick="deleteTask('${id}')" class="btn btn-danger" style="flex: 1;">
                        <i class="fas fa-trash"></i> Ya, Hapus!
                    </button>
                </div>
            </div>
        `;
        
        openModal('<i class="fas fa-trash"></i> Konfirmasi Hapus', content);
    }

    window.deleteTask = function(id) {
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('modules/delete_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeModal();
                refreshTaskList();
                refreshStats();
                refreshDeadlineNotification();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan', 'error');
            console.error('Error:', error);
        });
    };

    // ===== Permanent Delete Task (with confirmation) =====
    function confirmPermanentDelete(id) {
        const content = `
            <div style="text-align: center; padding: 10px 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--color-red); margin-bottom: 16px; display: block;"></i>
                <p style="color: var(--text-secondary); margin-bottom: 8px; font-size: 1rem;">
                    Apakah kamu yakin ingin menghapus tugas ini <strong>secara permanen</strong>?
                </p>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">
                    Tugas akan dihapus selamanya dan tidak bisa dikembalikan!
                </p>
                <div style="display: flex; gap: 10px;">
                    <button onclick="closeModal()" class="btn" 
                            style="flex: 1; background: var(--bg-card-hover); color: var(--text-secondary);">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button onclick="permanentDeleteTask('${id}')" class="btn btn-danger" style="flex: 1;">
                        <i class="fas fa-times"></i> Ya, Hapus Permanen!
                    </button>
                </div>
            </div>
        `;
        
        openModal('<i class="fas fa-trash"></i> Hapus Permanen', content);
    }

    window.permanentDeleteTask = function(id) {
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('modules/delete_permanent_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeModal();
                refreshTaskList();
                refreshStats();
                refreshDeadlineNotification();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan', 'error');
            console.error('Error:', error);
        });
    };

    // ===== Restore Task =====
    function restoreTask(id) {
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('modules/restore_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                refreshTaskList();
                refreshStats();
                refreshDeadlineNotification();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan', 'error');
            console.error('Error:', error);
        });
    }

    // ===== Refresh Task List =====
    function refreshTaskList() {
        const taskListContainer = document.getElementById('taskListContainer');
        if (!taskListContainer) return;
        
        const url = window.location.href;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTaskList = doc.getElementById('taskListContainer');
            
            if (newTaskList) {
                taskListContainer.innerHTML = newTaskList.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error refreshing task list:', error);
        });
    }

    // ===== Refresh Deadline Notification =====
    function refreshDeadlineNotification() {
        const notifContainer = document.getElementById('deadlineNotificationContainer');
        if (!notifContainer) return;
        
        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContainer = doc.getElementById('deadlineNotificationContainer');
            
            if (newContainer) {
                notifContainer.innerHTML = newContainer.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error refreshing deadline notification:', error);
        });
    }

    // ===== Refresh Stats =====
    function refreshStats() {
        const statsGrid = document.querySelector('.stats-grid');
        if (!statsGrid) return;
        
        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newStats = doc.querySelector('.stats-grid');
            
            if (newStats) {
                statsGrid.innerHTML = newStats.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
    }

    // ===== Status Filter =====
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedValue = this.value;
            window.location.href = '?filter=' + selectedValue;
        });
    }

    // ===== Bulk Selection =====
    function updateBulkSelection() {
        const checkboxes = document.querySelectorAll('.task-checkbox');
        const checkedBoxes = document.querySelectorAll('.task-checkbox:checked');
        
        if (selectedCountEl) {
            selectedCountEl.textContent = checkedBoxes.length;
        }
        
        if (bulkActionToolbar) {
            bulkActionToolbar.style.display = checkedBoxes.length > 0 ? 'flex' : 'none';
        }
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === checkedBoxes.length;
        }
    }
    
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const bulkActionToolbar = document.getElementById('bulkActionToolbar');
    const selectedCountEl = document.getElementById('selectedCount');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.task-checkbox');
            checkboxes.forEach(cb => {
                const taskItem = cb.closest('.task-item');
                if (taskItem && taskItem.style.display !== 'none') {
                    cb.checked = selectAllCheckbox.checked;
                }
            });
            updateBulkSelection();
        });
    }
    
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('task-checkbox')) {
            updateBulkSelection();
        }
    });
    
    // ===== Bulk Action Buttons =====
    const bulkCancelBtn = document.getElementById('bulkCancelBtn');
    if (bulkCancelBtn) {
        bulkCancelBtn.addEventListener('click', function() {
            document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = false);
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateBulkSelection();
        });
    }
    
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.dataset.id);
            if (selectedIds.length === 0) return;
            
            const content = `
                <div style="text-align: center; padding: 10px 0;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--color-yellow); margin-bottom: 16px; display: block;"></i>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Hapus ${selectedIds.length} tugas yang dipilih?
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="closeModal()" class="btn" style="flex: 1; background: var(--bg-card-hover); color: var(--text-secondary);">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button onclick="bulkDeleteTasks()" class="btn btn-danger" style="flex: 1;">
                            <i class="fas fa-trash"></i> Ya, Hapus!
                        </button>
                    </div>
                </div>
            `;
            openModal('<i class="fas fa-trash"></i> Konfirmasi Hapus', content);
        });
    }
    
    window.bulkDeleteTasks = function() {
        const selectedIds = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.dataset.id);
        
        Promise.all(selectedIds.map(id => {
            const formData = new FormData();
            formData.append('id', id);
            return fetch('modules/delete_task.php', { method: 'POST', body: formData }).then(r => r.json());
        })).then(results => {
            const successCount = results.filter(r => r.success).length;
            showToast(`${successCount} tugas berhasil dihapus`, 'success');
            closeModal();
            refreshTaskList();
            refreshStats();
            refreshDeadlineNotification();
        }).catch(() => showToast('Terjadi kesalahan', 'error'));
    };
    
    const bulkCompleteBtn = document.getElementById('bulkCompleteBtn');
    if (bulkCompleteBtn) {
        bulkCompleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.dataset.id);
            
            Promise.all(selectedIds.map(id => {
                const formData = new FormData();
                formData.append('id', id);
                return fetch('modules/complete_task.php', { method: 'POST', body: formData }).then(r => r.json());
            })).then(results => {
                const successCount = results.filter(r => r.success).length;
                showToast(`${successCount} tugas ditandai selesai`, 'success');
                refreshTaskList();
                refreshStats();
                refreshDeadlineNotification();
            }).catch(() => showToast('Terjadi kesalahan', 'error'));
        });
    }
    
    const bulkNotCompleteBtn = document.getElementById('bulkNotCompleteBtn');
    if (bulkNotCompleteBtn) {
        bulkNotCompleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.dataset.id);
            
            Promise.all(selectedIds.map(id => {
                const formData = new FormData();
                formData.append('id', id);
                return fetch('modules/complete_task.php', { method: 'POST', body: formData }).then(r => r.json());
            })).then(results => {
                const successCount = results.filter(r => r.success).length;
                showToast(`${successCount} tugas ditandai belum selesai`, 'success');
                refreshTaskList();
                refreshStats();
                refreshDeadlineNotification();
            }).catch(() => showToast('Terjadi kesalahan', 'error'));
        });
    }

    // ===== Utility: Escape HTML =====
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});