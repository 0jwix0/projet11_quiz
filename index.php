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
        <!-- <h1 class="mb-4">Plateforme de Quiz</h1> -->
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quiz disponibles</h2>
            <!-- <a href="admin/create_quiz.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Créer un nouveau quiz
            </a> -->
            <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#myResultsModal">
            📊 Mes résultats
            </button>
        </div>
        <div class="mb-4">
            <input type="text" id="quizSearch" class="form-control" placeholder="Rechercher un quiz par titre ou description...">
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
<!-- Modal Mes résultats -->
<div class="modal fade" id="myResultsModal" tabindex="-1" aria-labelledby="myResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="myResultsModalLabel">Consulter mes résultats</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Entrez votre nom et votre email (facultatif) pour voir tous les quizzes que vous avez réussis (score ≥ 60 %) et télécharger vos certificats.</p>
                <form method="POST" action="view_my_results.php">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email (facultatif)</label>
                        <input type="email" name="email" id="email" class="form-control">
                        <small class="text-muted">Utilisé pour une recherche plus précise si plusieurs personnes ont le même nom.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Rechercher mes résultats</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('quizSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const cards = document.querySelectorAll('.col-md-6.col-lg-4.mb-4');
    
    cards.forEach(card => {
        const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const desc = card.querySelector('.card-text')?.textContent.toLowerCase() || '';
        
        if (title.includes(filter) || desc.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>