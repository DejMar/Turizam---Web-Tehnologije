<?php
require_once __DIR__ . '/includes/auth.php';

// Prijavljeni korisnici ne mogu ponovo da se registruju
if (isLoggedIn()) {
    redirect('index.php');
}

$currentPage = 'register';
$pageTitle = 'Registracija';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // registerUser() validira podatke, hashuje lozinku i upisuje u bazu
    $result = registerUser(
        trim($_POST['ime'] ?? ''),
        trim($_POST['prezime'] ?? ''),
        trim($_POST['email'] ?? ''),
        $_POST['password'] ?? ''
    );

    if ($result['success']) {
        flash('success', $result['message']);
        redirect('login.php');  // nakon registracije ide na prijavu
    }

    flash('error', $result['message']);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card">
        <h1>Registracija</h1>
        <form method="POST" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="ime">Ime</label>
                    <input type="text" id="ime" name="ime" required value="<?= e($_POST['ime'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="prezime">Prezime</label>
                    <input type="text" id="prezime" name="prezime" required value="<?= e($_POST['prezime'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Lozinka</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm">Potvrda lozinke</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Registruj se</button>
        </form>
        <p class="auth-footer">Već imate nalog? <a href="login.php">Prijavite se</a></p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
