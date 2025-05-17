-- Utworzenie nowej bazy danych (jeśli nie istnieje) i jej użycie
CREATE DATABASE IF NOT EXISTS szpital_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE szpital_v2;

-- Tabela Użytkownicy
CREATE TABLE Users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, -- Zmieniono z 'haslo' na 'password_hash' dla jasności
    user_type ENUM('pacjent', 'lekarz', 'administrator_systemu', 'personel_rejestracji', 'personel_medyczny') NOT NULL,
    status ENUM('aktywny', 'nieaktywny', 'oczekuje_na_aktywacje') DEFAULT 'aktywny',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL
);

-- Tabela Specjalizacje Lekarskie
CREATE TABLE Specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL
);

-- Tabela Departamenty Szpitalne
CREATE TABLE Departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    location VARCHAR(100) NULL -- np. 'Budynek A, Piętro 1'
);

-- Tabela Gabinety/Pokoje
CREATE TABLE Rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(20) UNIQUE NOT NULL, -- np. '101A'
    name VARCHAR(100) NULL, -- np. 'Gabinet Kardiologiczny Dr. Kowalskiego'
    room_type ENUM('gabinet_lekarski', 'gabinet_zabiegowy', 'sala_operacyjna', 'recepcja', 'poczekalnia', 'laboratorium') NOT NULL,
    department_id INT NULL,
    equipment_description TEXT NULL, -- Opis wyposażenia
    FOREIGN KEY (department_id) REFERENCES Departments(id) ON DELETE SET NULL
);

-- Tabela Pacjenci
CREATE TABLE Patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    pesel VARCHAR(11) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    blood_type VARCHAR(5) NULL, -- np. A+, 0-, AB+ (NOWE POLE)
    phone_number VARCHAR(20) NULL,
    address_street VARCHAR(100) NULL,
    address_city VARCHAR(50) NULL,
    address_postal_code VARCHAR(10) NULL,
    address_country VARCHAR(50) DEFAULT 'Polska',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    insurance_policy_id INT NULL, -- Klucz obcy dodany później
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Tabela Polisy Ubezpieczeniowe
CREATE TABLE InsurancePolicies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNIQUE NOT NULL, -- Każdy pacjent ma jedną główną polisę (lub wiele, jeśli model jest inny)
    insurer_name VARCHAR(100) NOT NULL, -- Nazwa ubezpieczyciela
    policy_number VARCHAR(50) UNIQUE NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    coverage_details TEXT NULL,
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE
);

-- Dodanie klucza obcego z Patients do InsurancePolicies po utworzeniu obu tabel
ALTER TABLE Patients ADD CONSTRAINT fk_patient_insurance
    FOREIGN KEY (insurance_policy_id) REFERENCES InsurancePolicies(id) ON DELETE SET NULL;

-- Tabela Lekarze
CREATE TABLE Doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    specialization_id INT NOT NULL,
    license_number VARCHAR(30) UNIQUE NOT NULL, -- Numer Prawa Wykonywania Zawodu
    contact_phone_number VARCHAR(20) NULL,
    contact_email VARCHAR(100) UNIQUE NULL, -- Służbowy email lekarza
    department_id INT NULL,
    academic_title VARCHAR(50) NULL, -- np. 'dr n. med.', 'prof.'
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialization_id) REFERENCES Specializations(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES Departments(id) ON DELETE SET NULL
);

-- Tabela Administratorzy Systemu (jeśli potrzebna jest osobna tabela dla dodatkowych danych admina)
CREATE TABLE SystemAdministrators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    access_level ENUM('podstawowy', 'pelny') DEFAULT 'podstawowy',
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Tabela Personel (rejestracja, pielęgniarki itp.)
CREATE TABLE Staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(100) NOT NULL, -- np. 'Pielęgniarka dyplomowana', 'Rejestratorka medyczna'
    department_id INT NULL,
    contact_phone_number VARCHAR(20) NULL,
    employment_date DATE NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES Departments(id) ON DELETE SET NULL
);

