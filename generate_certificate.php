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
    margin: 0;
}

/* s'assurer que le document a la hauteur de la page A4 en paysage */
html, body {
    height: 210mm;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Georgia', 'Times New Roman', serif;
    background: white;
    position: relative; /* nécessaire pour que .certificate en absolute soit positionné par rapport à la page */
}

/* centrer de façon fiable dans Dompdf */
.certificate {
    /* utilisation d'un positionnement absolu centré par transformation */
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);

    /* légèrement plus petit que la page pour laisser des marges visibles */
    width: 280mm;
    height: 190mm;

    display: block;
    text-align: center;
    background: white;
    box-sizing: border-box;
}

/* laisser le reste pareil (ajuste si besoin) */
.content-wrapper {
    max-width: 250mm;
    margin: 0 auto;
}

/* ... le reste de tes règles CSS inchangées ... */

        
        .header {
            margin-bottom: 5mm;
        }
        
        .certificate-title {
            font-size: 24pt;
            color: #2c3e50;
            margin: 3mm 0;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2pt;
            border-bottom: 2px solid #3498db;
            display: inline-block;
            padding-bottom: 2mm;
        }
        
        .subtitle {
            font-size: 10pt;
            color: #7f8c8d;
            margin: 3mm 0 2mm;
            font-style: italic;
        }
        
        .recipient {
            font-size: 20pt;
            color: #2c3e50;
            margin: 3mm 0;
            font-weight: bold;
            text-decoration: underline;
            text-decoration-color: #3498db;
            text-underline-offset: 3px;
        }
        
        .description {
            font-size: 10pt;
            color: #34495e;
            margin: 3mm 0;
            line-height: 1.3;
        }
        
        .quiz-name {
            font-size: 14pt;
            color: #3498db;
            font-weight: bold;
            margin: 3mm 0;
            font-style: italic;
        }
        
        .score {
            font-size: 16pt;
            color: #27ae60;
            font-weight: bold;
            margin: 3mm 0;
            padding: 2mm 6mm;
            background: #d5f4e6;
            border-radius: 5px;
            display: inline-block;
        }
        
        .time-info {
            font-size: 9pt;
            color: #7f8c8d;
            margin: 2mm 0 3mm;
        }
        
        .footer {
            margin-top: 6mm;
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        
        .signature {
            display: table-cell;
            text-align: center;
            vertical-align: top;
            width: 50%;
            padding: 0 5mm;
        }
        
        .signature-line {
            width: 100pt;
            border-top: 2px solid #2c3e50;
            margin: 0 auto 2mm;
        }
        
        .signature p {
            margin: 1mm 0;
        }
        
        .signature strong {
            font-size: 10pt;
            color: #2c3e50;
        }
        
        .signature-label {
            font-size: 8pt;
            color: #95a5a6;
            font-style: italic;
        }
        
        .date {
            font-size: 9pt;
            color: #7f8c8d;
            margin-top: 5mm;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="content-wrapper">
            <div class="header">
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
            <p class="time-info">
                Temps de réalisation : <?= gmdate("i:s", $result['temps_ecoule']) ?> minutes
            </p>
            <?php endif; ?>
            
            <div class="footer">
                <div class="signature">
                    <div class="signature-line"></div>
                    <p><strong>Plateforme Quiz</strong></p>
                    <p class="signature-label">Directeur</p>
                </div>
                <div class="signature">
                    <div class="signature-line"></div>
                    <p><strong>ID: #<?= str_pad($resultat_id, 6, '0', STR_PAD_LEFT) ?></strong></p>
                    <p class="signature-label">Numéro de certificat</p>
                </div>
            </div>
            
            <p class="date">
                Délivré le <?= date('d F Y', strtotime($result['date_passage'])) ?>
            </p>
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean(); // Récupérer le contenu HTML capturé

// Initialiser Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Georgia');
$options->set('isHtml5ParserEnabled', true);
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