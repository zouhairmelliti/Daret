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
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les informations du tour
    $stmt = $pdo->prepare("SELECT dr.* FROM daret_rounds dr WHERE dr.id = ?");
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
    
    // Vérifier s'il y a des paiements effectués
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE daret_round_id = ? AND status = 'paid'");
    $stmt->execute([$round_id]);
    $paid_count = $stmt->fetchColumn();
    
    if ($paid_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer un tour avec des paiements effectués']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Supprimer les paiements associés
        $stmt = $pdo->prepare("DELETE FROM payments WHERE daret_round_id = ?");
        $stmt->execute([$round_id]);
        
        // Supprimer le tour
        $stmt = $pdo->prepare("DELETE FROM daret_rounds WHERE id = ?");
        $stmt->execute([$round_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tour supprimé avec succès!'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
        ]);
    }
}
?>