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
$page_title = "Gestion des Tours - " . htmlspecialchars($daret['name']) . " - DARET";
$show_navbar = true;

// Vérifier si l'utilisateur est admin
$stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
$stmt->execute([$daret_id, $user_id]);
$is_admin = $stmt->fetch(PDO::FETCH_ASSOC)['is_admin'];

// Récupérer les tours existants
$stmt = $pdo->prepare(" SELECT dr.*, u.username, u.full_name,
           (SELECT COUNT(*) FROM payments p WHERE p.daret_round_id = dr.id AND p.status = 'paid') as paid_count,
           (SELECT COUNT(*) FROM payments p WHERE p.daret_round_id = dr.id) as total_payers,
           (SELECT COUNT(*) FROM late_payments lp 
            JOIN payments p ON lp.payment_id = p.id 
            WHERE p.daret_round_id = dr.id AND lp.status = 'pending') as pending_penalties
    FROM daret_rounds dr
    JOIN users u ON dr.beneficiary_user_id = u.id
    WHERE dr.daret_id = ?
    ORDER BY dr.round_number ASC
");
$stmt->execute([$daret_id]);
$rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les membres pour la création manuelle de tours
$stmt = $pdo->prepare(" SELECT u.id, u.username, u.full_name
    FROM daret_members dm
    JOIN users u ON dm.user_id = u.id
    WHERE dm.daret_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$daret_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le dernier numéro de tour
$last_round_number = 0;
if (!empty($rounds)) {
    $last_round_number = max(array_column($rounds, 'round_number'));
}
?>
<?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="daret_details.php?id=<?php echo $daret_id; ?>"><?php echo htmlspecialchars($daret['name']); ?></a></li>
                        <li class="breadcrumb-item active">Gestion des Tours</li>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-list-ol me-2"></i>
                    Gestion des Tours - <?php echo htmlspecialchars($daret['name']); ?>
                </h2>
                <p class="text-muted">Planifiez et gérez les tours de votre DARET</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($is_admin && $daret['status'] == 'active'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoundModal">
                        <i class="fas fa-plus-circle me-2"></i>Créer un Tour
                    </button>
                <?php endif; ?>
                <a href="daret_details.php?id=<?php echo $daret_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques des tours -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4><?php echo count($rounds); ?></h4>
                        <p>Tours Créés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4>
                            <?php 
                            $completed_rounds = array_filter($rounds, function($round) {
                                return $round['paid_count'] == $round['total_payers'];
                            });
                            echo count($completed_rounds);
                            ?>
                        </h4>
                        <p>Tours Terminés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h4>
                            <?php 
                            $active_rounds = array_filter($rounds, function($round) {
                                return $round['paid_count'] > 0 && $round['paid_count'] < $round['total_payers'];
                            });
                            echo count($active_rounds);
                            ?>
                        </h4>
                        <p>Tours en Cours</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>
                            <?php 
                            $pending_rounds = array_filter($rounds, function($round) {
                                return $round['paid_count'] == 0;
                            });
                            echo count($pending_rounds);
                            ?>
                        </h4>
                        <p>Tours à Venir</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des tours -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Liste des Tours
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($rounds)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4>Aucun tour créé</h4>
                        <p class="text-muted">Commencez par créer le premier tour de votre DARET.</p>
                        <?php if ($is_admin): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoundModal">
                                <i class="fas fa-plus-circle me-2"></i>Créer le premier tour
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tour</th>
                                    <th>Bénéficiaire</th>
                                    <th>Montant Total</th>
                                    <th>Paiements</th>
                                    <th>Date d'échéance</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rounds as $round): ?>
                                    <?php
                                    $due_date = new DateTime($round['due_date']);
                                    $today = new DateTime();
                                    $is_overdue = $today > $due_date && $round['paid_count'] < $round['total_payers'];
                                    $progress_percentage = ($round['paid_count'] / $round['total_payers']) * 100;
                                    
                                    // Déterminer le statut
                                    if ($round['paid_count'] == $round['total_payers']) {
                                        $status = 'completed';
                                        $status_text = 'Terminé';
                                        $status_class = 'success';
                                    } elseif ($round['paid_count'] > 0) {
                                        $status = 'in_progress';
                                        $status_text = 'En cours';
                                        $status_class = 'warning';
                                    } else {
                                        $status = 'pending';
                                        $status_text = 'À venir';
                                        $status_class = 'info';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>Tour #<?php echo $round['round_number']; ?></strong>
                                            <?php if ($round['beneficiary_user_id'] == $user_id): ?>
                                                <span class="badge bg-success ms-1">
                                                    <i class="fas fa-gift me-1"></i>Vous
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($round['full_name']); ?></td>
                                        <td>
                                            <strong class="text-primary">
                                                <?php echo $daret['amount'] * ($round['total_payers'] ); ?> DH
                                            </strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php echo $round['paid_count']; ?>/<?php echo $round['total_payers']; ?>
                                                </div>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                                         style="width: <?php echo $progress_percentage; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($round['pending_penalties'] > 0): ?>
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    <?php echo $round['pending_penalties']; ?> pénalité(s)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $due_date->format('d/m/Y'); ?>
                                            <?php if ($is_overdue): ?>
                                                <br>
                                                <small class="text-danger">
                                                    <i class="fas fa-clock me-1"></i>
                                                    En retard
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewRoundDetails(<?php echo $round['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($is_admin): ?>
                                                    <button class="btn btn-outline-warning" 
                                                            onclick="editRound(<?php echo $round['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($round['paid_count'] == 0): ?>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteRound(<?php echo $round['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ordre des tours -->
        <?php if (!empty($rounds)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-sort-numeric-down me-2"></i>
                    Ordre des Tours
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($rounds as $round): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-<?php 
                                echo $round['paid_count'] == $round['total_payers'] ? 'success' : 
                                    ($round['paid_count'] > 0 ? 'warning' : 'secondary');
                            ?>">
                                <div class="card-body text-center">
                                    <div class="round-number-circle 
                                        <?php echo $round['paid_count'] == $round['total_payers'] ? 'bg-success' : 
                                              ($round['paid_count'] > 0 ? 'bg-warning' : 'bg-secondary'); ?>">
                                        <?php echo $round['round_number']; ?>
                                    </div>
                                    <h6 class="mt-2 mb-1"><?php echo htmlspecialchars($round['full_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($round['due_date'])); ?>
                                    </small>
                                    <div class="mt-2">
                                        <span class="badge bg-<?php 
                                            echo $round['paid_count'] == $round['total_payers'] ? 'success' : 
                                                ($round['paid_count'] > 0 ? 'warning' : 'info'); ?>">
                                            <?php echo $round['paid_count']; ?>/<?php echo $round['total_payers']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de création de tour -->
    <div class="modal fade" id="createRoundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Créer un Nouveau Tour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createRoundForm">
                    <div class="modal-body">
                        <input type="hidden" name="daret_id" value="<?php echo $daret_id; ?>">
                        
                        <div class="mb-3">
                            <label for="round_number" class="form-label">Numéro du Tour</label>
                            <input type="number" class="form-control" id="round_number" name="round_number" 
                                   value="<?php echo $last_round_number + 1; ?>" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="beneficiary_user_id" class="form-label">Bénéficiaire</label>
                            <select class="form-control" id="beneficiary_user_id" name="beneficiary_user_id" required>
                                <option value="">Sélectionner un membre</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?> (<?php echo $member['username']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="due_date" class="form-label">Date d'échéance</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnelle)</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer le Tour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal d'édition de tour -->
    <div class="modal fade" id="editRoundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Tour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editRoundForm">
                    <div class="modal-body" id="editRoundContent">
                        <!-- Contenu chargé via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .round-number-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        margin: 0 auto;
    }
    </style>

    <script>
    // Initialisation de la date d'échéance par défaut
    document.addEventListener('DOMContentLoaded', function() {
        // Définir la date d'échéance par défaut à 7 jours
        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 7);
        document.getElementById('due_date').valueAsDate = defaultDate;
    });

    // Création d'un tour
    document.getElementById('createRoundForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            daret_id: formData.get('daret_id'),
            round_number: formData.get('round_number'),
            beneficiary_user_id: formData.get('beneficiary_user_id'),
            due_date: formData.get('due_date'),
            description: formData.get('description')
        };

        fetch('api/create_single_round.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
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
            alert('Erreur lors de la création du tour');
        });
    });

    // Édition d'un tour
    function editRound(roundId) {
        fetch('api/get_round_for_edit.php?round_id=' + roundId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editRoundContent').innerHTML = data.html;
                    const modal = new bootstrap.Modal(document.getElementById('editRoundModal'));
                    modal.show();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors du chargement des données');
            });
    }

    // Soumission du formulaire d'édition
    document.getElementById('editRoundForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            round_id: formData.get('round_id'),
            beneficiary_user_id: formData.get('beneficiary_user_id'),
            due_date: formData.get('due_date'),
            description: formData.get('description')
        };

        fetch('api/update_round.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
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
            alert('Erreur lors de la modification du tour');
        });
    });

    // Suppression d'un tour
    function deleteRound(roundId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce tour? Cette action est irréversible.')) {
            fetch('api/delete_round.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ round_id: roundId })
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
                alert('Erreur lors de la suppression du tour');
            });
        }
    }

    // Voir les détails d'un tour
    function viewRoundDetails(roundId) {
        window.location.href = 'daret_payments.php?id=<?php echo $daret_id; ?>&round=' + roundId;
    }
    </script>

<?php include 'includes/footer.php'; ?>