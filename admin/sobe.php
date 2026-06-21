<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$adminPage = 'sobe';
$currentPage = 'admin';
$pageTitle = 'Upravljanje sobama';

$db = getDB();

// POST: dodavanje nove sobe ili uključivanje/isključivanje dostupnosti
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dodaj_sobu'])) {
        $stmt = $db->prepare('
            INSERT INTO sobe (naziv, opis, tip, kapacitet, cijena_po_noci, dostupna)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            trim($_POST['naziv']),
            trim($_POST['opis']),
            $_POST['tip'],
            (int) $_POST['kapacitet'],
            (float) $_POST['cijena_po_noci'],
            isset($_POST['dostupna']) ? 1 : 0,  // checkbox — ako nije čekiran, soba je nedostupna
        ]);
        flash('success', 'Soba je dodata.');
    } elseif (isset($_POST['toggle_dostupnost'], $_POST['id'])) {
        // NOT dostupna — prebacuje 1↔0 bez brisanja iz baze
        $db->prepare('UPDATE sobe SET dostupna = NOT dostupna WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Dostupnost sobe je promijenjena.');
    }

    redirect('sobe.php');
}

$sobe = $db->query('SELECT * FROM sobe ORDER BY naziv')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-content">
        <h1>Sobe</h1>

        <section class="admin-section">
            <h2>Dodaj sobu</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="dodaj_sobu" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Naziv</label>
                        <input type="text" name="naziv" required>
                    </div>
                    <div class="form-group">
                        <label>Tip</label>
                        <select name="tip" required>
                            <option value="jednokrevetna">Jednokrevetna</option>
                            <option value="dvokrevetna">Dvokrevetna</option>
                            <option value="apartman">Apartman</option>
                            <option value="suite">Suite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kapacitet</label>
                        <input type="number" name="kapacitet" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Cijena po noći (€)</label>
                        <input type="number" name="cijena_po_noci" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Opis</label>
                    <textarea name="opis" rows="2"></textarea>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="dostupna" checked> Dostupna za rezervaciju
                </label>
                <br><br>
                <button type="submit" class="btn btn-primary">Dodaj sobu</button>
            </form>
        </section>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Naziv</th>
                        <th>Tip</th>
                        <th>Kapacitet</th>
                        <th>Cijena/noć</th>
                        <th>Status</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sobe as $s): ?>
                        <tr>
                            <td><strong><?= e($s['naziv']) ?></strong></td>
                            <td><?= e(tipSobeLabel($s['tip'])) ?></td>
                            <td><?= (int) $s['kapacitet'] ?></td>
                            <td><?= formatPrice((float) $s['cijena_po_noci']) ?></td>
                            <td><?= $s['dostupna'] ? 'Dostupna' : 'Nedostupna' ?></td>
                            <td>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" name="toggle_dostupnost" value="1" class="btn btn-sm btn-outline">
                                        <?= $s['dostupna'] ? 'Onemogući' : 'Omogući' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
