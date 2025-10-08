<?php
include 'includes/config.php';
include 'includes/functions.php';
redirectIfNotLoggedIn();

$page_title = "Mes Paiements - DARET";
$show_navbar = true;

$user_id = $_SESSION['user_id'];

// Récupérer les paiements à effectuer
$stmt = $pdo->prepare(" SELECT p.*, dr.round_number, dr.due_date, d.name as daret_name, 
           u.username as beneficiary_name, u.full_name as beneficiary_full_name,
           d.amount as due_amount
    FROM payments p
    JOIN daret_rounds dr ON p.daret_round_id = dr.id
    JOIN darets d ON dr.daret_id = d.id
    JOIN users u ON dr.beneficiary_user_id = u.id
    WHERE p.payer_user_id = ? AND p.status = 'pending'
    ORDER BY dr.due_date ASC
");
$stmt->execute([$user_id]);
$pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer l'historique des paiements
$stmt = $pdo->prepare(" SELECT p.*, dr.round_number, dr.due_date, d.name as daret_name,
           u.username as beneficiary_name, u.full_name as beneficiary_full_name
    FROM payments p
    JOIN daret_rounds dr ON p.daret_round_id = dr.id
    JOIN darets d ON dr.daret_id = d.id
    JOIN users u ON dr.beneficiary_user_id = u.id
    WHERE p.payer_user_id = ? AND p.status = 'paid'
    ORDER BY p.payment_date DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les paiements que l'utilisateur doit recevoir
$stmt = $pdo->prepare(" SELECT dr.*, d.name as daret_name, d.amount,
           COUNT(p.id) as total_payers,
           SUM(CASE WHEN p.status = 'paid' THEN 1 ELSE 0 END) as paid_count
    FROM daret_rounds dr
    JOIN darets d ON dr.daret_id = d.id
    LEFT JOIN payments p ON dr.id = p.daret_round_id
    WHERE dr.beneficiary_user_id = ?
    GROUP BY dr.id
    ORDER BY dr.due_date ASC
");
$stmt->execute([$user_id]);
$receivables = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Gestion des Paiements
                </h2>
                <p class="text-muted">Gérez vos paiements et suivez votre historique</p>
            </div>
        </div>

        <!-- Paiements en attente -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Paiements en Attente
                            <span class="badge bg-dark"><?php echo count($pending_payments); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_payments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>Aucun paiement en attente</h5>
                                <p class="text-muted">Vous êtes à jour dans tous vos paiements!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>DARET</th>
                                            <th>Tour</th>
                                            <th>Bénéficiaire</th>
                                            <th>Montant</th>
                                            <th>Date d'échéance</th>
                                            <th>Jours restants</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_payments as $payment): ?>
                                            <?php
                                            $due_date = new DateTime($payment['due_date']);
                                            $today = new DateTime();
                                            $days_remaining = $today->diff($due_date)->days;
                                            $is_overdue = $today > $due_date;
                                            ?>
                                            <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                                <td><?php echo htmlspecialchars($payment['daret_name']); ?></td>
                                                <td>Tour #<?php echo $payment['round_number']; ?></td>
                                                <td><?php echo htmlspecialchars($payment['beneficiary_full_name']); ?></td>
                                                <td>
                                                    <strong class="text-primary"><?php echo $payment['due_amount']; ?> DH</strong>
                                                </td>
                                                <td>
                                                    <?php echo $due_date->format('d/m/Y'); ?>
                                                    <?php if ($is_overdue): ?>
                                                        <span class="badge bg-danger ms-1">En retard</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($is_overdue): ?>
                                                        <span class="text-danger">-<?php echo $days_remaining; ?>j</span>
                                                    <?php else: ?>
                                                        <span class="text-success"><?php echo $days_remaining; ?>j</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-success btn-sm" 
                                                            onclick="markAsPaid(<?php echo $payment['id']; ?>)">
                                                        <i class="fas fa-check me-1"></i>Marquer payé
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paiements à recevoir -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-download me-2"></i>
                            Paiements à Recevoir
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($receivables)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>Aucun paiement à recevoir</h5>
                                <p class="text-muted">Vous n'êtes pas bénéficiaire d'un tour pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>DARET</th>
                                            <th>Tour</th>
                                            <th>Montant total</th>
                                            <th>Paiements reçus</th>
                                            <th>Date d'échéance</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($receivables as $receivable): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($receivable['daret_name']); ?></td>
                                                <td>Tour #<?php echo $receivable['round_number']; ?></td>
                                                <td>
                                                    <strong class="text-success">
                                                        <?php echo $receivable['amount'] * ($receivable['total_payers'] ); ?> DH
                                                    </strong>
                                                </td>
                                                <td>
                                                    <?php echo $receivable['paid_count']; ?>/<?php echo $receivable['total_payers'] ; ?>
                                                    <div class="progress mt-1" style="height: 5px;">
                                                        <div class="progress-bar bg-success" 
                                                             style="width: <?php echo ($receivable['paid_count'] / $receivable['total_payers'] ) * 100; ?>%">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($receivable['due_date'])); ?></td>
                                                <td>
                                                    <?php if ($receivable['paid_count'] == $receivable['total_payers'] ): ?>
                                                        <span class="badge bg-success">Complet</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">En cours</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des paiements -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historique des Paiements
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($payment_history)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                <h5>Aucun historique de paiement</h5>
                                <p class="text-muted">Vos paiements effectués apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>DARET</th>
                                            <th>Tour</th>
                                            <th>Bénéficiaire</th>
                                            <th>Montant</th>
                                            <th>Date de paiement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_history as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['daret_name']); ?></td>
                                                <td>Tour #<?php echo $payment['round_number']; ?></td>
                                                <td><?php echo htmlspecialchars($payment['beneficiary_full_name']); ?></td>
                                                <td>
                                                    <strong class="text-success"><?php echo $payment['amount']; ?> DH</strong>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y H:i', strtotime($payment['payment_date'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function markAsPaid(paymentId) {
        if (confirm('Êtes-vous sûr de vouloir marquer ce paiement comme effectué?')) {
            fetch('api/mark_payment_paid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ payment_id: paymentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors du marquage du paiement');
            });
        }
    }
    </script>

<?php include 'includes/footer.php'; ?>