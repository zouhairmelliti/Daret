<?php
// Fonctions utilitaires pour l'application DARET

function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDaretById($pdo, $daret_id) {
    $stmt = $pdo->prepare("SELECT d.*, u.username as creator_name FROM darets d JOIN users u ON d.created_by = u.id WHERE d.id = ?");
    $stmt->execute([$daret_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDaretMembers($pdo, $daret_id) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, dm.join_date, dm.is_admin FROM daret_members dm JOIN users u ON dm.user_id = u.id WHERE dm.daret_id = ?");
    $stmt->execute([$daret_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isUserMemberOfDaret($pdo, $user_id, $daret_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daret_members WHERE user_id = ? AND daret_id = ?");
    $stmt->execute([$user_id, $daret_id]);
    return $stmt->fetchColumn() > 0;
}

function getUserDarets($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT d.*, dm.is_admin FROM darets d JOIN daret_members dm ON d.id = dm.daret_id WHERE dm.user_id = ? ORDER BY d.created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>