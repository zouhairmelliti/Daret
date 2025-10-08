<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $round_id = intval($data['round_id']);
    $beneficiary_user_id = intval($data['beneficiary_user_id']);
    $due_date = $data['due_date'];
    $description = $data['description'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les informations du tour
    $stmt = $pdo->prepare("SELECT daret_id, beneficiary_user_id FROM daret_rounds WHERE id = ?");
    $stmt->execute([$round_id]);
    $round = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$round) {
        echo json_encode(['success' => false, 'message' => 'Tour non trouvé']);
        exit;
    }
    
    // Vérifier que l'utilisateur est admin du DARET
    $stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$round['daret_id'], $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member || !$member['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas administrateur de ce DARET']);
        exit;
    }
    
    // Vérifier si le bénéficiaire a changé
    if ($round['beneficiary_user_id'] != $beneficiary_user_id) {
        // Vérifier si le nouveau bénéficiaire est membre du DARET
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM daret_members WHERE daret_id = ? AND user_id = ?");
        $stmt->execute([$round['daret_id'], $beneficiary_user_id]);
        if ($stmt->fetchColumn() == 0) {
            echo json_encode(['success' => false, 'message' => 'Le bénéficiaire n\'est pas membre de ce DARET']);
            exit;
        }
        
        // Vérifier si le nouveau bénéficiaire a déjà un tour
        $stmt = $pdo->prepare("SELECT id FROM daret_rounds WHERE daret_id = ? AND beneficiary_user_id = ? AND id != ?");
        $stmt->execute([$round['daret_id'], $beneficiary_user_id, $round_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ce membre a déjà un tour attribué']);
            exit;
        }
    }
    
    try {
        // Mettre à jour le tour
        $stmt = $pdo->prepare("
            UPDATE daret_rounds 
            SET beneficiary_user_id = ?, due_date = ?, description = ?
            WHERE id = ?
        ");
        $stmt->execute([$beneficiary_user_id, $due_date, $description, $round_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tour modifié avec succès!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la modification: ' . $e->getMessage()
        ]);
    }
}
?>