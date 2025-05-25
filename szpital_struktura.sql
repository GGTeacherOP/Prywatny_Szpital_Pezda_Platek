-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 25, 2025 at 05:52 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `szpital`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(100) NOT NULL,
  `pietro` int(11) NOT NULL,
  `liczba_lozek` int(11) NOT NULL,
  `kierownik_id` int(11) DEFAULT NULL,
  `opis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `specjalizacja` varchar(100) NOT NULL,
  `numer_licencji` varchar(20) NOT NULL,
  `tytul_naukowy` varchar(50) DEFAULT NULL,
  `data_rozpoczecia_pracy` date NOT NULL,
  `opis` text DEFAULT NULL,
  `zdjecie` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `doctor_hours`
--

CREATE TABLE `doctor_hours` (
  `id` int(11) NOT NULL,
  `lekarz_id` int(11) NOT NULL,
  `dzien_tygodnia` enum('poniedzialek','wtorek','sroda','czwartek','piatek','sobota','niedziela') NOT NULL,
  `godzina_rozpoczecia` time NOT NULL,
  `godzina_zakonczenia` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `doctor_reviews`
--

CREATE TABLE `doctor_reviews` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `lekarz_id` int(11) NOT NULL,
  `ocena` int(11) NOT NULL CHECK (`ocena` between 1 and 5),
  `tresc` text NOT NULL,
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `status` enum('oczekujaca','zatwierdzona','odrzucona') DEFAULT 'oczekujaca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `doctor_visit_prices`
--

CREATE TABLE `doctor_visit_prices` (
  `id` int(11) NOT NULL,
  `lekarz_id` int(11) NOT NULL,
  `typ_wizyty` enum('pierwsza','kontrolna','pogotowie','szczepienie','badanie') NOT NULL,
  `cena` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `hospitalizations`
--

CREATE TABLE `hospitalizations` (
  `id` int(11) NOT NULL,
  `pacjent_id` int(11) NOT NULL,
  `sala_id` int(11) NOT NULL,
  `data_przyjecia` datetime NOT NULL,
  `data_wypisu` datetime DEFAULT NULL,
  `przyczyna` text NOT NULL,
  `status` enum('trwajaca','zakonczona','anulowana') DEFAULT 'trwajaca',
  `lekarz_prowadzacy_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `tytul` varchar(200) NOT NULL,
  `tresc` text NOT NULL,
  `data_publikacji` datetime NOT NULL,
  `autor_id` int(11) NOT NULL,
  `status` enum('szkic','opublikowany','archiwalny') DEFAULT 'szkic',
  `zdjecie` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nurses`
--

CREATE TABLE `nurses` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `numer_licencji` varchar(20) NOT NULL,
  `specjalizacja` enum('ogolna','pediatryczna','anestezjologiczna','intensywnej_opieki','operacyjna') NOT NULL,
  `oddzial` varchar(50) NOT NULL,
  `data_rozpoczecia_pracy` date NOT NULL,
  `zdjecie` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nurse_schedule`
--

