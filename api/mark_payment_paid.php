<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $payment_id = intval($data['payment_id']);
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que l'utilisateur est bien le payeur de ce paiement
    $stmt = $pdo->prepare("SELECT p.* FROM payments p WHERE p.id = ? AND p.payer_user_id = ?");
    $stmt->execute([$payment_id, $user_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Paiement non trouvé ou non autorisé']);
        exit;
    }
    
    if ($payment['status'] == 'paid') {
        echo json_encode(['success' => false, 'message' => 'Ce paiement est déjà marqué comme payé']);
        exit;
    }
    
    // Marquer le paiement comme payé
    $stmt = $pdo->prepare("UPDATE payments SET status = 'paid', payment_date = NOW() WHERE id = ?");
    if ($stmt->execute([$payment_id])) {
        echo json_encode(['success' => true, 'message' => 'Paiement marqué comme effectué avec succès!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du marquage du paiement']);
    }
}
?>