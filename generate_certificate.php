<?php
require_once 'config/database.php';
require_once 'vendor/autoload.php'; // Assurez-vous d'avoir installé Dompdf via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

$resultat_id = $_GET['id'] ?? 0;

// Récupérer le résultat
$stmt = $pdo->prepare("SELECT r.*, q.titre as quiz_titre
FROM resultats r
JOIN quiz q ON r.quiz_id = q.id
WHERE r.id = ?");
$stmt->execute([$resultat_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$result) {
    die("Résultat non trouvé");
}

// Récupérer le total de points
$stmt = $pdo->prepare("SELECT SUM(points) as total_points FROM questions WHERE quiz_id = ?");
$stmt->execute([$result['quiz_id']]);
$total_points = $stmt->fetchColumn();
$percentage = ($result['score'] / $total_points) * 100;

// Vérifier si le score est suffisant (60%)
if ($percentage < 60) {
    die("Score insuffisant pour obtenir un certificat (minimum 60% requis)");
}

// Générer le contenu HTML du certificat
ob_start(); // Commencer la capture de sortie
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat de réussite</title>
<style>
    @page {
        size: A4 landscape;
        margin: 20mm;
    }
    body {
        font-family: 'Helvetica', sans-serif;
        margin: 0;
        padding: 0;
        background: white;
    }
    .certificate {
        padding: 40mm 30mm;
        width: 100%;
        height: 100%;
        text-align: center;
        position: relative;
        border: 1px solid #ccc;
    }
    .certificate-title {
        font-size: 32pt;
        color: #333;
        margin: 20pt 0;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2pt;
    }
    .subtitle {
        font-size: 14pt;
        color: #666;
        margin-bottom: 15pt;
    }
    .recipient {
        font-size: 24pt;
        color: #000;
        margin: 20pt 0;
        font-weight: bold;
    }
    .description {
        font-size: 12pt;
        color: #555;
        margin: 10pt 0;
        line-height: 1.4;
    }
    .quiz-name {
        font-size: 18pt;
        color: #007bff;
        font-weight: bold;
        margin: 15pt 0;
    }
    .score {
        font-size: 20pt;
        color: #28a745;
        font-weight: bold;
        margin: 15pt 0;
    }
    .footer {
        margin-top: 40pt;
        display: flex;
        justify-content: space-between;
        font-size: 10pt;
        color: #666;
    }
    .signature {
        text-align: center;
        width: 40%;
    }
    .signature-line {
        width: 150pt;
        border-top: 1px solid #333;
        margin: 10pt auto;
    }
    .date {
        font-size: 10pt;
        color: #666;
        margin-top: 20pt;
    }
    /* Remove seal and gradients for professionalism */
</style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="seal">
                <div>
                    CERTIFIÉ<br>
                    <?= date('Y', strtotime($result['date_passage'])) ?>
                </div>
            </div>
        </div>
        <h1 class="certificate-title">Certificat de Réussite</h1>
        <p class="subtitle">Ce certificat est décerné à</p>
        <h2 class="recipient"><?= htmlspecialchars($result['nom_participant']) ?></h2>
        <p class="description">
            Pour avoir complété avec succès le quiz
        </p>
        <div class="quiz-name">
            « <?= htmlspecialchars($result['quiz_titre']) ?? 'Quiz' ?> »
        </div>
        <p class="description">
            avec un score exceptionnel de
        </p>
        <div class="score">
            <?= $result['score'] ?> / <?= $total_points ?>
            (<?= number_format($percentage, 1) ?>%)
        </div>
        <?php if ($result['temps_ecoule']): ?>
        <p class="description">
            en un temps de <?= gmdate("i:s", $result['temps_ecoule']) ?> minutes
        </p>
        <?php endif; ?>
        <div class="footer">
            <div class="signature">
                <div class="signature-line"></div>
                <p><strong>Plateforme Quiz</strong></p>
                <p style="font-size: 12px; color: #999;">Directeur</p>
            </div>
            <div class="signature">
                <div class="signature-line"></div>
                <p><strong>ID: #<?= str_pad($resultat_id, 6, '0', STR_PAD_LEFT) ?></strong></p>
                <p style="font-size: 12px; color: #999;">Numéro de certificat</p>
            </div>
        </div>
        <p class="date">
            Délivré le <?= date('d F Y', strtotime($result['date_passage'])) ?>
        </p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean(); // Récupérer le contenu HTML capturé

// Initialiser Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true); // Pour charger des ressources distantes si nécessaire
$options->set('defaultFont', 'Georgia');
$dompdf = new Dompdf($options);

// Charger le HTML
$dompdf->loadHtml($html);

// Configurer le format de page
$dompdf->setPaper('A4', 'landscape');

// Rendre le PDF
$dompdf->render();

// Définir les en-têtes pour le téléchargement
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificat_' . $resultat_id . '.pdf"');

// Sortir le PDF
$dompdf->stream('certificat_' . $resultat_id . '.pdf', ["Attachment" => true]);
exit;