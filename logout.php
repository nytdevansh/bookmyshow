<?php
require_once __DIR__ . '/auth.php';
session_destroy();
header('Location: ' . BASE_PATH . '/index.php');
exit;