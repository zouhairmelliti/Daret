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
    $username = trim($data['username']);
    $inviter_id = $_SESSION['user_id'];
    
    // Vérifier si l'utilisateur est admin du DARET
    $stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$daret_id, $inviter_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member || !$member['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas administrateur de ce DARET']);
        exit;
    }
    
    // Vérifier si le DARET est ouvert
    $stmt = $pdo->prepare("SELECT status, max_members FROM darets WHERE id = ?");
    $stmt->execute([$daret_id]);
    $daret = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($daret['status'] != 'open') {
        echo json_encode(['success' => false, 'message' => 'Le DARET n\'est plus ouvert aux nouveaux membres']);
        exit;
    }
    
    // Vérifier le nombre de membres
    $stmt = $pdo->prepare("SELECT COUNT(*) as member_count FROM daret_members WHERE daret_id = ?");
    $stmt->execute([$daret_id]);
    $member_count = $stmt->fetch(PDO::FETCH_ASSOC)['member_count'];
    
    if ($member_count >= $daret['max_members']) {
        echo json_encode(['success' => false, 'message' => 'Le DARET a atteint son nombre maximum de membres']);
        exit;
    }
    
    // Trouver l'utilisateur à inviter
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user_to_invite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_to_invite) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    
    $user_id = $user_to_invite['id'];
    
    // Vérifier si l'utilisateur est déjà membre
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$daret_id, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Cet utilisateur est déjà membre du DARET']);
        exit;
    }
    
    // Ajouter l'utilisateur au DARET
    $stmt = $pdo->prepare("INSERT INTO daret_members (daret_id, user_id) VALUES (?, ?)");
    if ($stmt->execute([$daret_id, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Membre ajouté avec succès!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du membre']);
    }
}
?>