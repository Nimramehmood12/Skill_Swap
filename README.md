# SkillSwap

SkillSwap is a PHP-based web application that allows users to connect, teach, and learn skills from one another.

## Local Setup Instructions

1. Clone this repository into your local web server environment (e.g., the `htdocs` folder if you are using XAMPP).
2. Start your local Apache and MySQL servers.
3. Update the database credentials in `config/db.php` if your local MySQL setup requires a password (default is `root` with no password).
4. Open your browser and run the database setup script to initialize the tables, views, and procedures:
   `http://localhost/projecttrial3/setup_database.php`
5. (Optional) Run the account creation script to generate the default Admin and Moderator accounts:
   `http://localhost/projecttrial3/create_accounts.php`
6. Navigate to `index.php` or `auth/login.php` to start using the platform!

## Features

- Skill matching between users.
- Request, schedule, and manage skill-swapping sessions.
- Leave reviews and rate users.