-- Tabela Rodzaje Usług/Wizyt (cennik)
CREATE TABLE ServiceTypes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) UNIQUE NOT NULL, -- np. 'Konsultacja kardiologiczna', 'Badanie EKG', 'Szczepienie przeciw grypie'
    description TEXT NULL,
    standard_cost DECIMAL(10, 2) NOT NULL,
    average_duration_minutes INT NULL, -- Przewidywany czas trwania w minutach
    requires_doctor BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE -- Czy usługa jest aktualnie oferowana
);

-- Tabela Rodzaje Badań Diagnostycznych (cennik)
CREATE TABLE TestTypes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) UNIQUE NOT NULL, -- np. 'Morfologia krwi', 'RTG klatki piersiowej', 'Rezonans magnetyczny głowy'
    description TEXT NULL,
    standard_cost DECIMAL(10, 2) NOT NULL,
    preparation_instructions TEXT NULL, -- Instrukcje dla pacjenta przed badaniem
    is_active BOOLEAN DEFAULT TRUE
);

-- Tabela Leki
CREATE TABLE Medications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL, -- Nazwa handlowa
    active_substance VARCHAR(150) NULL, -- Substancja czynna
    manufacturer VARCHAR(100) NULL,
    form VARCHAR(50) NULL, -- np. 'tabletki', 'syrop', 'ampułki'
    dosage_unit VARCHAR(30) NULL, -- np. 'mg', 'ml'
    requires_prescription BOOLEAN DEFAULT TRUE,
    is_available BOOLEAN DEFAULT TRUE,
    UNIQUE KEY uk_medication_name_form (name, form)
);

-- Tabela Wizyty
CREATE TABLE Visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NULL, -- Może być null jeśli to np. wizyta na badanie bez konkretnego lekarza prowadzącego wizytę
    service_type_id INT NOT NULL,
    room_id INT NULL,
    scheduled_datetime DATETIME NOT NULL,
    actual_start_datetime DATETIME NULL,
    actual_end_datetime DATETIME NULL,
    status ENUM('zaplanowana', 'potwierdzona', 'w_trakcie', 'zakończona', 'anulowana_pacjent', 'anulowana_personel', 'nie_odbyła_sie', 'przełożona') DEFAULT 'zaplanowana',
    actual_cost DECIMAL(10, 2) NULL, -- Rzeczywisty koszt, jeśli inny niż standardowy z ServiceTypes
    doctor_notes TEXT NULL, -- Notatki lekarza z wizyty
    registration_notes TEXT NULL, -- Notatki z recepcji/rejestracji
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by_user_id INT NULL, -- ID użytkownika (personelu), który utworzył wizytę
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES Doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (service_type_id) REFERENCES ServiceTypes(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES Rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES Users(id) ON DELETE SET NULL
);

-- Tabela Zlecenia na Badania Diagnostyczne (powiązane z wizytą lub bezpośrednio)
CREATE TABLE TestOrders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NULL, -- Powiązanie z wizytą, jeśli badanie zlecono podczas wizyty
    patient_id INT NOT NULL,
    ordering_doctor_id INT NOT NULL, -- Lekarz zlecający
    test_type_id INT NOT NULL,
    order_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    priority ENUM('rutynowe', 'pilne', 'cito') DEFAULT 'rutynowe',
    status ENUM('zlecone', 'pobrano_material', 'w_laboratorium', 'wynik_dostepny', 'anulowane') DEFAULT 'zlecone',
    doctor_instructions_for_lab TEXT NULL,
    FOREIGN KEY (visit_id) REFERENCES Visits(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (ordering_doctor_id) REFERENCES Doctors(id) ON DELETE RESTRICT,
    FOREIGN KEY (test_type_id) REFERENCES TestTypes(id) ON DELETE RESTRICT
);

-- Tabela Wyniki Badań Diagnostycznych
CREATE TABLE TestResults (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_order_id INT UNIQUE NOT NULL, -- Każdy wynik jest dla konkretnego zlecenia
    performing_staff_id INT NULL, -- Personel wykonujący/wprowadzający wynik
    result_datetime DATETIME NOT NULL,
    result_summary TEXT NOT NULL, -- Podsumowanie wyniku
    result_details JSON NULL, -- Szczegółowe dane wyniku, np. w formacie JSON
    attached_file_path VARCHAR(255) NULL, -- Ścieżka do pliku z wynikiem (np. PDF)
    is_abnormal BOOLEAN DEFAULT FALSE,
    doctor_comments TEXT NULL, -- Komentarz lekarza do wyniku
    patient_notified_at DATETIME NULL,
    FOREIGN KEY (test_order_id) REFERENCES TestOrders(id) ON DELETE CASCADE,
    FOREIGN KEY (performing_staff_id) REFERENCES Staff(user_id) ON DELETE SET NULL -- Zakładając, że staff_id to user_id
);


