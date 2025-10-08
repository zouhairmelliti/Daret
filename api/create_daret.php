<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name']);
    $description = trim($data['description']);
    $amount = floatval($data['amount']);
    $max_members = intval($data['max_members']);
    $frequency = $data['frequency'];
    $created_by = $_SESSION['user_id'];
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Le nom du DARET est obligatoire.";
    }
    
    if ($amount <= 0) {
        $errors[] = "Le montant doit être supérieur à 0.";
    }
    
    if ($max_members < 2) {
        $errors[] = "Le nombre de membres doit être au moins 2.";
    }
    
    if (!in_array($frequency, ['weekly', 'monthly'])) {
        $errors[] = "Fréquence invalide.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Créer le DARET
            $stmt = $pdo->prepare("INSERT INTO darets (name, description, amount, frequency, max_members, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $amount, $frequency, $max_members, $created_by]);
            $daret_id = $pdo->lastInsertId();
            
            // Ajouter le créateur comme membre admin
            $stmt = $pdo->prepare("INSERT INTO daret_members (daret_id, user_id, is_admin) VALUES (?, ?, ?)");
            $stmt->execute([$daret_id, $created_by, true]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'DARET créé avec succès!',
                'daret_id' => $daret_id
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur lors de la création du DARET: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
}
?>