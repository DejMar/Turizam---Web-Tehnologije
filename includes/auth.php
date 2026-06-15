<?php

session_start();

require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['korisnik_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = getDB()->prepare('SELECT id, ime, prezime, email, uloga FROM korisnici WHERE id = ? AND aktivan = 1');
    $stmt->execute([$_SESSION['korisnik_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        logout();
        return null;
    }

    return $user;
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user && $user['uloga'] === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Morate biti prijavljeni za pristup ovoj stranici.');
        redirect('login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        flash('error', 'Nemate dozvolu za pristup administraciji.');
        redirect('index.php');
    }
}

function login(string $email, string $password): bool
{
    $stmt = getDB()->prepare('SELECT * FROM korisnici WHERE email = ? AND aktivan = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['lozinka'])) {
        $_SESSION['korisnik_id'] = $user['id'];
        $_SESSION['uloga'] = $user['uloga'];
        return true;
    }

    return false;
}

function logout(): void
{
    session_unset();
    session_destroy();
}

function registerUser(string $ime, string $prezime, string $email, string $password): array
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Neispravna email adresa.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Lozinka mora imati najmanje 6 karaktera.'];
    }

    $stmt = getDB()->prepare('SELECT id FROM korisnici WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email adresa je već registrovana.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = getDB()->prepare('INSERT INTO korisnici (ime, prezime, email, lozinka) VALUES (?, ?, ?, ?)');
    $stmt->execute([$ime, $prezime, $email, $hash]);

    return ['success' => true, 'message' => 'Registracija uspješna. Možete se prijaviti.'];
}

function sobaJeDostupna(int $sobaId, string $datumOd, string $datumDo, ?int $excludeRezervacijaId = null): bool
{
    $sql = "
        SELECT COUNT(*) FROM rezervacije
        WHERE soba_id = ?
        AND status IN ('na_cekanju', 'potvrdena')
        AND datum_od < ?
        AND datum_do > ?
    ";
    $params = [$sobaId, $datumDo, $datumOd];

    if ($excludeRezervacijaId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeRezervacijaId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() === 0;
}

function izracunajCijenu(float $cijenaPoNoci, string $datumOd, string $datumDo): float
{
    $od = new DateTime($datumOd);
    $do = new DateTime($datumDo);
    $nocenja = max(1, (int) $od->diff($do)->days);

    return $nocenja * $cijenaPoNoci;
}
