<?php
// Copy this file to mail.local.php and fill in credentials for your SMTP provider.
// Keep mail.local.php out of version control.
return [
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls', // tls, ssl, or none
    'from_email' => 'orders@example.com',
    'from_name' => 'Joey Tahian',
    'app_base_url' => 'http://localhost/joey-tahian/frontend'
];
