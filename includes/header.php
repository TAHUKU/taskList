<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Todo List Tugas Kuliah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="nav-brand">
            <i class="fas fa-check-double"></i>
            <span>Todo<span class="brand-highlight">Kuliah</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php?filter=all" class="nav-link <?php echo (!isset($_GET['filter']) || $_GET['filter'] === 'all') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Semua
            </a>
            <a href="index.php?filter=active" class="nav-link <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'active') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Aktif
            </a>
            <a href="index.php?filter=completed" class="nav-link <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'completed') ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Selesai
            </a>
            <a href="index.php?filter=trash" class="nav-link <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'trash') ? 'active' : ''; ?>">
                <i class="fas fa-trash"></i> Sampah
            </a>
        </div>
    </div>
</nav>
<div class="container main-content">
