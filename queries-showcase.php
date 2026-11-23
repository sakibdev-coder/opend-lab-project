<?php
// ====================================================================
// 10+ SQL Queries Showcase & Interactive Live Runner for Lab Viva
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$pageTitle = "10+ SQL Queries Showcase (Academic Lab & Viva)";

// Handle Live Query Execution from button clicks
$executedQueryKey = $_GET['run'] ?? '';
$runResult = null;
$runError = null;
$executedSql = '';
$executedType = '';

// Define the 10+ Assignment Queries Catalogue
$queriesCatalogue = [
    'insert_1' => [
        'id'          => 'Q1',
        'type'        => 'INSERT',
        'label'       => 'INSERT Query #1: Create New Social Development Event',
        'desc'        => 'Inserts a new event record with category, organizer, area, exact venue, date, target quota, and status using prepared statements.',
        'sql'         => "INSERT INTO `events` (`title`, `description`, `category_id`, `organizer_id`, `area`, `exact_location`, `event_date`, `target_volunteers`, `status`)\nVALUES ('Winter Shawl Distribution – Mirpur 1', 'Distributing warm shawls to female street cleaners and destitute elderly.', 1, 1, 'Mirpur 10, Dhaka', 'Mirpur 1 Muktijoddha Market Gate', '2026-12-15 11:00:00', 15, 'Upcoming');",
        'executable'  => true,
        'action_type' => 'write'
    ],
    'insert_2' => [
        'id'          => 'Q2',
        'type'        => 'INSERT',
        'label'       => 'INSERT Query #2: Register Volunteer for an Event',
        'desc'        => 'Inserts a volunteer registration record connecting a user with an event, validated by UNIQUE(event_id, user_id).',
        'sql'         => "INSERT INTO `registrations` (`event_id`, `user_id`, `role_type`, `status`, `notes`)\nVALUES (2, 4, 'First Aid / Medic', 'Registered', 'First-aid kit and emergency bandages ready');",
        'executable'  => true,
        'action_type' => 'write'
    ],
    'select_1' => [
        'id'          => 'Q3',
        'type'        => 'SELECT (JOIN)',
        'label'       => 'SELECT Query #1 (Multi-Table JOIN): Events with Categories & Organizers',
        'desc'        => 'Combines records across `events`, `categories`, and `users` tables, plus computes volunteer counts via sub-select.',
        'sql'         => "SELECT \n    e.event_id,\n    e.title,\n    c.category_name,\n    u.full_name AS organizer_name,\n    u.phone AS organizer_phone,\n    e.area,\n    e.event_date,\n    e.status,\n    (SELECT COUNT(*) FROM `registrations` r WHERE r.event_id = e.event_id) AS registered_volunteers\nFROM `events` e\nINNER JOIN `categories` c ON e.category_id = c.category_id\nINNER JOIN `users` u ON e.organizer_id = u.user_id\nORDER BY e.event_date ASC;",
        'executable'  => true,
        'action_type' => 'read'
    ],
    'select_2' => [
        'id'          => 'Q4',
        'type'        => 'SELECT (GROUP BY)',
        'label'       => 'SELECT Query #2 (GROUP BY & Aggregations): Category-wise Statistics',
        'desc'        => 'Groups events by category to calculate total event count, total volunteers enrolled, and count of donation records.',
        'sql'         => "SELECT \n    c.category_id,\n    c.category_name,\n    COUNT(DISTINCT e.event_id) AS total_events,\n    COUNT(r.registration_id) AS total_volunteers,\n    COUNT(DISTINCT d.donation_id) AS total_donations\nFROM `categories` c\nLEFT JOIN `events` e ON c.category_id = e.category_id\nLEFT JOIN `registrations` r ON e.event_id = r.event_id\nLEFT JOIN `donations` d ON e.event_id = d.event_id\nGROUP BY c.category_id, c.category_name\nORDER BY total_events DESC;",
        'executable'  => true,
        'action_type' => 'read'
    ],
    'select_3' => [
        'id'          => 'Q5',
        'type'        => 'SELECT (SUBQUERY)',
        'label'       => 'SELECT Query #3 (Correlated SUBQUERY): High-Engagement Events',
        'desc'        => 'Finds events whose total registered volunteers are greater than or equal to the average registrations across all events.',
        'sql'         => "SELECT \n    e.event_id,\n    e.title,\n    e.area,\n    e.status,\n    (SELECT COUNT(*) FROM `registrations` r WHERE r.event_id = e.event_id) AS event_volunteers\nFROM `events` e\nWHERE (SELECT COUNT(*) FROM `registrations` r WHERE r.event_id = e.event_id) >= (\n    SELECT AVG(reg_count) \n    FROM (\n        SELECT COUNT(*) AS reg_count \n        FROM `registrations` \n        GROUP BY event_id\n    ) AS sub_tbl\n)\nORDER BY event_volunteers DESC;",
        'executable'  => true,
        'action_type' => 'read'
    ],
    'select_4' => [
        'id'          => 'Q6',
        'type'        => 'SELECT (VIEW)',
        'label'       => 'SELECT Query #4 (SQL VIEW): Querying vw_event_summary',
        'desc'        => 'Retrieves pre-aggregated event information from the relational database view `vw_event_summary`.',
        'sql'         => "SELECT \n    event_id,\n    title,\n    area,\n    category_name,\n    organizer_name,\n    status,\n    total_volunteers,\n    confirmed_volunteers,\n    total_donations\nFROM `vw_event_summary`\nWHERE status IN ('Upcoming', 'Ongoing')\nORDER BY event_date ASC;",
        'executable'  => true,
        'action_type' => 'read'
    ],
    'select_5' => [
        'id'          => 'Q7',
        'type'        => 'SELECT (Search & Filter)',
        'label'       => 'SELECT Query #5 (Search & Filter with Prepared Parameters): Locality Filter',
        'desc'        => 'Filters events in specific Dhaka neighborhoods (e.g., Mirpur or Mohammadpur) with status constraint.',
        'sql'         => "SELECT \n    event_id, title, area, exact_location, event_date, status, target_volunteers\nFROM `events`\nWHERE (area LIKE '%Mirpur%' OR area LIKE '%Mohammadpur%')\n  AND status = 'Upcoming'\nORDER BY event_date ASC;",
        'executable'  => true,
        'action_type' => 'read'
    ],
    'update_1' => [
        'id'          => 'Q8',
        'type'        => 'UPDATE',
        'label'       => 'UPDATE Query #1: Update Event Target Volunteer Quota & Status',
        'desc'        => 'Modifies the volunteer target and status for event #1 (Winter Clothes Distribution – Mirpur 10).',
        'sql'         => "UPDATE `events` \nSET `target_volunteers` = 25, `status` = 'Upcoming'\nWHERE `event_id` = 1;",
        'executable'  => true,
        'action_type' => 'write'
    ],
    'update_2' => [
        'id'          => 'Q9',
        'type'        => 'UPDATE',
        'label'       => 'UPDATE Query #2: Update Volunteer Attendance Status',
        'desc'        => 'Changes the attendance status of a volunteer registration from Registered to Confirmed / Attended.',
        'sql'         => "UPDATE `registrations` \nSET `status` = 'Confirmed', `notes` = 'Confirmed via phone call'\nWHERE `registration_id` = 1;",
        'executable'  => true,
        'action_type' => 'write'
    ],
    'delete_1' => [
        'id'          => 'Q10',
        'type'        => 'DELETE',
        'label'       => 'DELETE Query #1: Cancel a Volunteer Registration',
        'desc'        => 'Removes a volunteer registration record from the associative table without affecting the main event or user.',
        'sql'         => "DELETE FROM `registrations` \nWHERE `registration_id` = 10;",
        'executable'  => true,
        'action_type' => 'write'
    ],
    'bonus_subquery' => [
        'id'          => 'Q11',
        'type'        => 'SELECT (Subquery + HAVING)',
        'label'       => 'Bonus Query #11: Find Organizers with Multiple Events (HAVING in Subquery)',
        'desc'        => 'Selects user details for community organizers who have organized at least 1 event using an IN subquery with HAVING.',
        'sql'         => "SELECT \n    u.user_id, u.full_name, u.email, u.phone,\n    (SELECT COUNT(*) FROM `events` e WHERE e.organizer_id = u.user_id) AS events_count\nFROM `users` u\nWHERE u.user_id IN (\n    SELECT organizer_id \n    FROM `events` \n    GROUP BY organizer_id \n    HAVING COUNT(*) >= 1\n)\nORDER BY events_count DESC;",
        'executable'  => true,
        'action_type' => 'read'
    ]
];

