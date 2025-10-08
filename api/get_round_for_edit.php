<?php
include '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$round_id = intval($_GET['round_id']);
$user_id = $_SESSION['user_id'];

// Récupérer les informations du tour
$stmt = $pdo->prepare("
    SELECT dr.*, d.name as daret_name, u.full_name as beneficiary_name
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

// Vérifier que l'utilisateur est admin du DARET
$stmt = $pdo->prepare("SELECT is_admin FROM daret_members WHERE daret_id = ? AND user_id = ?");
$stmt->execute([$round['daret_id'], $user_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member || !$member['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupérer les membres pour la sélection
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name
    FROM daret_members dm
    JOIN users u ON dm.user_id = u.id
    WHERE dm.daret_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$round['daret_id']]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Générer le formulaire HTML
$html = '
    <input type="hidden" name="round_id" value="' . $round_id . '">
    
    <div class="mb-3">
        <label class="form-label">Tour #</label>
        <input type="text" class="form-control" value="' . $round['round_number'] . '" readonly>
        <div class="form-text">Le numéro de tour ne peut pas être modifié</div>
    </div>

    <div class="mb-3">
        <label for="beneficiary_user_id" class="form-label">Bénéficiaire</label>
        <select class="form-control" id="beneficiary_user_id" name="beneficiary_user_id" required>';

foreach ($members as $member) {
    $selected = $member['id'] == $round['beneficiary_user_id'] ? 'selected' : '';
    $html .= '<option value="' . $member['id'] . '" ' . $selected . '>' . 
             htmlspecialchars($member['full_name']) . ' (' . $member['username'] . ')</option>';
}

$html .= '
        </select>
    </div>

    <div class="mb-3">
        <label for="due_date" class="form-label">Date d\'échéance</label>
        <input type="date" class="form-control" id="due_date" name="due_date" 
               value="' . $round['due_date'] . '" required>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description (optionnelle)</label>
        <textarea class="form-control" id="description" name="description" rows="2">' . 
        htmlspecialchars($round['description'] ?? '') . '</textarea>
    </div>';

echo json_encode([
    'success' => true,
    'html' => $html
]);
?>