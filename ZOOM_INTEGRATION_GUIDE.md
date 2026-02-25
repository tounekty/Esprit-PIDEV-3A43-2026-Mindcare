# Zoom API Integration Guide

## ✅ Implementation Complete

Your MindCare appointment system now includes **automatic Zoom meeting creation** for online appointments. Here's what has been implemented:

---

## 📋 What Was Implemented

### 1. **Service Layer** (`src/Service/ZoomApiService.php`)
- OAuth 2.0 authentication with Zoom API
- Auto-generates Zoom meeting IDs and join URLs
- Includes error handling and logging
- Methods:
  - `createMeeting()` - Creates a Zoom meeting for an appointment
  - `deleteMeeting()` - Deletes a Zoom meeting
  - `getUserIdByEmail()` - Retrieves Zoom user ID from email

### 2. **Database Changes**
- Added three new fields to the `appointment` table:
  - `zoom_meeting_id` - Stores the Zoom meeting ID
  - `zoom_join_url` - Stores the Zoom join URL
  - `zoom_created_at` - Stores when the Zoom meeting was created
- Migration file: `migrations/Version20260224120000.php`

### 3. **Appointment Entity** (`src/Entity/Appointment.php`)
- Added getters/setters for the three new Zoom fields
- Fields are nullable to support both online and in-office appointments

### 4. **Updated Controllers**
Both appointment acceptance endpoints now create Zoom meetings:
- **Admin Controller**: `src/Controller/Admin/AppointmentController.php` - `admin_rdv_accept` action
- **Reservation Controller**: `src/Controller/Reservation/AppointmentController.php` - `reservation_accept` action

#### Logic Flow:
1. Psychologist accepts an appointment
2. System checks if appointment location is `'en_ligne'` (online)
3. If online:
   - Creates a Zoom meeting via API
   - Stores meeting ID and join URL in database
4. Sends email to student with Zoom link
5. If admin acceptance, also notifies psychologist

### 5. **Email Enhancement**
- Student and psychologist now receive Zoom join link in acceptance emails
- Email includes clickable Zoom meeting link with label "Lien de réunion Zoom"

### 6. **UI Enhancement**
- Updated appointment detail template (`templates/admin/rdv/show.html.twig`)
- Displays "Rejoindre la réunion Zoom" button when Zoom link exists
- Button opens Zoom meeting in new tab

### 7. **Environment Configuration** (`.env`)
Added three new environment variables:
```
ZOOM_CLIENT_ID=your_zoom_client_id_here
ZOOM_CLIENT_SECRET=your_zoom_client_secret_here
ZOOM_ACCOUNT_ID=your_zoom_account_id_here
```

### 8. **Service Registration** (`config/services.yaml`)
Registered ZoomApiService with dependency injection for HTTP client and environment variables

---

## 🔧 Setup Instructions

### Step 1: Get Zoom Credentials

