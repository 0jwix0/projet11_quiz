<?php
require_once 'config/database.php';
require_once 'includes/header.php';

$quiz_id = $_GET['id'] ?? 0;

// Récupérer les informations du quiz
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: index.php');
    exit;
}

// Récupérer les questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY " . 
                      ($quiz['melanger_questions'] ? "RAND()" : "ordre"));
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réponses pour chaque question
foreach ($questions as &$question) {
    $stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
    $stmt->execute([$question['id']]);
    $question['reponses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><?= htmlspecialchars($quiz['titre']) ?></h2>
            </div>
            <div class="card-body">
                <?php if ($quiz['temps_limite']): ?>
                    <div class="alert alert-warning" id="timer-container">
                        <strong>Temps restant:</strong> 
                        <span id="timer" class="fs-4"><?= $quiz['temps_limite'] ?>:00</span>
                    </div>
                <?php endif; ?>

                <form id="quiz-form" method="POST" action="submit_quiz.php">
                    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                    <input type="hidden" name="temps_ecoule" id="temps_ecoule" value="0">

                    <div class="mb-4">
                        <label class="form-label">Nom:</label>
                        <input type="text" name="nom_participant" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <hr>

                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-block mb-4 p-3 border rounded">
                            <h5>Question <?= $index + 1 ?> (<?= $question['points'] ?> point<?= $question['points'] > 1 ? 's' : '' ?>)</h5>
                            <p class="lead"><?= htmlspecialchars($question['question']) ?></p>

                            <?php if ($question['type'] === 'qcm'): ?>
                                <?php foreach ($question['reponses'] as $reponse): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="question_<?= $question['id'] ?>" 
                                               value="<?= $reponse['id'] ?>" 
                                               id="rep_<?= $reponse['id'] ?>">
                                        <label class="form-check-label" for="rep_<?= $reponse['id'] ?>">
                                            <?= htmlspecialchars($reponse['reponse']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($question['type'] === 'vrai_faux'): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="question_<?= $question['id'] ?>" 
                                           value="vrai" id="vrai_<?= $question['id'] ?>">
                                    <label class="form-check-label" for="vrai_<?= $question['id'] ?>">
                                        Vrai
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="question_<?= $question['id'] ?>" 
                                           value="faux" id="faux_<?= $question['id'] ?>">
                                    <label class="form-check-label" for="faux_<?= $question['id'] ?>">
                                        Faux
                                    </label>
                                </div>

                            <?php elseif ($question['type'] === 'reponse_courte'): ?>
                                <input type="text" 
                                       name="question_<?= $question['id'] ?>" 
                                       class="form-control" 
                                       placeholder="Votre réponse">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            Soumettre le quiz
                        </button>
                        <a href="index.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($quiz['temps_limite']): ?>
<script>
let timeLeft = <?= $quiz['temps_limite'] * 60 ?>; // en secondes
let startTime = Date.now();
const timerElement = document.getElementById('timer');
const form = document.getElementById('quiz-form');
const tempsEcouleInput = document.getElementById('temps_ecoule');

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    if (timeLeft <= 60) {
        document.getElementById('timer-container').classList.remove('alert-warning');
        document.getElementById('timer-container').classList.add('alert-danger');
    }
    
    if (timeLeft <= 0) {
        alert('Temps écoulé! Le quiz sera soumis automatiquement.');
        form.submit();
        return;
    }
    
    timeLeft--;
    setTimeout(updateTimer, 1000);
}

form.addEventListener('submit', function() {
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    tempsEcouleInput.value = elapsed;
});

// Sauvegarde automatique toutes les 30 secondes
setInterval(function() {
    const formData = new FormData(form);
    localStorage.setItem('quiz_save_' + <?= $quiz_id ?>, JSON.stringify(Object.fromEntries(formData)));
}, 30000);

// Restaurer les données sauvegardées
window.addEventListener('load', function() {
    const saved = localStorage.getItem('quiz_save_' + <?= $quiz_id ?>);
    if (saved && confirm('Voulez-vous restaurer votre progression sauvegardée?')) {
        const data = JSON.parse(saved);
        for (let key in data) {
            const input = form.elements[key];
            if (input) {
                if (input.type === 'radio') {
                    const radio = form.querySelector(`input[name="${key}"][value="${data[key]}"]`);
                    if (radio) radio.checked = true;
                } else {
                    input.value = data[key];
                }
            }
        }
    }
});

updateTimer();
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>