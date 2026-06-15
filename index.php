<?php
require_once __DIR__ . '/includes/auth.php';

$currentPage = 'sobe';
$pageTitle = 'Pregled soba';

$tip = $_GET['tip'] ?? '';
$pretraga = trim($_GET['pretraga'] ?? '');

$sql = 'SELECT * FROM sobe WHERE dostupna = 1';
$params = [];

if ($tip && in_array($tip, ['jednokrevetna', 'dvokrevetna', 'apartman', 'suite'])) {
    $sql .= ' AND tip = ?';
    $params[] = $tip;
}

if ($pretraga !== '') {
    $sql .= ' AND (naziv LIKE ? OR opis LIKE ?)';
    $params[] = "%$pretraga%";
    $params[] = "%$pretraga%";
}

$sql .= ' ORDER BY cijena_po_noci ASC';

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$sobe = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Dobrodošli u naš hotel</h1>
    <p>Pregledajte dostupne sobe i rezervišite svoj boravak online.</p>
</section>

<section class="filters">
    <form method="GET" class="filter-form">
        <div class="form-group">
            <label for="pretraga">Pretraga</label>
            <input type="text" id="pretraga" name="pretraga" value="<?= e($pretraga) ?>" placeholder="Naziv ili opis sobe...">
        </div>
        <div class="form-group">
            <label for="tip">Tip sobe</label>
            <select id="tip" name="tip">
                <option value="">Svi tipovi</option>
                <option value="jednokrevetna" <?= $tip === 'jednokrevetna' ? 'selected' : '' ?>>Jednokrevetna</option>
                <option value="dvokrevetna" <?= $tip === 'dvokrevetna' ? 'selected' : '' ?>>Dvokrevetna</option>
                <option value="apartman" <?= $tip === 'apartman' ? 'selected' : '' ?>>Apartman</option>
                <option value="suite" <?= $tip === 'suite' ? 'selected' : '' ?>>Suite</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtriraj</button>
        <?php if ($tip || $pretraga): ?>
            <a href="index.php" class="btn btn-outline">Poništi</a>
        <?php endif; ?>
    </form>
</section>

<?php if (empty($sobe)): ?>
    <div class="empty-state">
        <p>Nema soba koje odgovaraju vašim kriterijumima.</p>
    </div>
<?php else: ?>
    <div class="room-grid">
        <?php foreach ($sobe as $soba): ?>
            <article class="room-card">
                <div class="room-image">
                    <div class="room-placeholder"><?= e(mb_substr($soba['naziv'], 0, 1)) ?></div>
                    <span class="room-badge"><?= e(tipSobeLabel($soba['tip'])) ?></span>
                </div>
                <div class="room-body">
                    <h2><?= e($soba['naziv']) ?></h2>
                    <p class="room-desc"><?= e(mb_substr($soba['opis'], 0, 120)) ?>...</p>
                    <div class="room-meta">
                        <span>Kapacitet: <?= (int) $soba['kapacitet'] ?> osoba</span>
                        <span class="room-price"><?= formatPrice((float) $soba['cijena_po_noci']) ?> / noć</span>
                    </div>
                    <div class="room-actions">
                        <a href="soba.php?id=<?= (int) $soba['id'] ?>" class="btn btn-outline">Detalji</a>
                        <a href="rezervacija.php?soba_id=<?= (int) $soba['id'] ?>" class="btn btn-primary">Rezerviši</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
