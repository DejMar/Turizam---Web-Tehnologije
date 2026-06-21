<?php
require_once __DIR__ . '/includes/auth.php';

// Samo prijavljeni korisnici vide svoje rezervacije
requireLogin();

$currentPage = 'rezervacije';
$pageTitle = 'Moje rezervacije';

// JOIN sa sobe da prikažemo naziv i tip sobe uz svaku rezervaciju
$stmt = getDB()->prepare('
    SELECT r.*, s.naziv AS soba_naziv, s.tip AS soba_tip
    FROM rezervacije r
    JOIN sobe s ON s.id = r.soba_id
    WHERE r.korisnik_id = ?
    ORDER BY r.datum_od DESC
');
$stmt->execute([$_SESSION['korisnik_id']]);  // ID iz sesije — korisnik vidi samo svoje
$rezervacije = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Moje rezervacije</h1>
    <a href="index.php" class="btn btn-primary">Nova rezervacija</a>
</section>

<?php if (empty($rezervacije)): ?>
    <div class="empty-state">
        <p>Nemate aktivnih rezervacija.</p>
        <a href="index.php" class="btn btn-primary">Pregledaj sobe</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Soba</th>
                    <th>Period</th>
                    <th>Gosti</th>
                    <th>Cijena</th>
                    <th>Status</th>
                    <th>Datum rezervacije</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rezervacije as $r): ?>
                    <tr>
                        <td>
                            <strong><?= e($r['soba_naziv']) ?></strong><br>
                            <small><?= e(tipSobeLabel($r['soba_tip'])) ?></small>
                        </td>
                        <td><?= formatDate($r['datum_od']) ?> — <?= formatDate($r['datum_do']) ?></td>
                        <td><?= (int) $r['broj_gostiju'] ?></td>
                        <td><?= formatPrice((float) $r['ukupna_cijena']) ?></td>
                        <td><span class="status status-<?= e($r['status']) ?>"><?= e(statusRezervacijeLabel($r['status'])) ?></span></td>
                        <td><?= formatDate($r['kreiran_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
