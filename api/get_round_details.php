<?php
include '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$round_id = intval($_GET['round_id']);
$user_id = $_SESSION['user_id'];

// Récupérer les détails du tour
$stmt = $pdo->prepare("
    SELECT dr.*, d.name as daret_name, d.amount, u.full_name as beneficiary_name
    FROM daret_rounds dr
    JOIN darets d ON dr.daret_id = d.id
    JOIN users u ON dr.beneficiary_user_id = u.id
    WHERE dr.id = ?
");
$stmt->execute([$round_id]);
$round = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$round) {
    echo json_encode(['success' => false, 'message' => 'Tour non trouvé']);
    exit;
}

// Vérifier que l'utilisateur est membre du DARET
$stmt = $pdo->prepare("SELECT COUNT(*) FROM daret_members WHERE daret_id = ? AND user_id = ?");
$stmt->execute([$round['daret_id'], $user_id]);
if ($stmt->fetchColumn() == 0) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupérer les paiements de ce tour
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.full_name,
           CASE WHEN p.payer_user_id = ? THEN 1 ELSE 0 END as is_current_user
    FROM payments p
    JOIN users u ON p.payer_user_id = u.id
    WHERE p.daret_round_id = ?
    ORDER BY p.status DESC, u.full_name ASC
");
$stmt->execute([$user_id, $round_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Générer le HTML
$html = '
    <div class="mb-4">
        <h6>Bénéficiaire: <strong>' . htmlspecialchars($round['beneficiary_name']) . '</strong></h6>
        <h6>Montant: <strong class="text-primary">' . $round['amount'] . ' DH par personne</strong></h6>
        <h6>Date d\'échéance: <strong>' . date('d/m/Y', strtotime($round['due_date'])) . '</strong></h6>
    </div>

    <h6>Liste des paiements:</h6>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Payeur</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date de paiement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

foreach ($payments as $payment) {
    $status_badge = $payment['status'] == 'paid' ? 
        '<span class="badge bg-success">Payé</span>' : 
        '<span class="badge bg-warning">En attente</span>';
    
    $payment_date = $payment['payment_date'] ? 
        date('d/m/Y H:i', strtotime($payment['payment_date'])) : 
        '-';
    
    $action_button = '';
    if ($payment['is_current_user'] && $payment['status'] == 'pending') {
        $action_button = '<button class="btn btn-success btn-sm" onclick="markPaymentAsPaid(' . $payment['id'] . ', ' . $round_id . ')">Marquer payé</button>';
    } elseif ($payment['is_current_user'] && $payment['status'] == 'paid') {
        $action_button = '<span class="text-success"><i class="fas fa-check me-1"></i>Payé par vous</span>';
    } else {
        $action_button = '-';
    }
    
    $html .= '
        <tr>
            <td>' . htmlspecialchars($payment['full_name']) . '</td>
            <td>' . $payment['amount'] . ' DH</td>
            <td>' . $status_badge . '</td>
            <td>' . $payment_date . '</td>
            <td>' . $action_button . '</td>
        </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>';

echo json_encode([
    'success' => true,
    'round' => $round,
    'html' => $html
]);
?>