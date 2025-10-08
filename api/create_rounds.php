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
    
    // Vérifier si le DARET est actif
    $stmt = $pdo->prepare("SELECT status, amount, frequency FROM darets WHERE id = ?");
    $stmt->execute([$daret_id]);
    $daret = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($daret['status'] != 'active') {
        echo json_encode(['success' => false, 'message' => 'Le DARET doit être actif pour créer des tours']);
        exit;
    }
    
    // Récupérer les membres du DARET
    $stmt = $pdo->prepare("SELECT user_id FROM daret_members WHERE daret_id = ? ORDER BY join_date");
    $stmt->execute([$daret_id]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($members) < 2) {
        echo json_encode(['success' => false, 'message' => 'Le DARET doit avoir au moins 2 membres']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Mélanger aléatoirement l'ordre des tours
        shuffle($members);
        
        // Créer les tours pour chaque membre
        $start_date = new DateTime();
        
        foreach ($members as $position => $beneficiary_id) {
            $round_number = $position + 1;
            
            // Calculer la date d'échéance
            $due_date = clone $start_date;
            if ($daret['frequency'] == 'weekly') {
                $due_date->modify("+" . ($position * 7) . " days");
            } else {
                $due_date->modify("+" . $position . " months");
            }
            
            // Créer le tour
            $stmt = $pdo->prepare("INSERT INTO daret_rounds (daret_id, round_number, beneficiary_user_id, amount, round_date, due_date) VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([$daret_id, $round_number, $beneficiary_id, $daret['amount'], $due_date->format('Y-m-d')]);
            $round_id = $pdo->lastInsertId();
            
            // Créer les paiements pour tous les membres sauf le bénéficiaire
            foreach ($members as $payer_id) {
                if ($payer_id != $beneficiary_id) {
                    $stmt = $pdo->prepare("INSERT INTO payments (daret_round_id, payer_user_id, amount, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$round_id, $payer_id, $daret['amount']]);
                }
            }
            
            // Stocker l'ordre du tour
            $stmt = $pdo->prepare("INSERT INTO daret_round_order (daret_id, user_id, round_number, position) VALUES (?, ?, ?, ?)");
            $stmt->execute([$daret_id, $beneficiary_id, $round_number, $position + 1]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tours créés avec succès! ' . count($members) . ' tours ont été planifiés.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la création des tours: ' . $e->getMessage()
        ]);
    }
}
?>