<?php
// ============================================================
// LUXEBYLUCIA — Logout
// ============================================================
session_start();
session_unset();
session_destroy();

header('Location: /luxebylucia/index.php');
exit;
