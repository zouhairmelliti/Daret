<?php
include 'includes/config.php';
include 'includes/functions.php';
redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$daret_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Vérifier si l'utilisateur est membre de ce DARET
if (!isUserMemberOfDaret($pdo, $user_id, $daret_id)) {
    header("Location: dashboard.php");
    exit();
}

$daret = getDaretById($pdo, $daret_id);
$page_title = "Paiements - " . htmlspecialchars($daret['name']) . " - DARET";
$show_navbar = true;

// Récupérer les tours du DARET
$stmt = $pdo->prepare(" SELECT dr.*, u.username, u.full_name,
           (SELECT COUNT(*) FROM payments p WHERE p.daret_round_id = dr.id AND p.status = 'paid') as paid_count,
           (SELECT COUNT(*) FROM payments p WHERE p.daret_round_id = dr.id) as total_payers
    FROM daret_rounds dr
    JOIN users u ON dr.beneficiary_user_id = u.id
    WHERE dr.daret_id = ?
    ORDER BY dr.round_number ASC
");
$stmt->execute([$daret_id]);
$rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur est admin
$stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
$stmt->execute([$daret_id, $user_id]);
$is_admin = $stmt->fetch(PDO::FETCH_ASSOC)['is_admin'];
?>
<?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="daret_details.php?id=<?php echo $daret_id; ?>"><?php echo htmlspecialchars($daret['name']); ?></a></li>
                        <li class="breadcrumb-item active">Paiements</li>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Paiements - <?php echo htmlspecialchars($daret['name']); ?>
                </h2>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($is_admin && $daret['status'] == 'active' && empty($rounds)): ?>
                    <button class="btn btn-primary" onclick="createRounds(<?php echo $daret_id; ?>)">
                        <i class="fas fa-play-circle me-2"></i>Générer les tours
                    </button>
                <?php endif; ?>
                <a href="daret_details.php?id=<?php echo $daret_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>

        <?php if (empty($rounds)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                    <h4>Aucun tour planifié</h4>
                    <p class="text-muted">
                        <?php if ($is_admin): ?>
                            Les tours n'ont pas encore été générés. Cliquez sur "Générer les tours" pour commencer.
                        <?php else: ?>
                            Les tours n'ont pas encore été générés par l'administrateur du DARET.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($rounds as $round): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    Tour #<?php echo $round['round_number']; ?>
                                    <?php if ($round['beneficiary_user_id'] == $user_id): ?>
                                        <span class="badge bg-success float-end">
                                            <i class="fas fa-gift me-1"></i>Vous
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Bénéficiaire:</strong><br>
                                    <?php echo htmlspecialchars($round['full_name']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Montant par personne:</strong><br>
                                    <span class="text-primary"><?php echo $daret['amount']; ?> DH</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Total à recevoir:</strong><br>
                                    <span class="text-success"><?php echo $daret['amount'] * $round['total_payers'] ; ?> DH</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Paiements reçus:</strong><br>
                                    <?php echo $round['paid_count']; ?>/<?php echo $round['total_payers'] ; ?>
                                    <div class="progress mt-1">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo ($round['paid_count'] / $round['total_payers'] ) * 100; ?>%">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong>Date d'échéance:</strong><br>
                                    <?php echo date('d/m/Y', strtotime($round['due_date'])); ?>
                                    <?php 
                                    $due_date = new DateTime($round['due_date']);
                                    $today = new DateTime();
                                    if ($today > $due_date && $round['paid_count'] < $round['total_payers']): 
                                    ?>
                                        <span class="badge bg-danger ms-1">En retard</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong>Statut:</strong><br>
                                    <?php if ($round['paid_count'] == $round['total_payers']): ?>
                                        <span class="badge bg-success">Terminé</span>
                                    <?php elseif ($round['paid_count'] > 0 && $round['paid_count'] < $round['total_payers']) : ?>
                                        <span class="badge bg-warning">En cours</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">À venir</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-outline-primary w-100" 
                                        onclick="viewRoundDetails(<?php echo $round['id']; ?>)">
                                    <i class="fas fa-list me-1"></i>Détails des paiements
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal pour les détails du tour -->
    <div class="modal fade" id="roundDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails des paiements - Tour <span id="modalRoundNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="roundDetailsContent">
                    <!-- Contenu chargé via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
    function createRounds(daretId) {
        if (confirm('Êtes-vous sûr de vouloir générer les tours? Cette action est irréversible.')) {
            fetch('api/create_rounds.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ daret_id: daretId })
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
                alert('Erreur lors de la génération des tours');
            });
        }
    }

    function viewRoundDetails(roundId) {
        fetch('api/get_round_details.php?round_id=' + roundId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalRoundNumber').textContent = '#' + data.round.round_number;
                    document.getElementById('roundDetailsContent').innerHTML = data.html;
                    const modal = new bootstrap.Modal(document.getElementById('roundDetailsModal'));
                    modal.show();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors du chargement des détails');
            });
    }

    function markPaymentAsPaid(paymentId, roundId) {
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
                    // Recharger les détails du tour
                    viewRoundDetails(roundId);
                    // Recharger la page principale après un délai
                    setTimeout(() => location.reload(), 1000);
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