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

    // Pobieranie danych właściciela
    $stmt = $conn->prepare("
        SELECT imie, nazwisko 
        FROM users 
        WHERE id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $wlasciciel = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pobieranie statystyk finansowych
    $stmt = $conn->query("
        SELECT 
            SUM(dvp.cena) as przychod_z_wizyt,
            (SELECT SUM(s.pensja) 
             FROM salaries s 
             JOIN users u ON s.uzytkownik_id = u.id 
             WHERE u.funkcja IN ('lekarz', 'obsluga')) as koszty_pensji,
            (SELECT SUM(dvp.cena) - 
             (SELECT SUM(s.pensja) 
              FROM salaries s 
              JOIN users u ON s.uzytkownik_id = u.id 
              WHERE u.funkcja IN ('lekarz', 'obsluga'))) as profit
        FROM visits v
        JOIN doctor_visit_prices dvp ON v.lekarz_id = dvp.lekarz_id AND v.typ_wizyty = dvp.typ_wizyty
        WHERE v.status = 'zakończona'
        AND MONTH(v.data_wizyty) = MONTH(CURRENT_DATE())
        AND YEAR(v.data_wizyty) = YEAR(CURRENT_DATE())
    ");
    $finanse = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pobieranie listy wypłat lekarzy i obsługi
    $stmt = $conn->query("
        SELECT 
            u.imie,
            u.nazwisko,
            u.funkcja,
            d.specjalizacja,
            COALESCE(s.pensja, 0) as pensja,
            COUNT(DISTINCT v.id) as liczba_wizyt,
            SUM(dvp.cena) as przychod_z_wizyt
        FROM users u
        LEFT JOIN doctors d ON d.uzytkownik_id = u.id
        LEFT JOIN salaries s ON u.id = s.uzytkownik_id
        LEFT JOIN visits v ON d.id = v.lekarz_id AND v.status = 'zakończona'
        LEFT JOIN doctor_visit_prices dvp ON v.lekarz_id = dvp.lekarz_id AND v.typ_wizyty = dvp.typ_wizyty
        WHERE u.funkcja IN ('lekarz', 'obsluga')
        AND (v.data_wizyty IS NULL OR (MONTH(v.data_wizyty) = MONTH(CURRENT_DATE()) AND YEAR(v.data_wizyty) = YEAR(CURRENT_DATE())))
        GROUP BY u.id, u.imie, u.nazwisko, u.funkcja, d.specjalizacja, s.pensja
        ORDER BY u.funkcja, u.nazwisko, u.imie
    ");
    $wypaty_lekarzy = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

} catch(PDOException $e) {
    echo "Błąd połączenia: " . $e->getMessage();
}
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
    <style>
        .doctors-salaries {
            margin-top: 30px;
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .doctors-salaries h3 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .salaries-table-container {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .salaries-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
            border: 1px solid #e9ecef;
        }

        .salaries-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-align: left;
            padding: 16px;
            border-bottom: 2px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 1;
            border-right: 1px solid #e9ecef;
        }

        .salaries-table th:first-child {
            border-top-left-radius: 8px;
        }

        .salaries-table th:last-child {
            border-top-right-radius: 8px;
            border-right: none;
        }

        .salaries-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e9ecef;
            border-right: 1px solid #e9ecef;
            color: #495057;
            transition: all 0.3s ease;
        }

        .salaries-table td:last-child {
            border-right: none;
        }

        .salaries-table tr:hover td {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .salaries-table tr:last-child td {
            border-bottom: none;
        }

        .salaries-table td:nth-child(3),
        .salaries-table td:nth-child(5) {
            font-weight: 600;
            color: #2c3e50;
        }

        .salaries-table td:nth-child(4) {
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .doctors-salaries {
                padding: 15px;
                margin-top: 20px;
            }

            .doctors-salaries h3 {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .salaries-table th,
            .salaries-table td {
                padding: 12px;
                font-size: 14px;
            }
        }

        /* Style dla menu z podstronami */
        .main-nav ul li a {
            font-weight: 600;
            font-size: 16px;
        }
    </style>
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
                    <p>Witaj, <span class="owner-name"><?php echo htmlspecialchars($wlasciciel['imie']); ?></span></p>
                </div>
            </div>

            <section class="stats-section">
                <h2>Statystyki Finansowe (<?php echo date('F Y'); ?>)</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Przychód z wizyt</h3>
                        <p><?php echo number_format($finanse['przychod_z_wizyt'] ?? 0, 2); ?> PLN</p>
                    </div>
                    <div class="stat-card">
                        <h3>Koszty pensji</h3>
                        <p><?php echo number_format($finanse['koszty_pensji'] ?? 0, 2); ?> PLN</p>
                    </div>
                    <div class="stat-card">
                        <h3>Profit</h3>
                        <p class="<?php echo ($finanse['profit'] ?? 0) >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo number_format($finanse['profit'] ?? 0, 2); ?> PLN
                        </p>
                    </div>
                </div>

                <div class="doctors-salaries">
                    <h3>Wypłaty personelu (<?php echo date('F Y'); ?>)</h3>
                    <div class="salaries-table-container">
                        <table class="salaries-table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Stanowisko</th>
                                    <th>Specjalizacja</th>
                                    <th>Pensja</th>
                                    <th>Liczba wizyt</th>
                                    <th>Przychód z wizyt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wypaty_lekarzy as $pracownik): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pracownik['imie'] . ' ' . $pracownik['nazwisko']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($pracownik['funkcja'])); ?></td>
                                    <td><?php echo $pracownik['funkcja'] === 'lekarz' ? htmlspecialchars($pracownik['specjalizacja']) : '-'; ?></td>
                                    <td><?php echo number_format($pracownik['pensja'], 2); ?> PLN</td>
                                    <td><?php echo $pracownik['liczba_wizyt']; ?></td>
                                    <td><?php echo number_format($pracownik['przychod_z_wizyt'] ?? 0, 2); ?> PLN</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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