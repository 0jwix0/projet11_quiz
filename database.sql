CREATE DATABASE plateforme_quiz;
USE plateforme_quiz;

CREATE TABLE quiz (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    temps_limite INT,
    tentatives_max INT DEFAULT 1,
    melanger_questions BOOLEAN DEFAULT TRUE
);

CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT,
    question TEXT NOT NULL,
    type ENUM('qcm', 'vrai_faux', 'reponse_courte') DEFAULT 'qcm',
    points INT DEFAULT 1,
    ordre INT DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quiz(id) ON DELETE CASCADE
);

CREATE TABLE reponses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT,
    reponse TEXT NOT NULL,
    est_correcte BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE resultats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT,
    nom_participant VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    score INT NOT NULL,
    temps_ecoule INT,
    date_passage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quiz(id) ON DELETE CASCADE
);
