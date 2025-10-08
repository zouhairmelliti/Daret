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
    $round_number = intval($data['round_number']);
    $beneficiary_user_id = intval($data['beneficiary_user_id']);
    $due_date = $data['due_date'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier si l'utilisateur est admin du DARET
    $stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$daret_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member || !$member['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas administrateur de ce DARET']);
        exit;
    }
    
    // Vérifier si le DARET est actif
    $stmt = $pdo->prepare("SELECT status, amount FROM darets WHERE id = ?");
    $stmt->execute([$daret_id]);
    $daret = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($daret['status'] != 'active') {
        echo json_encode(['success' => false, 'message' => 'Le DARET doit être actif pour créer des tours']);
        exit;
    }
    
    // Vérifier si le numéro de tour existe déjà
    $stmt = $pdo->prepare("SELECT id FROM daret_rounds WHERE daret_id = ? AND round_number = ?");
    $stmt->execute([$daret_id, $round_number]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce numéro de tour existe déjà']);
        exit;
    }
    
    // Vérifier si le bénéficiaire est membre du DARET
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$daret_id, $beneficiary_user_id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Le bénéficiaire n\'est pas membre de ce DARET']);
        exit;
    }
    
    // Vérifier si le bénéficiaire a déjà un tour
    $stmt = $pdo->prepare("SELECT id FROM daret_rounds WHERE daret_id = ? AND beneficiary_user_id = ?");
    $stmt->execute([$daret_id, $beneficiary_user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce membre a déjà un tour attribué']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Créer le tour
        $stmt = $pdo->prepare("
            INSERT INTO daret_rounds (daret_id, round_number, beneficiary_user_id, amount, round_date, due_date)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$daret_id, $round_number, $beneficiary_user_id, $daret['amount'], $due_date]);
        $round_id = $pdo->lastInsertId();
        
        // Récupérer tous les membres (sauf le bénéficiaire)
        $stmt = $pdo->prepare("SELECT user_id FROM daret_members WHERE daret_id = ? AND user_id != ?");
        $stmt->execute([$daret_id, $beneficiary_user_id]);
        $payers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Créer les paiements pour chaque payeur
        foreach ($payers as $payer_id) {
            $stmt = $pdo->prepare("
                INSERT INTO payments (daret_round_id, payer_user_id, amount, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$round_id, $payer_id, $daret['amount']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tour créé avec succès! ' . count($payers) . ' paiements générés.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la création du tour: ' . $e->getMessage()
        ]);
    }
}
?>