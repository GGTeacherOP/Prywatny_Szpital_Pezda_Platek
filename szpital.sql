-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS szpital;
USE szpital;

-- Tabela Użytkownicy
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    haslo VARCHAR(255) NOT NULL,
    typ_uzytkownika ENUM('pacjent', 'lekarz', 'admin') NOT NULL,
    status ENUM('aktywny', 'nieaktywny') DEFAULT 'aktywny',
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    ostatnie_logowanie DATETIME
);

-- Tabela Pacjenci
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    pesel VARCHAR(11) UNIQUE NOT NULL,
    data_urodzenia DATE NOT NULL,
    numer_telefonu VARCHAR(15),
    adres VARCHAR(200),
    kod_pocztowy VARCHAR(6),
    miasto VARCHAR(50),
    data_rejestracji DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Lekarze
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    specjalizacja VARCHAR(100) NOT NULL,
    numer_licencji VARCHAR(20) UNIQUE NOT NULL,
    numer_telefonu VARCHAR(15),
    godziny_pracy JSON,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Administratorzy
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    poziom_dostepu ENUM('podstawowy', 'zaawansowany', 'superadmin') NOT NULL,
    departament VARCHAR(100),
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Wizyty
CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pacjent_id INT NOT NULL,
    lekarz_id INT NOT NULL,
    data_wizyty DATETIME NOT NULL,
    status_wizyty ENUM('zaplanowana', 'zakończona', 'anulowana') DEFAULT 'zaplanowana',
    typ_wizyty VARCHAR(50) NOT NULL,
    opis TEXT,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Tabela Wyniki Badań
CREATE TABLE test_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pacjent_id INT NOT NULL,
    lekarz_id INT NOT NULL,
    data_badania DATETIME NOT NULL,
    typ_badania VARCHAR(100) NOT NULL,
    wynik TEXT NOT NULL,
    plik_wyniku VARCHAR(255),
    pin_dostepu VARCHAR(6) NOT NULL,
    status ENUM('oczekujący', 'gotowy', 'wysłany') DEFAULT 'oczekujący',
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Tabela Grafik Lekarzy
CREATE TABLE doctor_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lekarz_id INT NOT NULL,
    data DATE NOT NULL,
    godzina_rozpoczecia TIME NOT NULL,
    godzina_zakonczenia TIME NOT NULL,
    status ENUM('dostępny', 'zajęty', 'nieobecny') DEFAULT 'dostępny',
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Tabela Historia Medyczna
CREATE TABLE medical_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pacjent_id INT NOT NULL,
    data_wpisu DATETIME DEFAULT CURRENT_TIMESTAMP,
    typ_wpisu ENUM('wizyta', 'badanie', 'recepta') NOT NULL,
    opis TEXT NOT NULL,
    lekarz_id INT NOT NULL,
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Tabela Aktualności
CREATE TABLE news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tytul VARCHAR(200) NOT NULL,
    opis TEXT NOT NULL,
    sciezka_zdjecia VARCHAR(255),
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_publikacji DATETIME,
    status ENUM('szkic', 'opublikowany', 'archiwalny') DEFAULT 'szkic',
    autor_id INT NOT NULL,
    FOREIGN KEY (autor_id) REFERENCES admins(id)
);

-- Tabela Opinie
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    ocena INT NOT NULL CHECK (ocena BETWEEN 1 AND 5),
    tresc TEXT NOT NULL,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Dodanie indeksów dla optymalizacji zapytań
CREATE INDEX idx_patients_pesel ON patients(pesel);
CREATE INDEX idx_doctors_licencja ON doctors(numer_licencji);
CREATE INDEX idx_visits_data ON visits(data_wizyty);
CREATE INDEX idx_test_results_data ON test_results(data_badania);
CREATE INDEX idx_doctor_schedule_data ON doctor_schedule(data);
CREATE INDEX idx_news_data ON news(data_publikacji); 