-- Tabela Grafik Lekarzy
CREATE TABLE DoctorSchedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    room_id INT NULL,
    date_of_work DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    availability_type ENUM('konsultacje', 'zabiegi', 'operacje', 'przerwa', 'nieobecny_planowany', 'nieobecny_nagly') DEFAULT 'konsultacje',
    notes TEXT NULL, -- np. 'Przyjmuje tylko umówionych pacjentów'
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_pattern VARCHAR(100) NULL, -- np. 'weekly on Mondays' (jeśli is_recurring)
    UNIQUE KEY uk_doctor_time_room (doctor_id, date_of_work, start_time, room_id),
    FOREIGN KEY (doctor_id) REFERENCES Doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Rooms(id) ON DELETE SET NULL
);

-- Tabela Historia Medyczna Pacjenta
CREATE TABLE MedicalHistory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    entry_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    entry_type ENUM('wizyta_lekarska', 'badanie_diagnostyczne', 'recepta', 'szczepienie', 'hospitalizacja', 'alergia', 'notatka_ogolna', 'zabieg') NOT NULL,
    description TEXT NOT NULL,
    author_doctor_id INT NULL, -- Lekarz dokonujący wpisu (jeśli dotyczy)
    author_staff_id INT NULL, -- Inny personel dokonujący wpisu
    related_visit_id INT NULL,
    related_test_result_id INT NULL,
    related_prescription_id INT NULL, -- Klucz obcy dodany później
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (author_doctor_id) REFERENCES Doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (author_staff_id) REFERENCES Staff(user_id) ON DELETE SET NULL, -- Zakładając, że staff_id to user_id
    FOREIGN KEY (related_visit_id) REFERENCES Visits(id) ON DELETE SET NULL,
    FOREIGN KEY (related_test_result_id) REFERENCES TestResults(id) ON DELETE SET NULL
);

-- Tabela Recepty
CREATE TABLE Prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NULL, -- Recepta może być wystawiona podczas wizyty
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    issue_date DATE NOT NULL,
    valid_until_date DATE NOT NULL,
    prescription_code VARCHAR(50) UNIQUE NULL, -- Unikalny kod recepty (np. e-recepty)
    status ENUM('nowa', 'czesciowo_zrealizowana', 'zrealizowana_w_calosci', 'anulowana', 'przedawniona') DEFAULT 'nowa',
    notes_for_patient TEXT NULL,
    notes_for_pharmacist TEXT NULL,
    FOREIGN KEY (visit_id) REFERENCES Visits(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES Doctors(id) ON DELETE RESTRICT
);

-- Dodanie klucza obcego z MedicalHistory do Prescriptions
ALTER TABLE MedicalHistory ADD CONSTRAINT fk_medicalhistory_prescription
    FOREIGN KEY (related_prescription_id) REFERENCES Prescriptions(id) ON DELETE SET NULL;

-- Tabela Pozycje Recepty
CREATE TABLE PrescriptionItems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    medication_id INT NOT NULL,
    dosage VARCHAR(255) NOT NULL, -- np. '1 tabletka 2 razy dziennie'
    quantity VARCHAR(100) NOT NULL, -- np. '2 opakowania po 30 tabletek'
    payment_percentage VARCHAR(20) DEFAULT '100%', -- Odpłatność (np. '100%', '50%', 'Ryczałt', 'Bezpłatne do limitu')
    notes TEXT NULL,
    FOREIGN KEY (prescription_id) REFERENCES Prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES Medications(id) ON DELETE RESTRICT
);

