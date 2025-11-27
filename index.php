<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockFlow-app</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/mef.css">
</head>

<script src="assets/js/script.js"></script>
<style>
    #report-to-print {
        background-color: #ffffff !important;
        text-align: center;
        font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
        padding: 3rem;
        border: #333 1px solid;
    }

    #report-to-print .report-header h3 {
        text-decoration: underline;
    }

    #report-to-print .report-summary .stats-card {
        font-size: x-large;
        text-decoration: overline;
        text-overflow: ellipsis;
        text-transform: lowercase;
        color: #333;
    }
</style>

<body>
    <?php
    // Start session
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }

    // Include database connection
    require_once 'includes/db_connect.php';

    // Include header
    include 'includes/header.php';

    // Default to dashboard if no page is specified
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

    // Define allowed pages
    $allowed_pages = ['dashboard', 'users', 'suppliers', 'stock', 'transactions', 'reports'];

    // Validate page parameter
    if (!in_array($page, $allowed_pages)) {
        $page = 'dashboard';
    }

    // Include the appropriate page
    include 'pages/' . $page . '.php';

    // Include footer
    include 'includes/footer.php';
    ?>
</body>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/font-awesome.js"></script>
<script src="assets/js/bootstrap.bundle.js"></script>

</html>