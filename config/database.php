<?php

/**
 * Centralna konfiguracija aplikacije.
 * Sadrži podatke za konekciju na MySQL i pomoćne funkcije koje koriste sve stranice.
 */

// Podaci za pristup MySQL bazi — prilagoditi prema lokalnom okruženju
define('DB_HOST', 'localhost');
define('DB_NAME', 'turizam_sobe');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Turizam - Rezervacija Soba');
define('APP_URL', '/');  // bazni URL za linkove (npr. '/turizam/' ako je u podfolderu)

/**
 * Vraća PDO konekciju na bazu (singleton — kreira se samo jednom po zahtjevu).
 * ERRMODE_EXCEPTION baca grešku ako SQL ne uspije; FETCH_ASSOC vraća asocijativne nizove.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}

/** Escape HTML karaktera — štiti od XSS pri ispisu korisničkih podataka */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Formatira datum iz baze (YYYY-MM-DD) u prikaz dd.mm.yyyy */
function formatDate(string $date): string
{
    return date('d.m.Y', strtotime($date));
}

/** Formatira cijenu za prikaz (npr. 1250.50 → "1.250,50 €") */
function formatPrice(float $price): string
{
    return number_format($price, 2, ',', '.') . ' €';
}

/** Pretvara ENUM tip sobe iz baze u čitljiv naziv na srpskom */
function tipSobeLabel(string $tip): string
{
    $labels = [
        'jednokrevetna' => 'Jednokrevetna',
        'dvokrevetna' => 'Dvokrevetna',
        'apartman' => 'Apartman',
        'suite' => 'Suite',
    ];

    return $labels[$tip] ?? $tip;
}

/** Pretvara ENUM status rezervacije iz baze u čitljiv naziv */
function statusRezervacijeLabel(string $status): string
{
    $labels = [
        'na_cekanju' => 'Na čekanju',
        'potvrdena' => 'Potvrđena',
        'otkazana' => 'Otkazana',
        'zavrsena' => 'Završena',
    ];

    return $labels[$status] ?? $status;
}

/** Preusmjerava na drugu stranicu i zaustavlja izvršavanje skripte */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Pretvara YYYY-MM u čitljiv naziv mjeseca (npr. "2026-06" → "Juni 2026") */
function nazivMjeseca(string $ym): string
{
    $mjeseci = [
        1 => 'Januar', 2 => 'Februar', 3 => 'Mart', 4 => 'April',
        5 => 'Maj', 6 => 'Juni', 7 => 'Juli', 8 => 'Avgust',
        9 => 'Septembar', 10 => 'Oktobar', 11 => 'Novembar', 12 => 'Decembar',
    ];
    $parts = explode('-', $ym);
    $godina = $parts[0] ?? date('Y');
    $mjesec = (int) ($parts[1] ?? date('m'));

    return ($mjeseci[$mjesec] ?? $ym) . ' ' . $godina;
}

/**
 * Sprema poruku u sesiju za prikaz na sljedećoj stranici (nakon redirect-a).
 * $type: 'success' ili 'error'
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Čita i briše flash poruku — prikazuje se samo jednom */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