CREATE TABLE `nurse_schedule` (
  `id` int(11) NOT NULL,
  `pielegniarka_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `zmiana` enum('rano','popoludnie','noc') NOT NULL,
  `oddzial_id` int(11) NOT NULL,
  `status` enum('zaplanowana','zrealizowana','anulowana') DEFAULT 'zaplanowana'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `grupa_krwi` varchar(5) DEFAULT NULL,
  `alergia` text DEFAULT NULL,
  `choroby_przewlekle` text DEFAULT NULL,
  `przyjmowane_leki` text DEFAULT NULL,
  `ubezpieczenie` varchar(50) DEFAULT NULL,
  `numer_ubezpieczenia` varchar(50) DEFAULT NULL,
  `data_ostatniej_wizyty` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `pacjent_id` int(11) NOT NULL,
  `lekarz_id` int(11) NOT NULL,
  `typ_badania` varchar(100) NOT NULL,
  `data_wystawienia` datetime DEFAULT current_timestamp(),
  `pin` varchar(12) NOT NULL,
  `plik_wyniku` longblob DEFAULT NULL,
  `status` enum('oczekujący','gotowy') DEFAULT 'oczekujący'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `ocena` int(11) NOT NULL CHECK (`ocena` between 1 and 5),
  `tresc` text NOT NULL,
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `status` enum('oczekujaca','zatwierdzona','odrzucona') DEFAULT 'zatwierdzona'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `numer` varchar(10) NOT NULL,
  `oddzial_id` int(11) NOT NULL,
  `typ` enum('gabinet','sala_chorych','zabiegowa','operacyjna','porodowa','intensywnej_opieki') NOT NULL,
  `liczba_lozek` int(11) DEFAULT NULL,
  `status` enum('dostepna','zajeta','w_remoncie','nieczynna') DEFAULT 'dostepna'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `stanowisko` enum('sprzatacz','konserwator') NOT NULL,
  `data_rozpoczecia_pracy` date NOT NULL,
  `sekcja` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `pracownik_id` int(11) NOT NULL,
  `numer_pomieszczenia` varchar(10) NOT NULL,
  `typ_zadania` enum('sprzatanie','konserwacja') NOT NULL,
  `opis_zadania` text NOT NULL,
  `data_zadania` date NOT NULL,
  `godzina_rozpoczecia` time NOT NULL,
  `godzina_zakonczenia` time NOT NULL,
  `status` enum('do_wykonania','w_trakcie','wykonane','anulowane') NOT NULL DEFAULT 'do_wykonania',
  `data_utworzenia` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `imie` varchar(50) NOT NULL,
  `nazwisko` varchar(50) NOT NULL,
  `pesel` varchar(11) NOT NULL,
  `data_urodzenia` date NOT NULL,
  `numer_telefonu` varchar(15) NOT NULL,
  `adres` varchar(200) NOT NULL,
  `kod_pocztowy` varchar(6) NOT NULL,
  `miasto` varchar(50) NOT NULL,
  `funkcja` enum('pacjent','lekarz','administrator','obsluga','pielegniarka','wlasciciel') NOT NULL,
  `status` enum('aktywny','nieaktywny') DEFAULT 'aktywny',
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `ostatnie_logowanie` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `pacjent_id` int(11) NOT NULL,
  `lekarz_id` int(11) NOT NULL,
  `data_wizyty` datetime NOT NULL,
  `typ_wizyty` enum('pierwsza','kontrolna','pogotowie','szczepienie','badanie') NOT NULL,
  `status` enum('zaplanowana','zakończona','anulowana','nieobecny') NOT NULL DEFAULT 'zaplanowana',
  `gabinet` varchar(10) NOT NULL,
  `opis` text DEFAULT NULL,
  `diagnoza` text DEFAULT NULL,
  `zalecenia` text DEFAULT NULL,
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `data_modyfikacji` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kierownik_id` (`kierownik_id`);

--
-- Indeksy dla tabeli `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numer_licencji` (`numer_licencji`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `doctor_hours`
--
ALTER TABLE `doctor_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lekarz_id` (`lekarz_id`);

--
-- Indeksy dla tabeli `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `lekarz_id` (`lekarz_id`);

--
-- Indeksy dla tabeli `doctor_visit_prices`
--
ALTER TABLE `doctor_visit_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lekarz_id` (`lekarz_id`);

--
-- Indeksy dla tabeli `hospitalizations`
--
ALTER TABLE `hospitalizations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacjent_id` (`pacjent_id`),
  ADD KEY `sala_id` (`sala_id`),
  ADD KEY `lekarz_prowadzacy_id` (`lekarz_prowadzacy_id`),
  ADD KEY `idx_hospitalizations_data` (`data_przyjecia`);

--
-- Indeksy dla tabeli `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `autor_id` (`autor_id`),
  ADD KEY `idx_news_data` (`data_publikacji`);

--
-- Indeksy dla tabeli `nurses`
--
ALTER TABLE `nurses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numer_licencji` (`numer_licencji`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `nurse_schedule`
--
ALTER TABLE `nurse_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pielegniarka_id` (`pielegniarka_id`),
  ADD KEY `oddzial_id` (`oddzial_id`);

--
-- Indeksy dla tabeli `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacjent_id` (`pacjent_id`),
  ADD KEY `lekarz_id` (`lekarz_id`);

--
-- Indeksy dla tabeli `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oddzial_id` (`oddzial_id`);

--
-- Indeksy dla tabeli `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pracownik_id` (`pracownik_id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `pesel` (`pesel`),
  ADD KEY `idx_users_pesel` (`pesel`);

--
-- Indeksy dla tabeli `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacjent_id` (`pacjent_id`),
  ADD KEY `lekarz_id` (`lekarz_id`),
  ADD KEY `idx_visits_data` (`data_wizyty`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_hours`
--
ALTER TABLE `doctor_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_visit_prices`
--
ALTER TABLE `doctor_visit_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hospitalizations`
--
ALTER TABLE `hospitalizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurse_schedule`
--
ALTER TABLE `nurse_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`kierownik_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctor_hours`
--
ALTER TABLE `doctor_hours`
  ADD CONSTRAINT `doctor_hours_ibfk_1` FOREIGN KEY (`lekarz_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  ADD CONSTRAINT `doctor_reviews_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `doctor_reviews_ibfk_2` FOREIGN KEY (`lekarz_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `doctor_visit_prices`
--
ALTER TABLE `doctor_visit_prices`
  ADD CONSTRAINT `doctor_visit_prices_ibfk_1` FOREIGN KEY (`lekarz_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `hospitalizations`
--
ALTER TABLE `hospitalizations`
  ADD CONSTRAINT `hospitalizations_ibfk_1` FOREIGN KEY (`pacjent_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `hospitalizations_ibfk_2` FOREIGN KEY (`sala_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `hospitalizations_ibfk_3` FOREIGN KEY (`lekarz_prowadzacy_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`autor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `nurses`
--
ALTER TABLE `nurses`
  ADD CONSTRAINT `nurses_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `nurse_schedule`
--
ALTER TABLE `nurse_schedule`
  ADD CONSTRAINT `nurse_schedule_ibfk_1` FOREIGN KEY (`pielegniarka_id`) REFERENCES `nurses` (`id`),
  ADD CONSTRAINT `nurse_schedule_ibfk_2` FOREIGN KEY (`oddzial_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`pacjent_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`lekarz_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`oddzial_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`pracownik_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`pacjent_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`lekarz_id`) REFERENCES `doctors` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
