<?php
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$sobaId = (int) ($_GET['soba_id'] ?? $_POST['soba_id'] ?? 0);

$stmt = getDB()->prepare('SELECT * FROM sobe WHERE id = ? AND dostupna = 1');
$stmt->execute([$sobaId]);
$soba = $stmt->fetch();

if (!$soba) {
    flash('error', 'Soba nije dostupna za rezervaciju.');
    redirect('index.php');
}

$currentPage = 'rezervacije';
$pageTitle = 'Rezervacija';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datumOd = $_POST['datum_od'] ?? '';
    $datumDo = $_POST['datum_do'] ?? '';
    $brojGostiju = (int) ($_POST['broj_gostiju'] ?? 1);
    $napomena = trim($_POST['napomena'] ?? '');

    $errors = [];

    if (!$datumOd || !$datumDo) {
        $errors[] = 'Datum dolaska i odlaska su obavezni.';
    } elseif ($datumOd >= $datumDo) {
        $errors[] = 'Datum odlaska mora biti nakon datuma dolaska.';
    } elseif ($datumOd < date('Y-m-d')) {
        $errors[] = 'Datum dolaska ne može biti u prošlosti.';
    }

    if ($brojGostiju < 1 || $brojGostiju > (int) $soba['kapacitet']) {
        $errors[] = 'Broj gostiju mora biti između 1 i ' . $soba['kapacitet'] . '.';
    }

    if (empty($errors) && !sobaJeDostupna($sobaId, $datumOd, $datumDo)) {
        $errors[] = 'Soba nije dostupna u izabranom periodu.';
    }

    if (empty($errors)) {
        $ukupnaCijena = izracunajCijenu((float) $soba['cijena_po_noci'], $datumOd, $datumDo);

        $stmt = getDB()->prepare('
            INSERT INTO rezervacije (korisnik_id, soba_id, datum_od, datum_do, broj_gostiju, ukupna_cijena, napomena)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $_SESSION['korisnik_id'],
            $sobaId,
            $datumOd,
            $datumDo,
            $brojGostiju,
            $ukupnaCijena,
            $napomena ?: null,
        ]);

        flash('success', 'Rezervacija je uspješno poslata. Čeka se potvrda administratora.');
        redirect('moje-rezervacije.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="form-section">
    <h1>Rezervacija: <?= e($soba['naziv']) ?></h1>
    <p class="subtitle"><?= e(tipSobeLabel($soba['tip'])) ?> &middot; Kapacitet: <?= (int) $soba['kapacitet'] ?> &middot; <?= formatPrice((float) $soba['cijena_po_noci']) ?> / noć</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="reservation-form" id="reservationForm">
        <input type="hidden" name="soba_id" value="<?= (int) $soba['id'] ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="datum_od">Datum dolaska</label>
                <input type="date" id="datum_od" name="datum_od" required
                       min="<?= date('Y-m-d') ?>"
                       value="<?= e($_POST['datum_od'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="datum_do">Datum odlaska</label>
                <input type="date" id="datum_do" name="datum_do" required
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       value="<?= e($_POST['datum_do'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="broj_gostiju">Broj gostiju</label>
            <input type="number" id="broj_gostiju" name="broj_gostiju" min="1"
                   max="<?= (int) $soba['kapacitet'] ?>" value="<?= (int) ($_POST['broj_gostiju'] ?? 1) ?>" required>
        </div>

        <div class="form-group">
            <label for="napomena">Napomena (opciono)</label>
            <textarea id="napomena" name="napomena" rows="3" placeholder="Posebni zahtjevi..."><?= e($_POST['napomena'] ?? '') ?></textarea>
        </div>

        <div class="price-preview" id="pricePreview" data-price="<?= (float) $soba['cijena_po_noci'] ?>">
            <strong>Procijenjena cijena:</strong> <span id="totalPrice">—</span>
        </div>

        <div class="form-actions">
            <a href="soba.php?id=<?= (int) $soba['id'] ?>" class="btn btn-outline">Otkaži</a>
            <button type="submit" class="btn btn-primary">Pošalji rezervaciju</button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