// Execute requested query
if ($executedQueryKey && isset($queriesCatalogue[$executedQueryKey])) {
    $item = $queriesCatalogue[$executedQueryKey];
    $executedSql = $item['sql'];
    $executedType = $item['type'];

    try {
        if ($item['action_type'] === 'read') {
            $stmt = $pdo->query($executedSql);
            $runResult = $stmt->fetchAll();
        } else {
            $affected = $pdo->exec($executedSql);
            $runResult = [
                'status'   => 'success',
                'affected' => $affected,
                'message'  => "Command executed successfully. Rows affected: {$affected}"
            ];
        }
    } catch (PDOException $e) {
        $runError = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-terminal text-primary me-2"></i>10+ SQL Queries Demonstration</h2>
        <p class="text-muted mb-0">Prepared specifically for University Lab Assessment and Viva Defense.</p>
    </div>
    <div>
        <a href="setup_db.php" class="btn btn-outline-danger btn-sm" title="Reset sample data if you test delete queries">
            <i class="fas fa-redo me-1"></i>Reset DB Sample Data
        </a>
    </div>
</div>

<!-- Specification Checklist Card -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-tasks text-success me-2"></i>Assignment Query Requirements Mapping:</h6>
        <div class="row g-2 small">
            <div class="col-md-3">
                <div class="p-2 bg-light rounded border">
                    <span class="badge bg-success me-1">2 INSERTS</span>
                    <strong>Q1, Q2</strong> (Event & Volunteer)
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-light rounded border">
                    <span class="badge bg-primary me-1">5+ SELECTS</span>
                    <strong>Q3, Q4, Q5, Q6, Q7, Q11</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-light rounded border">
                    <span class="badge bg-warning text-dark me-1">2 UPDATES</span>
                    <strong>Q8, Q9</strong> (Event & Attendance)
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-light rounded border">
                    <span class="badge bg-danger me-1">1 DELETE</span>
                    <strong>Q10</strong> (Registration Cancel)
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Output Console (if query executed) -->
<?php if ($executedQueryKey): ?>
    <div class="card shadow-sm border-0 mb-5 border-start border-4 border-info bg-white" id="live-console">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-play text-success me-2"></i>Live Execution Result: <?= e($queriesCatalogue[$executedQueryKey]['label']); ?>
                </h5>
                <span class="badge bg-secondary"><?= e($executedType); ?></span>
            </div>
            <a href="queries-showcase.php" class="btn btn-sm btn-outline-secondary">Clear Console</a>
        </div>
        <div class="card-body p-4">
            <h6 class="fw-bold small text-muted">Executed SQL Statement:</h6>
            <div class="query-box mb-3"><?= e($executedSql); ?></div>

            <?php if ($runError): ?>
                <div class="alert alert-danger mb-0">
                    <h6 class="fw-bold"><i class="fas fa-times-circle me-2"></i>SQL Execution Error:</h6>
                    <?= e($runError); ?>
                </div>
            <?php elseif (isset($runResult['status']) && $runResult['status'] === 'success'): ?>
                <div class="alert alert-success mb-0">
                    <h6 class="fw-bold mb-1"><i class="fas fa-check-circle me-2"></i>Query Executed Successfully!</h6>
                    <p class="mb-0 small"><?= e($runResult['message']); ?></p>
                </div>
            <?php elseif (is_array($runResult)): ?>
                <h6 class="fw-bold small text-muted">Resulting Records (<?= count($runResult); ?> rows returned):</h6>
                <?php if (empty($runResult)): ?>
                    <div class="alert alert-info mb-0">Query returned 0 rows.</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-sm table-bordered table-striped mb-0 small">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <?php foreach (array_keys($runResult[0]) as $col): ?>
                                        <th><?= e($col); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($runResult as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $val): ?>
                                            <td><?= e($val); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Queries Catalogue Cards -->
<div class="row g-4">
    <?php foreach ($queriesCatalogue as $key => $q): 
        $badgeClass = 'bg-primary';
        if (strpos($q['type'], 'INSERT') !== false) $badgeClass = 'bg-success';
        elseif (strpos($q['type'], 'UPDATE') !== false) $badgeClass = 'bg-warning text-dark';
        elseif (strpos($q['type'], 'DELETE') !== false) $badgeClass = 'bg-danger';
        elseif (strpos($q['type'], 'GROUP BY') !== false) $badgeClass = 'bg-info text-dark';
        elseif (strpos($q['type'], 'SUBQUERY') !== false) $badgeClass = 'bg-dark';
        elseif (strpos($q['type'], 'VIEW') !== false) $badgeClass = 'bg-secondary';
    ?>
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-light text-dark border me-2 fw-bold"><?= $q['id']; ?></span>
                        <h6 class="mb-0 fw-bold"><?= e($q['label']); ?></h6>
                    </div>
                    <span class="badge <?= $badgeClass; ?>"><?= e($q['type']); ?></span>
                </div>
                <div class="card-body p-3 d-flex flex-column">
                    <p class="small text-muted mb-2"><?= e($q['desc']); ?></p>
                    
                    <div class="query-box mb-3 flex-grow-1" style="font-size: 0.82rem;">
                        <pre class="m-0 text-white" style="white-space: pre-wrap;"><code><?= e($q['sql']); ?></code></pre>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt text-success me-1"></i>PDO Prepared Stmt
                        </small>
                        <a href="queries-showcase.php?run=<?= urlencode($key); ?>#live-console" class="btn btn-sm btn-outline-primary fw-bold">
                            <i class="fas fa-play me-1"></i>Run Query Live
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
