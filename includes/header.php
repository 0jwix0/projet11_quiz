<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plateforme de Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f4f7fa;
            min-height: 100vh;
            padding-bottom: 50px;
        }
        .card {
            border-radius: 15px;
            border: none;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }
        .question-block {
            background: #f8f9fa;
            transition: all 0.3s;
        }
        .question-block:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .progress {
            border-radius: 10px;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .stat-box:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .question-item {
            background: #f8f9fa;
            border-left: 4px solid #667eea !important;
        }
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? '../index.php' : 'index.php' ?>">
                <i class="bi bi-trophy-fill text-warning"></i> Plateforme Quiz
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? '../index.php' : 'index.php' ?>">
                            <i class="bi bi-house"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'create_quiz.php' : 'admin/create_quiz.php' ?>">
                            <i class="bi bi-plus-circle"></i> Créer un quiz
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
