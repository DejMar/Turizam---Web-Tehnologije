<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/index.php' : 'index.php');
}

$currentPage = 'login';
$pageTitle = 'Prijava';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login($email, $password)) {
        flash('success', 'Uspješno ste se prijavili.');
        redirect(isAdmin() ? 'admin/index.php' : 'index.php');
    }

    flash('error', 'Pogrešan email ili lozinka.');
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card">
        <h1>Prijava</h1>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Lozinka</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Prijavi se</button>
        </form>
        <p class="auth-footer">Nemate nalog? <a href="register.php">Registrujte se</a></p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
