<?php
// Generate bcrypt hash for password
$password = 'eyaisbichA2701';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: $password\n";
echo "Hash: $hash\n";
