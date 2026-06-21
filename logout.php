<?php
require_once __DIR__ . '/includes/auth.php';

// Uništi sesiju i preusmjeri na početnu
logout();
redirect('index.php');