-- Tabela Aktualności Szpitalne
CREATE TABLE News (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    author_user_id INT NOT NULL, -- Kto opublikował (admin lub inny uprawniony użytkownik)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    status ENUM('szkic', 'opublikowany', 'archiwalny') DEFAULT 'szkic',
    category VARCHAR(50) NULL, -- np. 'Wydarzenia', 'Zdrowie', 'Komunikaty'
    FOREIGN KEY (author_user_id) REFERENCES Users(id) ON DELETE RESTRICT
);

-- Tabela Opinie (np. o lekarzach, usługach)
CREATE TABLE Reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    author_user_id INT NOT NULL, -- Pacjent piszący opinię
    related_doctor_id INT NULL, -- Opinia o konkretnym lekarzu
    related_service_type_id INT NULL, -- Opinia o konkretnej usłudze
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('oczekujaca_na_moderacje', 'zatwierdzona', 'odrzucona') DEFAULT 'oczekujaca_na_moderacje',
    is_anonymous BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (author_user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_doctor_id) REFERENCES Doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (related_service_type_id) REFERENCES ServiceTypes(id) ON DELETE SET NULL
);

-- Tabela Faktury/Rachunki
CREATE TABLE BillingInvoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visit_id INT NULL, -- Powiązanie z konkretną wizytą (jeśli dotyczy)
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('wystawiona', 'czesciowo_oplacona', 'oplacona', 'po_terminie', 'anulowana') DEFAULT 'wystawiona',
    payment_method ENUM('gotowka', 'karta', 'przelew', 'ubezpieczenie') NULL,
    notes TEXT NULL,
    created_by_user_id INT NULL, -- Personel wystawiający fakturę
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES Visits(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES Users(id) ON DELETE SET NULL
);

-- Tabela Pozycje Faktury/Rachunku
CREATE TABLE InvoiceItems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    item_description VARCHAR(255) NOT NULL, -- Opis pozycji (może być z ServiceType, TestType lub ręczny)
    service_type_id INT NULL, -- Opcjonalne powiązanie
    test_type_id INT NULL, -- Opcjonalne powiązanie
    medication_id INT NULL, -- Opcjonalne powiązanie (jeśli sprzedawany lek)
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL, -- quantity * unit_price
    vat_rate DECIMAL(4, 2) DEFAULT 0.00, -- Stawka VAT (np. 0.00, 0.08, 0.23)
    FOREIGN KEY (invoice_id) REFERENCES BillingInvoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES ServiceTypes(id) ON DELETE SET NULL,
    FOREIGN KEY (test_type_id) REFERENCES TestTypes(id) ON DELETE SET NULL,
    FOREIGN KEY (medication_id) REFERENCES Medications(id) ON DELETE SET NULL
);


-- --- PRZYKŁADOWE DANE ---

-- Users (hasła powinny być zahashowane w rzeczywistej aplikacji)
INSERT INTO Users (email, password_hash, user_type, status) VALUES
('jan.kowalski@example.com', 'hashed_password_patient1', 'pacjent', 'aktywny'),
('anna.nowak@example.com', 'hashed_password_patient2', 'pacjent', 'aktywny'),
('piotr.wisniewski@example.com', 'hashed_password_patient3', 'pacjent', 'oczekuje_na_aktywacje'),
('adam.lekarz@example.com', 'hashed_password_doctor1', 'lekarz', 'aktywny'),
('ewa.doktor@example.com', 'hashed_password_doctor2', 'lekarz', 'aktywny'),
('admin@szpital.pl', 'hashed_password_admin', 'administrator_systemu', 'aktywny'),
('recepcja1@szpital.pl', 'hashed_password_reception1', 'personel_rejestracji', 'aktywny'),
('pielegniarka.oddzialowa@szpital.pl', 'hashed_password_nurse1', 'personel_medyczny', 'aktywny');

-- Specializations
INSERT INTO Specializations (name, description) VALUES
('Kardiologia', 'Choroby serca i układu krążenia.'),
('Pediatria', 'Choroby dziecięce.'),
('Neurologia', 'Choroby układu nerwowego.'),
('Chirurgia Ogólna', 'Zabiegi operacyjne.');

