-- ==========================================
-- SQL Script to Populate Database for AI Testing
-- Password for all students: eyaisbichA2701
-- Psychologist ID: 3
-- ==========================================

-- Insert Students (verified accounts)
INSERT INTO `user` (`first_name`, `last_name`, `email`, `role`, `password`, `is_verified`, `verification_token`, `created_at`, `banned_until`) VALUES
('Sophie', 'Martin', 'sophie.martin@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2025-11-15 10:00:00', NULL),
('Lucas', 'Dubois', 'lucas.dubois@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2025-11-20 14:30:00', NULL),
('Emma', 'Bernard', 'emma.bernard@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2025-12-01 09:15:00', NULL),
('Noah', 'Petit', 'noah.petit@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2025-12-05 16:20:00', NULL),
('Chloé', 'Roux', 'chloe.roux@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2025-12-10 11:00:00', NULL),
('Gabriel', 'Moreau', 'gabriel.moreau@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2025-12-15 13:45:00', NULL),
('Léa', 'Simon', 'lea.simon@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2026-01-05 10:30:00', NULL),
('Hugo', 'Laurent', 'hugo.laurent@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2026-01-10 15:00:00', NULL),
('Alice', 'Michel', 'alice.michel@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2026-01-20 09:00:00', NULL),
('Tom', 'Lefevre', 'tom.lefevre@student.com', 'etudiant', '$2y$10$lDKgle4xvdnRkF5.L7j4/eUqdgQ7CtnNnPhAI84tbrcj6eYMDiQoC', 1, NULL, '2026-01-25 14:00:00', NULL);

-- Note: After inserting, you need to get the auto-generated IDs for students
-- Let's assume students get IDs 5-14 (after existing users 1-4)
-- If different, adjust the INSERT statements below accordingly

-- ==========================================
-- Insert Patient Files with Clinical Data
-- ==========================================

-- Patient File for Sophie Martin (ID: 5)
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`, `allergies`, `contact_urgence_nom`, `contact_urgence_tel`, 
`antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, 
`niveau_risque`, `created_at`, `updated_at`) VALUES
(5, 
'Sertraline 50mg daily', 
'None', 
'Marie Martin', 
'+33612345678',
'Diagnosed with generalized anxiety disorder at age 19. Experienced panic attacks during exam periods. Family history of anxiety disorders. Has tried cognitive behavioral therapy with good results.', 
'Mother has depression, grandfather had anxiety disorder',
'Recurring anxiety attacks affecting academic performance and social life',
'Develop better coping mechanisms for stress, reduce anxiety triggers, improve sleep quality',
'Patient shows good insight into their condition. Cooperative and motivated to improve. Recent sessions focused on breathing techniques and thought restructuring. Progress noted in managing minor stressors.',
'Medium',
'2025-12-01 10:00:00',
'2026-02-20 15:30:00');

-- Patient File for Lucas Dubois (ID: 6)
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`, `allergies`, `contact_urgence_nom`, `contact_urgence_tel`, 
`antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, 
`niveau_risque`, `created_at`, `updated_at`) VALUES
(6, 
'None', 
'Pollen allergies', 
'Jean Dubois', 
'+33698765432',
'Experiencing adjustment difficulties after moving cities for university. No prior mental health treatment. Reports feeling isolated and homesick.',
'No known family history of mental health issues',
'Difficulty adapting to university life, feeling isolated from family and friends',
'Build new social connections, develop independence, manage homesickness',
'Patient is articulate and self-aware. Shows willingness to engage in therapy. Working on building campus connections and establishing routines. Mood improving gradually.',
'Low',
'2025-12-15 14:00:00',
'2026-02-18 11:00:00');

-- Patient File for Emma Bernard (ID: 7)
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`, `allergies`, `contact_urgence_nom`, `contact_urgence_tel`, 
`antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, 
`niveau_risque`, `created_at`, `updated_at`) VALUES
(7, 
'Escitalopram 10mg, Melatonin supplements', 
'None', 
'Claire Bernard', 
'+33645678901',
'History of depression since age 17. Previous hospitalization for severe depressive episode. Currently stable on medication. Sleep disturbances persist.',
'Sister has bipolar disorder, father treated for depression',
'Managing persistent low mood and academic stress, improving sleep patterns',
'Maintain medication compliance, improve sleep hygiene, prevent relapse',
'Patient demonstrates good treatment adherence. Recent focus on sleep scheduling and relaxation techniques. Mood relatively stable but vigilant monitoring required. Regular check-ins recommended.',
'Medium',
'2025-11-25 09:30:00',
'2026-02-22 16:00:00');

