<?php
/**
 * Zajednički HTML header za sve stranice.
 * Svaka stranica prije include-a postavlja $currentPage i $pageTitle.
 */
require_once __DIR__ . '/auth.php';

$currentPage = $currentPage ?? '';
$user = currentUser();  // null ako korisnik nije prijavljen
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="<?= e(APP_URL) ?>index.php" class="logo"><?= e(APP_NAME) ?></a>
            <nav class="nav">
                <a href="<?= e(APP_URL) ?>index.php" class="<?= $currentPage === 'sobe' ? 'active' : '' ?>">Sobe</a>
                <?php if ($user): ?>
                    <!-- Meni za prijavljenog korisnika -->
                    <a href="<?= e(APP_URL) ?>moje-rezervacije.php" class="<?= $currentPage === 'rezervacije' ? 'active' : '' ?>">Moje rezervacije</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?= e(APP_URL) ?>admin/index.php" class="<?= str_starts_with($currentPage, 'admin') ? 'active' : '' ?>">Administracija</a>
                    <?php endif; ?>
                    <span class="user-greeting">Zdravo, <?= e($user['ime']) ?></span>
                    <a href="<?= e(APP_URL) ?>logout.php" class="btn btn-outline btn-sm">Odjava</a>
                <?php else: ?>
                    <!-- Meni za gosta (neprijavljenog) -->
                    <a href="<?= e(APP_URL) ?>login.php" class="<?= $currentPage === 'login' ? 'active' : '' ?>">Prijava</a>
                    <a href="<?= e(APP_URL) ?>register.php" class="btn btn-primary btn-sm">Registracija</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?php $flash = getFlash(); if ($flash): ?>
                <!-- Poruka nakon redirect-a (npr. uspješna prijava ili greška) -->
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
