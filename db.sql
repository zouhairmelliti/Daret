-- Créer la base de données
CREATE DATABASE daret_app;
USE daret_app;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des darets
CREATE TABLE darets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    frequency ENUM('weekly', 'monthly') NOT NULL,
    max_members INT NOT NULL,
    created_by INT NOT NULL,
    status ENUM('open', 'active', 'completed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Table des membres de daret
CREATE TABLE daret_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daret_id INT NOT NULL,
    user_id INT NOT NULL,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (daret_id) REFERENCES darets(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_daret_user (daret_id, user_id)
);

-- Table des tours de daret
CREATE TABLE daret_rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daret_id INT NOT NULL,
    round_number INT NOT NULL,
    beneficiary_user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    round_date DATE NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    FOREIGN KEY (daret_id) REFERENCES darets(id),
    FOREIGN KEY (beneficiary_user_id) REFERENCES users(id)
);

-- Table des paiements
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daret_round_id INT NOT NULL,
    payer_user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    FOREIGN KEY (daret_round_id) REFERENCES daret_rounds(id),
    FOREIGN KEY (payer_user_id) REFERENCES users(id)
);