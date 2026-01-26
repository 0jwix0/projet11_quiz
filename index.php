<?php
require_once 'config/database.php';
require_once 'includes/header.php';

// Récupérer tous les quiz disponibles
$stmt = $pdo->query("SELECT q.*, COUNT(DISTINCT qs.id) as nb_questions 
                     FROM quiz q 
                     LEFT JOIN questions qs ON q.id = qs.quiz_id 
                     GROUP BY q.id 
                     ORDER BY q.id DESC");
$quiz_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Plateforme de Quiz</h1>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quiz disponibles</h2>
            <a href="admin/create_quiz.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Créer un nouveau quiz
            </a>
        </div>

        <?php if (empty($quiz_list)): ?>
            <div class="alert alert-info">
                Aucun quiz disponible pour le moment. Créez-en un pour commencer !
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($quiz_list as $quiz): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($quiz['titre']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($quiz['description']) ?></p>
                                
                                <div class="quiz-info mt-3">
                                    <p class="mb-2">
                                        <strong>Questions:</strong> <?= $quiz['nb_questions'] ?>
                                    </p>
                                    <?php if ($quiz['temps_limite']): ?>
                                        <p class="mb-2">
                                            <strong>Temps limite:</strong> <?= $quiz['temps_limite'] ?> minutes
                                        </p>
                                    <?php endif; ?>
                                    <p class="mb-2">
                                        <strong>Tentatives max:</strong> <?= $quiz['tentatives_max'] ?>
                                    </p>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <a href="take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-success">
                                        Commencer
                                    </a>
                                    <a href="leaderboard.php?id=<?= $quiz['id'] ?>" class="btn btn-info">
                                        Classement
                                    </a>
                                    <a href="admin/edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-warning">
                                        Éditer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>