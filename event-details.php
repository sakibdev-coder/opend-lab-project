<?php
// ====================================================================
// Event Details View (READ Single Record with JOINs, INSERT Registrations & Donations)
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$eventId = intval($_GET['id'] ?? 0);
if ($eventId <= 0) {
    setFlashMessage('danger', 'Invalid Event ID specified.');
    header("Location: events.php");
    exit;
}

// Handle Form Submissions: Volunteer Registration (INSERT Query 2)
$regError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_volunteer') {
    $userId = intval($_POST['user_id'] ?? 0);
    $roleType = trim($_POST['role_type'] ?? 'General Volunteer');
    $notes = trim($_POST['notes'] ?? '');

    if ($userId <= 0) {
        $regError = "Please select a volunteer user.";
    } else {
        try {
            // SQL Query #2 (INSERT): Register a volunteer for an event
            $insertRegSql = "
                INSERT INTO `registrations` (`event_id`, `user_id`, `role_type`, `status`, `notes`)
                VALUES (:event_id, :user_id, :role_type, 'Registered', :notes)
            ";
            $stmt = $pdo->prepare($insertRegSql);
            $stmt->execute([
                ':event_id'  => $eventId,
                ':user_id'   => $userId,
                ':role_type' => $roleType,
                ':notes'     => $notes
            ]);
            setFlashMessage('success', "Volunteer successfully registered for this event! (INSERT Query #2 executed)");
            header("Location: event-details.php?id=" . $eventId);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $regError = "This volunteer is already registered for this event! (Enforced by UNIQUE constraint)";
            } else {
                $regError = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle Form Submission: Add Donation / Supply (INSERT Query 3)
$donError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_donation') {
    $donorName = trim($_POST['donor_name'] ?? '');
    $donorContact = trim($_POST['donor_contact'] ?? '');
    $itemType = trim($_POST['item_type'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'Pcs');

    if ($donorName === '' || $itemType === '' || $quantity <= 0) {
        $donError = "Donor name, item type, and valid quantity (> 0) are required.";
    } else {
        try {
            // SQL INSERT for donations
            $insertDonSql = "
                INSERT INTO `donations` (`event_id`, `donor_name`, `donor_contact`, `item_type`, `quantity`, `unit`)
                VALUES (:event_id, :donor_name, :donor_contact, :item_type, :quantity, :unit)
            ";
            $stmt = $pdo->prepare($insertDonSql);
            $stmt->execute([
                ':event_id'      => $eventId,
                ':donor_name'    => $donorName,
                ':donor_contact' => $donorContact,
                ':item_type'     => $itemType,
                ':quantity'      => $quantity,
                ':unit'          => $unit
            ]);
            setFlashMessage('success', "Supplies/Donation entry logged successfully!");
            header("Location: event-details.php?id=" . $eventId);
            exit;
        } catch (PDOException $e) {
            $donError = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Event Details with Multi-Table JOIN (events + categories + users)
$eventSql = "
    SELECT 
        e.*,
        c.category_name,
        c.description AS category_description,
        u.user_id AS organizer_id,
        u.full_name AS organizer_name,
        u.email AS organizer_email,
        u.phone AS organizer_phone,
        u.address AS organizer_address
    FROM `events` e
    INNER JOIN `categories` c ON e.category_id = c.category_id
    INNER JOIN `users` u ON e.organizer_id = u.user_id
    WHERE e.event_id = :id
";
$stmt = $pdo->prepare($eventSql);
$stmt->execute([':id' => $eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('danger', 'Event not found in the database.');
    header("Location: events.php");
    exit;
}

// Fetch Registrations with JOIN on users table
$regSql = "
    SELECT 
        r.registration_id,
        r.role_type,
        r.status AS reg_status,
        r.registered_at,
        r.notes,
        u.user_id,
        u.full_name,
        u.email,
        u.phone,
        u.role
    FROM `registrations` r
    INNER JOIN `users` u ON r.user_id = u.user_id
    WHERE r.event_id = :id
    ORDER BY r.registered_at DESC
";
$stmt = $pdo->prepare($regSql);
$stmt->execute([':id' => $eventId]);
$registrations = $stmt->fetchAll();

// Fetch Donations logged for this event
$donSql = "
    SELECT * 
    FROM `donations` 
    WHERE event_id = :id 
    ORDER BY donated_at DESC
";
$stmt = $pdo->prepare($donSql);
$stmt->execute([':id' => $eventId]);
$donations = $stmt->fetchAll();

// Fetch all volunteer users for registration dropdown
$volunteers = $pdo->query("SELECT user_id, full_name, email, phone FROM `users` ORDER BY full_name ASC")->fetchAll();

$pageTitle = $event['title'];
$totalVolunteers = count($registrations);
$progress = $event['target_volunteers'] > 0 ? min(100, round(($totalVolunteers / $event['target_volunteers']) * 100)) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb & Top Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="events.php" class="text-decoration-none">Events</a></li>
            <li class="breadcrumb-item active" aria-current="page">Event #<?= e($event['event_id']); ?></li>
        </ol>
    </nav>
    <div class="d-flex gap-2">
        <a href="event-edit.php?id=<?= e($event['event_id']); ?>" class="btn btn-outline-warning btn-sm fw-bold">
            <i class="fas fa-edit me-1"></i> Edit Event (UPDATE)
        </a>
        <a href="event-delete.php?id=<?= e($event['event_id']); ?>" class="btn btn-outline-danger btn-sm" data-confirm="Are you sure you want to delete this event? This action cannot be undone.">
            <i class="fas fa-trash-alt me-1"></i> Delete Event (DELETE)
        </a>
        <a href="events.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Event Core Details -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="category-pill fs-6"><?= e($event['category_name']); ?></span>
                    <?= getStatusBadge($event['status']); ?>
                </div>

                <h2 class="fw-bold text-dark mb-3"><?= e($event['title']); ?></h2>

                <div class="bg-light p-3 rounded-3 mb-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i> Area & Location:</div>
                            <strong class="d-block"><?= e($event['area']); ?></strong>
                            <span class="small text-secondary"><?= e($event['exact_location']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="fas fa-calendar-alt text-primary me-1"></i> Scheduled Date & Time:</div>
                            <strong class="d-block"><?= formatDate($event['event_date']); ?></strong>
                            <span class="small text-muted"><?= date('l', strtotime($event['event_date'])); ?></span>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-2"><i class="fas fa-align-left text-primary me-2"></i>About this Initiative</h5>
                <p class="text-secondary" style="white-space: pre-line; line-height: 1.7;"><?= e($event['description']); ?></p>

                <hr class="my-4">

                <!-- Progress Section -->
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-1">Volunteer Mobilization Status</h6>
                        <div class="small text-muted mb-2">
                            <strong><?= $totalVolunteers; ?></strong> enrolled out of target <strong><?= e($event['target_volunteers']); ?></strong> volunteers
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: <?= $progress; ?>%"></div>
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        <a href="#volunteer-section" class="btn btn-success fw-bold">
                            <i class="fas fa-user-plus me-1"></i> Register as Volunteer
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registered Volunteers Table (Multi-Table JOIN: registrations + users) -->
        <div class="card shadow-sm border-0 mb-4" id="volunteer-section">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-users text-primary me-2"></i>Registered Volunteers (<?= count($registrations); ?>)
                </h5>
                <span class="badge bg-secondary">JOIN registrations + users</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($registrations)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-user-friends fa-2x mb-2 text-secondary"></i>
                        <p class="mb-0">No volunteers registered yet. Be the first to join this cause!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Volunteer</th>
                                    <th>Contact</th>
                                    <th>Assigned Role</th>
                                    <th>Status</th>
                                    <th>Enrolled</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $r): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= e($r['full_name']); ?></div>
                                            <small class="text-muted"><?= e($r['role']); ?></small>
                                        </td>
                                        <td>
                                            <div class="small"><?= e($r['email']); ?></div>
                                            <div class="small text-muted"><?= e($r['phone']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($r['role_type']); ?></span>
                                            <?php if ($r['notes']): ?>
                                                <div class="small text-muted italic"><?= e($r['notes']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($r['reg_status']); ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= formatDate($r['registered_at'], 'M d, Y'); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <a href="registration-manage.php?event_id=<?= $eventId; ?>&reg_id=<?= $r['registration_id']; ?>" class="btn btn-sm btn-outline-primary" title="Update Attendance / Delete Registration">
                                                <i class="fas fa-cog"></i>
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

        <!-- Logged Supplies & Donations Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-gift text-warning me-2"></i>Contributed Supplies & Donations (<?= count($donations); ?>)
                </h5>
                <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="collapse" data-bs-target="#donationFormCollapse">
                    <i class="fas fa-plus me-1"></i> Log Donation
                </button>
            </div>
            <div class="card-body p-0">
                <!-- Collapsible Donation Form -->
                <div class="collapse p-3 bg-light border-bottom" id="donationFormCollapse">
                    <form action="event-details.php?id=<?= $eventId; ?>" method="POST">
                        <input type="hidden" name="action" value="add_donation">
                        <h6 class="fw-bold mb-2">Record a Donation / In-Kind Relief Item</h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <input type="text" name="donor_name" class="form-control form-control-sm" placeholder="Donor Name / Org" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="donor_contact" class="form-control form-control-sm" placeholder="Contact / Phone" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="item_type" class="form-control form-control-sm" placeholder="e.g. Blankets, Sweaters, Cash" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.5" name="quantity" class="form-control form-control-sm" placeholder="Quantity" required>
                            </div>
                            <div class="col-md-1">
                                <input type="text" name="unit" class="form-control form-control-sm" placeholder="Unit (Pcs/BDT)" value="Pcs" required>
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="submit" class="btn btn-warning btn-sm fw-bold">Save Donation</button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (empty($donations)): ?>
                    <div class="p-3 text-center text-muted small">
                        No material donations or funds logged for this event yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Donor</th>
                                    <th>Item / Supply Type</th>
                                    <th>Quantity</th>
                                    <th>Logged On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donations as $d): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($d['donor_name']); ?></strong>
                                            <div class="small text-muted"><?= e($d['donor_contact']); ?></div>
                                        </td>
                                        <td><?= e($d['item_type']); ?></td>
                                        <td><span class="badge bg-secondary"><?= e($d['quantity']); ?> <?= e($d['unit']); ?></span></td>
                                        <td><small class="text-muted"><?= formatDate($d['donated_at'], 'M d, Y'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Registration Form & Organizer Card -->
    <div class="col-lg-4">
        <!-- Quick Volunteer Registration Card (INSERT Query 2) -->
        <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 85px; z-index: 10;">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-hand-holding-heart me-2"></i>Join This Initiative</h5>
                <small class="opacity-75">INSERT Query #2 (Volunteers)</small>
            </div>
            <div class="card-body p-4">
                <?php if ($regError): ?>
                    <div class="alert alert-danger alert-dismissible fade show small">
                        <?= e($regError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="event-details.php?id=<?= $eventId; ?>" method="POST">
                    <input type="hidden" name="action" value="register_volunteer">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Select Volunteer Name <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Choose User...</option>
                            <?php foreach ($volunteers as $v): ?>
                                <option value="<?= e($v['user_id']); ?>">
                                    <?= e($v['full_name']); ?> (<?= e($v['phone']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Select any registered community member.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Preferred Role <span class="text-danger">*</span></label>
                        <select name="role_type" class="form-select" required>
                            <option value="General Volunteer">General Volunteer (Distribution/Queues)</option>
                            <option value="Event Coordinator">Event Coordinator (On-spot Team Lead)</option>
                            <option value="First Aid / Medic">First Aid / Medic (Health & Safety)</option>
                            <option value="Logistics & Distribution">Logistics & Distribution (Transport & Setup)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Notes / Availability (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Free after 2 PM, bringing personal car">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-bold py-2">
                            <i class="fas fa-check me-1"></i> Register Volunteer (INSERT)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Organizer Profile Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-tie text-primary me-2"></i>Lead Organizer Contact</h6>
            </div>
            <div class="card-body p-3">
                <h6 class="fw-bold mb-1"><?= e($event['organizer_name']); ?></h6>
                <div class="small text-muted mb-2"><i class="fas fa-envelope me-1"></i> <?= e($event['organizer_email']); ?></div>
                <div class="small text-muted mb-2"><i class="fas fa-phone me-1"></i> <?= e($event['organizer_phone']); ?></div>
                <?php if ($event['organizer_address']): ?>
                    <div class="small text-muted"><i class="fas fa-home me-1"></i> <?= e($event['organizer_address']); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SQL Queries Behind this page -->
        <div class="card border-0 bg-light p-3 small text-muted">
            <div class="fw-bold text-dark mb-1"><i class="fas fa-database text-info me-1"></i>SQL Queries in action on this page:</div>
            <ul class="mb-0 ps-3">
                <li><code>SELECT events + categories + users (JOIN)</code></li>
                <li><code>SELECT registrations + users (JOIN)</code></li>
                <li><code>INSERT INTO registrations (Prepared Stmt)</code></li>
                <li><code>INSERT INTO donations</code></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
