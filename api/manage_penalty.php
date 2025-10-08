<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $penalty_id = intval($data['penalty_id']);
    $action = $data['action'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que l'utilisateur est admin du DARET concerné
    $stmt = $pdo->prepare(" SELECT dm.is_admin 
        FROM late_payments lp
        JOIN payments p ON lp.payment_id = p.id
        JOIN daret_rounds dr ON p.daret_round_id = dr.id
        JOIN daret_members dm ON dr.daret_id = dm.daret_id AND dm.user_id = ?
        WHERE lp.id = ?
    ");
    $stmt->execute([$user_id, $penalty_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member || !$member['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas administrateur de ce DARET']);
        exit;
    }
    
    if ($action == 'mark_paid') {
        $stmt = $pdo->prepare("UPDATE late_payments SET status = 'paid' WHERE id = ?");
        if ($stmt->execute([$penalty_id])) {
            echo json_encode(['success' => true, 'message' => 'Pénalité marquée comme payée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du marquage']);
        }
    } elseif ($action == 'waive') {
        $stmt = $pdo->prepare("UPDATE late_payments SET status = 'waived' WHERE id = ?");
        if ($stmt->execute([$penalty_id])) {
            echo json_encode(['success' => true, 'message' => 'Pénalité annulée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'annulation']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>