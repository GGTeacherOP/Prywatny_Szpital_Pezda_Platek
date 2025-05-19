-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS szpital;
USE szpital;

-- Tabela Użytkownicy
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    haslo VARCHAR(255) NOT NULL,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    pesel VARCHAR(11) UNIQUE NOT NULL,
    data_urodzenia DATE NOT NULL,
    numer_telefonu VARCHAR(15) NOT NULL,
    adres VARCHAR(200) NOT NULL,
    kod_pocztowy VARCHAR(6) NOT NULL,
    miasto VARCHAR(50) NOT NULL,
    funkcja ENUM('pacjent', 'lekarz', 'administrator', 'obsluga', 'pielegniarka') NOT NULL,
    status ENUM('aktywny', 'nieaktywny') DEFAULT 'aktywny',
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    ostatnie_logowanie DATETIME
);

-- Tabela Lekarze
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    specjalizacja VARCHAR(100) NOT NULL,
    numer_licencji VARCHAR(20) UNIQUE NOT NULL,
    tytul_naukowy VARCHAR(50),
    data_rozpoczecia_pracy DATE NOT NULL,
    opis TEXT,
    zdjecie LONGBLOB,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Godziny Przyjęć Lekarzy
CREATE TABLE doctor_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lekarz_id INT NOT NULL,
    dzien_tygodnia ENUM('poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek', 'sobota', 'niedziela') NOT NULL,
    godzina_rozpoczecia TIME NOT NULL,
    godzina_zakonczenia TIME NOT NULL,
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Tabela Pacjenci
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    grupa_krwi VARCHAR(5),
    alergia TEXT,
    choroby_przewlekle TEXT,
    przyjmowane_leki TEXT,
    ubezpieczenie VARCHAR(50),
    numer_ubezpieczenia VARCHAR(50),
    data_ostatniej_wizyty DATE,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Wizyty
CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pacjent_id INT NOT NULL,
    lekarz_id INT NOT NULL,
    data_wizyty DATETIME NOT NULL,
    typ_wizyty ENUM('pierwsza', 'kontrolna', 'pogotowie', 'szczepienie', 'badanie') NOT NULL,
    status ENUM('zaplanowana', 'zakończona', 'anulowana', 'nieobecny') NOT NULL DEFAULT 'zaplanowana',
    gabinet VARCHAR(10) NOT NULL,
    opis TEXT,
    diagnoza TEXT,
    zalecenia TEXT,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modyfikacji DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Tabela Obsługa
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    stanowisko ENUM('sprzatacz', 'konserwator') NOT NULL,
    data_rozpoczecia_pracy DATE NOT NULL,
    sekcja VARCHAR(50) NOT NULL,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Zadania Obsługi
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pracownik_id INT NOT NULL,
    numer_pomieszczenia VARCHAR(10) NOT NULL,
    typ_zadania ENUM('sprzatanie', 'konserwacja') NOT NULL,
    opis_zadania TEXT NOT NULL,
    data_zadania DATE NOT NULL,
    godzina_rozpoczecia TIME NOT NULL,
    godzina_zakonczenia TIME NOT NULL,
    status ENUM('do_wykonania', 'w_trakcie', 'wykonane', 'anulowane') NOT NULL DEFAULT 'do_wykonania',
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pracownik_id) REFERENCES staff(id)
);

-- Tabela Pielęgniarki
CREATE TABLE nurses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    numer_licencji VARCHAR(20) UNIQUE NOT NULL,
    specjalizacja ENUM('ogolna', 'pediatryczna', 'anestezjologiczna', 'intensywnej_opieki', 'operacyjna') NOT NULL,
    oddzial VARCHAR(50) NOT NULL,
    data_rozpoczecia_pracy DATE NOT NULL,
    zdjecie LONGBLOB,
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Oddziały
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nazwa VARCHAR(100) NOT NULL,
    pietro INT NOT NULL,
    liczba_lozek INT NOT NULL,
    kierownik_id INT,
    opis TEXT,
    FOREIGN KEY (kierownik_id) REFERENCES doctors(id)
);

-- Tabela Sale
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numer VARCHAR(10) NOT NULL,
    oddzial_id INT NOT NULL,
    typ ENUM('gabinet', 'sala_chorych', 'zabiegowa', 'operacyjna', 'porodowa', 'intensywnej_opieki') NOT NULL,
    liczba_lozek INT,
    status ENUM('dostepna', 'zajeta', 'w_remoncie', 'nieczynna') DEFAULT 'dostepna',
    FOREIGN KEY (oddzial_id) REFERENCES departments(id)
);

-- Tabela Hospitalizacje
CREATE TABLE hospitalizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pacjent_id INT NOT NULL,
    sala_id INT NOT NULL,
    data_przyjecia DATETIME NOT NULL,
    data_wypisu DATETIME,
    przyczyna TEXT NOT NULL,
    status ENUM('trwajaca', 'zakonczona', 'anulowana') DEFAULT 'trwajaca',
    lekarz_prowadzacy_id INT NOT NULL,
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (sala_id) REFERENCES rooms(id),
    FOREIGN KEY (lekarz_prowadzacy_id) REFERENCES doctors(id)
);

-- Tabela Harmonogram Pielęgniarek
CREATE TABLE nurse_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pielegniarka_id INT NOT NULL,
    data DATE NOT NULL,
    zmiana ENUM('rano', 'popoludnie', 'noc') NOT NULL,
    oddzial_id INT NOT NULL,
    status ENUM('zaplanowana', 'zrealizowana', 'anulowana') DEFAULT 'zaplanowana',
    FOREIGN KEY (pielegniarka_id) REFERENCES nurses(id),
    FOREIGN KEY (oddzial_id) REFERENCES departments(id)
);

-- Tabela Aktualności
CREATE TABLE news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tytul VARCHAR(200) NOT NULL,
    tresc TEXT NOT NULL,
    data_publikacji DATETIME NOT NULL,
    autor_id INT NOT NULL,
    status ENUM('szkic', 'opublikowany', 'archiwalny') DEFAULT 'szkic',
    zdjecie VARCHAR(255),
    FOREIGN KEY (autor_id) REFERENCES users(id)
);

-- Tabela Opinie
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    ocena INT NOT NULL CHECK (ocena BETWEEN 1 AND 5),
    tresc TEXT NOT NULL,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('oczekujaca', 'zatwierdzona', 'odrzucona') DEFAULT 'oczekujaca',
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Tabela Wyniki
CREATE TABLE results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pacjent_id INT NOT NULL,
    lekarz_id INT NOT NULL,
    typ_badania VARCHAR(100) NOT NULL,
    data_wystawienia DATETIME DEFAULT CURRENT_TIMESTAMP,
    pin VARCHAR(12) NOT NULL,
    plik_wyniku LONGBLOB,
    status ENUM('oczekujący', 'gotowy') DEFAULT 'oczekujący',
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
);

-- Dodanie indeksów dla optymalizacji zapytań
CREATE INDEX idx_users_pesel ON users(pesel);
CREATE INDEX idx_visits_data ON visits(data_wizyty);
CREATE INDEX idx_hospitalizations_data ON hospitalizations(data_przyjecia);
CREATE INDEX idx_news_data ON news(data_publikacji); 