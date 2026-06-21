<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();  // samo admin vidi kontrolnu tablu

$adminPage = 'dashboard';
$currentPage = 'admin';
$pageTitle = 'Administracija';

$db = getDB();

// Brzi pregled ključnih brojki za dashboard kartice
$stats = [
    'sobe' => (int) $db->query('SELECT COUNT(*) FROM sobe WHERE dostupna = 1')->fetchColumn(),
    'rezervacije_aktivne' => (int) $db->query("SELECT COUNT(*) FROM rezervacije WHERE status IN ('na_cekanju', 'potvrdena')")->fetchColumn(),
    'na_cekanju' => (int) $db->query("SELECT COUNT(*) FROM rezervacije WHERE status = 'na_cekanju'")->fetchColumn(),
    'korisnici' => (int) $db->query('SELECT COUNT(*) FROM korisnici WHERE aktivan = 1')->fetchColumn(),
];

// Posljednjih 5 rezervacija za brzi pregled — JOIN za ime gosta i naziv sobe
$recentne = $db->query('
    SELECT r.*, s.naziv AS soba_naziv, k.ime, k.prezime
    FROM rezervacije r
    JOIN sobe s ON s.id = r.soba_id
    JOIN korisnici k ON k.id = r.korisnik_id
    ORDER BY r.kreiran_at DESC
    LIMIT 5
')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-content">
        <h1>Kontrolna tabla</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?= $stats['sobe'] ?></span>
                <span class="stat-label">Dostupne sobe</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= $stats['rezervacije_aktivne'] ?></span>
                <span class="stat-label">Aktivne rezervacije</span>
            </div>
            <div class="stat-card stat-warning">
                <span class="stat-value"><?= $stats['na_cekanju'] ?></span>
                <span class="stat-label">Na čekanju</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= $stats['korisnici'] ?></span>
                <span class="stat-label">Korisnici</span>
            </div>
        </div>

        <section class="admin-section">
            <h2>Nedavne rezervacije</h2>
            <?php if (empty($recentne)): ?>
                <p class="text-muted">Nema rezervacija.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Gost</th>
                                <th>Soba</th>
                                <th>Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentne as $r): ?>
                                <tr>
                                    <td><?= e($r['ime'] . ' ' . $r['prezime']) ?></td>
                                    <td><?= e($r['soba_naziv']) ?></td>
                                    <td><?= formatDate($r['datum_od']) ?> — <?= formatDate($r['datum_do']) ?></td>
                                    <td><span class="status status-<?= e($r['status']) ?>"><?= e(statusRezervacijeLabel($r['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
