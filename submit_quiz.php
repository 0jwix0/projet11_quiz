<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$quiz_id = $_POST['quiz_id'];
$nom_participant = $_POST['nom_participant'];
$email = $_POST['email'] ?? null;
$temps_ecoule = $_POST['temps_ecoule'] ?? 0;

// Récupérer toutes les questions du quiz
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$score = 0;
$total_points = 0;
$details = [];

foreach ($questions as $question) {
    $total_points += $question['points'];
    $question_key = 'question_' . $question['id'];
    $user_answer = $_POST[$question_key] ?? null;
    
    // Récupérer les bonnes réponses
    $stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
    $stmt->execute([$question['id']]);
    $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $is_correct = false;
    $correct_answer = '';
    
    if ($question['type'] === 'qcm') {
        foreach ($reponses as $reponse) {
            if ($reponse['est_correcte']) {
                $correct_answer = $reponse['reponse'];
                if ($user_answer == $reponse['id']) {
                    $is_correct = true;
                    $score += $question['points'];
                }
            }
        }
    } elseif ($question['type'] === 'vrai_faux') {
        foreach ($reponses as $reponse) {
            if ($reponse['est_correcte']) {
                $correct_answer = $reponse['reponse'];
                if (strtolower($user_answer) === strtolower($reponse['reponse'])) {
                    $is_correct = true;
                    $score += $question['points'];
                }
            }
        }
    } elseif ($question['type'] === 'reponse_courte') {
        foreach ($reponses as $reponse) {
            if ($reponse['est_correcte']) {
                $correct_answer = $reponse['reponse'];
                if (strtolower(trim($user_answer)) === strtolower(trim($reponse['reponse']))) {
                    $is_correct = true;
                    $score += $question['points'];
                }
            }
        }
    }
    
    $details[] = [
        'question' => $question['question'],
        'user_answer' => $user_answer,
        'correct_answer' => $correct_answer,
        'is_correct' => $is_correct,
        'points' => $question['points']
    ];
}

// Enregistrer le résultat
$stmt = $pdo->prepare("INSERT INTO resultats (quiz_id, nom_participant, email, score, temps_ecoule) 
                       VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$quiz_id, $nom_participant, $email, $score, $temps_ecoule]);
$resultat_id = $pdo->lastInsertId();

// Stocker les détails en session pour l'affichage
$_SESSION['quiz_result'] = [
    'score' => $score,
    'total_points' => $total_points,
    'details' => $details,
    'nom_participant' => $nom_participant,
    'temps_ecoule' => $temps_ecoule,
    'resultat_id' => $resultat_id,
    'quiz_id' => $quiz_id
];

// Supprimer la sauvegarde automatique
echo "<script>localStorage.removeItem('quiz_save_" . $quiz_id . "');</script>";

header('Location: result.php');
exit;