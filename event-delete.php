<?php
// ====================================================================
// Event Deletion (DELETE Operation - DELETE Query 1)
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$eventId = intval($_GET['id'] ?? 0);

if ($eventId <= 0) {
    setFlashMessage('danger', 'Invalid Event ID provided.');
    header("Location: events.php");
    exit;
}

// Fetch event details to confirm existence and title
$stmt = $pdo->prepare("SELECT title FROM `events` WHERE event_id = :id");
$stmt->execute([':id' => $eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('danger', 'Event does not exist.');
    header("Location: events.php");
    exit;
}

try {
    // SQL Query #10 (DELETE): Delete an event record
    // Foreign key CASCADE automatically purges registrations and donations
    $deleteSql = "DELETE FROM `events` WHERE `event_id` = :id";
    $delStmt = $pdo->prepare($deleteSql);
    $delStmt->execute([':id' => $eventId]);

    setFlashMessage('success', "Event '#{$eventId} - {$event['title']}' was permanently removed! (DELETE Query executed with ON DELETE CASCADE)");
} catch (PDOException $e) {
    setFlashMessage('danger', "Failed to delete event: " . $e->getMessage());
}

header("Location: events.php");
exit;
