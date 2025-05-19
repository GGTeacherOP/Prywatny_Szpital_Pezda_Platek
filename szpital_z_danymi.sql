-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 19, 2025 at 07:04 PM
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

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `uzytkownik_id`, `specjalizacja`, `numer_licencji`, `tytul_naukowy`, `data_rozpoczecia_pracy`, `opis`, `zdjecie`) VALUES
(1, 1, 'Kardiolog', 'LIC123456', 'dr n. med.', '2020-01-01', NULL, NULL);

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
  `zdjecie` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `tytul`, `tresc`, `data_publikacji`, `autor_id`, `status`, `zdjecie`) VALUES
(1, 'sdjhdfhbjksb', 'asdadasd', '2025-05-19 18:23:37', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623562303965666536392e6a7067),
(2, 'efsdfsdfsdf', 'dfsgdsgsdfgsdfg', '2025-05-19 18:24:12', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623562326331303635392e6a7067),
(3, 'sdfgsdfgsdfg', 'sdfgdffgsdfgfsds', '2025-05-19 18:24:20', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623562333430323864312e6a7067),
(4, 'asdfgsdfa', 'asdfasdfasdfasd', '2025-05-19 18:24:28', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623562336361633837622e6a706567),
(5, 'asdfasdfdasfasdf', 'asdfasdfs', '2025-05-19 18:24:37', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623562343537616262392e6a7067),
(6, 'sdfasdfasd', 'fasdfasdfasd', '2025-05-19 18:24:44', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623562346339303130332e6a7067),
(9, 'asdfasdf', 'asfdfasdfas', '2025-05-19 18:28:23', 8, 'opublikowany', 0x75706c6f6164732f6e6577732f363832623563323734356265662e6a7067);

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

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `uzytkownik_id`, `grupa_krwi`, `alergia`, `choroby_przewlekle`, `przyjmowane_leki`, `ubezpieczenie`, `numer_ubezpieczenia`, `data_ostatniej_wizyty`) VALUES
(4, 2, 'A+', NULL, NULL, NULL, 'NFZ', '123456789', NULL),
(5, 3, '0-', NULL, NULL, NULL, 'NFZ', '234567890', NULL),
(6, 4, 'B+', NULL, NULL, NULL, 'NFZ', '345678901', NULL),
(7, 6, 'B Rh+', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 7, 'A Rh+', NULL, NULL, NULL, NULL, NULL, NULL);

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

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `pacjent_id`, `lekarz_id`, `typ_badania`, `data_wystawienia`, `pin`, `plik_wyniku`, `status`) VALUES
(1, 4, 1, 'Morfologia krwi', '2024-05-18 09:00:00', '1234-5678-90', NULL, 'gotowy'),
(2, 5, 1, 'Morfologia krwi', '2024-05-18 09:00:00', '1234-5678-90', NULL, 'gotowy'),
(3, 6, 1, 'Morfologia krwi', '2024-05-18 09:00:00', '1234-5678-90', NULL, 'gotowy'),
(4, 4, 1, 'Badanie moczu', '2024-05-18 10:00:00', '2345-6789-01', NULL, 'gotowy'),
(5, 5, 1, 'Badanie moczu', '2024-05-18 10:00:00', '2345-6789-01', NULL, 'gotowy'),
(6, 6, 1, 'Badanie moczu', '2024-05-18 10:00:00', '2345-6789-01', NULL, 'gotowy'),
(7, 4, 1, 'EKG', '2024-05-18 11:00:00', '3456-7890-12', NULL, 'gotowy'),
(8, 5, 1, 'EKG', '2024-05-18 11:00:00', '3456-7890-12', NULL, 'gotowy'),
(9, 6, 1, 'EKG', '2024-05-18 11:00:00', '3456-7890-12', NULL, 'gotowy'),
(10, 4, 1, 'RTG', '2024-05-19 09:00:00', '4567-8901-23', NULL, 'gotowy'),
(11, 5, 1, 'RTG', '2024-05-19 09:00:00', '4567-8901-23', NULL, 'gotowy'),
(12, 6, 1, 'RTG', '2024-05-19 09:00:00', '4567-8901-23', NULL, 'gotowy'),
(13, 4, 1, 'USG', '2024-05-19 10:00:00', '5678-9012-34', NULL, 'gotowy'),
(14, 5, 1, 'USG', '2024-05-19 10:00:00', '5678-9012-34', NULL, 'gotowy'),
(15, 6, 1, 'USG', '2024-05-19 10:00:00', '5678-9012-34', NULL, 'gotowy'),
(16, 4, 1, 'Morfologia krwi', '2024-05-19 11:00:00', '6789-0123-45', NULL, 'gotowy'),
(17, 5, 1, 'Morfologia krwi', '2024-05-19 11:00:00', '6789-0123-45', NULL, 'gotowy'),
(18, 6, 1, 'Morfologia krwi', '2024-05-19 11:00:00', '6789-0123-45', NULL, 'gotowy'),
(19, 4, 1, 'Badanie moczu', '2024-05-20 09:00:00', '7890-1234-56', NULL, 'gotowy'),
(20, 5, 1, 'Badanie moczu', '2024-05-20 09:00:00', '7890-1234-56', NULL, 'gotowy'),
(21, 6, 1, 'Badanie moczu', '2024-05-20 09:00:00', '7890-1234-56', NULL, 'gotowy'),
(22, 4, 1, 'EKG', '2024-05-20 10:00:00', '8901-2345-67', NULL, 'gotowy'),
(23, 5, 1, 'EKG', '2024-05-20 10:00:00', '8901-2345-67', NULL, 'gotowy'),
(24, 6, 1, 'EKG', '2024-05-20 10:00:00', '8901-2345-67', NULL, 'gotowy'),
(25, 4, 1, 'RTG', '2024-05-20 11:00:00', '9012-3456-78', NULL, 'gotowy'),
(26, 5, 1, 'RTG', '2024-05-20 11:00:00', '9012-3456-78', NULL, 'gotowy'),
(27, 6, 1, 'RTG', '2024-05-20 11:00:00', '9012-3456-78', NULL, 'gotowy'),
(28, 6, 1, 'Morfologia krwi', '2025-05-19 17:50:00', '659878', 0x77796e696b5f363832623533353230663430632e6a7067, 'gotowy');

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
  `status` enum('oczekujaca','zatwierdzona','odrzucona') DEFAULT 'oczekujaca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `uzytkownik_id`, `ocena`, `tresc`, `data_utworzenia`, `status`) VALUES
