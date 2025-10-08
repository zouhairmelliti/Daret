<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $full_name = trim($data['full_name']);
    $username = trim($data['username']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $errors[] = "Tous les champs obligatoires doivent être remplis.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $errors[] = "Le nom d'utilisateur ou l'email existe déjà.";
    }
    
    if (empty($errors)) {
        // Hasher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $username, $email, $phone, $hashed_password])) {
            echo json_encode(['success' => true, 'message' => 'Inscription réussie!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
}
?>