<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$adminPage = 'izvjestaji';
$currentPage = 'admin';
$pageTitle = 'Izvještaji o zauzetosti';

$db = getDB();

// Mjesec iz URL-a (?mjesec=2026-06) ili trenutni mjesec po defaultu
$mjesec = $_GET['mjesec'] ?? date('Y-m');
$godina = (int) substr($mjesec, 0, 4);
$mjesecBroj = (int) substr($mjesec, 5, 2);

$prviDan = "$mjesec-01";
$posljednjiDan = date('Y-m-t', strtotime($prviDan));  // zadnji dan u mjesecu
$brojDana = (int) date('t', strtotime($prviDan));

$sobe = $db->query('SELECT id, naziv, tip FROM sobe ORDER BY naziv')->fetchAll();

$zauzetostPoSobi = [];
$ukupnoNocenja = 0;
$maksNocenja = count($sobe) * $brojDana;  // ukupno mogućih noćenja = sobe × dani u mjesecu

foreach ($sobe as $soba) {
    // Uzimamo samo potvrđene i završene rezervacije koje se preklapaju sa mjesecom
    $stmt = $db->prepare("
        SELECT datum_od, datum_do FROM rezervacije
        WHERE soba_id = ?
        AND status IN ('potvrdena', 'zavrsena')
        AND datum_od <= ?
        AND datum_do > ?
    ");
    $stmt->execute([$soba['id'], $posljednjiDan, $prviDan]);
    $rezervacije = $stmt->fetchAll();

    // Za svaki dan u mjesecu provjeravamo da li je soba zauzeta
    $zauzetiDani = 0;
    for ($dan = 1; $dan <= $brojDana; $dan++) {
        $trenutniDatum = sprintf('%s-%02d', $mjesec, $dan);
        foreach ($rezervacije as $rez) {
            // Hotel pravilo: noć = datum >= od AND datum < do (datum odlaska se ne računa)
            if ($trenutniDatum >= $rez['datum_od'] && $trenutniDatum < $rez['datum_do']) {
                $zauzetiDani++;
                break;  // jedan dan se računa samo jednom po sobi
            }
        }
    }

    $postotak = $brojDana > 0 ? round(($zauzetiDani / $brojDana) * 100, 1) : 0;
    $zauzetostPoSobi[] = [
        'soba' => $soba,
        'zauzeti_dani' => $zauzetiDani,
        'slobodni_dani' => $brojDana - $zauzetiDani,
        'postotak' => $postotak,
    ];
    $ukupnoNocenja += $zauzetiDani;
}

$ukupnaZauzetost = $maksNocenja > 0 ? round(($ukupnoNocenja / $maksNocenja) * 100, 1) : 0;

// Prihod = suma ukupna_cijena za rezervacije čiji datum_od pada u izabrani mjesec
$prihod = $db->prepare("
    SELECT COALESCE(SUM(ukupna_cijena), 0) FROM rezervacije
    WHERE status IN ('potvrdena', 'zavrsena')
    AND datum_od >= ? AND datum_od <= ?
");
$prihod->execute([$prviDan, $posljednjiDan]);
$ukupniPrihod = (float) $prihod->fetchColumn();

// Broj rezervacija po statusu u izabranom mjesecu
$statusi = $db->prepare("
    SELECT status, COUNT(*) as broj FROM rezervacije
    WHERE datum_od >= ? AND datum_od <= ?
    GROUP BY status
");
$statusi->execute([$prviDan, $posljednjiDan]);
$statistikaStatusa = $statusi->fetchAll();

// Padajući meni: posljednjih 6 mjeseci + sljedeći mjesec
$mjeseci = [];
for ($i = -5; $i <= 1; $i++) {
    $mjeseci[] = date('Y-m', strtotime("$i months", strtotime(date('Y-m-01'))));
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h1>Izvještaj o zauzetosti</h1>
            <form method="GET" class="inline-filter">
                <select name="mjesec" onchange="this.form.submit()">
                    <?php foreach ($mjeseci as $m): ?>
                        <option value="<?= e($m) ?>" <?= $m === $mjesec ? 'selected' : '' ?>>
                            <?= e(nazivMjeseca($m)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?= $ukupnaZauzetost ?>%</span>
                <span class="stat-label">Ukupna zauzetost</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= $ukupnoNocenja ?></span>
                <span class="stat-label">Zauzeta noćenja</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= $maksNocenja - $ukupnoNocenja ?></span>
                <span class="stat-label">Slobodna noćenja</span>
            </div>
            <div class="stat-card stat-success">
                <span class="stat-value"><?= formatPrice($ukupniPrihod) ?></span>
                <span class="stat-label">Prihod u mjesecu</span>
            </div>
        </div>

        <section class="admin-section">
            <h2>Zauzetost po sobama</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Soba</th>
                            <th>Tip</th>
                            <th>Zauzeto dana</th>
                            <th>Slobodno dana</th>
                            <th>Zauzetost</th>
                            <th>Grafikon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zauzetostPoSobi as $z): ?>
                            <tr>
                                <td><?= e($z['soba']['naziv']) ?></td>
                                <td><?= e(tipSobeLabel($z['soba']['tip'])) ?></td>
                                <td><?= $z['zauzeti_dani'] ?></td>
                                <td><?= $z['slobodni_dani'] ?></td>
                                <td><strong><?= $z['postotak'] ?>%</strong></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $z['postotak'] ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-section">
            <h2>Rezervacije po statusu</h2>
            <?php if (empty($statistikaStatusa)): ?>
                <p class="text-muted">Nema rezervacija za izabrani mjesec.</p>
            <?php else: ?>
                <div class="status-stats">
                    <?php foreach ($statistikaStatusa as $s): ?>
                        <div class="status-stat-item">
                            <span class="status status-<?= e($s['status']) ?>"><?= e(statusRezervacijeLabel($s['status'])) ?></span>
                            <strong><?= (int) $s['broj'] ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
