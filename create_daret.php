<?php
include 'includes/config.php';
include 'includes/functions.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un DARET - DARET</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Créer un nouveau DARET</h3>
                    </div>
                    <div class="card-body">
                        <form id="createDaretForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du DARET</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Montant par participant (DH)</label>
                                        <input type="number" class="form-control" id="amount" name="amount" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_members" class="form-label">Nombre maximum de membres</label>
                                        <input type="number" class="form-control" id="max_members" name="max_members" min="2" max="50" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Fréquence de paiement</label>
                                <select class="form-control" id="frequency" name="frequency" required>
                                    <option value="weekly">Hebdomadaire</option>
                                    <option value="monthly">Mensuelle</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Créer le DARET</button>
                        </form>
                        <div id="message" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Gestion de la création de DARET
        document.getElementById('createDaretForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                name: formData.get('name'),
                description: formData.get('description'),
                amount: formData.get('amount'),
                max_members: formData.get('max_members'),
                frequency: formData.get('frequency')
            };
            
            fetch('api/create_daret.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                if (data.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('message').innerHTML = `<div class="alert alert-danger">Erreur lors de la création du DARET</div>`;
            });
        });
    </script>
</body>
</html>