-- Departments
INSERT INTO Departments (name, description, location) VALUES
('Oddział Kardiologiczny', 'Specjalistyczny oddział leczenia chorób serca.', 'Budynek Główny, Piętro 2'),
('Oddział Pediatryczny', 'Oddział dla dzieci.', 'Budynek B, Parter'),
('Poradnia Specjalistyczna', 'Ambulatoryjne konsultacje specjalistyczne.', 'Budynek Główny, Parter');

-- Rooms
INSERT INTO Rooms (room_number, name, room_type, department_id) VALUES
('G101', 'Gabinet Kardiologiczny', 'gabinet_lekarski', 1),
('G102', 'Gabinet EKG', 'gabinet_zabiegowy', 1),
('P205', 'Gabinet Pediatryczny', 'gabinet_lekarski', 2),
('LAB01', 'Laboratorium Główne', 'laboratorium', 3);

-- Patients
INSERT INTO Patients (user_id, first_name, last_name, pesel, date_of_birth, phone_number, address_street, address_city, address_postal_code, blood_type) VALUES
(1, 'Jan', 'Kowalski', '80010112345', '1980-01-01', '500100200', 'ul. Słoneczna 10', 'Warszawa', '01-234', 'A+'),
(2, 'Anna', 'Nowak', '90020223456', '1990-02-02', '600200300', 'ul. Kwiatowa 5', 'Kraków', '30-001', '0-'),
(3, 'Piotr', 'Wiśniewski', '75030334567', '1975-03-03', '700300400', 'al. Niepodległości 100', 'Gdańsk', '80-800', 'AB+');

-- InsurancePolicies (po utworzeniu pacjentów)
INSERT INTO InsurancePolicies (patient_id, insurer_name, policy_number, valid_from, valid_to) VALUES
(1, 'PZU Zdrowie', 'POL12345JAN', '2023-01-01', '2024-12-31'),
(2, 'Allianz Opieka Medyczna', 'POL67890ANN', '2022-06-01', '2024-05-31');
-- Piotr Wiśniewski (patient_id=3) nie ma polisy w tym przykładzie

-- Aktualizacja Patients o insurance_policy_id
UPDATE Patients SET insurance_policy_id = 1 WHERE id = 1;
UPDATE Patients SET insurance_policy_id = 2 WHERE id = 2;

-- Doctors
INSERT INTO Doctors (user_id, first_name, last_name, specialization_id, license_number, contact_email, department_id, academic_title) VALUES
(4, 'Adam', 'Lekarski', 1, 'LP100200', 'a.lekarski@szpital.pl', 1, 'dr n. med.'),
(5, 'Ewa', 'Doktorek', 2, 'LP100300', 'e.doktorek@szpital.pl', 2, 'lek. med.');

-- SystemAdministrators
INSERT INTO SystemAdministrators (user_id, first_name, last_name, access_level) VALUES
(6, 'Admin', 'Główny', 'pelny');

-- Staff
INSERT INTO Staff (user_id, first_name, last_name, position, department_id, employment_date) VALUES
(7, 'Barbara', 'Rejestratorka', 'Starsza Rejestratorka Medyczna', 3, '2010-05-15'),
(8, 'Katarzyna', 'Pielęgniarka', 'Pielęgniarka Oddziałowa', 1, '2005-03-01');

-- ServiceTypes
INSERT INTO ServiceTypes (name, standard_cost, average_duration_minutes, requires_doctor) VALUES
('Konsultacja kardiologiczna', 250.00, 30, TRUE),
('Badanie EKG spoczynkowe', 80.00, 15, FALSE), -- Może wykonać technik/pielęgniarka
('Konsultacja pediatryczna', 200.00, 25, TRUE),
('Szczepienie przeciw grypie (z kwalifikacją)', 120.00, 20, TRUE);

-- TestTypes
INSERT INTO TestTypes (name, standard_cost, preparation_instructions) VALUES
('Morfologia krwi (pełna)', 50.00, 'Pacjent powinien być na czczo (min. 8 godzin).'),
('Badanie ogólne moczu', 30.00, 'Próbka porannego moczu, środkowy strumień.'),
('RTG klatki piersiowej AP+bok', 150.00, 'Brak specjalnych przygotowań, usunąć metalowe elementy z obszaru badania.');

