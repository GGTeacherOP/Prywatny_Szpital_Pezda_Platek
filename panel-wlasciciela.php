<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sprawdzenie czy użytkownik jest zalogowany i jest właścicielem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'wlasciciel') {
    header("Location: logowanie.php");
    exit();
}

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Błąd połączenia: " . $e->getMessage();
}

// Pobieranie statystyk finansowych
$stmt = $conn->query("
    SELECT 
        SUM(dvp.cena) as przychod_z_wizyt
    FROM visits v
    JOIN doctor_visit_prices dvp ON v.lekarz_id = dvp.lekarz_id AND v.typ_wizyty = dvp.typ_wizyty
    WHERE v.status = 'zakończona'
");
$finanse = $stmt->fetch(PDO::FETCH_ASSOC);

// Pobieranie statystyk kadrowych
$stmt = $conn->query("
    SELECT 
        COUNT(DISTINCT d.id) as liczba_lekarzy,
        COUNT(DISTINCT n.id) as liczba_pielegniarek,
        COUNT(DISTINCT s.id) as liczba_personelu
    FROM doctors d
    CROSS JOIN nurses n
    CROSS JOIN staff s
");
$kadry = $stmt->fetch(PDO::FETCH_ASSOC);

// Pobieranie statystyk pacjentów
$stmt = $conn->query("
    SELECT 
        COUNT(DISTINCT p.id) as liczba_pacjentow,
        COUNT(DISTINCT v.id) as liczba_wizyt,
        AVG(dr.ocena) as srednia_ocena_lekarzy
    FROM patients p
    LEFT JOIN visits v ON v.pacjent_id = p.id
    LEFT JOIN doctor_reviews dr ON dr.lekarz_id = v.lekarz_id
");
$pacjenci = $stmt->fetch(PDO::FETCH_ASSOC);

// Pobieranie statystyk obłożenia
$stmt = $conn->query("
    SELECT 
        COUNT(DISTINCT r.id) as liczba_sal,
        COUNT(DISTINCT d.id) as liczba_oddzialow
    FROM rooms r
    LEFT JOIN departments d ON d.id = r.oddzial_id
");
$oblozenie = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Panel Właściciela</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/panel-wlasciciela.css'>
    <script src='main.js'></script>
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="img/logo/logo.png" alt="Logo Szpitala">
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="index.html">Strona główna</a></li>
                <li><a href="o-nas.html">O nas</a></li>
                <li><a href="personel.php">Nasz Personel</a></li>
                <li><a href="aktualnosci.php">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="wyloguj.php" class="btn-login">Wyloguj się</a>
        </div>
    </header>

    <main class="main-content">
        <div class="owner-dashboard">
            <div class="dashboard-header">
                <h1>Panel Właściciela</h1>
                <div class="owner-info">
                    <p>Witaj, <span class="owner-name">Właściciel</span></p>
                </div>
            </div>

            <section class="stats-section">
                <h2>Statystyki Finansowe</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Przychód z wizyt</h3>
                        <p><?php echo number_format($finanse['przychod_z_wizyt'] ?? 0, 2); ?> PLN</p>
                    </div>
                </div>
            </section>

            <section class="stats-section">
                <h2>Statystyki Kadrowe</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Liczba lekarzy</h3>
                        <p><?php echo $kadry['liczba_lekarzy']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Liczba pielęgniarek</h3>
                        <p><?php echo $kadry['liczba_pielegniarek']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Liczba personelu</h3>
                        <p><?php echo $kadry['liczba_personelu']; ?></p>
                    </div>
                </div>
            </section>

            <section class="stats-section">
                <h2>Statystyki Pacjentów</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Liczba pacjentów</h3>
                        <p><?php echo $pacjenci['liczba_pacjentow']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Liczba wizyt</h3>
                        <p><?php echo $pacjenci['liczba_wizyt']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Średnia ocena lekarzy</h3>
                        <p><?php echo number_format($pacjenci['srednia_ocena_lekarzy'], 2); ?>/5</p>
                    </div>
                </div>
            </section>

            <section class="stats-section">
                <h2>Statystyki Obłożenia</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Liczba sal</h3>
                        <p><?php echo $oblozenie['liczba_sal']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Liczba oddziałów</h3>
                        <p><?php echo $oblozenie['liczba_oddzialow']; ?></p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html> 