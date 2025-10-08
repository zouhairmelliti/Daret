<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    $errors = [];
    
    // Validation
    if (empty($username) || empty($password)) {
        $errors[] = "Tous les champs doivent être remplis.";
    }
    
    if (empty($errors)) {
        // Vérifier l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Connexion réussie!'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
}
?>