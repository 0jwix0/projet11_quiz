<?php
require_once 'config/database.php';
require_once 'includes/header.php';
session_start();

if (!isset($_SESSION['quiz_result'])) {
    header('Location: index.php');
    exit;
}

$result = $_SESSION['quiz_result'];
$percentage = ($result['score'] / $result['total_points']) * 100;
$passed = $percentage >= 60; // Seuil de réussite à 60%

// Récupérer le titre du quiz
$stmt = $pdo->prepare("SELECT titre FROM quiz WHERE id = ?");
$stmt->execute([$result['quiz_id']]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow">
            <div class="card-header <?= $passed ? 'bg-success' : 'bg-danger' ?> text-white">
                <h2 class="mb-0">
                    <?= $passed ? '🎉 Félicitations!' : '❌ Quiz terminé' ?>
                </h2>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3><?= htmlspecialchars($result['nom_participant']) ?></h3>
                    <h4><?= htmlspecialchars($quiz['titre']) ?></h4>
                    
                    <div class="score-display my-4">
                        <div class="display-3 fw-bold <?= $passed ? 'text-success' : 'text-danger' ?>">
                            <?= $result['score'] ?> / <?= $result['total_points'] ?>
                        </div>
                        <div class="fs-4 text-muted">
                            (<?= number_format($percentage, 1) ?>%)
                        </div>
                        <?php if ($result['temps_ecoule']): ?>
                            <div class="mt-2 text-muted">
                                Temps écoulé: <?= gmdate("i:s", $result['temps_ecoule']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($passed): ?>
                        <div class="alert alert-success">
                            <strong>Quiz réussi!</strong> Vous avez obtenu un score suffisant.
                        </div>
                        <a href="generate_certificate.php?id=<?= $result['resultat_id'] ?>" 
                           class="btn btn-primary btn-lg mb-3" target="_blank">
                            📜 Télécharger le certificat
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Le score minimum requis est de 60%. Réessayez!
                        </div>
                    <?php endif; ?>
                </div>

                <hr>

                <h4 class="mb-3">Détails des réponses</h4>
                
                <?php foreach ($result['details'] as $index => $detail): ?>
                    <div class="card mb-3 <?= $detail['is_correct'] ? 'border-success' : 'border-danger' ?>">
                        <div class="card-header <?= $detail['is_correct'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Question <?= $index + 1 ?></strong>
                                <span class="badge <?= $detail['is_correct'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $detail['is_correct'] ? '✓ Correct' : '✗ Incorrect' ?>
                                    (<?= $detail['points'] ?> pt<?= $detail['points'] > 1 ? 's' : '' ?>)
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="fw-bold"><?= htmlspecialchars($detail['question']) ?></p>
                            
                            <?php if (!$detail['is_correct']): ?>
                                <div class="mb-2">
                                    <span class="text-danger">Votre réponse:</span>
                                    <span class="ms-2"><?= htmlspecialchars($detail['user_answer'] ?? 'Pas de réponse') ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <span class="text-success">Réponse correcte:</span>
                                <span class="ms-2 fw-bold"><?= htmlspecialchars($detail['correct_answer']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex gap-2 justify-content-center mt-4">
                    <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                    <a href="leaderboard.php?id=<?= $result['quiz_id'] ?>" class="btn btn-info">Voir le classement</a>
                    <?php if (!$passed): ?>
                        <a href="take_quiz.php?id=<?= $result['quiz_id'] ?>" class="btn btn-warning">Réessayer</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="card shadow mt-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Statistiques</h4>
            </div>
            <div class="card-body">
                <?php
                // Calculer les statistiques
                $correct = array_filter($result['details'], fn($d) => $d['is_correct']);
                $incorrect = count($result['details']) - count($correct);
                ?>
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="stat-box p-3">
                            <div class="display-6 text-success"><?= count($correct) ?></div>
                            <div class="text-muted">Bonnes réponses</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box p-3">
                            <div class="display-6 text-danger"><?= $incorrect ?></div>
                            <div class="text-muted">Mauvaises réponses</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box p-3">
                            <div class="display-6 text-primary"><?= count($result['details']) ?></div>
                            <div class="text-muted">Total questions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Nettoyer la session
unset($_SESSION['quiz_result']);
require_once 'includes/footer.php';
?>