-- Patient File for Noah Petit (ID: 8)
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`, `allergies`, `contact_urgence_nom`, `contact_urgence_tel`, 
`antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, 
`niveau_risque`, `created_at`, `updated_at`) VALUES
(8, 
'None', 
'Penicillin', 
'Sophie Petit', 
'+33687654321',
'Perfectionistic tendencies leading to burnout. High academic achiever experiencing impostor syndrome. No prior therapy.',
'No significant family history',
'Overwhelming stress from self-imposed high standards, fear of failure',
'Reduce perfectionism, develop healthier work-life balance, address impostor syndrome',
'Intelligent and driven student. Struggles with setting realistic expectations. Therapy focusing on cognitive distortions and self-compassion. Making gradual progress in accepting imperfection.',
'Low',
'2026-01-10 10:00:00',
'2026-02-23 14:30:00');

-- Patient File for Léa Simon (ID: 9)
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`, `allergies`, `contact_urgence_nom`, `contact_urgence_tel`, 
`antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, 
`niveau_risque`, `created_at`, `updated_at`) VALUES
(9, 
'Propranolol for performance anxiety', 
'Latex', 
'Marc Simon', 
'+33623456789',
'Social anxiety disorder. Difficulty with public speaking and group interactions. Started medication 6 months ago with improvement.',
'Mother has social anxiety',
'Severe anxiety in social and academic presentation situations',
'Increase comfort in social situations, improve public speaking skills, build confidence',
'Patient shows significant progress. Successfully completed class presentation with manageable anxiety. Continuing exposure therapy. Medication helping with physical symptoms.',
'Low',
'2026-01-15 11:30:00',
'2026-02-24 10:00:00');

-- ==========================================
-- Insert Appointments (Past 3 Months)
-- Creating patterns: Tuesdays 2pm, Thursdays 10am, Fridays 3pm are common
-- ==========================================

-- December 2025 Appointments
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
-- Week 1
('2025-12-03 10:00:00', 'Cabinet', 'Initial consultation', 'accepted', 5, 3, 1),
('2025-12-05 14:00:00', 'Visio', 'Follow-up session', 'accepted', 6, 3, 2),
-- Week 2
('2025-12-10 14:00:00', 'Cabinet', 'CBT session', 'accepted', 5, 3, 1),
('2025-12-12 10:00:00', 'Visio', 'Adjustment therapy', 'accepted', 6, 3, 2),
('2025-12-13 15:00:00', 'Cabinet', 'Initial assessment', 'accepted', 7, 3, 3),
-- Week 3
('2025-12-17 14:00:00', 'Visio', 'Anxiety management', 'accepted', 5, 3, 1),
('2025-12-19 10:00:00', 'Cabinet', 'Social skills work', 'accepted', 6, 3, 2),
('2025-12-20 15:00:00', 'Visio', 'Medication review', 'accepted', 7, 3, 3),
-- Week 4 (Holiday pause)
('2025-12-24 10:00:00', 'Cabinet', 'Pre-holiday session', 'accepted', 5, 3, 1);

-- January 2026 Appointments
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
-- Week 1
('2026-01-07 14:00:00', 'Cabinet', 'New year check-in', 'accepted', 5, 3, 1),
('2026-01-09 10:00:00', 'Visio', 'Goal setting', 'accepted', 7, 3, 3),
('2026-01-10 15:00:00', 'Cabinet', 'Initial consultation', 'accepted', 8, 3, 4),
-- Week 2
('2026-01-14 14:00:00', 'Visio', 'Stress management', 'accepted', 5, 3, 1),
('2026-01-16 10:00:00', 'Cabinet', 'Therapy session', 'accepted', 6, 3, 2),
('2026-01-17 15:00:00', 'Visio', 'Sleep hygiene discussion', 'accepted', 7, 3, 3),
('2026-01-17 11:00:00', 'Cabinet', 'Perfectionism work', 'accepted', 8, 3, 4),
-- Week 3
('2026-01-21 14:00:00', 'Cabinet', 'CBT techniques', 'accepted', 5, 3, 1),
('2026-01-23 10:00:00', 'Visio', 'Progress review', 'accepted', 6, 3, 2),
('2026-01-24 15:00:00', 'Cabinet', 'Medication adjustment', 'accepted', 7, 3, 3),
('2026-01-24 13:00:00', 'Visio', 'Cognitive reframing', 'accepted', 8, 3, 4),
-- Week 4
('2026-01-28 14:00:00', 'Visio', 'Anxiety tools practice', 'accepted', 5, 3, 1),
('2026-01-30 10:00:00', 'Cabinet', 'Social connections', 'accepted', 6, 3, 2),
('2026-01-31 15:00:00', 'Visio', 'Monthly review', 'accepted', 7, 3, 3);

