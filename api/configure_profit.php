<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $daret_id = intval($data['daret_id']);
    $user_id = $_SESSION['user_id'];
    
    // Vérifier si l'utilisateur est admin du DARET
    $stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$daret_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member || !$member['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas administrateur de ce DARET']);
        exit;
    }
    
    $profit_type = $data['profit_type'];
    $profit_value = floatval($data['profit_value']);
    $calculation_method = $data['calculation_method'];
    $distribution_frequency = $data['distribution_frequency'];
    
    // Validation
    if ($profit_value <= 0) {
        echo json_encode(['success' => false, 'message' => 'La valeur du rend doit être supérieure à 0']);
        exit;
    }
    
    if ($profit_type == 'percentage' && $profit_value > 100) {
        echo json_encode(['success' => false, 'message' => 'Le pourcentage ne peut pas dépasser 100%']);
        exit;
    }
    
    try {
        // Vérifier si des paramètres existent déjà
        $stmt = $pdo->prepare("SELECT id FROM daret_profits WHERE daret_id = ?");
        $stmt->execute([$daret_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Mettre à jour
            $stmt = $pdo->prepare("
                UPDATE daret_profits 
                SET profit_type = ?, profit_value = ?, calculation_method = ?, distribution_frequency = ?
                WHERE daret_id = ?
            ");
            $stmt->execute([$profit_type, $profit_value, $calculation_method, $distribution_frequency, $daret_id]);
        } else {
            // Insérer
            $stmt = $pdo->prepare("
                INSERT INTO daret_profits (daret_id, profit_type, profit_value, calculation_method, distribution_frequency)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$daret_id, $profit_type, $profit_value, $calculation_method, $distribution_frequency]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Paramètres de rend configurés avec succès!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la configuration: ' . $e->getMessage()
        ]);
    }
}
?>