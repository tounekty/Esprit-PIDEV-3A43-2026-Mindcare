-- ==========================================
-- Verification Queries after Data Import
-- ==========================================

-- 1. Check all students were created
SELECT id, firstName, lastName, email, role, isVerified, created_at 
FROM user 
WHERE role = 'etudiant' 
ORDER BY id;

-- 2. Verify psychologist exists
SELECT id, firstName, lastName, email, role 
FROM user 
WHERE id = 3 AND role = 'psychologue';

-- 3. Check patient files
SELECT pf.id, u.firstName, u.lastName, pf.niveau_risque, pf.created_at, pf.updated_at
FROM patient_file pf
JOIN user u ON pf.student_id = u.id
ORDER BY pf.id;

-- 4. Count appointments by status
SELECT status, COUNT(*) as count
FROM appointment
WHERE idpsy = 3
GROUP BY status;

-- 5. View all appointments chronologically
SELECT 
    a.id,
    a.date,
    u.firstName as student_name,
    a.location,
    a.status,
    a.description
FROM appointment a
JOIN user u ON a.idetudiant = u.id
WHERE a.idpsy = 3
ORDER BY a.date;

-- 6. Check appointment patterns (for AI analysis)
SELECT 
    DAYNAME(date) as day_of_week,
    HOUR(date) as hour,
    COUNT(*) as frequency
FROM appointment
WHERE idpsy = 3 AND status = 'accepted'
GROUP BY DAYNAME(date), HOUR(date)
ORDER BY frequency DESC;

-- 7. Recent appointments (February 2026)
SELECT 
    a.date,
    u.firstName,
    u.lastName,
    a.location,
    a.status
FROM appointment a
JOIN user u ON a.idetudiant = u.id
WHERE a.idpsy = 3 AND a.date >= '2026-02-01'
ORDER BY a.date;

-- 8. Verify patient file links
SELECT 
    u.firstName,
    u.lastName,
    COUNT(a.id) as appointment_count,
    pf.niveau_risque
FROM user u
LEFT JOIN patient_file pf ON u.id = pf.student_id
LEFT JOIN appointment a ON u.id = a.idetudiant
WHERE u.role = 'etudiant'
GROUP BY u.id, pf.niveau_risque
ORDER BY u.firstName;

-- 9. Test login credentials
-- Email: sophie.martin@student.com
-- Password: eyaisbichA2701
SELECT id, email, firstName, lastName, password
FROM user 
WHERE email = 'sophie.martin@student.com';