-- February 2026 Appointments (includes recent and upcoming)
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
-- Week 1
('2026-02-04 14:00:00', 'Cabinet', 'Routine session', 'accepted', 5, 3, 1),
('2026-02-06 10:00:00', 'Visio', 'Therapy session', 'accepted', 6, 3, 2),
('2026-02-07 15:00:00', 'Cabinet', 'Mood monitoring', 'accepted', 7, 3, 3),
('2026-02-07 11:00:00', 'Visio', 'Work-life balance', 'accepted', 8, 3, 4),
-- Week 2  
('2026-02-11 14:00:00', 'Visio', 'Coping strategies', 'accepted', 5, 3, 1),
('2026-02-13 10:00:00', 'Cabinet', 'Progress check', 'accepted', 6, 3, 2),
('2026-02-14 15:00:00', 'Visio', 'Therapy session', 'accepted', 7, 3, 3),
('2026-02-14 13:00:00', 'Cabinet', 'Self-compassion work', 'accepted', 8, 3, 4),

('2026-02-18 14:00:00', 'Cabinet', 'Mindfulness practice', 'accepted', 5, 3, 1),
('2026-02-20 10:00:00', 'Visio', 'Routine session', 'accepted', 6, 3, 2),
('2026-02-21 15:00:00', 'Cabinet', 'Check-in', 'accepted', 7, 3, 3),
('2026-02-21 11:00:00', 'Visio', 'Academic stress', 'accepted', 8, 3, 4),
-- Week 4 (current/future)
('2026-02-25 14:00:00', 'Visio', 'Weekly session', 'pending', 5, 3, 1),
('2026-02-27 10:00:00', 'Cabinet', 'Therapy session', 'pending', 6, 3, 2),
('2026-02-28 15:00:00', 'Visio', 'Monthly review', 'pending', 7, 3, 3);

-- Some appointments for new patients (initial consultations)
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2026-01-20 14:00:00', 'Cabinet', 'Initial consultation', 'accepted', 9, 3, 5),
('2026-02-03 10:00:00', 'Visio', 'Follow-up assessment', 'accepted', 9, 3, 5),
('2026-02-17 15:00:00', 'Cabinet', 'Anxiety management', 'accepted', 9, 3, 5),
('2026-02-26 14:00:00', 'Cabinet', 'Therapy session', 'pending', 9, 3, 5);

-- Some rejected/cancelled appointments (realistic scenario)
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2025-12-27 10:00:00', 'Cabinet', 'Holiday week session', 'rejected', 6, 3, NULL),
('2026-01-03 14:00:00', 'Visio', 'New year session', 'rejected', 7, 3, NULL),
('2026-02-10 09:00:00', 'Cabinet', 'Early morning slot', 'rejected', 5, 3, NULL);

-- ==========================================
-- NOTES FOR EXECUTION:
-- ==========================================
-- 1. First, verify psychologist with ID=3 exists: SELECT * FROM user WHERE id=3 AND role='psychologue';
-- 2. Adjust student IDs (5-14) if your auto_increment is different
-- 3. Adjust patient_file IDs (1-5) if different
-- 4. Run this script: mysql -u root -p database_name < populate_data.sql
-- 5. Or execute in phpMyAdmin
-- ==========================================

-- After inserting, verify:
-- SELECT COUNT(*) FROM user WHERE role='etudiant'; -- Should be 10+ students
-- SELECT COUNT(*) FROM patient_file; -- Should have 5 patient files
-- SELECT COUNT(*) FROM appointment WHERE idpsy=3; -- Should have 40+ appointments
-- SELECT * FROM appointment WHERE date >= '2026-02-01' ORDER BY date; -- Recent appointments

-- Pattern Analysis for AI:
-- - Tuesdays 2pm (14:00) - VERY COMMON
-- - Thursdays 10am (10:00) - COMMON
-- - Fridays 3pm (15:00) - COMMON
-- - Fridays 11am (11:00) - OCCASIONAL
-- - Fridays 1pm (13:00) - OCCASIONAL
-- This gives the AI clear patterns to recognize!
