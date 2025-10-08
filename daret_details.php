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
$members = getDaretMembers($pdo, $daret_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($daret['name']); ?> - DARET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">DARET</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Tableau de bord</a>
                <a class="nav-link" href="api/logout.php">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><?php echo htmlspecialchars($daret['name']); ?></h4>
                        <span class="badge bg-<?php echo $daret['status'] == 'open' ? 'warning' : ($daret['status'] == 'active' ? 'success' : 'secondary'); ?>">
                            <?php echo $daret['status'] == 'open' ? 'Ouvert' : ($daret['status'] == 'active' ? 'Actif' : 'Terminé'); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($daret['description']); ?></p>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Montant:</strong> <?php echo $daret['amount']; ?> DH
                            </div>
                            <div class="col-md-6">
                                <strong>Fréquence:</strong> <?php echo $daret['frequency'] == 'weekly' ? 'Hebdomadaire' : 'Mensuelle'; ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Membres:</strong> <?php echo count($members); ?>/<?php echo $daret['max_members']; ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Créé par:</strong> <?php echo htmlspecialchars($daret['creator_name']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Membres du DARET</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Date d'adhésion</th>
                                        <th>Rôle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($member['full_name']); ?>
                                                <?php if ($member['id'] == $user_id): ?>
                                                    <span class="badge bg-info">Vous</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($member['join_date'])); ?></td>
                                            <td>
                                                <?php if ($member['is_admin']): ?>
                                                    <span class="badge bg-primary">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Membre</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($daret['status'] == 'open'): ?>
                            <button class="btn btn-success w-100 mb-2" onclick="startDaret(<?php echo $daret_id; ?>)">
                                Démarrer le DARET
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="inviteMember(<?php echo $daret_id; ?>)">
                            Inviter un membre
                        </button>

                        <button class="btn btn-outline-info w-100 mb-2" onclick="location.href='daret_payments.php?id=<?php echo $daret_id; ?>'">
                            <i class="fas fa-money-bill-wave me-1"></i>Gérer les paiements
                        </button>

                        <button class="btn btn-outline-primary w-100 mb-2" onclick="location.href='manage_rounds.php?id=<?php echo $daret_id; ?>'">
                            <i class="fas fa-list-ol me-1"></i>Gérer les Tours
                        </button>

                        <button class="btn btn-outline-warning w-100 mb-2" 
                                onclick="location.href='profit_settings.php?id=<?php echo $daret_id; ?>'">
                            <i class="fas fa-chart-line me-1"></i>Gestion des Rends
                        </button>
                        
                        <button class="btn btn-outline-info w-100 mb-2" onclick="viewPayments(<?php echo $daret_id; ?>)">
                            Voir les paiements
                        </button>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Prochain tour</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-center">Aucun tour en cours</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function startDaret(daretId) {
            if (confirm('Êtes-vous sûr de vouloir démarrer ce DARET? Cette action est irréversible.')) {
                fetch('api/start_daret.php', {
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
                    alert('Erreur lors du démarrage du DARET');
                });
            }
        }

        function inviteMember(daretId) {
            const username = prompt('Entrez le nom d\'utilisateur à inviter:');
            if (username) {
                fetch('api/invite_member.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        daret_id: daretId,
                        username: username 
                    })
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
                    alert('Erreur lors de l\'invitation');
                });
            }
        }

        function viewPayments(daretId) {
            alert('Fonctionnalité à implémenter');
        }
    </script>
</body>
</html>