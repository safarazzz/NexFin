<?php
// Titik masuk sederhana: arahkan ke dashboard atau login.
session_start();
header('Location: ' . (isset($_SESSION['user']) ? 'dashboard.php' : 'login.php'));
exit;
