-- Fix missing patient files and appointments
-- Students: Sophie=4, Lucas=5, Emma=6, Noah=7, (Léa=10)
-- Patient Files will be: 12 (Sophie), 13 (Léa)

-- Patient File for Sophie
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`,`allergies`, `contact_urgence_nom`, `contact_urgence_tel`, `antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, `niveau_risque`, `created_at`, `updated_at`) VALUES
(4, 'Sertraline 50mg daily', 'None', 'Marie Martin', '+33612345678',
'Diagnosed with generalized anxiety disorder at age 19. Panic attacks during exams. Family history of anxiety.', 
'Mother has depression, grandfather had anxiety disorder',
'Recurring anxiety attacks affecting academic performance',
'Develop better coping mechanisms for stress and sleep',
'Patient shows good insight. Cooperative. Recent sessions on breathing techniques.',
'Medium', '2025-12-01 10:00:00', '2026-02-20 15:30:00');

-- Patient File for Léa
INSERT INTO `patient_file` (`student_id`, `traitements_en_cours`, `allergies`, `contact_urgence_nom`, `contact_urgence_tel`, `antecedents_personnels`, `antecedents_familiaux`, `motif_consultation`, `objectifs_therapeutiques`, `notes_generales`, `niveau_risque`, `created_at`, `updated_at`) VALUES
(10, 'Propranolol for performance anxiety', 'Latex', 'Marc Simon', '+33623456789',
'Social anxiety disorder. Difficulty with public speaking. Medication for 6 months.',
'Mother has social anxiety',
'Severe anxiety in social situations',
'Increase comfort in social situations and public speaking',
'Significant progress. Completed class presentation. Exposure therapy continues.',
'Low', '2026-01-15 11:30:00', '2026-02-24 10:00:00');

-- Now insert appointments with correct IDs
-- Sophie=4, patientFile=12; Lucas=5, patientFile=7; Emma=6, patientFile=8; Noah=7, patientFile=9; Léa=10, patientFile=13

-- December 2025
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2025-12-03 10:00:00', 'Cabinet', 'Initial consultation', 'accepted', 4, 3, 12),
('2025-12-05 14:00:00', 'Visio', 'Follow-up session', 'accepted', 5, 3, 7),
('2025-12-10 14:00:00', 'Cabinet', 'CBT session', 'accepted', 4, 3, 12),
('2025-12-12 10:00:00', 'Visio', 'Adjustment therapy', 'accepted', 5, 3, 7),
('2025-12-13 15:00:00', 'Cabinet', 'Initial assessment', 'accepted', 6, 3, 8),
('2025-12-17 14:00:00', 'Visio', 'Anxiety management', 'accepted', 4, 3, 12),
('2025-12-19 10:00:00', 'Cabinet', 'Social skills work', 'accepted', 5, 3, 7),
('2025-12-20 15:00:00', 'Visio', 'Medication review', 'accepted', 6, 3, 8),
('2025-12-24 10:00:00', 'Cabinet', 'Pre-holiday session', 'accepted', 4, 3, 12);

-- January 2026
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2026-01-07 14:00:00', 'Cabinet', 'New year check-in', 'accepted', 4, 3, 12),
('2026-01-09 10:00:00', 'Visio', 'Goal setting', 'accepted', 6, 3, 8),
('2026-01-10 15:00:00', 'Cabinet', 'Initial consultation', 'accepted', 7, 3, 9),
('2026-01-14 14:00:00', 'Visio', 'Stress management', 'accepted', 4, 3, 12),
('2026-01-16 10:00:00', 'Cabinet', 'Therapy session', 'accepted', 5, 3, 7),
('2026-01-17 15:00:00', 'Visio', 'Sleep hygiene', 'accepted', 6, 3, 8),
('2026-01-17 11:00:00', 'Cabinet', 'Perfectionism work', 'accepted', 7, 3, 9),
('2026-01-21 14:00:00', 'Cabinet', 'CBT techniques', 'accepted', 4, 3, 12),
('2026-01-23 10:00:00', 'Visio', 'Progress review', 'accepted', 5, 3, 7),
('2026-01-24 15:00:00', 'Cabinet', 'Medication adjustment', 'accepted', 6, 3, 8),
('2026-01-24 13:00:00', 'Visio', 'Cognitive reframing', 'accepted', 7, 3, 9),
('2026-01-28 14:00:00', 'Visio', 'Anxiety tools', 'accepted', 4, 3, 12),
('2026-01-30 10:00:00', 'Cabinet', 'Social connections', 'accepted', 5, 3, 7),
('2026-01-31 15:00:00', 'Visio', 'Monthly review', 'accepted', 6, 3, 8);

-- February 2026
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2026-02-04 14:00:00', 'Cabinet', 'Routine session', 'accepted', 4, 3, 12),
('2026-02-06 10:00:00', 'Visio', 'Therapy session', 'accepted', 5, 3, 7),
('2026-02-07 15:00:00', 'Cabinet', 'Mood monitoring', 'accepted', 6, 3, 8),
('2026-02-07 11:00:00', 'Visio', 'Work-life balance', 'accepted', 7, 3, 9),
('2026-02-11 14:00:00', 'Visio', 'Coping strategies', 'accepted', 4, 3, 12),
('2026-02-13 10:00:00', 'Cabinet', 'Progress check', 'accepted', 5, 3, 7),
('2026-02-14 15:00:00', 'Visio', 'Therapy session', 'accepted', 6, 3, 8),
('2026-02-14 13:00:00', 'Cabinet', 'Self-compassion', 'accepted', 7, 3, 9),
('2026-02-18 14:00:00', 'Cabinet', 'Mindfulness', 'accepted', 4, 3, 12),
('2026-02-20 10:00:00', 'Visio', 'Routine session', 'accepted', 5, 3, 7),
('2026-02-21 15:00:00', 'Cabinet', 'Check-in', 'accepted', 6, 3, 8),
('2026-02-21 11:00:00', 'Visio', 'Academic stress', 'accepted', 7, 3, 9),
('2026-02-25 14:00:00', 'Visio', 'Weekly session', 'pending', 4, 3, 12),
('2026-02-27 10:00:00', 'Cabinet', 'Therapy session', 'pending', 5, 3, 7),
('2026-02-28 15:00:00', 'Visio', 'Monthly review', 'pending', 6, 3, 8);

-- Léa appointments
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2026-01-20 14:00:00', 'Cabinet', 'Initial consultation', 'accepted', 10, 3, 13),
('2026-02-03 10:00:00', 'Visio', 'Follow-up assessment', 'accepted', 10, 3, 13),
('2026-02-17 15:00:00', 'Cabinet', 'Anxiety management', 'accepted', 10, 3, 13),
('2026-02-26 14:00:00', 'Cabinet', 'Therapy session', 'pending', 10, 3, 13);

-- Rejected appointments
INSERT INTO `appointment` (`date`, `location`, `description`, `status`, `idetudiant`, `idpsy`, `patient_file_id`) VALUES
('2025-12-27 10:00:00', 'Cabinet', 'Holiday week', 'rejected', 5, 3, NULL),
('2026-01-03 14:00:00', 'Visio', 'New year', 'rejected', 6, 3, NULL),
('2026-02-10 09:00:00', 'Cabinet', 'Early morning', 'rejected', 4, 3, NULL);
