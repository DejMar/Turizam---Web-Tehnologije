<?php
/**
 * Jednokratna instalacija baze i admin naloga.
 * Pokrenite: php setup.php
 */
require_once __DIR__ . '/config/database.php';

echo "=== Instalacija " . APP_NAME . " ===\n\n";

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }

    echo "Baza podataka kreirana.\n";

    $pdo = getDB();

    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $gostHash = password_hash('gost123', PASSWORD_DEFAULT);

    $pdo->exec("DELETE FROM korisnici");

    $stmt = $pdo->prepare('INSERT INTO korisnici (ime, prezime, email, lozinka, uloga) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['Admin', 'Sistem', 'admin@turizam.rs', $adminHash, 'admin']);
    $stmt->execute(['Marko', 'Petrović', 'gost@turizam.rs', $gostHash, 'gost']);

    $gostId = (int) $pdo->lastInsertId();

    $pdo->prepare('
        INSERT INTO rezervacije (korisnik_id, soba_id, datum_od, datum_do, broj_gostiju, ukupna_cijena, status, napomena)
        VALUES (?, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 1, 135.00, "potvrdena", "Rani check-in po mogućnosti")
    ')->execute([$gostId]);

    $pdo->prepare('
        INSERT INTO rezervacije (korisnik_id, soba_id, datum_od, datum_do, broj_gostiju, ukupna_cijena, status, napomena)
        VALUES (?, 3, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 3, 360.00, "na_cekanju", "Potreban dodatni krevet")
    ')->execute([$gostId]);

    echo "Korisnici kreirani:\n";
    echo "  Admin: admin@turizam.rs / admin123\n";
    echo "  Gost:  gost@turizam.rs / gost123\n\n";
    echo "Instalacija završena. Pokrenite PHP server: php -S localhost:8000\n";

} catch (PDOException $e) {
    echo "Greška: " . $e->getMessage() . "\n";
    exit(1);
}
