<?php
// ====================================================================
// Volunteer Registration Management (READ, UPDATE Query 2, DELETE Query 2)
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$pageTitle = "Volunteer Registrations Management";

// Handle DELETE Registration (DELETE Query 2)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['reg_id'])) {
    $regId = intval($_GET['reg_id']);
    try {
        // SQL Query: DELETE a volunteer registration
        $delSql = "DELETE FROM `registrations` WHERE `registration_id` = :id";
        $stmt = $pdo->prepare($delSql);
        $stmt->execute([':id' => $regId]);
        setFlashMessage('success', "Volunteer registration #{$regId} cancelled and deleted successfully! (DELETE Query executed)");
    } catch (PDOException $e) {
        setFlashMessage('danger', "Error deleting registration: " . $e->getMessage());
    }
    $redirectUrl = isset($_GET['return_event']) ? 'event-details.php?id=' . intval($_GET['return_event']) : 'registration-manage.php';
    header("Location: " . $redirectUrl);
    exit;
}

// Handle UPDATE Registration Status (UPDATE Query 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_registration') {
    $regId = intval($_POST['registration_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'Registered');
    $newRole = trim($_POST['role_type'] ?? 'General Volunteer');
    $notes = trim($_POST['notes'] ?? '');

    try {
        // SQL Query #9 (UPDATE 2): Update volunteer attendance status and role
        $updateSql = "
            UPDATE `registrations` 
            SET `status` = :status, `role_type` = :role_type, `notes` = :notes 
            WHERE `registration_id` = :id
        ";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            ':status'    => $newStatus,
            ':role_type' => $newRole,
            ':notes'     => $notes,
            ':id'        => $regId
        ]);
        setFlashMessage('success', "Registration #{$regId} updated to '{$newStatus}'! (UPDATE Query #2 executed)");
    } catch (PDOException $e) {
        setFlashMessage('danger', "Error updating registration: " . $e->getMessage());
    }
    $redirectUrl = !empty($_POST['return_event']) ? 'event-details.php?id=' . intval($_POST['return_event']) : 'registration-manage.php';
    header("Location: " . $redirectUrl);
    exit;
}

// Optional: If editing a specific registration
$editReg = null;
if (isset($_GET['reg_id'])) {
    $regId = intval($_GET['reg_id']);
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name, u.email, e.title AS event_title, e.event_id 
        FROM `registrations` r
        INNER JOIN `users` u ON r.user_id = u.user_id
        INNER JOIN `events` e ON r.event_id = e.event_id
        WHERE r.registration_id = :id
    ");
    $stmt->execute([':id' => $regId]);
    $editReg = $stmt->fetch();
}

// Fetch all registrations with multi-table JOIN
$filterEvent = intval($_GET['event_id'] ?? 0);
$listSql = "
    SELECT 
        r.registration_id,
        r.event_id,
        r.role_type,
        r.status,
        r.registered_at,
        r.notes,
        e.title AS event_title,
        e.area,
        e.event_date,
        u.user_id,
        u.full_name,
        u.email,
        u.phone
    FROM `registrations` r
    INNER JOIN `events` e ON r.event_id = e.event_id
    INNER JOIN `users` u ON r.user_id = u.user_id
";
$params = [];
if ($filterEvent > 0) {
    $listSql .= " WHERE r.event_id = :event_id";
    $params[':event_id'] = $filterEvent;
}
$listSql .= " ORDER BY r.registered_at DESC";

$stmt = $pdo->prepare($listSql);
$stmt->execute($params);
$allRegistrations = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-users-cog text-primary me-2"></i>Volunteer Registrations Management</h3>
        <p class="text-muted mb-0">Live Update (Attendance, Roles) and Delete (Cancellation) for Lab Viva Demo.</p>
    </div>
    <div>
        <a href="events.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-calendar-alt me-1"></i>Back to Events
        </a>
    </div>
</div>

