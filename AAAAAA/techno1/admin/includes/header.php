<?php
// technowatch/admin/includes/header.php

// 1. Start the Session and Security Check
session_start();

// Ensure the user is logged in. If not, redirect them to the login page.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Include Database Connection (required for data fetching on all pages)
require_once 'db_connect.php'; 

// Fetch user info for display (from session)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']); // Used to highlight the active menu item
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technowatch Club Admin Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        #wrapper { display: flex; }
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -15rem;
            transition: margin .25s ease-out;
            width: 15rem; /* Fixed width for sidebar */
            background-color: #343a40; /* Dark background */
            color: white;
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
            background-color: #495057;
            text-align: center;
        }
        #page-content-wrapper { min-width: 100vw; }
        .list-group-item-action { 
            color: #ccc; 
            background-color: transparent; 
            border: none;
        }
        .list-group-item-action:hover, .list-group-item-action.active {
            color: white;
            background-color: #007bff; /* Primary color highlight */
        }
        /* Active class to highlight the current page in the sidebar */
        a[href$="<?php echo $current_page; ?>"] {
             background-color: #007bff !important;
             color: white !important;
        }
    </style>
</head>
<body>

<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">Admin Panel</div>
        <div class="list-group list-group-flush">
            <a href="index.php" class="list-group-item list-group-item-action">🏠 Dashboard</a>
            <a href="manage_index.php" class="list-group-item list-group-item-action">📝 Homepage Content</a>
            <a href="manage_events_news.php" class="list-group-item list-group-item-action">📰 Events & News</a>
            <a href="manage_projects.php" class="list-group-item list-group-item-action">🚧 Projects</a>
            <a href="manage_jobs.php" class="list-group-item list-group-item-action">💼 Job Postings</a>
            <a href="manage_merch.php" class="list-group-item list-group-item-action">🛍️ Merchandise</a>
            <a href="manage_officials.php" class="list-group-item list-group-item-action">🧑‍🏫 Officials</a>
            <a href="manage_officers.php" class="list-group-item list-group-item-action">🧑‍💼 Officers</a>
        </div>
    </div>
    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <div class="container-fluid">
                <span class="navbar-brand">Welcome, <?php echo htmlspecialchars($admin_username); ?>!</span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </nav>
        
        <div class="container-fluid py-4">