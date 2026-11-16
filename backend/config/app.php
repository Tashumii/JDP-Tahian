<?php
// backend/config/app.php

date_default_timezone_set('Asia/Manila'); // Default timezone
session_name('JOEY_TAHIAN_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