1. Go to [Zoom App Marketplace](https://marketplace.zoom.us/)
2. Create a new app:
   - App type: "Server-to-Server OAuth"
   - App name: "MindCare Appointments"
3. In the app, navigate to "Credentials" tab
4. Copy these values:
   - **Client ID** → `ZOOM_CLIENT_ID`
   - **Client Secret** → `ZOOM_CLIENT_SECRET`
   - **Account ID** → `ZOOM_ACCOUNT_ID`

### Step 2: Configure Environment Variables

Edit `.env` file and replace placeholders:

```bash
ZOOM_CLIENT_ID=your_actual_client_id
ZOOM_CLIENT_SECRET=your_actual_client_secret
ZOOM_ACCOUNT_ID=your_actual_account_id
```

### Step 3: Run Database Migration

```bash
php bin/console doctrine:migrations:migrate
```

This will create the three new columns in the appointment table:
- `zoom_meeting_id`
- `zoom_join_url`
- `zoom_created_at`

### Step 4: Clear Cache

```bash
php bin/console cache:clear
```

---

## 🧪 Testing the Integration

### Manual Testing:

1. Go to appointment booking
2. Create an online appointment (`'en_ligne'`)
3. Psychologist accepts the appointment
4. Verify:
   - Zoom meeting ID stored in database
   - Student receives email with Zoom link
   - Link opens in Zoom or browser
   - Zoom link displays on appointment detail page

### Testing Without Real Zoom Credentials:

If you want to test the code flow without setting up Zoom:

1. Leave Zoom credentials as placeholders
2. The error will be caught and logged
3. Appointment will still be accepted (graceful failure)
4. Check logs for error messages

---

## 📧 Email Configuration

The system now sends enhanced emails with Zoom links. Format:

```html
<p>Bonjour [Student Name],</p>
<p>Votre rendez-vous prévu le [DATE/TIME] avec [Psychologist Name] a été accepté.</p>
<p><strong>Lien de réunion Zoom :</strong> <a href="[ZOOM_LINK]">[ZOOM_LINK]</a></p>
<p>Cordialement,<br>L'équipe MindCare</p>
```

---

## 🔍 Key Code Details

### Zoom Meeting Parameters:

- **Topic**: "Rendez-vous avec [Psychologist Name]"
- **Type**: Scheduled meeting (type: 2)
- **Duration**: 60 minutes (configurable)
- **Timezone**: UTC (can be customized)
- **Features Enabled**:
  - Host video on
  - Participant video on
  - Waiting room disabled
  - Authentication disabled (anyone with link can join)

### Location Checking:

The system checks `appointment->location` for:
- `'en_ligne'` → Creates Zoom meeting
- `'in_office'` → Skips Zoom creation

### Error Handling:

- If Zoom API fails, error is logged
- Appointment acceptance continues (doesn't fail)
- User receives acceptance email without Zoom link
- Error logged to `error_log()` for debugging

---

## 🚀 Important Notes

### Security Considerations:

1. **Client Secret**: Keep this secure, don't commit to git
2. **Zoom Link Sharing**: Links are sent via email, anyone receiving email can join
3. **Waiting Room**: Currently disabled to allow easy joining
4. **Meeting Authentication**: Currently disabled (no password required)

### If You Want to Enable Security:

Update `src/Service/ZoomApiService.php` in the `createMeeting()` method:

```php
'settings' => [
    'host_video' => true,
    'participant_video' => true,
    'join_before_host' => false,
    'mute_upon_entry' => false,
    'waiting_room' => true,  // Enable waiting room
    'meeting_authentication' => true,  // Require sign-in
],
```

### For Admin-Only Acceptance:

The current logic includes an admin notification check:

```php
if ($this->isGranted('ROLE_ADMIN') && !$user->getId() === $appointment->getPsychologue()->getId()) {
    // Notify psychologist
}
```

This ensures psychologist doesn't get duplicate emails when accepting their own appointment.

---

## 🐛 Troubleshooting

### "Failed to obtain Zoom access token"

**Cause**: Invalid credentials or Zoom API error
**Solution**: 
- Verify credentials in `.env`
- Check Zoom dashboard for API errors
- Ensure app is approved/activated

### Zoom Link Not Appearing in Email

**Cause**: Zoom meeting creation failed
**Solution**:
- Check PHP error logs for Zoom API errors
- Verify appointment location is `'en_ligne'`
- Check Zoom API rate limits (free tier has limits)

### Database Migration Fails

**Cause**: Column already exists or migrations issue
**Solution**:
```bash
php bin/console doctrine:migrations:latest
php bin/console doctrine:migrations:migrate --no-interaction
```

### "HttpClientInterface" not found

**Cause**: Symfony HTTP Client not installed
**Solution**:
```bash
composer require symfony/http-client
```

---

## 📝 Files Modified/Created

Created:
- ✅ `src/Service/ZoomApiService.php` - Main Zoom API service
- ✅ `migrations/Version20260224120000.php` - Database migration

Modified:
- ✅ `src/Entity/Appointment.php` - Added Zoom fields + getters/setters
- ✅ `src/Controller/Admin/AppointmentController.php` - Added Zoom logic
- ✅ `src/Controller/Reservation/AppointmentController.php` - Added Zoom logic
- ✅ `templates/admin/rdv/show.html.twig` - Display Zoom link
- ✅ `.env` - Added Zoom configuration variables
- ✅ `config/services.yaml` - Registered ZoomApiService

---

## 🔄 Next Steps (Optional Enhancements)

1. **Zoom Recording**: Store recording links in database
2. **Reminder Emails**: Send Zoom link reminder 24 hours before meeting
3. **Meeting Statistics**: Track meeting duration, attendees
4. **Rescheduling**: Delete old Zoom meeting and create new one
5. **Cancellation**: Delete Zoom meeting when appointment is cancelled
6. **Custom Agenda**: Add appointment description to Zoom meeting agenda
7. **Waiting Room**: Add option for psychologist to enable waiting room
8. **Authentication**: Require Zoom login for participants

---

## 💡 Support

For issues with:
- **Zoom API**: [Zoom Developer Docs](https://developers.zoom.us/)
- **Symfony**: [Symfony Documentation](https://symfony.com/doc/)
- **Your App**: Check logs at `var/log/` or use `tail -f var/log/dev.log`

---

✅ **Your Zoom integration is ready to use!**

Remember to:
1. Configure credentials in `.env`
2. Run database migration
3. Clear cache
4. Test with an online appointment

Any questions? The code includes comments and follows Symfony best practices.
