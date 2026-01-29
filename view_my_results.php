<?php
require_once 'config/database.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nom === '') {
    die('<div class="alert alert-danger">Le nom est obligatoire.</div>');
}

// Construction de la requête (tous les résultats, pas seulement les réussis)
$sql = "SELECT r.id AS resultat_id, r.score, r.temps_ecoule, r.date_passage, 
               q.titre, q.id AS quiz_id
        FROM resultats r
        JOIN quiz q ON r.quiz_id = q.id
        WHERE r.nom_participant = ?";
$params = [$nom];

if ($email !== '') {
    $sql .= " AND r.email = ?";
    $params[] = $email;
}

$sql .= " ORDER BY r.date_passage DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h2 class="mb-0">📊 Mes résultats<?= $email ? ' — ' . htmlspecialchars($email) : '' ?></h2>
                <p class="mb-0">Recherche pour : <strong><?= htmlspecialchars($nom) ?></strong></p>
            </div>
            <div class="card-body">
                <?php if (empty($all_results)): ?>
                    <div class="alert alert-info text-center">
                        <strong>Aucun résultat trouvé.</strong><br>
                        Aucun quiz n'a été passé avec ces informations.
                    </div>
                <?php else: ?>
                    <!-- Barre de recherche dans les résultats -->
                    <div class="mb-4">
                        <input type="text" id="searchInput" class="form-control" placeholder="Rechercher par titre de quiz...">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="resultsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Quiz</th>
                                    <th>Date</th>
                                    <th>Score</th>
                                    <th>Temps</th>
                                    <th>Résultat</th>
                                    <th>Certificat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_results as $res): ?>
                                    <?php
                                    // Calcul du total de points
                                    $stmt_total = $pdo->prepare("SELECT SUM(points) AS total FROM questions WHERE quiz_id = ?");
                                    $stmt_total->execute([$res['quiz_id']]);
                                    $total_points = $stmt_total->fetchColumn() ?: 0;

                                    $percentage = $total_points > 0 ? ($res['score'] / $total_points) * 100 : 0;
                                    $passed = $percentage >= 60;
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($res['titre']) ?></strong></td>
                                        <td><?= date('d/m/Y à H:i', strtotime($res['date_passage'])) ?></td>
                                        <td>
                                            <span class="badge <?= $passed ? 'bg-success' : 'bg-danger' ?> fs-6">
                                                <?= $res['score'] ?> / <?= $total_points ?> (<?= number_format($percentage, 1) ?> %)
                                            </span>
                                        </td>
                                        <td>
                                            <?= $res['temps_ecoule'] ? gmdate("i:s", $res['temps_ecoule']) : '-' ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $passed ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $passed ? 'Réussi' : 'Échec' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($passed): ?>
                                                <a href="generate_certificate.php?id=<?= $res['resultat_id'] ?>" 
                                                   target="_blank" class="btn btn-success btn-sm">
                                                    📜 Télécharger
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Score insuffisant</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <a href="index.php" class="btn btn-secondary">← Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script de recherche client-side dans le tableau -->
<script>
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#resultsTable tbody tr');
    
    rows.forEach(row => {
        const title = row.cells[0].textContent.toLowerCase();
        if (title.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>