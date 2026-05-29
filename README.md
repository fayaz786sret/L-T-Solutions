# L&T Solutions LearnHub

L&T Solutions LearnHub is a PHP and MySQL learning platform for branch-based education. It includes separate admin and student portals for managing branches, subjects, chapters, materials, quizzes, and monthly tests.

## Features

- Student registration, login, password reset, and dashboard
- Admin dashboard for managing branches, subjects, chapters, materials, quizzes, and monthly tests
- Branch-wise and chapter-wise learning materials
- Timed monthly tests with configurable duration
- File previews for uploaded study materials
- Admin password change screen

## Requirements

- PHP 8.x or later
- MySQL or MariaDB
- A local web server such as Apache, Nginx, or PHP built-in server

## Setup

1. Create a MySQL database named `learning_platform`.
2. Import your project tables and sample data if available.
3. Update database credentials in [includes/config.php](includes/config.php).
4. Make sure the `assets/uploads/materials` and `storage` folders are writable.
5. Open the app in your browser.

## Run Locally

If you want to use PHP's built-in server:

```bash
php -S 127.0.0.1:8080 -t .
```

Then open:

- `http://127.0.0.1:8080/` for the public home page
- `http://127.0.0.1:8080/login.php` for login
- `http://127.0.0.1:8080/admin/dashboard.php` for the admin dashboard
- `http://127.0.0.1:8080/student/dashboard.php` for the student dashboard

## Project Structure

- `admin/` Admin pages and management screens
- `student/` Student pages and learning flow
- `includes/` Shared configuration, database, and helper functions
- `assets/` Styles and uploaded files
- `material_view.php` Shared material preview page
- `login.php` Unified login page
- `index.php` Public landing page

## Notes

- File uploads accept PDF, DOC, DOCX, PPT, and PPTX files.
- Monthly tests now support a configurable duration per test.
- The admin area includes a dedicated password change page.

## License

Internal project for L&T Solutions.