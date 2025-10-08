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
    $round_number = isset($data['round_number']) ? intval($data['round_number']) : null;
    $user_id = $_SESSION['user_id'];
    
    // Vérifier si l'utilisateur est admin du DARET
    $stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
    $stmt->execute([$daret_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member || !$member['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas administrateur de ce DARET']);
        exit;
    }
    
    // Récupérer les paramètres de rend
    $stmt = $pdo->prepare("SELECT * FROM daret_profits WHERE daret_id = ?");
    $stmt->execute([$daret_id]);
    $profit_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profit_settings) {
        echo json_encode(['success' => false, 'message' => 'Paramètres de rend non configurés']);
        exit;
    }
    
    // Récupérer les informations du DARET
    $stmt = $pdo->prepare("SELECT amount FROM darets WHERE id = ?");
    $stmt->execute([$daret_id]);
    $daret = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les membres
    $stmt = $pdo->prepare("SELECT user_id FROM daret_members WHERE daret_id = ?");
    $stmt->execute([$daret_id]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    try {
        $pdo->beginTransaction();
        $distributed_amount = 0;
        $distribution_count = 0;
        
        foreach ($members as $member_id) {
            // Calculer le montant du rend
            if ($profit_settings['profit_type'] == 'percentage') {
                $profit_amount = $daret['amount'] * ($profit_settings['profit_value'] / 100);
            } else {
                $profit_amount = $profit_settings['profit_value'];
            }
            
            // Appliquer la méthode de calcul composé si nécessaire
            if ($profit_settings['calculation_method'] == 'compound') {
                // Récupérer le total des distributions précédentes pour ce membre
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) as total_distributed 
                    FROM profit_distributions 
                    WHERE daret_id = ? AND user_id = ? AND distribution_type = 'interest'
                ");
                $stmt->execute([$daret_id, $member_id]);
                $total_distributed = $stmt->fetch(PDO::FETCH_ASSOC)['total_distributed'];
                
                if ($profit_settings['profit_type'] == 'percentage') {
                    $profit_amount = ($daret['amount'] + $total_distributed) * ($profit_settings['profit_value'] / 100);
                }
            }
            
            // Arrondir à 2 décimales
            $profit_amount = round($profit_amount, 2);
            
            if ($profit_amount > 0) {
                // Enregistrer la distribution
                $stmt = $pdo->prepare("
                    INSERT INTO profit_distributions (daret_id, round_number, user_id, amount, distribution_date, distribution_type, description)
                    VALUES (?, ?, ?, ?, CURDATE(), 'interest', ?)
                ");
                
                $description = "Distribution de rend - " . 
                    ($round_number ? "Tour #$round_number" : "Fin du DARET") . 
                    " (" . $profit_settings['profit_value'] . 
                    ($profit_settings['profit_type'] == 'percentage' ? '%' : 'DH') . ")";
                
                $stmt->execute([$daret_id, $round_number, $member_id, $profit_amount, $description]);
                
                $distributed_amount += $profit_amount;
                $distribution_count++;
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Rends distribués avec succès! " .
                        "$distribution_count distributions effectuées pour un total de " .
                        "$distributed_amount DH."
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la distribution: ' . $e->getMessage()
        ]);
    }
}
?>