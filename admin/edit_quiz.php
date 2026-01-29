<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$quiz_id = $_GET['id'] ?? 0;

// Récupérer le quiz
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: ../index.php');
    exit;
}

// Récupérer les questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réponses pour chaque question
foreach ($questions as &$question) {
    $stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
    $stmt->execute([$question['id']]);
    $question['reponses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement de la mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Mettre à jour le quiz
        $stmt = $pdo->prepare("UPDATE quiz SET titre = ?, description = ?, temps_limite = ?, 
                               tentatives_max = ?, melanger_questions = ? WHERE id = ?");
        $stmt->execute([
            $_POST['titre'],
            $_POST['description'],
            $_POST['temps_limite'] ?: null,
            $_POST['tentatives_max'],
            isset($_POST['melanger_questions']) ? 1 : 0,
            $quiz_id
        ]);
        
        // Supprimer les anciennes questions
        $stmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Insérer les nouvelles questions
        if (isset($_POST['questions'])) {
            foreach ($_POST['questions'] as $index => $q) {
                if (empty($q['question'])) continue;
                
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question, type, points, ordre) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $quiz_id,
                    $q['question'],
                    $q['type'],
                    $q['points'],
                    $index
                ]);
                
                $question_id = $pdo->lastInsertId();
                
                // Insérer les réponses
                if (isset($q['reponses'])) {
                    foreach ($q['reponses'] as $r) {
                        if (empty($r['reponse'])) continue;
                        
                        $stmt = $pdo->prepare("INSERT INTO reponses (question_id, reponse, est_correcte) 
                                               VALUES (?, ?, ?)");
                        $stmt->execute([
                            $question_id,
                            $r['reponse'],
                            isset($r['correcte']) ? 1 : 0
                        ]);
                    }
                }
            }
        }
        
        $pdo->commit();
        $success = "Quiz mis à jour avec succès !";
        
        // Recharger les données
        header('Location: edit_quiz.php?id=' . $quiz_id . '&success=1');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur: " . $e->getMessage();
    }
}