-- Medications
INSERT INTO Medications (name, active_substance, manufacturer, form, dosage_unit, requires_prescription) VALUES
('Paracetamol ABC', 'Paracetamolum', 'Pharma ABC', 'tabletki', '500mg', FALSE),
('Amoksycylina XYZ', 'Amoxicillinum', 'BioPharm XYZ', 'kapsułki', '1000mg', TRUE),
('Witamina C Forte', 'Acidum ascorbicum', 'VitPol', 'tabletki musujące', '1000mg', FALSE);

-- Visits (Przykładowe wizyty)
INSERT INTO Visits (patient_id, doctor_id, service_type_id, room_id, scheduled_datetime, status, actual_cost, created_by_user_id) VALUES
(1, 1, 1, 1, '2024-08-01 10:00:00', 'zaplanowana', 250.00, 7), -- Jan Kowalski u Dr. Adam Lekarski (Kardiologia)
(2, 2, 3, 3, '2024-08-01 11:00:00', 'potwierdzona', 200.00, 7); -- Anna Nowak u Dr. Ewa Doktorek (Pediatria)

-- TestOrders
INSERT INTO TestOrders (visit_id, patient_id, ordering_doctor_id, test_type_id, priority) VALUES
(1, 1, 1, 1, 'rutynowe'); -- Morfologia dla Jana K. zlecona podczas wizyty 1

-- TestResults (dla zlecenia 1)
INSERT INTO TestResults (test_order_id, performing_staff_id, result_datetime, result_summary, is_abnormal) VALUES
(1, 8, '2024-08-01 14:00:00', 'Wyniki w normie, niewielka leukocytoza.', FALSE); -- Katarzyna Pielęgniarka wprowadziła wynik

-- DoctorSchedules
INSERT INTO DoctorSchedules (doctor_id, room_id, date_of_work, start_time, end_time, availability_type) VALUES
(1, 1, '2024-08-01', '09:00:00', '15:00:00', 'konsultacje'),
(2, 3, '2024-08-01', '08:00:00', '14:00:00', 'konsultacje');

-- MedicalHistory
INSERT INTO MedicalHistory (patient_id, entry_type, description, author_doctor_id, related_visit_id) VALUES
(1, 'wizyta_lekarska', 'Pacjent zgłasza bóle w klatce piersiowej. Zalecono EKG i morfologię.', 1, 1),
(1, 'badanie_diagnostyczne', 'Morfologia krwi wykonana.', NULL, NULL); -- Tu można by dać related_test_result_id jeśli jest

-- Prescriptions
INSERT INTO Prescriptions (visit_id, patient_id, doctor_id, issue_date, valid_until_date, prescription_code) VALUES
(1, 1, 1, '2024-08-01', '2024-09-01', 'eRx001001'); -- Recepta dla Jana K.

-- PrescriptionItems
INSERT INTO PrescriptionItems (prescription_id, medication_id, dosage, quantity, payment_percentage) VALUES
(1, 2, '1 kapsułka co 12 godzin przez 7 dni', '1 opakowanie (14 kaps.)', '100%'); -- Amoksycylina dla Jana K.

-- News
INSERT INTO News (title, content, author_user_id, published_at, status, category) VALUES
('Nowy sprzęt USG w naszej poradni!', 'Z przyjemnością informujemy o zakupie nowoczesnego aparatu USG...', 6, '2024-07-20 10:00:00', 'opublikowany', 'Sprzęt'),
('Darmowe badania profilaktyczne w sierpniu', 'Zapraszamy na bezpłatne badania poziomu cukru i cholesterolu.', 6, '2024-07-25 00:00:00', 'opublikowany', 'Profilaktyka');

-- Reviews
INSERT INTO Reviews (author_user_id, related_doctor_id, rating, comment, status, is_anonymous) VALUES
(1, 1, 5, 'Bardzo profesjonalny i miły lekarz. Wszystko dokładnie wyjaśnił.', 'zatwierdzona', FALSE),
(2, NULL, 4, 'Czysto i sprawna rejestracja.', 'zatwierdzona', TRUE); -- Ogólna opinia

-- BillingInvoices
INSERT INTO BillingInvoices (patient_id, visit_id, invoice_number, issue_date, due_date, total_amount, status, created_by_user_id) VALUES
(1, 1, 'FV/2024/08/001', '2024-08-01', '2024-08-15', 330.00, 'wystawiona', 7); -- Faktura dla Jana K. (wizyta + badanie)

