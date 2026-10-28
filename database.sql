CREATE DATABASE IF NOT EXISTS doctor_appointment;
USE doctor_appointment;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'patient') NOT NULL DEFAULT 'patient'
);

CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    fee DECIMAL(10,2) DEFAULT 0,
    available_days VARCHAR(100) DEFAULT 'Monday-Friday'
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('Booked', 'Cancelled', 'Completed') DEFAULT 'Booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    INDEX doctor_slot (doctor_id, appointment_date, appointment_time, status)
);

CREATE TABLE IF NOT EXISTS waitlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('Waiting', 'Booked', 'Removed') DEFAULT 'Waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

INSERT INTO doctors (name, specialty, phone, fee, available_days)
SELECT 'Dr. Aditi Sharma', 'General Physician', '9876500001', 400, 'Monday-Friday'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE name = 'Dr. Aditi Sharma');

INSERT INTO doctors (name, specialty, phone, fee, available_days)
SELECT 'Dr. Rahul Verma', 'Dermatologist', '9876500002', 600, 'Tuesday-Saturday'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE name = 'Dr. Rahul Verma');

INSERT INTO doctors (name, specialty, phone, fee, available_days)
SELECT 'Dr. Neha Patel', 'Dentist', '9876500003', 500, 'Monday-Saturday'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE name = 'Dr. Neha Patel');