$success_msg = isset($_GET['success']) ? "Quiz mis à jour avec succès !" : "";
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Éditer le quiz</h2>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
            <div class="card-body">
                
                <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST" id="quiz-form">
                    <!-- Informations du quiz -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h4>Informations générales</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Titre du quiz *</label>
                                <input type="text" name="titre" class="form-control" 
                                       value="<?= htmlspecialchars($quiz['titre']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($quiz['description']) ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Temps limite (minutes)</label>
                                    <input type="number" name="temps_limite" class="form-control" min="1" 
                                           value="<?= $quiz['temps_limite'] ?>" placeholder="Illimité">
                                    <small class="text-muted">Laissez vide pour aucune limite</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tentatives maximum</label>
                                    <input type="number" name="tentatives_max" class="form-control" 
                                           value="<?= $quiz['tentatives_max'] ?>" min="1" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="melanger_questions" class="form-check-input" 
                                               id="melanger" <?= $quiz['melanger_questions'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="melanger">
                                            Mélanger les questions
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Questions -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Questions</h4>
                            <button type="button" class="btn btn-success btn-sm" onclick="addQuestion()">
                                + Ajouter une question
                            </button>
                        </div>
                        <div class="card-body" id="questions-container">
                            <!-- Les questions existantes seront chargées ici -->
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-save"></i> Mettre à jour le quiz
                        </button>
                        <a href="../index.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="card shadow mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Zone dangereuse</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    La suppression d'un quiz est irréversible. Toutes les questions, réponses et résultats seront perdus.
                </p>
                <button type="button" class="btn btn-danger" onclick="deleteQuiz()">
                    <i class="bi bi-trash"></i> Supprimer ce quiz
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let questionCount = 0;
const existingQuestions = <?= json_encode($questions) ?>;

function addQuestion(questionData = null) {
    const container = document.getElementById('questions-container');
    const questionIndex = questionCount++;
    
    const question = questionData || {
        question: '',
        type: 'qcm',
        points: 1,
        reponses: []
    };
    
    const questionHTML = `
        <div class="question-item border rounded p-3 mb-3" id="question-${questionIndex}">
            <div class="d-flex justify-content-between mb-3">
                <h5>Question ${questionIndex + 1}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(${questionIndex})">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Question *</label>
                <textarea name="questions[${questionIndex}][question]" class="form-control" rows="2" required>${question.question}</textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Type de question</label>
                    <select name="questions[${questionIndex}][type]" class="form-select" onchange="changeQuestionType(${questionIndex}, this.value)">
                        <option value="qcm" ${question.type === 'qcm' ? 'selected' : ''}>QCM (Choix multiples)</option>
                        <option value="vrai_faux" ${question.type === 'vrai_faux' ? 'selected' : ''}>Vrai/Faux</option>
                        <option value="reponse_courte" ${question.type === 'reponse_courte' ? 'selected' : ''}>Réponse courte</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Points</label>
                    <input type="number" name="questions[${questionIndex}][points]" class="form-control" value="${question.points}" min="1" required>
                </div>
            </div>
            
            <div id="answers-container-${questionIndex}">
                <!-- Les réponses seront ajoutées ici -->
            </div>
            
            <button type="button" class="btn btn-secondary btn-sm" onclick="addAnswer(${questionIndex})">
                + Ajouter une réponse
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', questionHTML);
    
    // Charger les réponses existantes
    if (questionData && questionData.reponses) {
        questionData.reponses.forEach((reponse, idx) => {
            addAnswer(questionIndex, reponse);
        });
    } else if (question.type === 'qcm') {
        for (let i = 0; i < 4; i++) {
            addAnswer(questionIndex);
        }
    }
}

function removeQuestion(index) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette question?')) {
        document.getElementById(`question-${index}`).remove();
    }
}

function changeQuestionType(questionIndex, type) {
    const container = document.getElementById(`answers-container-${questionIndex}`);
    container.innerHTML = '';
    
    if (type === 'qcm') {
        for (let i = 0; i < 4; i++) {
            addAnswer(questionIndex);
        }
    } else if (type === 'vrai_faux') {
        addVraiFauxAnswers(questionIndex);
    } else {
        addShortAnswer(questionIndex);
    }
}

function addAnswer(questionIndex, answerData = null) {
    const container = document.getElementById(`answers-container-${questionIndex}`);
    const answerIndex = container.children.length;
    
    const answer = answerData || { reponse: '', est_correcte: false };
    
    const answerHTML = `
        <div class="input-group mb-2">
            <div class="input-group-text">
                <input type="checkbox" name="questions[${questionIndex}][reponses][${answerIndex}][correcte]" 
                       class="form-check-input mt-0" ${answer.est_correcte ? 'checked' : ''}>
            </div>
            <input type="text" name="questions[${questionIndex}][reponses][${answerIndex}][reponse]" 
                   class="form-control" placeholder="Réponse ${answerIndex + 1}" 
                   value="${answer.reponse}" required>
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">×</button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', answerHTML);
}

function addVraiFauxAnswers(questionIndex) {
    const container = document.getElementById(`answers-container-${questionIndex}`);
    container.innerHTML = `
        <div class="mb-2">
            <div class="form-check">
                <input type="radio" name="questions[${questionIndex}][vf_correct]" value="vrai" class="form-check-input" id="vrai-${questionIndex}" checked>
                <label class="form-check-label" for="vrai-${questionIndex}">Vrai (réponse correcte)</label>
            </div>
            <div class="form-check">
                <input type="radio" name="questions[${questionIndex}][vf_correct]" value="faux" class="form-check-input" id="faux-${questionIndex}">
                <label class="form-check-label" for="faux-${questionIndex}">Faux (réponse correcte)</label>
            </div>
        </div>
        <input type="hidden" name="questions[${questionIndex}][reponses][0][reponse]" value="vrai">
        <input type="hidden" name="questions[${questionIndex}][reponses][1][reponse]" value="faux">
    `;
}

function addShortAnswer(questionIndex) {
    const container = document.getElementById(`answers-container-${questionIndex}`);
    container.innerHTML = `
        <div class="mb-2">
            <label class="form-label">Réponse correcte</label>
            <input type="text" name="questions[${questionIndex}][reponses][0][reponse]" class="form-control" required>
            <input type="hidden" name="questions[${questionIndex}][reponses][0][correcte]" value="1">
            <small class="text-muted">La réponse doit correspondre exactement (insensible à la casse)</small>
        </div>
    `;
}

function deleteQuiz() {
    if (confirm('ATTENTION: Êtes-vous absolument sûr de vouloir supprimer ce quiz?\n\nCette action est IRRÉVERSIBLE et supprimera:\n- Toutes les questions\n- Toutes les réponses\n- Tous les résultats\n\nTapez "SUPPRIMER" pour confirmer')) {
        const confirmation = prompt('Tapez "SUPPRIMER" en majuscules pour confirmer:');
        if (confirmation === 'SUPPRIMER') {
            window.location.href = 'delete_quiz.php?id=<?= $quiz_id ?>';
        }
    }
}

// Charger les questions existantes au chargement de la page
window.addEventListener('load', function() {
    existingQuestions.forEach(question => {
        addQuestion(question);
    });
    
    // Si aucune question, en ajouter une vide
    if (existingQuestions.length === 0) {
        addQuestion();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>