-- Tworzenie tabeli Opinie
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uzytkownik_id INT NOT NULL,
    ocena INT NOT NULL CHECK (ocena BETWEEN 1 AND 5),
    tresc TEXT NOT NULL,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('oczekujaca', 'zatwierdzona', 'odrzucona') DEFAULT 'oczekujaca',
    FOREIGN KEY (uzytkownik_id) REFERENCES users(id)
);

-- Dodanie przykładowych opinii
INSERT INTO reviews (uzytkownik_id, ocena, tresc, status) VALUES
(1, 5, 'Świetna obsługa i profesjonalne podejście do pacjenta.', 'zatwierdzona'),
(2, 4, 'Bardzo dobra opieka medyczna, polecam.', 'zatwierdzona'),
(3, 5, 'Wysoki poziom usług medycznych.', 'zatwierdzona'); 