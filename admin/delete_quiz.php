<?php
require_once '../config/database.php';

$quiz_id = $_GET['id'] ?? 0;

if (!$quiz_id) {
    header('Location: ../index.php?error=invalid_id');
    exit;
}

// Vérifier que le quiz existe
$stmt = $pdo->prepare("SELECT titre FROM quiz WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: ../index.php?error=quiz_not_found');
    exit;
}

try {
    // Supprimer le quiz (CASCADE supprimera automatiquement questions, réponses et résultats)
    $stmt = $pdo->prepare("DELETE FROM quiz WHERE id = ?");
    $stmt->execute([$quiz_id]);
    
    // Redirection avec message de succès
    header('Location: ../index.php?deleted=1&quiz_name=' . urlencode($quiz['titre']));
    exit;
    
} catch (Exception $e) {
    // En cas d'erreur
    header('Location: ../index.php?error=delete_failed&message=' . urlencode($e->getMessage()));
    exit;
}
?>