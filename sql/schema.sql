-- Baza podataka za sistem rezervacije soba
CREATE DATABASE IF NOT EXISTS turizam_sobe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE turizam_sobe;

-- Korisnici
CREATE TABLE korisnici (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(100) NOT NULL,
    prezime VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    lozinka VARCHAR(255) NOT NULL,
    uloga ENUM('admin', 'gost') NOT NULL DEFAULT 'gost',
    aktivan TINYINT(1) NOT NULL DEFAULT 1,
    kreiran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sobe
CREATE TABLE sobe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(100) NOT NULL,
    opis TEXT,
    tip ENUM('jednokrevetna', 'dvokrevetna', 'apartman', 'suite') NOT NULL,
    kapacitet INT NOT NULL DEFAULT 1,
    cijena_po_noci DECIMAL(10, 2) NOT NULL,
    slika VARCHAR(255) DEFAULT NULL,
    dostupna TINYINT(1) NOT NULL DEFAULT 1,
    kreiran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rezervacije
CREATE TABLE rezervacije (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    soba_id INT NOT NULL,
    datum_od DATE NOT NULL,
    datum_do DATE NOT NULL,
    broj_gostiju INT NOT NULL DEFAULT 1,
    ukupna_cijena DECIMAL(10, 2) NOT NULL,
    status ENUM('na_cekanju', 'potvrdena', 'otkazana', 'zavrsena') NOT NULL DEFAULT 'na_cekanju',
    napomena TEXT,
    kreiran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE,
    FOREIGN KEY (soba_id) REFERENCES sobe(id) ON DELETE CASCADE,
    INDEX idx_datumi (datum_od, datum_do),
    INDEX idx_status (status)
);

-- Korisnici se kreiraju pokretanjem setup.php

-- Primjer soba
INSERT INTO sobe (naziv, opis, tip, kapacitet, cijena_po_noci, slika) VALUES
('Soba 101', 'Udobna jednokrevetna soba sa pogledom na grad. Klima uređaj, Wi-Fi, TV.', 'jednokrevetna', 1, 45.00, 'soba1.jpg'),
('Soba 102', 'Prostrana dvokrevetna soba idealna za parove. Balkon, mini bar.', 'dvokrevetna', 2, 75.00, 'soba2.jpg'),
('Soba 201', 'Luksuzni apartman sa dnevnom sobom i kuhinjom. Pogled na more.', 'apartman', 4, 120.00, 'soba3.jpg'),
('Suite 301', 'Premium suite sa jacuzzi kupatilom i panoramskim pogledom.', 'suite', 2, 180.00, 'soba4.jpg'),
('Soba 103', 'Ekonomična dvokrevetna soba, savršena za kraći boravak.', 'dvokrevetna', 2, 55.00, 'soba5.jpg'),
('Soba 202', 'Porodični apartman sa dva spavaća mjesta.', 'apartman', 5, 140.00, 'soba6.jpg'),
('Soba 104', 'Kompaktna jednokrevetna soba u prizemlju, blizu recepcije.', 'jednokrevetna', 1, 40.00, 'soba7.jpg'),
('Soba 203', 'Dvokrevetna soba sa terasom i pogledom na baštu.', 'dvokrevetna', 2, 80.00, 'soba8.jpg'),
('Apartman 302', 'Moderan apartman sa potpuno opremljenom kuhinjom.', 'apartman', 3, 110.00, 'soba9.jpg'),
('Suite 401', 'Ekskluzivni suite sa odvojenim dnevnim boravkom.', 'suite', 3, 200.00, 'soba10.jpg'),
('Soba 105', 'Tiha jednokrevetna soba sa radnim stolom.', 'jednokrevetna', 1, 50.00, 'soba11.jpg'),
('Soba 204', 'Prostrana dvokrevetna soba pogodna za poslovne putnike.', 'dvokrevetna', 2, 70.00, 'soba12.jpg');
