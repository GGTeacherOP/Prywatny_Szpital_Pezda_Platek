<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
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

    // Pobieranie danych lekarza
    $stmt = $conn->prepare("
        SELECT u.imie, u.nazwisko, d.specjalizacja 
        FROM users u 
        JOIN doctors d ON u.id = d.uzytkownik_id 
        WHERE u.id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $lekarz = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Panel Lekarza</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/panel-lekarza.css'>
    <script src='main.js'></script>
    <script src='js/panel-lekarza.js'></script>
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
            <a href="wyloguj.php" class="btn-login">Wyloguj się</a>
        </div>
    </header>

    <nav class="doctor-nav">
        <ul>
            <li><a href="#panel-glowny" class="active">Panel główny</a></li>
            <li><a href="#pacjenci">Pacjenci</a></li>
            <li><a href="#wizyty">Wizyty</a></li>
            <li><a href="#statystyki">Statystyki</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="doctor-dashboard">
            <div class="dashboard-header">
                <h1>Panel Lekarza</h1>
                <div class="doctor-info">
                    <p>Witaj, <span class="doctor-name"><?php echo $lekarz['imie'] . ' ' . $lekarz['nazwisko']; ?></span></p>
                    <p>Specjalizacja: <span class="doctor-specialization"><?php echo $lekarz['specjalizacja']; ?></span></p>
                </div>
            </div>

            <!-- Sekcja Panel Główny -->
            <div id="panel-glowny" class="dashboard-section">
                <h2>Panel Główny</h2>
                
                <!-- Sekcja Dzisiejsze Wizyty -->
                <div class="today-visits-section">
                    <h3>Dzisiejsze Wizyty</h3>
                    <div class="visits-container">
                        <?php
                        // Debugowanie
                        $stmt = $conn->prepare("SELECT id FROM doctors WHERE uzytkownik_id = :user_id");
                        $stmt->bindParam(':user_id', $_SESSION['user_id']);
                        $stmt->execute();
                        $lekarz_id = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$lekarz_id) {
                            echo '<p class="error">Nie znaleziono ID lekarza dla zalogowanego użytkownika</p>';
                        } else {
                            // Pobieranie dzisiejszych wizyt
                            $stmt = $conn->prepare("
                                SELECT 
                                    v.id,
                                    v.data_wizyty,
                                    v.typ_wizyty,
                                    v.status,
                                    v.gabinet,
                                    u.imie,
                                    u.nazwisko,
                                    p.id as pacjent_id
                                FROM visits v
                                JOIN patients p ON v.pacjent_id = p.id
                                JOIN users u ON p.uzytkownik_id = u.id
                                WHERE v.lekarz_id = :lekarz_id
                                AND DATE(v.data_wizyty) = CURDATE()
                                ORDER BY v.data_wizyty ASC
                            ");
                            $stmt->bindParam(':lekarz_id', $lekarz_id['id']);
                            $stmt->execute();
                            $dzisiejsze_wizyty = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($dzisiejsze_wizyty) > 0) {
                                foreach ($dzisiejsze_wizyty as $wizyta) {
                                    echo '<div class="visit-card">';
                                    echo '<div class="visit-info">';
                                    echo '<h4>' . htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']) . '</h4>';
                                    echo '<p class="visit-time">' . date('H:i', strtotime($wizyta['data_wizyty'])) . '</p>';
                                    echo '<p class="visit-type ' . strtolower($wizyta['typ_wizyty']) . '">' . 
                                         ucfirst(htmlspecialchars($wizyta['typ_wizyty'])) . '</p>';
                                    echo '<p class="visit-room">Gabinet: ' . htmlspecialchars($wizyta['gabinet']) . '</p>';
                                    echo '</div>';
                                    echo '<div class="visit-actions">';
                                    if ($wizyta['status'] === 'zaplanowana') {
                                        echo '<a href="edytuj-wizyte.php?id=' . $wizyta['id'] . '" class="btn-start">Rozpocznij wizytę</a>';
                                    } else {
                                        echo '<span class="visit-status ' . strtolower($wizyta['status']) . '">' . 
                                             ucfirst(htmlspecialchars($wizyta['status'])) . '</span>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="no-visits">Brak zaplanowanych wizyt na dziś</p>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- Sekcja Ostatnie Wizyty -->
                <div class="recent-visits-section">
                    <h3>Ostatnie Wizyty</h3>
                    <div class="visits-container">
                        <?php
                        if ($lekarz_id) {
                            // Pobieranie ostatnich wizyt
                            $stmt = $conn->prepare("
                                SELECT 
                                    v.id,
                                    v.data_wizyty,
                                    v.typ_wizyty,
                                    v.status,
                                    v.gabinet,
                                    u.imie,
                                    u.nazwisko,
                                    p.id as pacjent_id
                                FROM visits v
                                JOIN patients p ON v.pacjent_id = p.id
                                JOIN users u ON p.uzytkownik_id = u.id
                                WHERE v.lekarz_id = :lekarz_id
                                AND DATE(v.data_wizyty) < CURDATE()
                                ORDER BY v.data_wizyty DESC
                                LIMIT 5
                            ");
                            $stmt->bindParam(':lekarz_id', $lekarz_id['id']);
                            $stmt->execute();
                            $ostatnie_wizyty = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($ostatnie_wizyty) > 0) {
                                foreach ($ostatnie_wizyty as $wizyta) {
                                    echo '<div class="visit-card">';
                                    echo '<div class="visit-info">';
                                    echo '<h4>' . htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']) . '</h4>';
                                    echo '<p class="visit-time">' . date('d.m.Y H:i', strtotime($wizyta['data_wizyty'])) . '</p>';
                                    echo '<p class="visit-type ' . strtolower($wizyta['typ_wizyty']) . '">' . 
                                         ucfirst(htmlspecialchars($wizyta['typ_wizyty'])) . '</p>';
                                    echo '<p class="visit-room">Gabinet: ' . htmlspecialchars($wizyta['gabinet']) . '</p>';
                                    echo '</div>';
                                    echo '<div class="visit-actions">';
                                    echo '<span class="visit-status ' . strtolower($wizyta['status']) . '">' . 
                                         ucfirst(htmlspecialchars($wizyta['status'])) . '</span>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="no-visits">Brak ostatnich wizyt</p>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- Sekcja Ostatnie Wyniki -->
                <div class="recent-results-section">
                    <h3>Ostatnie Wyniki</h3>
                    <div class="results-container">
                        <?php
                        if ($lekarz_id) {
                            // Pobieranie ostatnich wyników
                            $stmt = $conn->prepare("
                                SELECT 
                                    r.id,
                                    r.typ_badania,
                                    r.data_wystawienia,
                                    r.status,
                                    r.pin,
                                    u.imie,
                                    u.nazwisko
                                FROM results r
                                JOIN patients p ON r.pacjent_id = p.id
                                JOIN users u ON p.uzytkownik_id = u.id
                                WHERE r.lekarz_id = :lekarz_id
                                ORDER BY r.data_wystawienia DESC
                                LIMIT 5
                            ");
                            $stmt->bindParam(':lekarz_id', $lekarz_id['id']);
                            $stmt->execute();
                            $ostatnie_wyniki = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($ostatnie_wyniki) > 0) {
                                foreach ($ostatnie_wyniki as $wynik) {
                                    echo '<div class="result-card">';
                                    echo '<div class="result-info">';
                                    echo '<h4>' . htmlspecialchars($wynik['imie'] . ' ' . $wynik['nazwisko']) . '</h4>';
                                    echo '<p class="result-type">' . htmlspecialchars($wynik['typ_badania']) . '</p>';
                                    echo '<p class="result-date">' . date('d.m.Y H:i', strtotime($wynik['data_wystawienia'])) . '</p>';
                                    echo '<p class="result-pin">PIN: ' . htmlspecialchars($wynik['pin']) . '</p>';
                                    echo '<p class="result-status ' . strtolower($wynik['status']) . '">' . 
                                         ucfirst(htmlspecialchars($wynik['status'])) . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="no-results">Brak ostatnich wyników</p>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Sekcja Pacjenci -->
            <div id="pacjenci" class="dashboard-section" style="display: none;">
                <h2>Pacjenci</h2>
                <!-- Tutaj będzie zawartość sekcji pacjentów -->
            </div>

            <!-- Sekcja Wizyty -->
            <div id="wizyty" class="dashboard-section" style="display: none;">
                <h2>Wizyty</h2>
                <!-- Tutaj będzie zawartość sekcji wizyt -->
            </div>

            <!-- Sekcja Statystyki -->
            <div id="statystyki" class="dashboard-section" style="display: none;">
                <h2>Statystyki</h2>
                <!-- Tutaj będzie zawartość sekcji statystyk -->
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn-start');
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const visitId = this.dataset.visitId;
                    const button = this;
                    
                    fetch('panel-lekarza.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'visit_id=' + visitId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.textContent = 'Wizyta zakończona';
                            button.classList.remove('btn-start');
                            button.classList.add('btn-completed');
                            button.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Wystąpił błąd podczas aktualizacji statusu wizyty');
                    });
                });
            });
        });
    </script>
</body>
</html> 