<?php
// ====================================================================
// Header Template with Navigation Bar
// System: Social Development Events Platform
// ====================================================================
require_once __DIR__ . '/../config/database.php';

$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($pageTitle)) {
    $pageTitle = "CommunityAid - Social Development Events";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> | CommunityAid Dhaka</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-hand-holding-heart fa-lg me-2 text-warning"></i>
            <span>CommunityAid <small class="text-white-50 fs-6 fw-normal">| সমাজসেবা ঢাকা</small></span>
        </a>
        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['events.php', 'event-details.php', 'event-edit.php']) ? 'active' : ''; ?>" href="events.php">
                        <i class="fas fa-calendar-alt me-1"></i> Explore Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'event-create.php' ? 'active' : ''; ?>" href="event-create.php">
                        <i class="fas fa-plus-circle me-1"></i> Create Event
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['register-volunteer.php', 'registration-manage.php']) ? 'active' : ''; ?>" href="registration-manage.php">
                        <i class="fas fa-users-cog me-1"></i> Volunteers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'analytics.php' ? 'active' : ''; ?>" href="analytics.php">
                        <i class="fas fa-chart-pie me-1"></i> Analytics (GROUP BY)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-bold <?= $currentPage === 'queries-showcase.php' ? 'active' : ''; ?>" href="queries-showcase.php">
                        <i class="fas fa-code me-1"></i> 10+ SQL Queries (Viva)
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <a href="setup_db.php" class="btn btn-outline-light btn-sm me-2" title="Reset or Setup Database">
                    <i class="fas fa-database me-1"></i> DB Setup
                </a>
                <a href="event-create.php" class="btn btn-warning btn-sm fw-bold">
                    <i class="fas fa-bullhorn me-1"></i> Post Event
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container main-content my-4">
    <?= displayFlashMessage(); ?>
