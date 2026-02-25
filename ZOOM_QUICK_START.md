# 🚀 Quick Start Checklist - Zoom Integration

## Immediate Actions Required:

### 1. ✅ Get Zoom Credentials (5-10 minutes)
- [ ] Go to https://marketplace.zoom.us/
- [ ] Create "Server-to-Server OAuth" app
- [ ] Copy Client ID, Client Secret, Account ID

### 2. ✅ Update `.env` File (2 minutes)
```bash
ZOOM_CLIENT_ID=paste_your_client_id
ZOOM_CLIENT_SECRET=paste_your_client_secret  
ZOOM_ACCOUNT_ID=paste_your_account_id
```

### 3. ✅ Run Database Migration (1 minute)
```bash
php bin/console doctrine:migrations:migrate
```

### 4. ✅ Clear Cache (30 seconds)
```bash
php bin/console cache:clear
```

### 5. ✅ Test It (5 minutes)
- Create an online appointment (location: "en_ligne")
- Accept it from psychologist account
- Check email for Zoom link
- Click link to verify it works

---

## What Happens Automatically:

✅ When psychologist accepts an online appointment:
1. Zoom meeting automatically created
2. Student receives email with Zoom link
3. Psychologist receives email with Zoom link
4. Zoom link displays on appointment detail page

✅ Error Handling:
- If Zoom fails, appointment still accepted (graceful failure)
- Error logged for debugging
- No email Zoom link if creation failed

---

## Files Changed:

**New Files:**
- ✨ `src/Service/ZoomApiService.php` 
- ✨ `migrations/Version20260224120000.php`

**Modified Files:**
- 📝 `src/Entity/Appointment.php` (added Zoom fields)
- 📝 `src/Controller/Admin/AppointmentController.php` (added Zoom logic)
- 📝 `src/Controller/Reservation/AppointmentController.php` (added Zoom logic)
- 📝 `templates/admin/rdv/show.html.twig` (display Zoom link)
- 📝 `.env` (added Zoom credentials)
- 📝 `config/services.yaml` (registered service)

---

## 💡 Important Notes:

⚠️ **Default Settings:**
- No waiting room (anyone can join)
- No authentication required
- 60-minute duration
- Video enabled for both parties
- Mute upon entry: disabled

🔒 **To Make It More Secure:**
Edit `src/Service/ZoomApiService.php` and change:
```php
'waiting_room' => true,  // Require host to let them in
'meeting_authentication' => true,  // Require Zoom login
```

📧 **Email Format:**
Includes clickable Zoom link sent to both student and psychologist

📺 **Display:**
Appointment detail page shows "Rejoindre la réunion Zoom" button

---

## Troubleshooting:

| Problem | Solution |
|---------|----------|
| "Failed to obtain Zoom access token" | Check credentials in `.env` |
| Zoom link not in email | Check PHP error logs |
| DB migration fails | Run `php bin/console doctrine:migrations:latest` first |
| Button not showing | Clear browser cache, check if location = 'en_ligne' |

---

## Full Documentation:

See `ZOOM_INTEGRATION_GUIDE.md` for detailed setup and customization.

---

**Status: ✅ READY TO USE**

Just add credentials and run the 4 commands above!
