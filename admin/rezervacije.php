<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$adminPage = 'rezervacije';
$currentPage = 'admin';
$pageTitle = 'Upravljanje rezervacijama';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int) $_POST['id'];
    $action = $_POST['action'];

    if (in_array($action, ['potvrdena', 'otkazana', 'zavrsena', 'na_cekanju'])) {
        $stmt = getDB()->prepare('UPDATE rezervacije SET status = ? WHERE id = ?');
        $stmt->execute([$action, $id]);
        flash('success', 'Status rezervacije je ažuriran.');
    }

    redirect('rezervacije.php');
}

$statusFilter = $_GET['status'] ?? '';
$idPretraga = ltrim(trim($_GET['id'] ?? ''), '#');
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$fromJoin = '
    FROM rezervacije r
    JOIN sobe s ON s.id = r.soba_id
    JOIN korisnici k ON k.id = r.korisnik_id
';

$where = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['na_cekanju', 'potvrdena', 'otkazana', 'zavrsena'])) {
    $where[] = 'r.status = ?';
    $params[] = $statusFilter;
}

if ($idPretraga !== '' && ctype_digit($idPretraga)) {
    $where[] = 'r.id = ?';
    $params[] = (int) $idPretraga;
}

$whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = getDB()->prepare("SELECT COUNT(*) $fromJoin $whereClause");
$countStmt->execute($params);
$totalRezervacija = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRezervacija / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT r.*, s.naziv AS soba_naziv, k.ime, k.prezime, k.email
    $fromJoin
    $whereClause
    ORDER BY r.kreiran_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$rezervacije = $stmt->fetchAll();

function rezervacijePaginationUrl(int $pageNum, string $status, string $id): string
{
    $query = ['page' => $pageNum];
    if ($status !== '') {
        $query['status'] = $status;
    }
    if ($id !== '') {
        $query['id'] = $id;
    }

    return 'rezervacije.php?' . http_build_query($query);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h1>Rezervacije</h1>
            <form method="GET" class="admin-filters">
                <input type="text" name="id" value="<?= e($idPretraga) ?>" placeholder="Pretraga po ID (npr. 5)" class="admin-search-input">
                <select name="status">
                    <option value="">Svi statusi</option>
                    <option value="na_cekanju" <?= $statusFilter === 'na_cekanju' ? 'selected' : '' ?>>Na čekanju</option>
                    <option value="potvrdena" <?= $statusFilter === 'potvrdena' ? 'selected' : '' ?>>Potvrđene</option>
                    <option value="otkazana" <?= $statusFilter === 'otkazana' ? 'selected' : '' ?>>Otkazane</option>
                    <option value="zavrsena" <?= $statusFilter === 'zavrsena' ? 'selected' : '' ?>>Završene</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Pretraži</button>
                <?php if ($statusFilter || $idPretraga !== ''): ?>
                    <a href="rezervacije.php" class="btn btn-outline btn-sm">Poništi</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($rezervacije)): ?>
            <div class="empty-state">
                <p>Nema rezervacija koje odgovaraju kriterijumima pretrage.</p>
            </div>
        <?php else: ?>
            <p class="results-info">
                Prikazano <?= count($rezervacije) ?> od <?= $totalRezervacija ?> rezervacija
                <?php if ($totalPages > 1): ?>
                    — stranica <?= $page ?> / <?= $totalPages ?>
                <?php endif; ?>
            </p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gost</th>
                            <th>Soba</th>
                            <th>Period</th>
                            <th>Gosti</th>
                            <th>Cijena</th>
                            <th>Status</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rezervacije as $r): ?>
                            <tr>
                                <td>#<?= (int) $r['id'] ?></td>
                                <td>
                                    <?= e($r['ime'] . ' ' . $r['prezime']) ?><br>
                                    <small><?= e($r['email']) ?></small>
                                </td>
                                <td><?= e($r['soba_naziv']) ?></td>
                                <td><?= formatDate($r['datum_od']) ?> — <?= formatDate($r['datum_do']) ?></td>
                                <td><?= (int) $r['broj_gostiju'] ?></td>
                                <td><?= formatPrice((float) $r['ukupna_cijena']) ?></td>
                                <td><span class="status status-<?= e($r['status']) ?>"><?= e(statusRezervacijeLabel($r['status'])) ?></span></td>
                                <td class="actions-cell">
                                    <?php if ($r['status'] === 'na_cekanju'): ?>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" name="action" value="potvrdena" class="btn btn-sm btn-success">Potvrdi</button>
                                            <button type="submit" name="action" value="otkazana" class="btn btn-sm btn-danger">Otkaži</button>
                                        </form>
                                    <?php elseif ($r['status'] === 'potvrdena'): ?>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" name="action" value="zavrsena" class="btn btn-sm btn-outline">Završi</button>
                                            <button type="submit" name="action" value="otkazana" class="btn btn-sm btn-danger">Otkaži</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Stranice rezervacija">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(rezervacijePaginationUrl($page - 1, $statusFilter, $idPretraga)) ?>" class="pagination-btn">&larr; Prethodna</a>
                    <?php endif; ?>

                    <div class="pagination-pages">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= e(rezervacijePaginationUrl($i, $statusFilter, $idPretraga)) ?>"
                               class="pagination-page <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(rezervacijePaginationUrl($page + 1, $statusFilter, $idPretraga)) ?>" class="pagination-btn">Sljedeća &rarr;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
