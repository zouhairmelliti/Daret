<?php
include '../includes/config.php';

// Fonction pour appliquer automatiquement les pénalités de retard
function applyLatePenalties($pdo) {
    // Récupérer les paiements en retard non pénalisés
    $stmt = $pdo->prepare(" SELECT p.id as payment_id, p.payer_user_id, dr.daret_id, dr.round_number,
               d.amount, DATEDIFF(CURDATE(), dr.due_date) as days_late
        FROM payments p
        JOIN daret_rounds dr ON p.daret_round_id = dr.id
        JOIN darets d ON dr.daret_id = d.id
        LEFT JOIN late_payments lp ON p.id = lp.payment_id
        WHERE p.status = 'pending' 
          AND dr.due_date < CURDATE() 
          AND lp.id IS NULL
        HAVING days_late > 0
    ");
    $stmt->execute();
    $late_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $applied_penalties = 0;
    
    foreach ($late_payments as $payment) {
        // Calculer la pénalité (ex: 5% du montant par semaine de retard)
        $weeks_late = ceil($payment['days_late'] / 7);
        $penalty_amount = $payment['amount'] * 0.05 * $weeks_late; // 5% par semaine
        $penalty_amount = min($penalty_amount, $payment['amount'] * 0.2); // Maximum 20%
        
        // Appliquer la pénalité
        $stmt = $pdo->prepare("
            INSERT INTO late_payments (payment_id, penalty_amount, penalty_reason, status)
            VALUES (?, ?, ?, 'pending')
        ");
        $reason = "Retard de paiement - " . $payment['days_late'] . " jour(s) de retard - Tour #" . $payment['round_number'];
        $stmt->execute([$payment['payment_id'], $penalty_amount, $reason]);
        
        $applied_penalties++;
    }
    
    return $applied_penalties;
}

// Exécuter automatiquement cette fonction une fois par jour via cron job
// ou l'appeler manuellement depuis l'interface admin
?>
