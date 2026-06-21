<?php
require_once __DIR__ . '/includes/auth.php';

$currentPage = 'sobe';
$pageTitle = 'Pregled soba';

$tip = $_GET['tip'] ?? '';
$pretraga = trim($_GET['pretraga'] ?? '');
$perPage = 6;
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = 'WHERE dostupna = 1';
$params = [];

if ($tip && in_array($tip, ['jednokrevetna', 'dvokrevetna', 'apartman', 'suite'])) {
    $where .= ' AND tip = ?';
    $params[] = $tip;
}

if ($pretraga !== '') {
    $where .= ' AND (naziv LIKE ? OR opis LIKE ?)';
    $params[] = "%$pretraga%";
    $params[] = "%$pretraga%";
}

$countStmt = getDB()->prepare("SELECT COUNT(*) FROM sobe $where");
$countStmt->execute($params);
$totalSoba = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalSoba / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM sobe $where ORDER BY cijena_po_noci ASC LIMIT $perPage OFFSET $offset";

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$sobe = $stmt->fetchAll();

function paginationUrl(int $pageNum, string $tipFilter, string $pretragaFilter): string
{
    $query = ['page' => $pageNum];
    if ($tipFilter !== '') {
        $query['tip'] = $tipFilter;
    }
    if ($pretragaFilter !== '') {
        $query['pretraga'] = $pretragaFilter;
    }

    return 'index.php?' . http_build_query($query);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Dobrodošli u naš hotel</h1>
    <p>Pregledajte dostupne sobe i rezervišite svoj boravak online.</p>
    <a href="dokumentacija.html" class="btn btn-outline hero-docs-btn">Dokumentacija projekta</a>
    <a href="php-fajlovi.html" class="btn btn-outline hero-docs-btn">PHP fajlovi</a>
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
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Filtriraj</button>
            <?php if ($tip || $pretraga): ?>
                <a href="index.php" class="btn btn-outline">Poništi</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if (empty($sobe)): ?>
    <div class="empty-state">
        <p>Nema soba koje odgovaraju vašim kriterijumima.</p>
    </div>
<?php else: ?>
    <p class="results-info">
        Prikazano <?= count($sobe) ?> od <?= $totalSoba ?> soba
        <?php if ($totalPages > 1): ?>
            — stranica <?= $page ?> / <?= $totalPages ?>
        <?php endif; ?>
    </p>

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

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Stranice">
            <?php if ($page > 1): ?>
                <a href="<?= e(paginationUrl($page - 1, $tip, $pretraga)) ?>" class="pagination-btn">&larr; Prethodna</a>
            <?php endif; ?>

            <div class="pagination-pages">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= e(paginationUrl($i, $tip, $pretraga)) ?>"
                       class="pagination-page <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if ($page < $totalPages): ?>
                <a href="<?= e(paginationUrl($page + 1, $tip, $pretraga)) ?>" class="pagination-btn">Sljedeća &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
