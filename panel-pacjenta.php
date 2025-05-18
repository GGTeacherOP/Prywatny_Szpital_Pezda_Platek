<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Sprawdzenie czy użytkownik jest zalogowany i jest pacjentem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'pacjent') {
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
    
    // Pobieranie danych pacjenta
    $stmt = $conn->prepare("SELECT u.*, p.grupa_krwi 
                           FROM users u 
                           JOIN patients p ON u.id = p.uzytkownik_id 
                           WHERE u.id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $pacjent = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Błąd połączenia: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Panel Pacjenta</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/panel-pacjenta.css'>
    <script src='main.js'></script>
    <script src='js/panel-pacjenta.js'></script>
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
                <li><a href="aktualnosci.html">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="logowanie.php" class="btn-login">Wyloguj się</a>
        </div>
    </header>

    <main class="main-content">
        <div class="patient-dashboard">
            <div class="dashboard-header">
                <h1>Panel Pacjenta</h1>
                <div class="patient-info">
                    <p>Witaj, <span class="patient-name"><?php echo htmlspecialchars($pacjent['imie'] . ' ' . $pacjent['nazwisko']); ?></span></p>
                    <p>PESEL: <span class="patient-pesel"><?php echo htmlspecialchars($pacjent['pesel']); ?></span></p>
                </div>
            </div>

            <div class="dashboard-container">
                <!-- Boczny panel nawigacyjny -->
                <nav class="side-nav">
                    <ul>
                        <li>
                            <a href="#" class="nav-item active" data-panel="panel-glowny">
                                <span class="nav-icon">🏠</span>
                                <span class="nav-text">Panel główny</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-item" data-panel="historia-wynikow">
                                <span class="nav-icon">📋</span>
                                <span class="nav-text">Historia wyników</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-item" data-panel="umow-wizyte">
                                <span class="nav-icon">📅</span>
                                <span class="nav-text">Umów się na wizytę</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="dashboard-grid">
                    <!-- Panel Główny -->
                    <div class="panel-content" id="panel-glowny">
                        <div class="main-grid">
                            <!-- Sekcja Nadchodzące Wizyty -->
                            <div class="grid-item">
                                <section class="dashboard-section upcoming-visits">
                                    <h2>Nadchodzące Wizyty</h2>
                                    <div class="visits-scroll-container">
                                        <?php
                                        // Pobieranie nadchodzących wizyt
                                        $stmt = $conn->prepare("
                                            SELECT 
                                                v.id,
                                                v.data_wizyty,
                                                v.typ_wizyty,
                                                v.status,
                                                v.gabinet,
                                                u.imie,
                                                u.nazwisko,
                                                d.specjalizacja
                                            FROM visits v
                                            JOIN doctors d ON v.lekarz_id = d.id
                                            JOIN users u ON d.uzytkownik_id = u.id
                                            JOIN patients p ON v.pacjent_id = p.id
                                            WHERE p.uzytkownik_id = :user_id
                                            AND v.data_wizyty >= CURDATE()
                                            ORDER BY v.data_wizyty ASC
                                        ");
                                        $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                        $stmt->execute();
                                        $wizyty = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (count($wizyty) > 0) {
                                            foreach ($wizyty as $wizyta) {
                                                echo '<div class="visit-card">';
                                                echo '<div class="visit-info">';
                                                echo '<h3>Dr ' . htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']) . '</h3>';
                                                echo '<p class="visit-specialization">' . htmlspecialchars($wizyta['specjalizacja']) . '</p>';
                                                echo '<p class="visit-time">' . date('d.m.Y H:i', strtotime($wizyta['data_wizyty'])) . '</p>';
                                                echo '<p class="visit-type ' . strtolower($wizyta['typ_wizyty']) . '">' . 
                                                     ucfirst(htmlspecialchars($wizyta['typ_wizyty'])) . '</p>';
                                                echo '<p class="visit-room">Gabinet: ' . htmlspecialchars($wizyta['gabinet']) . '</p>';
                                                echo '<p class="visit-status">Status: ' . htmlspecialchars($wizyta['status']) . '</p>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        } else {
                                            echo '<p class="no-visits">Brak zaplanowanych wizyt</p>';
                                        }
                                        ?>
                                    </div>
                                </section>
                            </div>

                            <!-- Sekcja Ostatnie Wyniki -->
                            <div class="grid-item">
                                <section class="dashboard-section recent-results">
                                    <h2>Ostatnie Wyniki</h2>
                                    <?php
                                    // Pobieranie ostatnich wyników
                                    $stmt = $conn->prepare("
                                        SELECT r.*, u.imie, u.nazwisko 
                                        FROM results r 
                                        JOIN doctors d ON r.lekarz_id = d.id 
                                        JOIN users u ON d.uzytkownik_id = u.id
                                        JOIN patients p ON r.pacjent_id = p.id 
                                        WHERE p.uzytkownik_id = :user_id 
                                        ORDER BY r.data_wystawienia DESC 
                                        LIMIT 5
                                    ");
                                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                    $stmt->execute();
                                    $wyniki = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (count($wyniki) > 0) {
                                        foreach ($wyniki as $wynik) {
                                            echo '<div class="result-card">';
                                            echo '<div class="result-info">';
                                            echo '<h3>' . htmlspecialchars($wynik['typ_badania']) . '</h3>';
                                            echo '<p class="result-date">' . date('d.m.Y H:i', strtotime($wynik['data_wystawienia'])) . '</p>';
                                            echo '<p class="result-doctor">Dr ' . htmlspecialchars($wynik['imie'] . ' ' . $wynik['nazwisko']) . '</p>';
                                            echo '<p class="result-pin">PIN: ' . htmlspecialchars($wynik['pin']) . '</p>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<p class="no-results">Brak wyników badań</p>';
                                    }
                                    ?>
                                </section>
                            </div>

                            <!-- Sekcja Historia Wizyt -->
                            <div class="grid-item full-width">
                                <section class="dashboard-section visit-history">
                                    <h2>Historia Wizyt</h2>
                                    <div class="history-container">
                                        <?php
                                        // Pobieranie historii wizyt
                                        $stmt = $conn->prepare("
                                            SELECT 
                                                v.*,
                                                u.imie,
                                                u.nazwisko,
                                                d.specjalizacja
                                            FROM visits v
                                            JOIN doctors d ON v.lekarz_id = d.id
                                            JOIN users u ON d.uzytkownik_id = u.id
                                            JOIN patients p ON v.pacjent_id = p.id
                                            WHERE p.uzytkownik_id = :user_id
                                            AND v.data_wizyty < CURDATE()
                                            ORDER BY v.data_wizyty DESC
                                            LIMIT 10
                                        ");
                                        $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                        $stmt->execute();
                                        $historia = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (count($historia) > 0) {
                                            echo '<table class="history-table">';
                                            echo '<thead><tr>';
                                            echo '<th>Data</th>';
                                            echo '<th>Lekarz</th>';
                                            echo '<th>Specjalizacja</th>';
                                            echo '<th>Typ wizyty</th>';
                                            echo '<th>Status</th>';
                                            echo '<th>Gabinet</th>';
                                            echo '</tr></thead><tbody>';
                                            
                                            foreach ($historia as $wizyta) {
                                                echo '<tr>';
                                                echo '<td>' . date('d.m.Y H:i', strtotime($wizyta['data_wizyty'])) . '</td>';
                                                echo '<td>Dr ' . htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']) . '</td>';
                                                echo '<td>' . htmlspecialchars($wizyta['specjalizacja']) . '</td>';
                                                echo '<td>' . htmlspecialchars($wizyta['typ_wizyty']) . '</td>';
                                                echo '<td>' . htmlspecialchars($wizyta['status']) . '</td>';
                                                echo '<td>' . htmlspecialchars($wizyta['gabinet']) . '</td>';
                                                echo '</tr>';
                                            }
                                            
                                            echo '</tbody></table>';
                                        } else {
                                            echo '<p class="no-history">Brak historii wizyt</p>';
                                        }
                                        ?>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <!-- Historia Wyników -->
                    <div class="panel-content" id="historia-wynikow" style="display: none;">
                        <h2>Historia Wyników</h2>
                        <div class="results-container">
                            <div class="results-list">
                                <?php
                                // Pobieranie wszystkich wyników
                                $stmt = $conn->prepare("
                                    SELECT r.*, u.imie, u.nazwisko 
                                    FROM results r 
                                    JOIN doctors d ON r.lekarz_id = d.id 
                                    JOIN users u ON d.uzytkownik_id = u.id
                                    JOIN patients p ON r.pacjent_id = p.id 
                                    WHERE p.uzytkownik_id = :user_id 
                                    ORDER BY r.data_wystawienia DESC
                                ");
                                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                $stmt->execute();
                                $wyniki = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($wyniki) > 0) {
                                    foreach ($wyniki as $wynik) {
                                        echo '<div class="result-card" data-result-id="' . $wynik['id'] . '">';
                                        echo '<div class="result-header">';
                                        echo '<h3>' . htmlspecialchars($wynik['typ_badania']) . '</h3>';
                                        echo '<span class="result-date">' . date('d.m.Y H:i', strtotime($wynik['data_wystawienia'])) . '</span>';
                                        echo '</div>';
                                        echo '<div class="result-details">';
                                        echo '<p class="result-doctor">Dr ' . htmlspecialchars($wynik['imie'] . ' ' . $wynik['nazwisko']) . '</p>';
                                        echo '<p class="result-pin">PIN: ' . htmlspecialchars($wynik['pin']) . '</p>';
                                        if (isset($wynik['opis']) && !empty($wynik['opis'])) {
                                            echo '<p class="result-description">' . htmlspecialchars($wynik['opis']) . '</p>';
                                        }
                                        if (isset($wynik['plik_wyniku']) && !empty($wynik['plik_wyniku'])) {
                                            echo '<a href="uploads/results/' . htmlspecialchars($wynik['plik_wyniku']) . '" class="result-file" target="_blank">Pobierz wynik</a>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p class="no-results">Brak wyników badań</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Umów się na wizytę -->
                    <div class="panel-content" id="umow-wizyte" style="display: none;">
                        <h2>Umów się na wizytę</h2>
                        <div class="visit-form-container">
                            <form action="save_visit.php" method="POST" class="visit-form">
                                <div class="form-group">
                                    <label for="lekarz_id">Wybierz lekarza:</label>
                                    <select name="lekarz_id" id="lekarz_id" required>
                                        <option value="">-- Wybierz lekarza --</option>
                                        <?php
                                        $stmt = $conn->prepare("
                                            SELECT d.id, u.imie, u.nazwisko, d.specjalizacja 
                                            FROM doctors d 
                                            JOIN users u ON d.uzytkownik_id = u.id 
                                            WHERE u.aktywny = 1 
                                            ORDER BY d.specjalizacja, u.nazwisko, u.imie
                                        ");
                                        $stmt->execute();
                                        while ($row = $stmt->fetch()) {
                                            echo "<option value='{$row['id']}'>Dr {$row['imie']} {$row['nazwisko']} - {$row['specjalizacja']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="data_wizyty">Data wizyty:</label>
                                    <input type="date" name="data_wizyty" id="data_wizyty" required min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="godzina_wizyty">Godzina wizyty:</label>
                                    <input type="time" name="godzina_wizyty" id="godzina_wizyty" required>
                                </div>

                                <div class="form-group">
                                    <label for="typ_wizyty">Typ wizyty:</label>
                                    <select name="typ_wizyty" id="typ_wizyty" required>
                                        <option value="pierwsza">Pierwsza wizyta</option>
                                        <option value="kontrolna">Wizyta kontrolna</option>
                                        <option value="pogotowie">Pogotowie</option>
                                        <option value="szczepienie">Szczepienie</option>
                                        <option value="badanie">Badanie</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="gabinet">Numer gabinetu:</label>
                                    <input type="text" name="gabinet" id="gabinet" required>
                                </div>

                                <div class="form-group">
                                    <label for="opis">Opis problemu:</label>
                                    <textarea name="opis" id="opis" rows="3"></textarea>
                                </div>

                                <input type="hidden" name="pacjent_id" value="<?php echo $pacjent['id']; ?>">
                                <input type="hidden" name="status" value="zaplanowana">

                                <button type="submit" class="btn-submit">Umów wizytę</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Kontakt</h3>
                <p>aleja Niepodległości 6</p>
                <p>39-300 Mielec</p>
                <p>tel: (+48) 451 551 819</p>
            </div>
            <div class="footer-section">
                <h3>Godziny przyjęć</h3>
                <p>Poniedziałek - Piątek: 11:00 - 17:00</p>
                <p>Sobota: Zamknięte</p>
                <p>Niedziela: Zamknięte</p>
            </div>
            <div class="footer-section">
                <h3>Obserwuj nas</h3>
                <div class="social-links">
                    <a href="#" target="_blank" title="Facebook"><img src="img/social/facebook.png" alt="Facebook"></a>
                    <a href="#" target="_blank" title="Instagram"><img src="img/social/instagram.png" alt="Instagram"></a>
                    <a href="#" target="_blank" title="Twitter"><img src="img/social/twitter.png" alt="Twitter"></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Prywatny Szpital im. Coinplex. Wszelkie prawa zastrzeżone.</p>
        </div>
    </footer>
</body>
</html> 