<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$adminPage = 'korisnici';
$currentPage = 'admin';
$pageTitle = 'Upravljanje korisnicima';

$db = getDB();

// POST: deaktivacija, aktivacija ili promjena uloge postojećeg korisnika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int) $_POST['id'];
    $action = $_POST['action'];
    $currentUserId = (int) $_SESSION['korisnik_id'];

    // Admin ne može sam sebe deaktivirati ili promijeniti ulogu — sprječava zaključavanje
    if ($id === $currentUserId) {
        flash('error', 'Ne možete mijenjati vlastiti nalog na ovaj način.');
    } elseif ($action === 'deaktiviraj') {
        $db->prepare('UPDATE korisnici SET aktivan = 0 WHERE id = ?')->execute([$id]);
        flash('success', 'Korisnik je deaktiviran.');
    } elseif ($action === 'aktiviraj') {
        $db->prepare('UPDATE korisnici SET aktivan = 1 WHERE id = ?')->execute([$id]);
        flash('success', 'Korisnik je aktiviran.');
    } elseif ($action === 'promijeni_ulogu') {
        $novaUloga = $_POST['uloga'] ?? '';
        if (in_array($novaUloga, ['admin', 'gost'])) {
            $db->prepare('UPDATE korisnici SET uloga = ? WHERE id = ?')->execute([$novaUloga, $id]);
            flash('success', 'Uloga korisnika je promijenjena.');
        }
    }

    redirect('korisnici.php');
}

// POST: admin ručno dodaje novog korisnika (koristi istu registerUser logiku)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novi_korisnik'])) {
    $result = registerUser(
        trim($_POST['ime'] ?? ''),
        trim($_POST['prezime'] ?? ''),
        trim($_POST['email'] ?? ''),
        $_POST['password'] ?? ''
    );

    // registerUser uvijek kreira gosta — ako je izabrano admin, naknadno ažuriramo ulogu
    if ($result['success'] && isset($_POST['uloga']) && $_POST['uloga'] === 'admin') {
        $db->prepare('UPDATE korisnici SET uloga = "admin" WHERE email = ?')->execute([trim($_POST['email'])]);
    }

    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('korisnici.php');
}

$korisnici = $db->query('SELECT * FROM korisnici ORDER BY kreiran_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-content">
        <h1>Korisnici</h1>

        <section class="admin-section">
            <h2>Dodaj korisnika</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="novi_korisnik" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Ime</label>
                        <input type="text" name="ime" required>
                    </div>
                    <div class="form-group">
                        <label>Prezime</label>
                        <input type="text" name="prezime" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Lozinka</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Uloga</label>
                        <select name="uloga">
                            <option value="gost">Gost</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Dodaj</button>
            </form>
        </section>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ime</th>
                        <th>Email</th>
                        <th>Uloga</th>
                        <th>Status</th>
                        <th>Registrovan</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($korisnici as $k): ?>
                        <tr class="<?= !$k['aktivan'] ? 'row-inactive' : '' ?>">
                            <td><?= e($k['ime'] . ' ' . $k['prezime']) ?></td>
                            <td><?= e($k['email']) ?></td>
                            <td>
                                <?php if ((int) $k['id'] !== (int) $_SESSION['korisnik_id']): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                                        <input type="hidden" name="action" value="promijeni_ulogu">
                                        <select name="uloga" onchange="this.form.submit()">
                                            <option value="gost" <?= $k['uloga'] === 'gost' ? 'selected' : '' ?>>Gost</option>
                                            <option value="admin" <?= $k['uloga'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span class="badge"><?= e(ucfirst($k['uloga'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $k['aktivan'] ? 'Aktivan' : 'Neaktivan' ?></td>
                            <td><?= formatDate($k['kreiran_at']) ?></td>
                            <td>
                                <?php if ((int) $k['id'] !== (int) $_SESSION['korisnik_id']): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                                        <?php if ($k['aktivan']): ?>
                                            <button type="submit" name="action" value="deaktiviraj" class="btn btn-sm btn-danger">Deaktiviraj</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="aktiviraj" class="btn btn-sm btn-success">Aktiviraj</button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Vi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
