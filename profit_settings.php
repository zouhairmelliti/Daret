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

// Vérifier si l'utilisateur est admin de ce DARET
$stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
$stmt->execute([$daret_id, $user_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member || !$member['is_admin']) {
    header("Location: daret_details.php?id=" . $daret_id);
    exit();
}

$daret = getDaretById($pdo, $daret_id);
$page_title = "Paramètres de Rend - " . htmlspecialchars($daret['name']) . " - DARET";
$show_navbar = true;

// Récupérer les paramètres de rend actuels
$stmt = $pdo->prepare("SELECT * FROM daret_profits WHERE daret_id = ?");
$stmt->execute([$daret_id]);
$profit_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer l'historique des distributions
$stmt = $pdo->prepare("
    SELECT pd.*, u.full_name, u.username
    FROM profit_distributions pd
    JOIN users u ON pd.user_id = u.id
    WHERE pd.daret_id = ?
    ORDER BY pd.distribution_date DESC, pd.created_at DESC
    LIMIT 50
");
$stmt->execute([$daret_id]);
$distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les pénalités en attente
$stmt = $pdo->prepare("
    SELECT lp.*, p.payer_user_id, u.full_name, u.username,
           dr.round_number, d.name as daret_name
    FROM late_payments lp
    JOIN payments p ON lp.payment_id = p.id
    JOIN daret_rounds dr ON p.daret_round_id = dr.id
    JOIN darets d ON dr.daret_id = d.id
    JOIN users u ON p.payer_user_id = u.id
    WHERE dr.daret_id = ? AND lp.status = 'pending'
    ORDER BY lp.applied_date DESC
");
$stmt->execute([$daret_id]);
$pending_penalties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="daret_details.php?id=<?php echo $daret_id; ?>"><?php echo htmlspecialchars($daret['name']); ?></a></li>
                        <li class="breadcrumb-item active">Gestion des Rends</li>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-chart-line me-2"></i>
                    Gestion des Rends - <?php echo htmlspecialchars($daret['name']); ?>
                </h2>
                <p class="text-muted">Configurez les paramètres de rend et gérez les distributions</p>
            </div>
        </div>

        <!-- Paramètres de rend -->
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Paramètres de Rend
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($profit_settings): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Paramètres actuels configurés
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Type de rend:</strong><br>
                                    <?php echo $profit_settings['profit_type'] == 'fixed' ? 'Montant fixe' : 'Pourcentage'; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Valeur:</strong><br>
                                    <?php echo $profit_settings['profit_value']; ?>
                                    <?php echo $profit_settings['profit_type'] == 'percentage' ? '%' : 'DH'; ?>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <strong>Méthode de calcul:</strong><br>
                                    <?php echo $profit_settings['calculation_method'] == 'simple' ? 'Simple' : 'Composé'; ?>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <strong>Fréquence de distribution:</strong><br>
                                    <?php echo $profit_settings['distribution_frequency'] == 'per_round' ? 'Par tour' : 'Fin du DARET'; ?>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button class="btn btn-warning" onclick="showEditForm()">
                                    <i class="fas fa-edit me-2"></i>Modifier les paramètres
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucun paramètre de rend configuré
                            </div>
                        <?php endif; ?>

                        <!-- Formulaire de configuration (caché par défaut) -->
                        <div id="profitSettingsForm" style="display: <?php echo $profit_settings ? 'none' : 'block'; ?>;">
                            <form id="configureProfitForm">
                                <input type="hidden" name="daret_id" value="<?php echo $daret_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="profit_type" class="form-label">Type de rend</label>
                                            <select class="form-control" id="profit_type" name="profit_type" required>
                                                <option value="fixed">Montant fixe</option>
                                                <option value="percentage">Pourcentage</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="profit_value" class="form-label">Valeur</label>
                                            <input type="number" class="form-control" id="profit_value" name="profit_value" 
                                                   step="0.01" min="0" required>
                                            <div class="form-text" id="valueHelp">
                                                Entrez le montant fixe ou le pourcentage
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calculation_method" class="form-label">Méthode de calcul</label>
                                            <select class="form-control" id="calculation_method" name="calculation_method" required>
                                                <option value="simple">Simple</option>
                                                <option value="compound">Composé</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="distribution_frequency" class="form-label">Fréquence de distribution</label>
                                            <select class="form-control" id="distribution_frequency" name="distribution_frequency" required>
                                                <option value="per_round">À chaque tour</option>
                                                <option value="end_of_daret">À la fin du DARET</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (optionnelle)</label>
                                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $profit_settings ? 'Mettre à jour' : 'Configurer'; ?> les paramètres
                                </button>
                                <?php if ($profit_settings): ?>
                                    <button type="button" class="btn btn-secondary" onclick="hideEditForm()">
                                        Annuler
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Calculatrice de rend -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calculator me-2"></i>
                            Simulation de Rend
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="simulation_amount" class="form-label">Montant à simuler (DH)</label>
                            <input type="number" class="form-control" id="simulation_amount" 
                                   value="<?php echo $daret['amount']; ?>" min="1">
                        </div>
                        <div class="mb-3">
                            <label for="simulation_periods" class="form-label">Nombre de périodes</label>
                            <input type="number" class="form-control" id="simulation_periods" value="12" min="1">
                        </div>
                        <button class="btn btn-outline-info w-100" onclick="calculateProfit()">
                            <i class="fas fa-calculator me-2"></i>Calculer
                        </button>
                        <div id="simulationResult" class="mt-3 p-3 bg-light rounded" style="display: none;">
                            <h6>Résultat de la simulation:</h6>
                            <div id="resultContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pénalités en attente -->
        <?php if (!empty($pending_penalties)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Pénalités de Retard en Attente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Membre</th>
                                        <th>Tour</th>
                                        <th>Montant de la pénalité</th>
                                        <th>Raison</th>
                                        <th>Date d'application</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_penalties as $penalty): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($penalty['full_name']); ?></td>
                                            <td>Tour #<?php echo $penalty['round_number']; ?></td>
                                            <td>
                                                <strong class="text-danger"><?php echo $penalty['penalty_amount']; ?> DH</strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($penalty['penalty_reason']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($penalty['applied_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm" 
                                                        onclick="markPenaltyPaid(<?php echo $penalty['id']; ?>)">
                                                    <i class="fas fa-check me-1"></i>Marquer payé
                                                </button>
                                                <button class="btn btn-secondary btn-sm" 
                                                        onclick="waivePenalty(<?php echo $penalty['id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>Annuler
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historique des distributions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historique des Distributions
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($distributions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>Aucune distribution enregistrée</h5>
                                <p class="text-muted">Les distributions apparaîtront ici une fois configurées.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Membre</th>
                                            <th>Type</th>
                                            <th>Tour</th>
                                            <th>Montant</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($distributions as $distribution): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($distribution['distribution_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($distribution['full_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $type_badge = [
                                                        'interest' => 'bg-info',
                                                        'bonus' => 'bg-success', 
                                                        'penalty' => 'bg-danger'
                                                    ];
                                                    $type_text = [
                                                        'interest' => 'Intérêt',
                                                        'bonus' => 'Bonus',
                                                        'penalty' => 'Pénalité'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $type_badge[$distribution['distribution_type']]; ?>">
                                                        <?php echo $type_text[$distribution['distribution_type']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $distribution['round_number'] ? 'Tour #' . $distribution['round_number'] : '-'; ?>
                                                </td>
                                                <td>
                                                    <strong class="<?php echo $distribution['distribution_type'] == 'penalty' ? 'text-danger' : 'text-success'; ?>">
                                                        <?php echo $distribution['amount']; ?> DH
                                                    </strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($distribution['description']); ?></td>
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
    // Gestion de l'affichage du formulaire
    function showEditForm() {
        document.getElementById('profitSettingsForm').style.display = 'block';
    }

    function hideEditForm() {
        document.getElementById('profitSettingsForm').style.display = 'none';
    }

    // Simulation de calcul
    function calculateProfit() {
        const amount = parseFloat(document.getElementById('simulation_amount').value);
        const periods = parseInt(document.getElementById('simulation_periods').value);
        const profitType = document.getElementById('profit_type').value;
        const profitValue = parseFloat(document.getElementById('profit_value').value || 0);
        const calculationMethod = document.getElementById('calculation_method').value;

        if (!amount || !periods || !profitValue) {
            alert('Veuillez remplir tous les champs de simulation');
            return;
        }

        let totalProfit = 0;
        let details = '';

        if (calculationMethod === 'simple') {
            const profitPerPeriod = profitType === 'percentage' ? 
                (amount * profitValue / 100) : profitValue;
            totalProfit = profitPerPeriod * periods;
            details = `Profit par période: ${profitPerPeriod.toFixed(2)} DH<br>`;
        } else {
            // Calcul composé
            let currentAmount = amount;
            for (let i = 1; i <= periods; i++) {
                const periodProfit = profitType === 'percentage' ? 
                    (currentAmount * profitValue / 100) : profitValue;
                currentAmount += periodProfit;
                totalProfit += periodProfit;
                details += `Période ${i}: ${periodProfit.toFixed(2)} DH<br>`;
            }
        }

        const resultDiv = document.getElementById('simulationResult');
        const resultContent = document.getElementById('resultContent');
        
        resultContent.innerHTML = `
            <strong>Montant initial:</strong> ${amount.toFixed(2)} DH<br>
            <strong>Périodes:</strong> ${periods}<br>
            <strong>Profit total:</strong> <span class="text-success">${totalProfit.toFixed(2)} DH</span><br>
            <strong>Montant final:</strong> <span class="text-primary">${(amount + totalProfit).toFixed(2)} DH</span><br>
            <hr>
            <small>${details}</small>
        `;
        resultDiv.style.display = 'block';
    }

    // Gestion du formulaire de configuration
    document.getElementById('configureProfitForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            daret_id: formData.get('daret_id'),
            profit_type: formData.get('profit_type'),
            profit_value: formData.get('profit_value'),
            calculation_method: formData.get('calculation_method'),
            distribution_frequency: formData.get('distribution_frequency'),
            description: formData.get('description')
        };

        fetch('api/configure_profit.php', {
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
            alert('Erreur lors de la configuration');
        });
    });

    // Gestion des pénalités
    function markPenaltyPaid(penaltyId) {
        if (confirm('Marquer cette pénalité comme payée?')) {
            fetch('api/manage_penalty.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    penalty_id: penaltyId,
                    action: 'mark_paid'
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
                alert('Erreur lors du traitement');
            });
        }
    }

    function waivePenalty(penaltyId) {
        if (confirm('Annuler cette pénalité? Cette action est irréversible.')) {
            fetch('api/manage_penalty.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    penalty_id: penaltyId,
                    action: 'waive'
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
                alert('Erreur lors du traitement');
            });
        }
    }

    // Mise à jour de l'aide en fonction du type sélectionné
    document.getElementById('profit_type').addEventListener('change', function() {
        const helpText = document.getElementById('valueHelp');
        if (this.value === 'percentage') {
            helpText.textContent = 'Entrez le pourcentage (ex: 5 pour 5%)';
        } else {
            helpText.textContent = 'Entrez le montant fixe en DH';
        }
    });
    </script>

<?php include 'includes/footer.php'; ?>