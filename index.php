<?php
include 'includes/config.php';
$page_title = "DARET - Application de Gestion d'Épargne Rotative";
$show_navbar = false;

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}
?>
<?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <h1 class="display-4">Bienvenue sur DARET</h1>
                <p class="lead">Gérez facilement vos systèmes d'épargne rotative en ligne.</p>
                <p>Créez ou rejoignez des groupes DARET, suivez les paiements et recevez votre tour en toute transparence.</p>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-user-plus me-2"></i>S'inscrire
                    </a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-hand-holding-usd display-1 text-primary mb-3"></i>
                    <h3>Épargne Collective Intelligente</h3>
                    <p class="text-muted">Rejoignez la communauté DARET dès aujourd'hui</p>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Créez un DARET</h5>
                        <p class="card-text">Créez votre propre groupe DARET et invitez des membres de confiance.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Rejoignez un DARET</h5>
                        <p class="card-text">Rejoignez un groupe DARET existant et commencez à épargner ensemble.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Gérez les Paiements</h5>
                        <p class="card-text">Suivez facilement les paiements et recevez votre tour en toute sécurité.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>