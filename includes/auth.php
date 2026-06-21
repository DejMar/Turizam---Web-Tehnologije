<?php

/**
 * Modul za autentifikaciju, autorizaciju i poslovnu logiku rezervacija.
 * Uključuje se na početku skoro svake stranice u projektu.
 */

// Pokreće PHP sesiju — omogućava čuvanje podataka o prijavljenom korisniku
// između učitavanja različitih stranica (mora biti prije bilo kakvog HTML outputa)
session_start();

// Učitava konfiguraciju baze i pomoćne funkcije (getDB, e, flash, redirect...)
require_once __DIR__ . '/../config/database.php';

/** Provjerava da li je korisnik prijavljen (da li postoji ID u sesiji) */
function isLoggedIn(): bool
{
    return isset($_SESSION['korisnik_id']);
}

/**
 * Vraća podatke trenutno prijavljenog korisnika iz baze.
 * Ako korisnik ne postoji ili je deaktiviran — automatski ga odjavljuje.
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    // Učitavamo samo potrebna polja; aktivan = 1 isključuje deaktivirane naloge
    $stmt = getDB()->prepare('SELECT id, ime, prezime, email, uloga FROM korisnici WHERE id = ? AND aktivan = 1');
    $stmt->execute([$_SESSION['korisnik_id']]);
    $user = $stmt->fetch();

    // Sesija postoji ali korisnik više nije validan — čistimo sesiju
    if (!$user) {
        logout();
        return null;
    }

    return $user;
}

/** Provjerava da li prijavljeni korisnik ima administratorsku ulogu */
function isAdmin(): bool
{
    $user = currentUser();
    return $user && $user['uloga'] === 'admin';
}

/**
 * Zaštita stranica koje zahtijevaju prijavu (npr. rezervacija, moje-rezervacije).
 * Neprijavljenog korisnika preusmjerava na login sa porukom.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Morate biti prijavljeni za pristup ovoj stranici.');
        redirect('login.php');
    }
}

/**
 * Zaštita admin stranica — prvo provjerava prijavu, zatim ulogu admin.
 * Običan gost se preusmjerava na početnu stranicu.
 */
function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        flash('error', 'Nemate dozvolu za pristup administraciji.');
        redirect('index.php');
    }
}

/**
 * Prijava korisnika — provjerava email i lozinku protiv baze.
 * Uspjeh: sprema korisnik_id i ulogu u sesiju.
 */
function login(string $email, string $password): bool
{
    // Prepared statement štiti od SQL injection napada
    $stmt = getDB()->prepare('SELECT * FROM korisnici WHERE email = ? AND aktivan = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // password_verify upoređuje plain-text lozinku sa bcrypt hašom iz baze
    if ($user && password_verify($password, $user['lozinka'])) {
        $_SESSION['korisnik_id'] = $user['id'];
        $_SESSION['uloga'] = $user['uloga'];
        return true;
    }

    return false;
}

/** Briše sve podatke iz sesije — koristi se pri odjavi */
function logout(): void
{
    session_unset();
    session_destroy();
}

/**
 * Registracija novog korisnika (uloga: gost).
 * Vraća niz sa 'success' i 'message' za prikaz korisniku.
 */
function registerUser(string $ime, string $prezime, string $email, string $password): array
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Neispravna email adresa.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Lozinka mora imati najmanje 6 karaktera.'];
    }

    // Provjera da email još nije zauzet
    $stmt = getDB()->prepare('SELECT id FROM korisnici WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email adresa je već registrovana.'];
    }

    // Lozinka se nikad ne čuva u plain textu — samo bcrypt hash
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = getDB()->prepare('INSERT INTO korisnici (ime, prezime, email, lozinka) VALUES (?, ?, ?, ?)');
    $stmt->execute([$ime, $prezime, $email, $hash]);

    return ['success' => true, 'message' => 'Registracija uspješna. Možete se prijaviti.'];
}

/**
 * Provjerava da li je soba slobodna u zadanom periodu.
 * Uzima u obzir samo aktivne rezervacije (na čekanju i potvrđene).
 *
 * Logika preklapanja: postojeća rezervacija preklapa novi period ako:
 *   postojeci_datum_od < novi_datum_do  AND  postojeci_datum_do > novi_datum_od
 */
function sobaJeDostupna(int $sobaId, string $datumOd, string $datumDo, ?int $excludeRezervacijaId = null): bool
{
    $sql = "
        SELECT COUNT(*) FROM rezervacije
        WHERE soba_id = ?
        AND status IN ('na_cekanju', 'potvrdena')
        AND datum_od < ?
        AND datum_do > ?
    ";
    // Parametri: soba, kraj novog perioda, početak novog perioda
    $params = [$sobaId, $datumDo, $datumOd];

    // Za buduće uređivanje rezervacije — isključuje samu sebe iz provjere
    if ($excludeRezervacijaId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeRezervacijaId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    // COUNT = 0 znači nema preklapanja → soba je dostupna
    return (int) $stmt->fetchColumn() === 0;
}

/**
 * Računa ukupnu cijenu boravka: broj noćenja × cijena po noći.
 * Datum odlaska se ne računa kao noć (hotel pravilo: noć = datum_od do datum_do-1).
 */
function izracunajCijenu(float $cijenaPoNoci, string $datumOd, string $datumDo): float
{
    $od = new DateTime($datumOd);
    $do = new DateTime($datumDo);
    // diff()->days daje razliku u danima; max(1,...) osigurava minimum jednu noć
    $nocenja = max(1, (int) $od->diff($do)->days);

    return $nocenja * $cijenaPoNoci;
}
