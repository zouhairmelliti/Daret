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
    
    // Vérifier le nombre de membres
    $stmt = $pdo->prepare("SELECT COUNT(*) as member_count FROM daret_members WHERE daret_id = ?");
    $stmt->execute([$daret_id]);
    $member_count = $stmt->fetch(PDO::FETCH_ASSOC)['member_count'];
    
    $stmt = $pdo->prepare("SELECT max_members FROM darets WHERE id = ?");
    $stmt->execute([$daret_id]);
    $max_members = $stmt->fetch(PDO::FETCH_ASSOC)['max_members'];
    
    if ($member_count < 2) {
        echo json_encode(['success' => false, 'message' => 'Le DARET doit avoir au moins 2 membres']);
        exit;
    }
    
    // Démarrer le DARET
    $stmt = $pdo->prepare("UPDATE darets SET status = 'active' WHERE id = ?");
    if ($stmt->execute([$daret_id])) {
        echo json_encode(['success' => true, 'message' => 'DARET démarré avec succès!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du démarrage du DARET']);
    }
}
?>