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

// Récupérer le top 10
$stmt = $pdo->prepare("SELECT nom_participant, email, score, temps_ecoule, date_passage 
                       FROM resultats 
                       WHERE quiz_id = ? 
                       ORDER BY score DESC, temps_ecoule ASC 
                       LIMIT 10");
$stmt->execute([$quiz_id]);
$top_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques globales
$stmt = $pdo->prepare("SELECT 
                       COUNT(*) as total_tentatives,
                       AVG(score) as score_moyen,
                       MAX(score) as meilleur_score,
                       MIN(score) as score_minimum,
                       AVG(temps_ecoule) as temps_moyen
                       FROM resultats 
                       WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer le nombre total de points possible
$stmt = $pdo->prepare("SELECT SUM(points) as total_points FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$total_points = $stmt->fetchColumn();
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-warning">
                <h2 class="mb-0">🏆 Classement - <?= htmlspecialchars($quiz['titre']) ?></h2>
            </div>
            <div class="card-body">
                
                <!-- Statistiques globales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fs-4 fw-bold text-primary"><?= $stats['total_tentatives'] ?></div>
                            <div class="text-muted small">Tentatives</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fs-4 fw-bold text-success">
                                <?= number_format($stats['score_moyen'], 1) ?>/<?= $total_points ?>
                            </div>
                            <div class="text-muted small">Score moyen</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fs-4 fw-bold text-warning">
                                <?= $stats['meilleur_score'] ?>/<?= $total_points ?>
                            </div>
                            <div class="text-muted small">Meilleur score</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fs-4 fw-bold text-info">
                                <?= gmdate("i:s", $stats['temps_moyen']) ?>
                            </div>
                            <div class="text-muted small">Temps moyen</div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Top 10 -->
                <h4 class="mb-3">Top 10 des meilleurs scores</h4>
                
                <?php if (empty($top_scores)): ?>
                    <div class="alert alert-info">
                        Aucun résultat pour le moment. Soyez le premier à passer ce quiz!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="60">Rang</th>
                                    <th>Participant</th>
                                    <th>Score</th>
                                    <th>Pourcentage</th>
                                    <th>Temps</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_scores as $index => $result): ?>
                                    <?php 
                                    $percentage = ($result['score'] / $total_points) * 100;
                                    $rank = $index + 1;
                                    $medal = '';
                                    if ($rank === 1) $medal = '🥇';
                                    elseif ($rank === 2) $medal = '🥈';
                                    elseif ($rank === 3) $medal = '🥉';
                                    ?>
                                    <tr class="<?= $rank <= 3 ? 'table-warning' : '' ?>">
                                        <td class="fw-bold fs-5"><?= $medal ?> #<?= $rank ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($result['nom_participant']) ?></div>
                                            <?php if ($result['email']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($result['email']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6">
                                                <?= $result['score'] ?> / <?= $total_points ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar <?= $percentage >= 80 ? 'bg-success' : ($percentage >= 60 ? 'bg-warning' : 'bg-danger') ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $percentage ?>%"
                                                     aria-valuenow="<?= $percentage ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?= number_format($percentage, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($result['temps_ecoule']): ?>
                                                <span class="badge bg-info">
                                                    <?= gmdate("i:s", $result['temps_ecoule']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($result['date_passage'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="mt-4 d-flex gap-2 justify-content-center">
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    <a href="take_quiz.php?id=<?= $quiz_id ?>" class="btn btn-success">Passer le quiz</a>
                    <a href="statistics.php?id=<?= $quiz_id ?>" class="btn btn-info">Statistiques détaillées</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>