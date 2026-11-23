<?php
// ====================================================================
// Event Creation (CREATE Operation - INSERT Query 1)
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$pageTitle = "Create New Social Event";
$errors = [];

// Fetch categories for dropdown
$categories = $pdo->query("SELECT category_id, category_name FROM `categories` ORDER BY category_name ASC")->fetchAll();

// Fetch organizers for dropdown
$organizers = $pdo->query("SELECT user_id, full_name, email, role FROM `users` WHERE role IN ('Organizer', 'Admin') ORDER BY full_name ASC")->fetchAll();

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
    if ($title === '') {
        $errors[] = "Event title is required.";
    }
    if ($categoryId <= 0) {
        $errors[] = "Please select a valid event category.";
    }
    if ($organizerId <= 0) {
        $errors[] = "Please select an event organizer.";
    }
    if ($area === '') {
        $errors[] = "Area / locality is required (e.g. Mirpur 10, Dhaka).";
    }
    if ($exactLocation === '') {
        $errors[] = "Exact location / landmark is required.";
    }
    if ($eventDate === '') {
        $errors[] = "Event date and time is required.";
    }
    if ($targetVolunteers <= 0) {
        $errors[] = "Target volunteers must be greater than 0.";
    }
    if ($description === '') {
        $errors[] = "Event description is required.";
    }

    // Execute INSERT Query with Prepared Statement
    if (empty($errors)) {
        try {
            // SQL Query #1 (INSERT): Add a new event record
            $sql = "
                INSERT INTO `events` 
                (`title`, `description`, `category_id`, `organizer_id`, `area`, `exact_location`, `event_date`, `target_volunteers`, `status`)
                VALUES 
                (:title, :description, :category_id, :organizer_id, :area, :exact_location, :event_date, :target_volunteers, :status)
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title'             => $title,
                ':description'       => $description,
                ':category_id'       => $categoryId,
                ':organizer_id'      => $organizerId,
                ':area'              => $area,
                ':exact_location'    => $exactLocation,
                ':event_date'        => date('Y-m-d H:i:s', strtotime($eventDate)),
                ':target_volunteers' => $targetVolunteers,
                ':status'            => $status
            ]);

            $newEventId = $pdo->lastInsertId();
            setFlashMessage('success', "Event '{$title}' created successfully! (Event #{$newEventId})");
            header("Location: event-details.php?id=" . $newEventId);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-0"><i class="fas fa-plus-circle text-success me-2"></i>Create Social Welfare Event</h3>
                <small class="text-muted">Web Form with PDO Prepared Statement (CREATE Operation)</small>
            </div>
            <a href="events.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Events
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
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold text-primary"><i class="fas fa-edit me-2"></i>Event Information Form</h5>
            </div>
            <div class="card-body p-4">
                <form action="event-create.php" method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- Title -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Event Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Winter Clothes Distribution – Mirpur 10, Dhaka" value="<?= e($_POST['title'] ?? ''); ?>" required>
                            <div class="form-text">Clear, descriptive title naming the cause and Dhaka neighborhood.</div>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['category_id']); ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?= e($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Organizer -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Organizer <span class="text-danger">*</span></label>
                            <select name="organizer_id" class="form-select" required>
                                <option value="">Select Lead Organizer...</option>
                                <?php foreach ($organizers as $org): ?>
                                    <option value="<?= e($org['user_id']); ?>" <?= (isset($_POST['organizer_id']) && $_POST['organizer_id'] == $org['user_id']) ? 'selected' : ''; ?>>
                                        <?= e($org['full_name']); ?> (<?= e($org['role']); ?> - <?= e($org['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Area / Locality -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Area / Locality <span class="text-danger">*</span></label>
                            <input type="text" name="area" class="form-control" list="dhaka_areas" placeholder="e.g. Mirpur 10, Dhaka" value="<?= e($_POST['area'] ?? ''); ?>" required>
                            <datalist id="dhaka_areas">
                                <option value="Mirpur 10, Dhaka">
                                <option value="Mohammadpur, Dhaka">
                                <option value="Dhanmondi, Dhaka">
                                <option value="Banani, Dhaka">
                                <option value="Old Dhaka, Dhaka">
                                <option value="Farmgate, Dhaka">
                                <option value="Uttara Sector 7, Dhaka">
                                <option value="Gulshan 2, Dhaka">
                            </datalist>
                        </div>

                        <!-- Exact Location -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Exact Meeting Point / Landmark <span class="text-danger">*</span></label>
                            <input type="text" name="exact_location" class="form-control" placeholder="e.g. Town Hall Ground & Mohammadpur Bus Stand" value="<?= e($_POST['exact_location'] ?? ''); ?>" required>
                        </div>

                        <!-- Event Date -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="event_date" class="form-control" value="<?= e($_POST['event_date'] ?? date('Y-m-d\TH:i', strtotime('+3 days 10:00'))); ?>" required>
                        </div>

                        <!-- Target Volunteers -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Target Volunteers <span class="text-danger">*</span></label>
                            <input type="number" name="target_volunteers" min="1" max="500" class="form-control" value="<?= e($_POST['target_volunteers'] ?? '15'); ?>" required>
                            <div class="form-text">Constraint: MUST be > 0.</div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Upcoming" <?= (isset($_POST['status']) && $_POST['status'] === 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="Ongoing" <?= (isset($_POST['status']) && $_POST['status'] === 'Ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="Completed" <?= (isset($_POST['status']) && $_POST['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?= (isset($_POST['status']) && $_POST['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Event Description & Action Plan <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Describe the goal, items to distribute, target beneficiaries, shift hours, and rules..." required><?= e($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="events.php" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold">
                                <i class="fas fa-check-circle me-1"></i> Save Event (Run INSERT Query)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Viva Info Box -->
        <div class="card border-0 bg-light p-3 small text-muted">
            <div>
                <strong><i class="fas fa-info-circle text-primary me-1"></i>Lab Viva Feature:</strong>
                Submitting this form triggers <code>SQL Query #1 (INSERT INTO events ...)</code> using prepared statements to prevent SQL Injection.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
