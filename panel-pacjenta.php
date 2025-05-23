<?php
session_start();

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
    $stmt = $conn->prepare("
        SELECT 
            u.imie, 
            u.nazwisko, 
            u.pesel,
            u.data_urodzenia,
            p.grupa_krwi,
            p.id as pacjent_id
        FROM users u 
        JOIN patients p ON u.id = p.uzytkownik_id 
        WHERE u.id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $pacjent = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
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
                <li><a href="personel.php">Nasz Personel</a></li>
                <li><a href="aktualnosci.php">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="wyloguj.php" class="btn-login">Wyloguj się</a>
        </div>
    </header>

    <nav class="patient-nav">
        <ul>
            <li><a href="#panel-glowny" class="active">Panel główny</a></li>
            <li><a href="#umow-wizyte">Umów Wizytę</a></li>
            <li><a href="#historia-wizyt">Historia wizyt</a></li>
            <li><a href="#historia-wynikow">Historia wyników</a></li>
            <li><a href="#wystaw-opinie">Wystaw opinię</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="patient-dashboard">
            <div class="dashboard-header">
                <h1>Panel Pacjenta</h1>
                <div class="patient-info">
                    <p>Witaj, <span class="patient-name"><?php echo $pacjent['imie'] . ' ' . $pacjent['nazwisko']; ?></span></p>
                    <p>PESEL: <span class="patient-pesel"><?php echo $pacjent['pesel']; ?></span></p>
                </div>
            </div>

            <!-- Sekcja Panel Główny -->
            <div id="panel-glowny" class="dashboard-section">
                <h2>Panel Główny</h2>
                
                <!-- Sekcja Nadchodzące Wizyty -->
                <div class="upcoming-visits-section">
                    <h3>Nadchodzące Wizyty</h3>
                    <div class="visits-container">
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
                            WHERE v.pacjent_id = :pacjent_id
                            AND v.data_wizyty >= NOW()
                            ORDER BY v.data_wizyty ASC
                        ");
                        $stmt->bindParam(':pacjent_id', $pacjent['pacjent_id']);
                        $stmt->execute();
                        $nadchodzace_wizyty = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($nadchodzace_wizyty) > 0) {
                            foreach ($nadchodzace_wizyty as $wizyta) {
                                echo '<div class="visit-card">';
                                echo '<div class="visit-info">';
                                echo '<h4>Dr ' . htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']) . '</h4>';
                                echo '<p class="doctor-specialization">' . htmlspecialchars($wizyta['specjalizacja']) . '</p>';
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
                            echo '<p class="no-visits">Brak zaplanowanych wizyt</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Sekcja Ostatnie Wyniki -->
                <div class="recent-results-section">
                    <h3>Ostatnie Wyniki Badań</h3>
                    <div class="results-container">
                        <?php
                        // Pobieranie ostatnich wyników
                        $stmt = $conn->prepare("
                            SELECT 
                                r.id,
                                r.typ_badania,
                                r.data_wystawienia,
                                r.status,
                                r.pin,
                                u.imie,
                                u.nazwisko,
                                d.specjalizacja
                            FROM results r
                            JOIN doctors d ON r.lekarz_id = d.id
                            JOIN users u ON d.uzytkownik_id = u.id
                            WHERE r.pacjent_id = :pacjent_id
                            ORDER BY r.data_wystawienia DESC
                            LIMIT 5
                        ");
                        $stmt->bindParam(':pacjent_id', $pacjent['pacjent_id']);
                        $stmt->execute();
                        $ostatnie_wyniki = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($ostatnie_wyniki) > 0) {
                            foreach ($ostatnie_wyniki as $wynik) {
                                echo '<div class="result-card">';
                                echo '<div class="result-info">';
                                echo '<h4>' . htmlspecialchars($wynik['typ_badania']) . '</h4>';
                                echo '<p class="result-date">' . date('d.m.Y H:i', strtotime($wynik['data_wystawienia'])) . '</p>';
                                echo '<p class="result-doctor">Dr ' . htmlspecialchars($wynik['imie'] . ' ' . $wynik['nazwisko']) . '</p>';
                                echo '<p class="result-specialization">' . htmlspecialchars($wynik['specjalizacja']) . '</p>';
                                echo '<p class="result-pin">PIN: ' . htmlspecialchars($wynik['pin']) . '</p>';
                                echo '<p class="result-status ' . strtolower($wynik['status']) . '">' . 
                                     ucfirst(htmlspecialchars($wynik['status'])) . '</p>';
                                echo '</div>';
                                if (!empty($wynik['plik_wyniku'])) {
                                    echo '<div class="result-actions">';
                                    echo '<a href="uploads/wyniki/' . htmlspecialchars($wynik['plik_wyniku']) . '" class="btn-download" target="_blank">Pobierz wynik</a>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-results">Brak wyników badań</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Sekcja Historia Wizyt -->
            <div id="historia-wizyt" class="dashboard-section" style="display: none;">
                <h2>Historia Wizyt</h2>
                <div class="visits-history-container">
                    <?php
                    // Pobieranie historii wizyt
                    $stmt = $conn->prepare("
                        SELECT 
                            v.id,
                            v.data_wizyty,
                            v.typ_wizyty,
                            v.status,
                            v.gabinet,
                            v.diagnoza,
                            v.zalecenia,
                            u.imie,
                            u.nazwisko,
                            d.specjalizacja
                        FROM visits v
                        JOIN doctors d ON v.lekarz_id = d.id
                        JOIN users u ON d.uzytkownik_id = u.id
                        WHERE v.pacjent_id = :pacjent_id
                        ORDER BY v.data_wizyty DESC
                    ");
                    $stmt->bindParam(':pacjent_id', $pacjent['pacjent_id']);
                    $stmt->execute();
                    $historia_wizyt = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($historia_wizyt) > 0) {
                        foreach ($historia_wizyt as $wizyta) {
                            echo '<div class="visit-history-card">';
                            echo '<div class="visit-history-info">';
                            echo '<h4>Dr ' . htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']) . '</h4>';
                            echo '<p class="doctor-specialization">' . htmlspecialchars($wizyta['specjalizacja']) . '</p>';
                            echo '<p class="visit-time">' . date('d.m.Y H:i', strtotime($wizyta['data_wizyty'])) . '</p>';
                            echo '<p class="visit-type ' . strtolower($wizyta['typ_wizyty']) . '">' . 
                                 ucfirst(htmlspecialchars($wizyta['typ_wizyty'])) . '</p>';
                            echo '<p class="visit-room">Gabinet: ' . htmlspecialchars($wizyta['gabinet']) . '</p>';
                            if ($wizyta['diagnoza']) {
                                echo '<div class="visit-details">';
                                echo '<h5>Diagnoza:</h5>';
                                echo '<p>' . nl2br(htmlspecialchars($wizyta['diagnoza'])) . '</p>';
                                echo '</div>';
                            }
                            if ($wizyta['zalecenia']) {
                                echo '<div class="visit-details">';
                                echo '<h5>Zalecenia:</h5>';
                                echo '<p>' . nl2br(htmlspecialchars($wizyta['zalecenia'])) . '</p>';
                                echo '</div>';
                            }
                            echo '</div>';
                            echo '<div class="visit-actions">';
                            echo '<span class="visit-status ' . strtolower($wizyta['status']) . '">' . 
                                 ucfirst(htmlspecialchars($wizyta['status'])) . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="no-visits">Brak historii wizyt</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Sekcja Historia Wyników -->
            <div id="historia-wynikow" class="dashboard-section" style="display: none;">
                <h2>Historia Wyników Badań</h2>
                <div class="results-history-container">
                    <?php
                    // Pobieranie historii wyników
                    $stmt = $conn->prepare("
                        SELECT 
                            r.id,
                            r.typ_badania,
                            r.data_wystawienia,
                            r.status,
                            r.pin,
                            r.plik_wyniku,
                            u.imie,
                            u.nazwisko,
                            d.specjalizacja
                        FROM results r
                        JOIN doctors d ON r.lekarz_id = d.id
                        JOIN users u ON d.uzytkownik_id = u.id
                        WHERE r.pacjent_id = :pacjent_id
                        ORDER BY r.data_wystawienia DESC
                    ");
                    $stmt->bindParam(':pacjent_id', $pacjent['pacjent_id']);
                    $stmt->execute();
                    $historia_wynikow = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($historia_wynikow) > 0) {
                        foreach ($historia_wynikow as $wynik) {
                            echo '<div class="result-history-card">';
                            echo '<div class="result-history-info">';
                            echo '<h4>' . htmlspecialchars($wynik['typ_badania']) . '</h4>';
                            echo '<p class="result-date">' . date('d.m.Y H:i', strtotime($wynik['data_wystawienia'])) . '</p>';
                            echo '<p class="result-doctor">Dr ' . htmlspecialchars($wynik['imie'] . ' ' . $wynik['nazwisko']) . '</p>';
                            echo '<p class="result-specialization">' . htmlspecialchars($wynik['specjalizacja']) . '</p>';
                            echo '<p class="result-pin">PIN: ' . htmlspecialchars($wynik['pin']) . '</p>';
                            if ($wynik['plik_wyniku']) {
                                echo '<a href="uploads/wyniki/' . htmlspecialchars($wynik['plik_wyniku']) . '" class="btn-download" target="_blank">Pobierz wynik</a>';
                            }
                            echo '<p class="result-status ' . strtolower($wynik['status']) . '">' . 
                                 ucfirst(htmlspecialchars($wynik['status'])) . '</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="no-results">Brak historii wyników badań</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Sekcja Wystaw Opinię -->
            <div id="wystaw-opinie" class="dashboard-section" style="display: none;">
                <h2>Wystaw Opinię</h2>
                <div class="opinion-form-container">
                    <form id="opinionForm" class="opinion-form">
                        <div class="form-group">
                            <label for="ocena">Ocena:</label>
                            <div class="rating">
                                <input type="radio" id="star5" name="ocena" value="5" required>
                                <label for="star5">★</label>
                                <input type="radio" id="star4" name="ocena" value="4">
                                <label for="star4">★</label>
                                <input type="radio" id="star3" name="ocena" value="3">
                                <label for="star3">★</label>
                                <input type="radio" id="star2" name="ocena" value="2">
                                <label for="star2">★</label>
                                <input type="radio" id="star1" name="ocena" value="1">
                                <label for="star1">★</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="komentarz">Komentarz:</label>
                            <textarea id="komentarz" name="komentarz" rows="4" required></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Wyślij opinię</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sekcja Umów Wizytę -->
            <div id="umow-wizyte" class="dashboard-section" style="display: none;">
                <h2>Umów Wizytę</h2>
                <div class="appointment-form-container">
                    <form id="appointmentForm" class="appointment-form">
                        <div class="form-group">
                            <label for="lekarz">Wybierz lekarza:</label>
                            <select id="lekarz" name="lekarz_id" required>
                                <option value="">Wybierz lekarza</option>
                                <?php
                                // Pobieranie listy lekarzy
                                $stmt = $conn->prepare("
                                    SELECT 
                                        d.id,
                                        u.imie,
                                        u.nazwisko,
                                        d.specjalizacja
                                    FROM doctors d
                                    JOIN users u ON d.uzytkownik_id = u.id
                                    WHERE u.status = 'aktywny'
                                    ORDER BY d.specjalizacja, u.nazwisko, u.imie
                                ");
                                $stmt->execute();
                                $lekarze = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($lekarze as $lekarz) {
                                    echo '<option value="' . $lekarz['id'] . '">' . 
                                         'Dr ' . htmlspecialchars($lekarz['imie'] . ' ' . $lekarz['nazwisko']) . 
                                         ' - ' . htmlspecialchars($lekarz['specjalizacja']) . 
                                         '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="data_wizyty">Data wizyty:</label>
                            <input type="date" id="data_wizyty" name="data_wizyty" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="godzina_wizyty">Godzina wizyty:</label>
                            <select id="godzina_wizyty" name="godzina_wizyty" required disabled>
                                <option value="">Najpierw wybierz lekarza i datę</option>
                            </select>
                            <div id="doctor_unavailable" class="error-message" style="display: none; color: #dc3545; margin-top: 5px;">
                                Lekarz nie przyjmuje w wybranym dniu
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="typ_wizyty">Typ wizyty:</label>
                            <select id="typ_wizyty" name="typ_wizyty" required>
                                <option value="">Wybierz typ wizyty</option>
                                <option value="pierwsza">Pierwsza wizyta</option>
                                <option value="kontrolna">Wizyta kontrolna</option>
                                <option value="badanie">Badanie</option>
                            </select>
                            <div id="visit_price" class="price-info" style="display: none; margin-top: 5px;">
                                Cena wizyty: <span id="price_value">0</span> zł
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="opis">Opis problemu (opcjonalnie):</label>
                            <textarea id="opis" name="opis" rows="4"></textarea>
                        </div>

                        <div class="payment-info">
                            <h4>Informacje o płatności:</h4>
                            <p>Płatność za wizytę możesz dokonać:</p>
                            <ul>
                                <li>W gabinecie lekarskim przed wizytą</li>
                                <li>W sekretariacie szpitala (parter, pokój 101)</li>
                            </ul>
                            <p class="payment-note">Uwaga: W przypadku rezygnacji z wizyty na mniej niż 24h przed umówionym terminem, może zostać naliczona opłata w wysokości 50% ceny wizyty.</p>
                        </div>

                        <input type="hidden" name="pacjent_id" value="<?php echo $pacjent['pacjent_id']; ?>">
                        <input type="hidden" name="status" value="zaplanowana">
                        <input type="hidden" name="gabinet" value="1">

                        <button type="submit" class="btn-submit">Umów wizytę</button>
                    </form>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const lekarzSelect = document.getElementById('lekarz');
        const dataWizytyInput = document.getElementById('data_wizyty');
        const godzinaWizytySelect = document.getElementById('godzina_wizyty');
        const doctorUnavailable = document.getElementById('doctor_unavailable');
        const typWizytySelect = document.getElementById('typ_wizyty');
        const visitPrice = document.getElementById('visit_price');
        const priceValue = document.getElementById('price_value');

        // Funkcja do aktualizacji dostępnych godzin
        function updateAvailableHours() {
            const selectedLekarz = lekarzSelect.value;
            const selectedDate = dataWizytyInput.value;
            
            console.log('Wybrany lekarz:', selectedLekarz);
            console.log('Wybrana data:', selectedDate);
            
            if (!selectedLekarz || !selectedDate) {
                godzinaWizytySelect.disabled = true;
                godzinaWizytySelect.innerHTML = '<option value="">Najpierw wybierz lekarza i datę</option>';
                doctorUnavailable.style.display = 'none';
                return;
            }

            // Pobierz dzień tygodnia z wybranej daty
            const date = new Date(selectedDate);
            const days = ['niedziela', 'poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek', 'sobota'];
            const dayOfWeek = days[date.getDay()];
            
            console.log('Dzień tygodnia:', dayOfWeek);

            // Sprawdź zajęte godziny dla wybranego lekarza i daty
            fetch('check_available_hours.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lekarz_id=${selectedLekarz}&data=${selectedDate}&dzien=${dayOfWeek}`
            })
            .then(response => {
                console.log('Status odpowiedzi:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Otrzymane dane:', data);
                
                if (data.error) {
                    console.error('Błąd:', data.error);
                    godzinaWizytySelect.disabled = true;
                    godzinaWizytySelect.innerHTML = '<option value="">Brak dostępnych godzin</option>';
                    doctorUnavailable.style.display = 'block';
                    return;
                }

                // Wyczyść obecne opcje
                godzinaWizytySelect.innerHTML = '';
                godzinaWizytySelect.disabled = false;
                doctorUnavailable.style.display = 'none';

                // Dodaj opcję domyślną
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Wybierz godzinę';
                godzinaWizytySelect.appendChild(defaultOption);

                // Dodaj dostępne godziny
                if (data.availableHours && data.availableHours.length > 0) {
                    data.availableHours.forEach(hour => {
                        const option = document.createElement('option');
                        option.value = hour;
                        option.textContent = hour;
                        godzinaWizytySelect.appendChild(option);
                    });
                } else {
                    godzinaWizytySelect.innerHTML = '<option value="">Brak dostępnych godzin</option>';
                }
            })
            .catch(error => {
                console.error('Błąd podczas pobierania dostępnych godzin:', error);
                godzinaWizytySelect.disabled = true;
                godzinaWizytySelect.innerHTML = '<option value="">Błąd podczas pobierania godzin</option>';
                doctorUnavailable.style.display = 'none';
            });
        }

        // Funkcja do aktualizacji ceny wizyty
        function updateVisitPrice() {
            const selectedLekarz = lekarzSelect.value;
            const selectedType = typWizytySelect.value;
            
            if (!selectedLekarz || !selectedType) {
                visitPrice.style.display = 'none';
                return;
            }

            // Pobierz cenę wizyty
            fetch('get_visit_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lekarz_id=${selectedLekarz}&typ_wizyty=${selectedType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.price) {
                    priceValue.textContent = data.price;
                    visitPrice.style.display = 'block';
                } else {
                    visitPrice.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Błąd podczas pobierania ceny:', error);
                visitPrice.style.display = 'none';
            });
        }

        // Nasłuchuj zmian w wyborze lekarza i daty
        lekarzSelect.addEventListener('change', updateAvailableHours);
        dataWizytyInput.addEventListener('change', updateAvailableHours);

        // Nasłuchuj zmian w wyborze typu wizyty
        typWizytySelect.addEventListener('change', updateVisitPrice);
    });
    </script>
</body>
</html> 