<!-- If editing a registration, show edit modal / card -->
<?php if ($editReg): ?>
    <div class="card shadow-sm border-0 border-start border-4 border-primary mb-4 bg-white">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-user-edit me-2"></i>Edit Registration #<?= e($editReg['registration_id']); ?> (UPDATE Query 2)
            </h5>
            <a href="registration-manage.php" class="btn btn-sm btn-outline-secondary">Close Edit Form</a>
        </div>
        <div class="card-body p-4">
            <form action="registration-manage.php" method="POST">
                <input type="hidden" name="action" value="update_registration">
                <input type="hidden" name="registration_id" value="<?= e($editReg['registration_id']); ?>">
                <?php if (isset($_GET['event_id'])): ?>
                    <input type="hidden" name="return_event" value="<?= intval($_GET['event_id']); ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Volunteer Name</label>
                        <input type="text" class="form-control" value="<?= e($editReg['full_name']); ?> (<?= e($editReg['email']); ?>)" readonly disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Event</label>
                        <input type="text" class="form-control" value="<?= e($editReg['event_title']); ?>" readonly disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Status (UPDATE)</label>
                        <select name="status" class="form-select fw-bold">
                            <option value="Registered" <?= $editReg['status'] === 'Registered' ? 'selected' : ''; ?>>Registered</option>
                            <option value="Confirmed" <?= $editReg['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Attended" <?= $editReg['status'] === 'Attended' ? 'selected' : ''; ?>>Attended (Participated)</option>
                            <option value="Cancelled" <?= $editReg['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Role Assignment</label>
                        <select name="role_type" class="form-select">
                            <option value="General Volunteer" <?= $editReg['role_type'] === 'General Volunteer' ? 'selected' : ''; ?>>General Volunteer</option>
                            <option value="Event Coordinator" <?= $editReg['role_type'] === 'Event Coordinator' ? 'selected' : ''; ?>>Event Coordinator</option>
                            <option value="First Aid / Medic" <?= $editReg['role_type'] === 'First Aid / Medic' ? 'selected' : ''; ?>>First Aid / Medic</option>
                            <option value="Logistics & Distribution" <?= $editReg['role_type'] === 'Logistics & Distribution' ? 'selected' : ''; ?>>Logistics & Distribution</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Volunteer Notes</label>
                        <input type="text" name="notes" class="form-control" value="<?= e($editReg['notes']); ?>" placeholder="Add volunteer feedback or timing notes...">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fas fa-check-circle me-1"></i> Save Changes (Run UPDATE Query)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Registrations List Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-list text-success me-2"></i>All Volunteer Registrations (<?= count($allRegistrations); ?> records)
        </h5>
        <span class="badge bg-secondary">Multi-Table JOIN: registrations + events + users</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($allRegistrations)): ?>
            <div class="p-4 text-center text-muted">
                No volunteer registrations found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Volunteer</th>
                            <th>Event & Area</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Enrolled</th>
                            <th class="text-end">Actions (UPDATE / DELETE)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRegistrations as $r): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= e($r['registration_id']); ?></td>
                                <td>
                                    <strong><?= e($r['full_name']); ?></strong>
                                    <div class="small text-muted"><?= e($r['email']); ?> | <?= e($r['phone']); ?></div>
                                </td>
                                <td>
                                    <a href="event-details.php?id=<?= e($r['event_id']); ?>" class="text-decoration-none fw-semibold">
                                        <?= e($r['event_title']); ?>
                                    </a>
                                    <div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= e($r['area']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($r['role_type']); ?></span>
                                </td>
                                <td><?= getStatusBadge($r['status']); ?></td>
                                <td><small class="text-muted"><?= formatDate($r['registered_at'], 'M d, Y h:i A'); ?></small></td>
                                <td class="text-end">
                                    <a href="registration-manage.php?reg_id=<?= e($r['registration_id']); ?><?= $filterEvent ? '&event_id=' . $filterEvent : ''; ?>" class="btn btn-sm btn-outline-primary" title="Edit Status (UPDATE Query 2)">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="registration-manage.php?action=delete&reg_id=<?= e($r['registration_id']); ?><?= $filterEvent ? '&return_event=' . $filterEvent : ''; ?>" class="btn btn-sm btn-outline-danger" title="Cancel/Delete (DELETE Query 2)" data-confirm="Are you sure you want to cancel this registration?">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
