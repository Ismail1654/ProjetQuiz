-- phpMyAdmin SQL AVEC MAMP
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `questions` (
  `question_id` int UNSIGNED NOT NULL,
  `quiz_id` int UNSIGNED NOT NULL,
  `texte_question` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `options_json` json NOT NULL,
  `reponse_correcte` tinyint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





INSERT INTO `questions` (`question_id`, `quiz_id`, `texte_question`, `options_json`, `reponse_correcte`) VALUES
(8, 7, 'Quel symbole est utilisé pour commencer une variable en PHP ?', '[\"$\", \"%\", \"#\", \"&\"]', 0),
(9, 7, 'Comment commence un script PHP ?', '[\"<php>\", \"<?php\", \"<script php>\", \"<?script>\"]', 1),
(10, 7, 'Quelle fonction PHP permet d’afficher du texte à l’écran ?', '[\"print()\", \"echo()\", \"display()\", \"show()\"]', 1),
(11, 7, 'Quel symbole est utilisé pour la concaténation de chaînes en PHP ?', '[\"+\", \".\", \"-\", \"&\"]', 1),
(12, 8, 'Quelle est la capitale de la France ?', '[\"Madrid\", \"Berlin\", \"Paris\", \"Rome\"]', 2),
(13, 8, 'Sur quel continent se trouve le Brésil ?', '[\"Afrique\", \"Amérique du Sud\", \"Asie\", \"Europe\"]', 1),
(14, 8, 'Quelle est la plus grande planète du système solaire ?', '[\"Mars\", \"Jupiter\", \"Saturn\", \"Terre\"]', 1),
(15, 8, 'Quel pays utilise le yen comme monnaie ?', '[\"Chine\", \"Corée du Sud\", \"Thailande\", \"Japon\"]', 3),
(16, 8, 'Quel est le plus grand océan du monde ?', '[\"Atlantique\", \"Indien\", \"Pacifique\", \"Arctique\"]', 2);





CREATE TABLE `quizzes` (
  `quiz_id` int UNSIGNED NOT NULL,
  `author_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






INSERT INTO `quizzes` (`quiz_id`, `author_id`, `title`, `description`, `created_at`, `is_active`) VALUES
(7, 7, 'Quiz sur PHP', 'Premier quiz.', '2025-12-02 14:15:19', 1),
(8, 20, 'Quiz Culture G', 'Surprise', '2025-12-02 14:39:08', 1);





CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','ecole','entreprise','utilisateur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'utilisateur',
  `firstname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role`, `firstname`, `lastname`, `created_at`, `is_active`) VALUES
(7, 'test', 'test@test.test', '$2y$10$AoFkcbXY.o9yJkF84MXU2.gcapd/cjIQO7pvmZr6T5ZEfg7Dv.u1C', 'entreprise', NULL, NULL, '2025-12-01 22:21:38', 1),
(11, 'user', 'user@orange.fr', '$2y$10$JNLTUdjDhPqoTur3DU6cZudoQ.QGOjre3Vj/jm9zFst78sGnG3T4e', 'utilisateur', NULL, NULL, '2025-12-02 00:30:58', 1),
(18, 'superadmin', 'admin@example.com', '$2y$12$kC3Gt.y0uxYQsYQUAzrEhOgNg4ODEnDThWePXdisi4hIbwfU7EYSW', 'admin', NULL, NULL, '2025-12-02 12:28:34', 1),
(19, 'admin', 'admin@amdin.com', '$2y$10$1KAQP1Iew0f19Yvyzr07kONm.dgNQqfe.uufVqhLeFREGTy6TN02K', 'admin', NULL, NULL, '2025-12-02 14:31:43', 1),
(20, 'poirier', 'julien.poirier@orange.fr', '$2y$10$4pndyr4bIRrInQk27yo/iuYwUbJJOALGuNstNn6P4oObUXNIONMZ6', 'ecole', NULL, NULL, '2025-12-02 14:38:02', 1),
(21, 'yous', 'yousismail.moi@orange.fr', '$2y$10$F.VWQBm/j/wKsPCYTzJ4iOKxyEEfr.Myc1AUz.E.Qy5Iblf13AXB2', 'utilisateur', 'Moi', '', '2025-12-02 14:48:27', 1);





CREATE TABLE `user_responses` (
  `response_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `quiz_id` int UNSIGNED NOT NULL,
  `score` int UNSIGNED NOT NULL,
  `max_score` int UNSIGNED NOT NULL,
  `answered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `user_responses` (`response_id`, `user_id`, `quiz_id`, `score`, `max_score`, `answered_at`) VALUES
(15, 11, 7, 4, 4, '2025-12-02 14:26:04'),
(17, 21, 8, 1, 5, '2025-12-02 16:10:51');











ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `fk_question_quiz` (`quiz_id`);


ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `fk_quiz_author` (`author_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `user_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD UNIQUE KEY `unique_user_quiz` (`user_id`,`quiz_id`),
  ADD KEY `fk_response_quiz` (`quiz_id`);


ALTER TABLE `questions`
  MODIFY `question_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;



ALTER TABLE `quizzes`
  MODIFY `quiz_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;


ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;



ALTER TABLE `user_responses`
  MODIFY `response_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;



ALTER TABLE `questions`
  ADD CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;


ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT;


ALTER TABLE `user_responses`
  ADD CONSTRAINT `fk_response_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

