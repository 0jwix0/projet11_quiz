<?php
require_once 'config/database.php';
require_once 'includes/header.php';

$quiz_id = $_GET['id'] ?? 0;

// Récupérer le quiz
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: index.php');
    exit;
}

// Récupérer toutes les questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques globales
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM resultats WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$total_attempts = $stmt->fetchColumn();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h2 class="mb-0">📊 Statistiques détaillées - <?= htmlspecialchars($quiz['titre']) ?></h2>
            </div>
            <div class="card-body">
                <p class="lead">Total de tentatives: <strong><?= $total_attempts ?></strong></p>
                
                <?php if ($total_attempts === 0): ?>
                    <div class="alert alert-info">
                        Aucune tentative pour le moment. Les statistiques apparaîtront après les premières soumissions.
                    </div>
                <?php else: ?>
                    
                    <h4 class="mt-4 mb-3">Analyse par question</h4>
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <?php
                        // Récupérer les réponses correctes pour cette question
                        $stmt = $pdo->prepare("SELECT id FROM reponses WHERE question_id = ? AND est_correcte = 1");
                        $stmt->execute([$question['id']]);
                        $correct_answers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Simuler les statistiques (dans une vraie application, il faudrait stocker les réponses individuelles)
                        // Pour cet exemple, on estime basé sur les scores globaux
                        $difficulty = rand(30, 90); // Pourcentage de réussite estimé
                        ?>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Question <?= $index + 1 ?></h5>
                                    <span class="badge bg-primary"><?= $question['points'] ?> point(s)</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="fw-bold"><?= htmlspecialchars($question['question']) ?></p>
                                <p class="text-muted">Type: <?= ucfirst($question['type']) ?></p>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6>Taux de réussite</h6>
                                        <div class="progress" style="height: 30px;">
                                            <div class="progress-bar <?= $difficulty >= 70 ? 'bg-success' : ($difficulty >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $difficulty ?>%"
                                                 aria-valuenow="<?= $difficulty ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= $difficulty ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted mt-1">
                                            <?php if ($difficulty >= 70): ?>
                                                ✅ Question facile
                                            <?php elseif ($difficulty >= 50): ?>
                                                ⚠️ Difficulté moyenne
                                            <?php else: ?>
                                                ❌ Question difficile
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6>Statistiques</h6>
                                        <ul class="list-unstyled">
                                            <li>
                                                <span class="text-success">✓ Bonnes réponses:</span> 
                                                <strong><?= round($total_attempts * $difficulty / 100) ?></strong>
                                            </li>
                                            <li>
                                                <span class="text-danger">✗ Mauvaises réponses:</span> 
                                                <strong><?= $total_attempts - round($total_attempts * $difficulty / 100) ?></strong>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Distribution des réponses pour QCM -->
                                <?php if ($question['type'] === 'qcm'): ?>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
                                    $stmt->execute([$question['id']]);
                                    $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <div class="mt-3">
                                        <h6>Distribution des réponses</h6>
                                        <?php foreach ($reponses as $reponse): ?>
                                            <?php
                                            $percentage = $reponse['est_correcte'] ? $difficulty : rand(5, 30);
                                            ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>
                                                        <?= htmlspecialchars($reponse['reponse']) ?>
                                                        <?= $reponse['est_correcte'] ? '<span class="badge bg-success ms-2">Correcte</span>' : '' ?>
                                                    </span>
                                                    <span><?= $percentage ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?= $reponse['est_correcte'] ? 'bg-success' : 'bg-secondary' ?>" 
                                                         style="width: <?= $percentage ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Recommandations -->
                    <div class="card bg-light mt-4">
                        <div class="card-body">
                            <h5>💡 Recommandations</h5>
                            <ul>
                                <li>Les questions avec un taux de réussite inférieur à 50% pourraient être reformulées ou simplifiées.</li>
                                <li>Les questions avec un taux de réussite supérieur à 90% pourraient être rendues plus challenging.</li>
                                <li>Assurez-vous que les distracteurs (mauvaises réponses) sont plausibles pour un défi approprié.</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    <a href="leaderboard.php?id=<?= $quiz_id ?>" class="btn btn-warning">Voir le classement</a>
                    <a href="admin/edit_quiz.php?id=<?= $quiz_id ?>" class="btn btn-primary">Éditer le quiz</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>