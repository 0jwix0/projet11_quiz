# 🎯 Plateforme de Quiz Interactive

## Description

Plateforme web complète de gestion de quiz permettant aux formateurs de créer des évaluations interactives et aux apprenants de tester leurs connaissances avec un feedback immédiat.

Le système offre une expérience d'apprentissage moderne avec timer en temps réel, correction automatique, génération de certificats et système de classement pour stimuler la compétition positive.

## Fonctionnalités principales

**Pour les créateurs de quiz :**
- Création de quiz avec interface drag-and-drop
- Support de 3 types de questions : QCM, Vrai/Faux, Réponse courte
- Configuration flexible : temps limite, nombre de tentatives, mélange des questions
- Points personnalisables par question
- Statistiques détaillées sur les performances des participants

**Pour les participants :**
- Timer avec compte à rebours visuel
- Sauvegarde automatique toutes les 30 secondes
- Restauration en cas de rafraîchissement de page
- Feedback immédiat après soumission
- Affichage détaillé des bonnes/mauvaises réponses
- Certificat PDF pour les scores ≥ 60%

**Système de gamification :**
- Classement Top 10 avec médailles (🥇🥈🥉)
- Score et temps affiché pour chaque participant
- Statistiques globales (moyenne, meilleur score, taux de réussite)

**Analyse et reporting :**
- Taux de réussite par question
- Identification des questions difficiles
- Recommandations d'amélioration
- Distribution des réponses pour les QCM

## Technologies utilisées

**Backend :** PHP 7.4+ avec PDO pour la sécurité  
**Base de données :** MySQL avec architecture relationnelle optimisée  
**Frontend :** Bootstrap 5.3 pour un design responsive moderne  
**JavaScript :** Vanilla JS pour timer, sauvegarde automatique et interactions  
**Sécurité :** Protection contre injections SQL et attaques XSS