(1, 2, 5, 'super', '2025-05-19 18:08:23', 'zatwierdzona'),
(2, 7, 5, '123', '2025-05-19 18:12:52', 'zatwierdzona');

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
  `funkcja` enum('pacjent','lekarz','administrator','obsluga','pielegniarka') NOT NULL,
  `status` enum('aktywny','nieaktywny') DEFAULT 'aktywny',
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `ostatnie_logowanie` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `haslo`, `imie`, `nazwisko`, `pesel`, `data_urodzenia`, `numer_telefonu`, `adres`, `kod_pocztowy`, `miasto`, `funkcja`, `status`, `data_utworzenia`, `ostatnie_logowanie`) VALUES
(1, 'lekarz@test.pl', 'test123', 'Jan', 'Kowalski', '12345678901', '1980-01-01', '123456789', 'ul. Testowa 1', '39-300', 'Mielec', 'lekarz', 'aktywny', '2025-05-18 15:51:05', NULL),
(2, 'pacjent1@test.pl', 'test123', 'Anna', 'Nowak', '23456789012', '1990-02-02', '234567890', 'ul. Kwiatowa 2', '39-300', 'Mielec', 'pacjent', 'aktywny', '2025-05-18 15:51:05', NULL),
(3, 'pacjent2@test.pl', 'test123', 'Piotr', 'Wiśniewski', '34567890123', '1985-03-03', '345678901', 'ul. Słoneczna 3', '39-300', 'Mielec', 'pacjent', 'aktywny', '2025-05-18 15:51:05', NULL),
(4, 'pacjent3@test.pl', 'test123', 'Maria', 'Kowalczyk', '45678901234', '1995-04-04', '456789012', 'ul. Leśna 4', '39-300', 'Mielec', 'pacjent', 'aktywny', '2025-05-18 15:51:05', NULL),
(6, 'dr.nowak@szpital.pl', '$2y$10$XmNr/9Dzcx4TLmiYhR37OuvN4sqtPeblQVcUe7Z0p0OkshT3cOgoO', 'Paweł', 'Płatek', '07777113987', '2025-05-02', '', '', '', '', 'pacjent', 'aktywny', '2025-05-18 19:32:39', NULL),
(7, 'essa@test.pl', '123', 'tego', 'typu', '07777113982', '2025-05-04', '', '', '', '', 'pacjent', 'aktywny', '2025-05-19 18:12:19', NULL),
(8, 'admin@test.pl', '123', 'Piotr', 'Esia', '12345678907', '2025-05-05', '213769420', 'cos tam dla testu 3', '39-300', 'Mielec', 'administrator', 'aktywny', '2025-05-19 18:16:18', NULL);

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
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `pacjent_id`, `lekarz_id`, `data_wizyty`, `typ_wizyty`, `status`, `gabinet`, `opis`, `diagnoza`, `zalecenia`, `data_utworzenia`, `data_modyfikacji`) VALUES
(10, 4, 1, '2025-05-18 09:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Wizyta kontrolna', NULL, NULL, '2025-05-18 15:54:04', '2025-05-19 17:01:53'),
(11, 5, 1, '2024-05-18 09:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Wizyta kontrolna', NULL, NULL, '2025-05-18 15:54:04', NULL),
(12, 6, 1, '2024-05-18 09:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Wizyta kontrolna', NULL, NULL, '2025-05-18 15:54:04', NULL),
(13, 4, 1, '2024-05-18 10:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola po zabiegu', NULL, NULL, '2025-05-18 15:54:04', NULL),
(14, 5, 1, '2024-05-18 10:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola po zabiegu', NULL, NULL, '2025-05-18 15:54:04', NULL),
(15, 6, 1, '2024-05-18 10:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola po zabiegu', NULL, NULL, '2025-05-18 15:54:04', NULL),
(16, 4, 1, '2024-05-18 11:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie EKG', NULL, NULL, '2025-05-18 15:54:04', NULL),
(17, 5, 1, '2025-05-18 11:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie EKG', NULL, NULL, '2025-05-18 15:54:04', '2025-05-19 17:02:11'),
(18, 6, 1, '2024-05-18 11:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie EKG', NULL, NULL, '2025-05-18 15:54:04', NULL),
(19, 4, 1, '2024-05-19 09:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola wyników', NULL, NULL, '2025-05-18 15:54:04', NULL),
(20, 5, 1, '2024-05-19 09:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola wyników', NULL, NULL, '2025-05-18 15:54:04', NULL),
(21, 6, 1, '2024-05-19 09:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola wyników', NULL, NULL, '2025-05-18 15:54:04', NULL),
(22, 4, 1, '2024-05-19 10:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Pierwsza wizyta', NULL, NULL, '2025-05-18 15:54:04', NULL),
(23, 5, 1, '2024-05-19 10:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Pierwsza wizyta', NULL, NULL, '2025-05-18 15:54:04', NULL),
(24, 6, 1, '2024-05-19 10:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Pierwsza wizyta', NULL, NULL, '2025-05-18 15:54:04', NULL),
(25, 4, 1, '2024-05-19 11:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie krwi', NULL, NULL, '2025-05-18 15:54:04', NULL),
(26, 5, 1, '2025-05-19 11:00:00', 'badanie', 'zakończona', 'G1', 'Badanie krwi', 'cos tam test', 'essa', '2025-05-18 15:54:04', '2025-05-19 17:03:07'),
(27, 6, 1, '2025-05-19 11:00:00', 'badanie', 'zakończona', 'G1', 'Badanie krwi', 'trzeba kopac dołek', 'nic sie nie da zrobix', '2025-05-18 15:54:04', '2025-05-19 18:58:12'),
(28, 4, 1, '2024-05-20 09:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola leczenia', NULL, NULL, '2025-05-18 15:54:04', NULL),
(29, 5, 1, '2024-05-20 09:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola leczenia', NULL, NULL, '2025-05-18 15:54:04', NULL),
(30, 6, 1, '2024-05-20 09:00:00', 'kontrolna', 'zaplanowana', 'G1', 'Kontrola leczenia', NULL, NULL, '2025-05-18 15:54:04', NULL),
(31, 4, 1, '2024-05-20 10:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie USG', NULL, NULL, '2025-05-18 15:54:04', NULL),
(32, 5, 1, '2024-05-20 10:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie USG', NULL, NULL, '2025-05-18 15:54:04', NULL),
(33, 6, 1, '2024-05-20 10:00:00', 'badanie', 'zaplanowana', 'G1', 'Badanie USG', NULL, NULL, '2025-05-18 15:54:04', NULL),
(34, 4, 1, '2024-05-20 11:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Pierwsza wizyta', NULL, NULL, '2025-05-18 15:54:04', NULL),
(35, 5, 1, '2024-05-20 11:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Pierwsza wizyta', NULL, NULL, '2025-05-18 15:54:04', NULL),
(36, 6, 1, '2024-05-20 11:00:00', 'pierwsza', 'zaplanowana', 'G1', 'Pierwsza wizyta', NULL, NULL, '2025-05-18 15:54:04', NULL),
(37, 6, 1, '2025-05-21 09:00:00', 'pierwsza', 'zaplanowana', '1', 'sterydy', NULL, NULL, '2025-05-19 18:55:48', NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_hours`
--
ALTER TABLE `doctor_hours`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
