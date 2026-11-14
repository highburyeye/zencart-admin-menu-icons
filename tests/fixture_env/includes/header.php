<?php
$upperMenuArray = [];
require $reference . '/admin/includes/header_navigation.php';
if (isset($_SESSION['fixture_notice'])) {
    echo '<p class="alert alert-success">' . htmlspecialchars($_SESSION['fixture_notice']) . '</p>';
    unset($_SESSION['fixture_notice']);
}
