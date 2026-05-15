# MyClassroom LMS

A modern, responsive Learning Management System built with PHP, SQLite3, vanilla JavaScript, HTML5, and CSS3.

## Key Features

- Light theme with red / black / white premium glassmorphism UI
- Mobile-first responsive dashboards
- Student, Tutor, and Super Admin roles
- Phone + password login
- OTP verification via Text.lk API
- Manual payment upload and approval workflow
- Course store, tutor profiles, live Zoom classes, recordings, materials, announcements, notes
- Role-based route protection and session authentication
- Secure password hashing and input sanitization
- CSRF protection for auth routes
- SQLite database auto-initialization

## Installation

1. Place the `myclassroom` folder into your XAMPP `htdocs` folder.
2. Update `config/config.php` if needed:
   - `APP_URL`
   - `TEXTLK_API_KEY`
   - `TEXTLK_SENDER_ID`
3. Ensure PHP has `pdo_sqlite` enabled.
4. Open your browser and visit:
   - `http://localhost/myclassroom/`

The SQLite database file is automatically created at `database/lms.db`.

## Default Admin Login

- Email: `admin@myclassroom.lk`
- Password: `Admin@123456`

## Important Notes

- Tutor accounts must be created by the Super Admin from the admin dashboard.
- Students must verify phone numbers by OTP before registration.
- Payment approvals unlock course enrollments after manual review.
- Uploads are stored in `assets/uploads/`.

## File Structure

- `auth/` — login, register, forgot password pages
- `student/` — student dashboard and pages
- `tutor/` — tutor dashboard and pages
- `admin/` — super admin dashboard
- `api/` — AJAX endpoints for auth, student, tutor, admin
- `assets/css/` — theme and dashboard styles
- `assets/js/` — UI and dashboard scripts
- `database/` — SQLite storage
- `includes/` — shared helpers
- `config/` — app and database configuration

## Ready for Deployment

- Suitable for XAMPP or VPS hosting
- Designed for fast setup and secure multi-role LMS operation
- Works great on desktop, tablet, and mobile screens
