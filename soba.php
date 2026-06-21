<?php
require_once __DIR__ . '/includes/auth.php';

// ID sobe iz URL-a (npr. soba.php?id=3)
$id = (int) ($_GET['id'] ?? 0);

$stmt = getDB()->prepare('SELECT * FROM sobe WHERE id = ? AND dostupna = 1');
$stmt->execute([$id]);
$soba = $stmt->fetch();

// Soba ne postoji ili je onemogućena — vrati korisnika na listu
if (!$soba) {
    flash('error', 'Soba nije pronađena.');
    redirect('index.php');
}

$currentPage = 'sobe';
$pageTitle = $soba['naziv'];

require_once __DIR__ . '/includes/header.php';
?>

<section class="room-detail">
    <a href="index.php" class="back-link">&larr; Nazad na sobe</a>

    <div class="room-detail-grid">
        <div class="room-detail-image">
            <div class="room-placeholder large"><?= e(mb_substr($soba['naziv'], 0, 1)) ?></div>
        </div>
        <div class="room-detail-info">
            <span class="badge"><?= e(tipSobeLabel($soba['tip'])) ?></span>
            <h1><?= e($soba['naziv']) ?></h1>
            <p class="room-full-desc"><?= e($soba['opis']) ?></p>

            <ul class="feature-list">
                <li>Kapacitet: <strong><?= (int) $soba['kapacitet'] ?> osoba</strong></li>
                <li>Cijena: <strong><?= formatPrice((float) $soba['cijena_po_noci']) ?> po noći</strong></li>
                <li>Status: <strong>Dostupna</strong></li>
            </ul>

            <a href="rezervacija.php?soba_id=<?= (int) $soba['id'] ?>" class="btn btn-primary btn-lg">Rezerviši ovu sobu</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