-- InvoiceItems
INSERT INTO InvoiceItems (invoice_id, item_description, service_type_id, quantity, unit_price, total_price, vat_rate) VALUES
(1, 'Konsultacja kardiologiczna', 1, 1, 250.00, 250.00, 0.00),
(1, 'Morfologia krwi (pełna)', NULL, 1, 50.00, 50.00, 0.00); -- Pozycja za badanie
-- Tu powinno być TestType_id, poprawka:
-- INSERT INTO InvoiceItems (invoice_id, item_description, test_type_id, quantity, unit_price, total_price, vat_rate) VALUES
-- (1, 'Morfologia krwi (pełna)', 1, 1, 50.00, 50.00, 0.00);

-- Indeksy dla optymalizacji (dodatkowe, jeśli potrzebne)
CREATE INDEX idx_patients_lastname ON Patients(last_name);
CREATE INDEX idx_doctors_lastname ON Doctors(last_name);
CREATE INDEX idx_visits_scheduled_datetime ON Visits(scheduled_datetime);
CREATE INDEX idx_testorders_patient_doctor ON TestOrders(patient_id, ordering_doctor_id);
CREATE INDEX idx_prescriptions_patient_doctor ON Prescriptions(patient_id, doctor_id);

-- Komentarz końcowy: Pamiętaj o poprawnym ustawieniu AUTO_INCREMENT dla swojej bazy danych
-- oraz o rzeczywistym hashowaniu haseł. Ten skrypt to przykład.
-- Poprawka w InvoiceItems - morfologia powinna linkować do TestTypes
DELETE FROM InvoiceItems WHERE invoice_id = 1 AND item_description LIKE 'Morfologia%';
INSERT INTO InvoiceItems (invoice_id, item_description, test_type_id, quantity, unit_price, total_price, vat_rate) VALUES
(1, 'Morfologia krwi (pełna)', 1, 1, 50.00, 50.00, 0.00);

-- Aktualizacja sumy faktury po dodaniu poprawnej pozycji
UPDATE BillingInvoices SET total_amount = (SELECT SUM(total_price) FROM InvoiceItems WHERE invoice_id = 1) WHERE id = 1;

-- --- NOWE TABELE I DANE (DODANE) ---

-- Tabela Kontakty Alarmowe Pacjentów
CREATE TABLE EmergencyContacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    relationship VARCHAR(50) NOT NULL, -- np. 'Małżonek', 'Rodzic', 'Przyjaciel'
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    notes TEXT NULL,
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE
);

-- Tabela Rodzaje Alergii
CREATE TABLE Allergies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL, -- np. 'Alergia na penicylinę', 'Alergia na orzechy arachidowe', 'Katar sienny'
    allergy_type ENUM('lekowa', 'pokarmowa', 'wziewna', 'kontaktowa', 'inna') NOT NULL,
    description TEXT NULL,
    common_reactions TEXT NULL -- np. 'Wysypka, trudności w oddychaniu, wstrząs anafilaktyczny'
);

-- Tabela Alergie Pacjentów (tabela łącząca)
CREATE TABLE PatientAllergies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    allergy_id INT NOT NULL,
    reaction_description TEXT NULL, -- Opis specyficznej reakcji u tego pacjenta
    severity ENUM('łagodna', 'umiarkowana', 'ciężka', 'zagrażająca życiu') NULL,
    diagnosed_date DATE NULL,
    notes TEXT NULL,
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (allergy_id) REFERENCES Allergies(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_patient_allergy (patient_id, allergy_id)
);

-- Tabela Szczepionki
CREATE TABLE Vaccines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) UNIQUE NOT NULL, -- np. 'Szczepionka przeciw grypie sezonowej 2023/2024', 'MMR (Odra, Świnka, Różyczka)'
    manufacturer VARCHAR(100) NULL,
    vaccine_type VARCHAR(100) NULL, -- np. 'inaktywowana', 'żywa atenuowana', 'mRNA'
    target_diseases VARCHAR(255) NULL, -- Choroby, przed którymi chroni
    standard_schedule TEXT NULL -- np. '1 dawka rocznie', '2 dawki w odstępie 4 tygodni'
);

