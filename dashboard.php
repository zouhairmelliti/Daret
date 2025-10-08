<?php
include 'includes/config.php';
include 'includes/functions.php';
redirectIfNotLoggedIn();

$page_title = "Tableau de Bord - DARET";
$show_navbar = true;

$user_id = $_SESSION['user_id'];
$user_darets = getUserDarets($pdo, $user_id);

// Statistiques
$stmt = $pdo->prepare("SELECT COUNT(*) as total_darets FROM daret_members WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_darets = $stmt->fetch(PDO::FETCH_ASSOC)['total_darets'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active_darets FROM daret_members dm JOIN darets d ON dm.daret_id = d.id WHERE dm.user_id = ? AND d.status = 'active'");
$stmt->execute([$user_id]);
$active_darets = $stmt->fetch(PDO::FETCH_ASSOC)['active_darets'];
?>
<?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Tableau de Bord
                </h2>
                <p class="text-muted">Bienvenue, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="create_daret.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Créer un DARET
                </a>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $total_darets; ?></h4>
                                <p>Total DARETs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $active_darets; ?></h4>
                                <p>DARETs Actifs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-play-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $total_darets - $active_darets; ?></h4>
                                <p>DARETs Ouverts</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 class="mb-3">Mes DARETs</h3>
        
        <div class="row" id="daretsList">
            <?php if (empty($user_darets)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4>Aucun DARET trouvé</h4>
                            <p class="text-muted">Vous n'êtes membre d'aucun DARET pour le moment.</p>
                            <a href="create_daret.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Créer votre premier DARET
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($user_darets as $daret): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($daret['name']); ?></h5>
                                    <?php if ($daret['is_admin']): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-crown me-1"></i>Admin
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($daret['description']); ?></p>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        <strong>Montant:</strong> <?php echo $daret['amount']; ?> DH
                                    </small>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <strong>Fréquence:</strong> 
                                        <?php echo $daret['frequency'] == 'weekly' ? 'Hebdomadaire' : 'Mensuelle'; ?>
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php echo $daret['status'] == 'open' ? 'warning' : ($daret['status'] == 'active' ? 'success' : 'secondary'); ?>">
                                        <?php echo $daret['status'] == 'open' ? 'Ouvert' : ($daret['status'] == 'active' ? 'Actif' : 'Terminé'); ?>
                                    </span>
                                    <a href="daret_details.php?id=<?php echo $daret['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>