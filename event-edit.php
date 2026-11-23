<?php
// ====================================================================
// Event Edit (UPDATE Operation - UPDATE Query 1)
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$eventId = intval($_GET['id'] ?? 0);
if ($eventId <= 0) {
    setFlashMessage('danger', 'Invalid Event ID.');
    header("Location: events.php");
    exit;
}

// Fetch existing event record
$stmt = $pdo->prepare("SELECT * FROM `events` WHERE event_id = :id");
$stmt->execute([':id' => $eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('danger', 'Event not found.');
    header("Location: events.php");
    exit;
}

$categories = $pdo->query("SELECT category_id, category_name FROM `categories` ORDER BY category_name ASC")->fetchAll();
$organizers = $pdo->query("SELECT user_id, full_name, role FROM `users` WHERE role IN ('Organizer', 'Admin') ORDER BY full_name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $organizerId = intval($_POST['organizer_id'] ?? 0);
    $area = trim($_POST['area'] ?? '');
    $exactLocation = trim($_POST['exact_location'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $targetVolunteers = intval($_POST['target_volunteers'] ?? 10);
    $status = trim($_POST['status'] ?? 'Upcoming');
    $description = trim($_POST['description'] ?? '');

    // Form Validations
    if ($title === '') $errors[] = "Event title is required.";
    if ($categoryId <= 0) $errors[] = "Valid category must be selected.";
    if ($organizerId <= 0) $errors[] = "Valid organizer must be selected.";
    if ($area === '') $errors[] = "Area / locality is required.";
    if ($exactLocation === '') $errors[] = "Exact location is required.";
    if ($eventDate === '') $errors[] = "Event date is required.";
    if ($targetVolunteers <= 0) $errors[] = "Target volunteers must be greater than 0.";
    if ($description === '') $errors[] = "Description is required.";

    // Execute UPDATE Query 1 using PDO Prepared Statement
    if (empty($errors)) {
        try {
            // SQL Query #8 (UPDATE 1): Update an existing event record
            $updateSql = "
                UPDATE `events` SET 
                    `title` = :title,
                    `description` = :description,
                    `category_id` = :category_id,
                    `organizer_id` = :organizer_id,
                    `area` = :area,
                    `exact_location` = :exact_location,
                    `event_date` = :event_date,
                    `target_volunteers` = :target_volunteers,
                    `status` = :status
                WHERE `event_id` = :event_id
            ";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':title'             => $title,
                ':description'       => $description,
                ':category_id'       => $categoryId,
                ':organizer_id'      => $organizerId,
                ':area'              => $area,
                ':exact_location'    => $exactLocation,
                ':event_date'        => date('Y-m-d H:i:s', strtotime($eventDate)),
                ':target_volunteers' => $targetVolunteers,
                ':status'            => $status,
                ':event_id'          => $eventId
            ]);

            setFlashMessage('success', "Event '#{$eventId} - {$title}' updated successfully! (UPDATE Query #1 executed)");
            header("Location: event-details.php?id=" . $eventId);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = "Edit Event: " . $event['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-0"><i class="fas fa-edit text-warning me-2"></i>Edit Social Event #<?= e($event['event_id']); ?></h3>
                <small class="text-muted">UPDATE Operation with Prepared Statements</small>
            </div>
            <a href="event-details.php?id=<?= $eventId; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Cancel & Back to Details
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following issues:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form action="event-edit.php?id=<?= $eventId; ?>" method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Event Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? $event['title']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['category_id']); ?>" <?= (($_POST['category_id'] ?? $event['category_id']) == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?= e($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Organizer <span class="text-danger">*</span></label>
                            <select name="organizer_id" class="form-select" required>
                                <?php foreach ($organizers as $org): ?>
                                    <option value="<?= e($org['user_id']); ?>" <?= (($_POST['organizer_id'] ?? $event['organizer_id']) == $org['user_id']) ? 'selected' : ''; ?>>
                                        <?= e($org['full_name']); ?> (<?= e($org['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Area / Locality <span class="text-danger">*</span></label>
                            <input type="text" name="area" class="form-control" list="dhaka_areas" value="<?= e($_POST['area'] ?? $event['area']); ?>" required>
                            <datalist id="dhaka_areas">
                                <option value="Mirpur 10, Dhaka">
                                <option value="Mohammadpur, Dhaka">
                                <option value="Dhanmondi, Dhaka">
                                <option value="Banani, Dhaka">
                                <option value="Old Dhaka, Dhaka">
                                <option value="Farmgate, Dhaka">
                            </datalist>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Exact Meeting Point <span class="text-danger">*</span></label>
                            <input type="text" name="exact_location" class="form-control" value="<?= e($_POST['exact_location'] ?? $event['exact_location']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="event_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($_POST['event_date'] ?? $event['event_date'])); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Target Volunteers <span class="text-danger">*</span></label>
                            <input type="number" name="target_volunteers" min="1" max="500" class="form-control" value="<?= e($_POST['target_volunteers'] ?? $event['target_volunteers']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                            <?php $currStatus = $_POST['status'] ?? $event['status']; ?>
                            <select name="status" class="form-select" required>
                                <option value="Upcoming" <?= $currStatus === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="Ongoing" <?= $currStatus === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="Completed" <?= $currStatus === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?= $currStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4" class="form-control" required><?= e($_POST['description'] ?? $event['description']); ?></textarea>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="event-details.php?id=<?= $eventId; ?>" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="fas fa-save me-1"></i> Update Event (Execute UPDATE)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
