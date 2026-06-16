# Turizam - Sistem za rezervaciju soba

Početna verzija web aplikacije za pregled soba, online rezervacije, administraciju i izvještaje o zauzetosti.

## Tehnologije

- HTML, CSS, JavaScript
- PHP 8+
- MySQL

## Funkcionalnosti

### Javni dio
- **Pregled soba** — lista dostupnih soba sa filtriranjem po tipu i pretrazi
- **Detalji sobe** — opis, kapacitet i cijena
- **Online rezervacija** — izbor datuma, broja gostiju i automatski izračun cijene
- **Registracija i prijava** korisnika
- **Moje rezervacije** — pregled statusa rezervacija

### Administracija
- **Kontrolna tabla** — statistike i nedavne rezervacije
- **Upravljanje rezervacijama** — potvrda, otkazivanje, završetak
- **Upravljanje sobama** — dodavanje i aktivacija/deaktivacija
- **Upravljanje korisnicima** — dodavanje, uloge, aktivacija
- **Izvještaji o zauzetosti** — mjesečna zauzetost po sobama, prihod, statusi

## Instalacija

### 1. Preduvjeti
- PHP 8.0+ sa PDO MySQL ekstenzijom
- MySQL 5.7+ ili MariaDB
- Web server (Apache/Nginx) ili PHP ugrađeni server

### 2. Konfiguracija baze

Uredite `config/database.php` ako je potrebno:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'turizam_sobe');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Kreiranje baze

```bash
php setup.php
```

### 4. Pokretanje

```bash
php -S localhost:8000
```

Otvorite [http://localhost:8000](http://localhost:8000)

## Test nalozi

| Uloga | Email | Lozinka |
|-------|-------|---------|
| Admin | admin@turizam.ba | admin123 |
| Gost | gost@turizam.ba | gost123 |

## Struktura projekta

```
├── admin/              # Administrativni panel
│   ├── index.php       # Kontrolna tabla
│   ├── rezervacije.php # Upravljanje rezervacijama
│   ├── sobe.php        # Upravljanje sobama
│   ├── korisnici.php   # Upravljanje korisnicima
│   └── izvjestaji.php  # Izvještaji o zauzetosti
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── config/database.php
├── includes/           # Zajednički PHP fajlovi
├── sql/schema.sql
├── index.php           # Pregled soba
├── rezervacija.php     # Online rezervacija
├── login.php / register.php
└── setup.php           # Instalaciona skripta
```

## Baza podataka

- **korisnici** — korisnički nalozi (admin/gost)
- **sobe** — hotelske sobe
- **rezervacije** — rezervacije sa statusima (na čekanju, potvrđena, otkazana, završena)
