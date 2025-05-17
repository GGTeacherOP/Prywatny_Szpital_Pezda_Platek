CREATE TABLE IF NOT EXISTS wyniki_badan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pacjent_id INT NOT NULL,
    lekarz_id INT NOT NULL,
    typ_badania VARCHAR(100) NOT NULL,
    data_badania DATETIME NOT NULL,
    opis_wyniku TEXT NOT NULL,
    plik_wyniku VARCHAR(255),
    status ENUM('oczekujący', 'zakończony', 'anulowany') NOT NULL DEFAULT 'zakończony',
    data_wystawienia DATETIME NOT NULL,
    pin VARCHAR(10) UNIQUE,
    FOREIGN KEY (pacjent_id) REFERENCES patients(id),
    FOREIGN KEY (lekarz_id) REFERENCES doctors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 