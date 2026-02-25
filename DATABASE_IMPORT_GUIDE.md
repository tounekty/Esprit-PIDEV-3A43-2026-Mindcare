# Database Population Guide

## 📦 What's Included

### Students Created (10)
1. **Sophie Martin** - sophie.martin@student.com (GAD, anxiety)
2. **Lucas Dubois** - lucas.dubois@student.com (Adjustment issues)
3. **Emma Bernard** - emma.bernard@student.com (Depression, sleep issues)
4. **Noah Petit** - noah.petit@student.com (Perfectionism, burnout)
5. **Chloé Roux** - chloe.roux@student.com (No patient file yet)
6. **Gabriel Moreau** - gabriel.moreau@student.com (No patient file yet)
7. **Léa Simon** - lea.simon@student.com (Social anxiety)
8. **Hugo Laurent** - hugo.laurent@student.com (No patient file yet)
9. **Alice Michel** - alice.michel@student.com (No patient file yet)
10. **Tom Lefevre** - tom.lefevre@student.com (No patient file yet)

**All passwords:** `eyaisbichA2701`

### Patient Files (5)
- Detailed clinical histories with antecedents, notes, risk levels
- Ready for AI analysis

### Appointments (40+)
- **December 2025:** 9 appointments
- **January 2026:** 14 appointments
- **February 2026:** 17 appointments
- Mix of Cabinet and Visio sessions
- Mostly accepted, some pending, some rejected

### AI Pattern Built
- **Tuesdays 2pm (14:00)** - Most common slot ⭐
- **Thursdays 10am (10:00)** - Common slot ⭐
- **Fridays 3pm (15:00)** - Common slot ⭐
- Other times scattered

## 🚀 How to Import

### Method 1: MySQL Command Line
```bash
mysql -u root -p your_database_name < populate_database.sql
```

### Method 2: phpMyAdmin
1. Open phpMyAdmin
2. Select your database
3. Go to "SQL" tab
4. Copy content from `populate_database.sql`
5. Click "Go"

### Method 3: MySQL Workbench
1. Open MySQL Workbench
2. Connect to your database
3. File → Open SQL Script
4. Select `populate_database.sql`
5. Execute

## ⚠️ Important Notes

### Before Running:
1. **Backup your database first!**
2. Verify psychologist ID=3 exists:
   ```sql
   SELECT * FROM user WHERE id=3 AND role='psychologue';
   ```
3. Check your current max user ID to adjust if needed:
   ```sql
   SELECT MAX(id) FROM user;
   ```

### After Running:
1. Run verification queries from `verify_data.sql`
2. Test login with any student email + password `eyaisbichA2701`
3. Check patient files in admin panel
4. View appointments in calendar

## 🧪 Testing AI Features

### Test Patient File AI Insights:
1. Login as psychologist (ID: 3)
2. Go to: Dossiers Étudiants
3. Open Sophie Martin's file
4. Click "Générer les Insights" button
5. Wait 20-30s for AI analysis

### Test AI Suggest Times (when implemented):
1. AI will analyze past appointments
2. Should suggest: Tuesday 2pm, Thursday 10am, Friday 3pm
3. Based on the pattern we created

## 📊 Data Summary

```
Students: 10 (all verified)
Patient Files: 5 (with detailed data)
Appointments: 40+
   - Accepted: 35+
   - Pending: 4
   - Rejected: 3
Psychologist: 1 (ID: 3)
Date Range: Dec 2025 - Feb 2026
```

## 🔑 Test Credentials

**Any student account:**
- Email: [firstname].[lastname]@student.com
- Password: eyaisbichA2701

**Examples:**
- sophie.martin@student.com
- lucas.dubois@student.com
- emma.bernard@student.com

## 🐛 Troubleshooting

### If student IDs don't match:
The SQL assumes students get IDs 5-14. If your auto_increment is different:
1. Check: `SELECT MAX(id) FROM user;`
2. Adjust all `idetudiant` values in appointments accordingly

### If patient_file IDs don't match:
The SQL assumes patient files get IDs 1-5. Adjust `patient_file_id` in appointments if needed.

### If appointments fail to insert:
- Check foreign key constraints
- Ensure psychologist ID=3 exists
- Verify student IDs exist
- Check date formats

## ✅ Verification Checklist

After import, verify:
- [ ] 10+ students in database
- [ ] All students have `isVerified = 1`
- [ ] 5 patient files created
- [ ] 40+ appointments for psychologist ID=3
- [ ] Appointments have varied dates (Dec-Feb)
- [ ] Can login with test credentials
- [ ] Patient files show in admin panel
- [ ] Calendar shows all appointments
- [ ] AI button appears on patient files with data

## 🎯 Next Steps

1. Import the data
2. Run verification queries
3. Test login with student accounts
4. Test psychologist view of appointments
5. Test AI insights on patient files
6. Check appointment patterns in calendar

Enjoy testing! 🚀
