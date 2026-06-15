<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'turizam_sobe');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Turizam - Rezervacija Soba');
define('APP_URL', '/');

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

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDate(string $date): string
{
    return date('d.m.Y', strtotime($date));
}

function formatPrice(float $price): string
{
    return number_format($price, 2, ',', '.') . ' €';
}

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

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

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

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
