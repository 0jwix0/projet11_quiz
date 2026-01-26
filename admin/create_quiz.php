<?php
require_once '../config/database.php';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insérer le quiz
        $stmt = $pdo->prepare("INSERT INTO quiz (titre, description, temps_limite, tentatives_max, melanger_questions) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['titre'],
            $_POST['description'],
            $_POST['temps_limite'] ?: null,
            $_POST['tentatives_max'],
            isset($_POST['melanger_questions']) ? 1 : 0
        ]);
        
        $quiz_id = $pdo->lastInsertId();
        
        // Insérer les questions
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
        header('Location: ../index.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur: " . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Créer un nouveau quiz</h2>
            </div>
            <div class="card-body">
                
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
                                <input type="text" name="titre" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Temps limite (minutes)</label>
                                    <input type="number" name="temps_limite" class="form-control" min="1" placeholder="Illimité">
                                    <small class="text-muted">Laissez vide pour aucune limite</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tentatives maximum</label>
                                    <input type="number" name="tentatives_max" class="form-control" value="1" min="1" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="melanger_questions" class="form-check-input" id="melanger" checked>
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
                            <!-- Les questions seront ajoutées ici dynamiquement -->
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Créer le quiz</button>
                        <a href="../index.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let questionCount = 0;

function addQuestion() {
    const container = document.getElementById('questions-container');
    const questionIndex = questionCount++;
    
    const questionHTML = `
        <div class="question-item border rounded p-3 mb-3" id="question-${questionIndex}">
            <div class="d-flex justify-content-between mb-3">
                <h5>Question ${questionIndex + 1}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(${questionIndex})">
                    Supprimer
                </button>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Question *</label>
                <textarea name="questions[${questionIndex}][question]" class="form-control" rows="2" required></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Type de question</label>
                    <select name="questions[${questionIndex}][type]" class="form-select" onchange="changeQuestionType(${questionIndex}, this.value)">
                        <option value="qcm">QCM (Choix multiples)</option>
                        <option value="vrai_faux">Vrai/Faux</option>
                        <option value="reponse_courte">Réponse courte</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Points</label>
                    <input type="number" name="questions[${questionIndex}][points]" class="form-control" value="1" min="1" required>
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
    
    // Ajouter 4 réponses par défaut pour QCM
    for (let i = 0; i < 4; i++) {
        addAnswer(questionIndex);
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

function addAnswer(questionIndex) {
    const container = document.getElementById(`answers-container-${questionIndex}`);
    const answerIndex = container.children.length;
    
    const answerHTML = `
        <div class="input-group mb-2">
            <div class="input-group-text">
                <input type="checkbox" name="questions[${questionIndex}][reponses][${answerIndex}][correcte]" 
                       class="form-check-input mt-0">
            </div>
            <input type="text" name="questions[${questionIndex}][reponses][${answerIndex}][reponse]" 
                   class="form-control" placeholder="Réponse ${answerIndex + 1}" required>
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

// Ajouter une question par défaut au chargement
window.addEventListener('load', function() {
    addQuestion();
});
</script>

<?php require_once '../includes/footer.php'; ?>