-- Tabela Szczepienia Pacjentów
CREATE TABLE PatientVaccinations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    vaccination_date DATE NOT NULL,
    dose_number INT NULL, -- Która dawka w serii (jeśli dotyczy)
    lot_number VARCHAR(50) NULL, -- Numer serii szczepionki
    administered_by_staff_id INT NULL, -- Personel podający szczepionkę (Doctors lub Staff)
    notes TEXT NULL,
    FOREIGN KEY (patient_id) REFERENCES Patients(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES Vaccines(id) ON DELETE RESTRICT,
    FOREIGN KEY (administered_by_staff_id) REFERENCES Users(id) ON DELETE SET NULL -- Ogólny user_id, bo może to być lekarz lub pielęgniarka
);

-- Tabela Dziennik Zdarzeń (Audyt)
CREATE TABLE AuditLogs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL, -- Użytkownik wykonujący akcję (może być NULL dla akcji systemowych)
    action_type VARCHAR(100) NOT NULL, -- np. 'CREATE_PATIENT', 'UPDATE_VISIT', 'VIEW_MEDICAL_HISTORY'
    table_name VARCHAR(100) NULL, -- Nazwa tabeli, której dotyczy akcja
    record_id INT NULL, -- ID rekordu, którego dotyczy akcja
    action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    details JSON NULL, -- Szczegóły akcji, np. zmienione wartości (stare/nowe)
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE SET NULL
);

-- Przykładowe dane dla nowych tabel

INSERT INTO EmergencyContacts (patient_id, first_name, last_name, relationship, phone_number) VALUES
(1, 'Maria', 'Kowalska', 'Żona', '500100201'),
(1, 'Krzysztof', 'Kowalski', 'Brat', '501200300'),
(2, 'Tomasz', 'Nowak', 'Mąż', '601200301');

INSERT INTO Allergies (name, allergy_type, common_reactions) VALUES
('Alergia na penicylinę', 'lekowa', 'Wysypka skórna, pokrzywka, obrzęk, trudności w oddychaniu'),
('Alergia na orzechy arachidowe', 'pokarmowa', 'Świąd jamy ustnej, pokrzywka, obrzęk naczynioruchowy, wstrząs anafilaktyczny'),
('Katar sienny (pyłki traw)', 'wziewna', 'Kichanie, wodnisty katar, swędzenie i łzawienie oczu');

INSERT INTO PatientAllergies (patient_id, allergy_id, reaction_description, severity, diagnosed_date) VALUES
(1, 1, 'Silna wysypka po podaniu Augmentinu w dzieciństwie.', 'ciężka', '1990-05-15'),
(2, 3, 'Sezonowe nasilenie objawów w okresie pylenia traw.', 'umiarkowana', '2010-06-01');

INSERT INTO Vaccines (name, manufacturer, vaccine_type, target_diseases) VALUES
('Influvac Tetra 2023/2024', 'Abbott', 'inaktywowana', 'Grypa sezonowa'),
('Priorix', 'GSK', 'żywa atenuowana', 'Odra, Świnka, Różyczka'),
('Comirnaty', 'Pfizer/BioNTech', 'mRNA', 'COVID-19');

INSERT INTO PatientVaccinations (patient_id, vaccine_id, vaccination_date, administered_by_staff_id) VALUES
(1, 1, '2023-10-15', 5), -- Jan Kowalski, grypa, podana przez Dr. Ewę Doktorek (user_id=5)
(2, 3, '2021-05-20', 8); -- Anna Nowak, COVID-19, podana przez Katarzynę Pielęgniarkę (user_id=8)

INSERT INTO AuditLogs (user_id, action_type, table_name, record_id, ip_address, details) VALUES
(7, 'CREATE_VISIT', 'Visits', 1, '192.168.1.100', '{"patient_id": 1, "doctor_id": 1, "service_type_id": 1}'),
(6, 'UPDATE_USER_STATUS', 'Users', 3, '10.0.0.5', '{"old_status": "oczekuje_na_aktywacje", "new_status": "aktywny"}');

-- Koniec dodanych tabel i danych 