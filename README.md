# Doctor Appointment System

This is a mini project made using PHP, MySQL, HTML, CSS and JavaScript.

## Features
- Patient registration and login
- Admin login
- Add and delete doctors
- Book and cancel appointments
- Check whether a doctor has an appointment at the selected time
- Add a patient to the waitlist when a slot is already booked
- Book the next waiting patient when an appointment is cancelled
- Search the appointment list

## Requirements
- PHP with PDO MySQL enabled
- MySQL or MariaDB
- XAMPP, Laragon, or another local PHP/MySQL setup

## How to run using XAMPP
1. Extract this folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin`.
4. Import `database.sql`.
5. Check the database username and password in `db.php`.
6. Open `http://localhost/doctor-appointment-beginner/login.php` in your browser. If you rename the folder, use that folder name in the URL.

## Admin login
Email: `admin@clinic.local`  
Password: `Admin@123`

The admin account is created automatically when you open the login page for the first time after importing the database. Patients can register from the registration page.

## Demo steps
1. Log in as admin and add a doctor.
2. Register a patient and book an appointment.
3. Register another patient and try booking the same doctor, date and time.
4. The second patient should be added to the waitlist.
5. Cancel the first appointment and check that the next waiting patient gets the slot.

## Database tables
- `users`
- `doctors`
- `appointments`
- `waitlist`

## Online hosting
GitHub stores the project files but does not run PHP or MySQL. To make the website available online, use a hosting service that supports both PHP and MySQL. Create a database on that service, import `database.sql`, update `db.php` with the hosting database details, and upload the project files.

Do not upload real patient information or database passwords to a public repository. This project is for demonstration and should use sample data only.
