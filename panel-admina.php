<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
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

    // Pobieranie danych administratora
    $stmt = $conn->prepare("SELECT imie, nazwisko FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pobieranie ostatnich 5 opinii z dzisiejszego dnia
    $stmt = $conn->prepare("
        SELECT r.*, u.imie, u.nazwisko 
        FROM reviews r 
        JOIN users u ON r.uzytkownik_id = u.id 
        WHERE DATE(r.data_utworzenia) = CURDATE()
        ORDER BY r.data_utworzenia DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $ostatnie_opinie = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobieranie statystyk wiadomości
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_news,
            SUM(CASE WHEN status = 'opublikowany' THEN 1 ELSE 0 END) as published_news,
            SUM(CASE WHEN status = 'szkic' THEN 1 ELSE 0 END) as draft_news
        FROM news
    ");
    $stmt->execute();
    $statystyki_wiadomosci = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Panel Administratora</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/panel-admina.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/wyniki-badan.css'>
    <script src='main.js'></script>
    <script src='js/panel-admina.js'></script>
    <script>
        // Obsługa zmiany statusu wyników badań
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelects = document.querySelectorAll('.status-select[data-result-id]');
            
            statusSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const resultId = this.dataset.resultId;
                    const newStatus = this.value;
                    
                    // Wysłanie żądania AJAX
                    fetch('update_result_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `result_id=${resultId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Aktualizacja statusu w interfejsie
                            const resultCard = this.closest('.result-card');
                            resultCard.querySelector('.result-status').textContent = newStatus;
                            
                            // Opcjonalnie: pokazanie komunikatu o sukcesie
                            alert('Status został zaktualizowany');
                        } else {
                            // W przypadku błędu, przywrócenie poprzedniej wartości
                            this.value = this.dataset.originalValue;
                            alert('Wystąpił błąd podczas aktualizacji statusu: ' + (data.message || 'Nieznany błąd'));
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        this.value = this.dataset.originalValue;
                        alert('Wystąpił błąd podczas aktualizacji statusu');
                    });
                });
                
                // Zapisywanie oryginalnej wartości przy załadowaniu
                select.dataset.originalValue = select.value;
            });

            // Obsługa zmiany statusu opinii o lekarzach
            const reviewStatusSelects = document.querySelectorAll('.review-status .status-select');
            
            reviewStatusSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const reviewId = this.dataset.reviewId;
                    const newStatus = this.value;
                    
                    // Wysłanie żądania AJAX
                    fetch('update_doctor_review_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `review_id=${reviewId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Opcjonalnie: pokazanie komunikatu o sukcesie
                            alert(data.message || 'Status został zaktualizowany');
                        } else {
                            // W przypadku błędu, przywrócenie poprzedniej wartości
                            this.value = this.dataset.originalValue;
                            alert('Wystąpił błąd podczas aktualizacji statusu: ' + (data.message || 'Nieznany błąd'));
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        this.value = this.dataset.originalValue;
                        alert('Wystąpił błąd podczas aktualizacji statusu');
                    });
                });
                
                // Zapisywanie oryginalnej wartości przy załadowaniu
                select.dataset.originalValue = select.value;
            });
        });
    </script>
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

    <nav class="admin-nav">
        <ul>
            <li><a href="#panel-glowny" class="active">Panel główny</a></li>
            <li><a href="#nowa-wiadomosc">Utwórz nową wiadomość</a></li>
            <li><a href="#historia-opinii">Historia opinii</a></li>
            <li><a href="#historia-wiadomosci">Historia wiadomości</a></li>
            <li><a href="#wyniki-badan">Wyniki badań</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="admin-dashboard">
            <div class="dashboard-header">
                <h1>Panel Administratora</h1>
                <div class="admin-info">
                    <p>Witaj, <span class="admin-name"><?php echo $admin['imie'] . ' ' . $admin['nazwisko']; ?></span></p>
                </div>
            </div>

            <!-- Sekcja Panel Główny -->
            <div id="panel-glowny" class="dashboard-section">
                <h2>Panel Główny</h2>
                
                <!-- Sekcja Ostatnie Opinie -->
                <div class="recent-reviews-section">
                    <h3>Ostatnie Opinie</h3>
                    <div class="reviews-container">
                        <?php if (count($ostatnie_opinie) > 0): ?>
                            <?php foreach ($ostatnie_opinie as $opinia): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <h4><?php echo htmlspecialchars($opinia['imie'] . ' ' . $opinia['nazwisko']); ?></h4>
                                        <span class="review-date"><?php echo date('d.m.Y H:i', strtotime($opinia['data_utworzenia'])); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $opinia['ocena'] ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="review-content"><?php echo nl2br(htmlspecialchars($opinia['tresc'])); ?></p>
                                    <div class="review-status <?php echo strtolower($opinia['status']); ?>">
                                        <?php echo ucfirst($opinia['status']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-reviews">Brak opinii</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sekcja Statystyki Wiadomości -->
                <div class="news-stats-section">
                    <h3>Statystyki Wiadomości</h3>
                    <div class="stats-container">
                        <div class="stat-card">
                            <h4>Wszystkie wiadomości</h4>
                            <p class="stat-number"><?php echo $statystyki_wiadomosci['total_news']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h4>Opublikowane</h4>
                            <p class="stat-number"><?php echo $statystyki_wiadomosci['published_news']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h4>Szkice</h4>
                            <p class="stat-number"><?php echo $statystyki_wiadomosci['draft_news']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja Nowa Wiadomość -->
            <div id="nowa-wiadomosc" class="dashboard-section" style="display: none;">
                <h2>Utwórz Nową Wiadomość</h2>
                <div class="news-form-container">
                    <form id="newsForm" class="news-form">
                        <div class="form-group">
                            <label for="tytul">Tytuł:</label>
                            <input type="text" id="tytul" name="tytul" required>
                        </div>

                        <div class="form-group">
                            <label for="tresc">Treść:</label>
                            <textarea id="tresc" name="tresc" rows="10" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="zdjecie">Zdjęcie:</label>
                            <input type="file" id="zdjecie" name="zdjecie" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="szkic">Szkic</option>
                                <option value="opublikowany">Opublikuj</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Zapisz wiadomość</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sekcja Historia Opinii -->
            <div id="historia-opinii" class="dashboard-section" style="display: none;">
                <h2>Historia Opinii</h2>
                <div class="reviews-history-container">
                    <?php
                    try {
                        // Pobieranie wszystkich opinii
                        $stmt = $conn->prepare("
                            SELECT 
                                r.id,
                                r.ocena,
                                r.tresc,
                                r.data_utworzenia,
                                r.status,
                                u.imie,
                                u.nazwisko
                            FROM reviews r
                            JOIN users u ON r.uzytkownik_id = u.id
                            ORDER BY r.data_utworzenia DESC
                        ");
                        $stmt->execute();
                        $wszystkie_opinie = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($wszystkie_opinie) > 0):
                            foreach ($wszystkie_opinie as $opinia):
                    ?>
                        <div class="review-history-card" data-review-id="<?php echo htmlspecialchars($opinia['id']); ?>">
                            <div class="review-header">
                                <h4><?php echo htmlspecialchars($opinia['imie'] . ' ' . $opinia['nazwisko']); ?></h4>
                                <span class="review-date"><?php echo date('d.m.Y H:i', strtotime($opinia['data_utworzenia'])); ?></span>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $opinia['ocena'] ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <p class="review-content"><?php echo nl2br(htmlspecialchars($opinia['tresc'])); ?></p>
                            <div class="review-actions">
                                <select class="status-select" data-review-id="<?php echo htmlspecialchars($opinia['id']); ?>">
                                    <option value="oczekujaca" <?php echo $opinia['status'] === 'oczekujaca' ? 'selected' : ''; ?>>Oczekująca</option>
                                    <option value="zatwierdzona" <?php echo $opinia['status'] === 'zatwierdzona' ? 'selected' : ''; ?>>Zatwierdzona</option>
                                    <option value="odrzucona" <?php echo $opinia['status'] === 'odrzucona' ? 'selected' : ''; ?>>Odrzucona</option>
                                </select>
                            </div>
                        </div>
                    <?php 
                            endforeach;
                        else:
                    ?>
                        <p class="no-reviews">Brak opinii</p>
                    <?php 
                        endif;
                    } catch(PDOException $e) {
                        echo '<p class="error-message">Błąd podczas pobierania opinii: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    ?>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Obsługa zmiany statusu opinii o szpitalu
                    const reviewStatusSelects = document.querySelectorAll('.review-actions .status-select');
                    
                    reviewStatusSelects.forEach(select => {
                        // Zapisywanie oryginalnej wartości przy załadowaniu
                        select.dataset.originalValue = select.value;
                        
                        select.addEventListener('change', function() {
                            const reviewId = this.dataset.reviewId;
                            const newStatus = this.value;
                            
                            // Wysłanie żądania AJAX
                            fetch('update_review_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `review_id=${reviewId}&status=${newStatus}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Opcjonalnie: pokazanie komunikatu o sukcesie
                                    alert(data.message || 'Status został zaktualizowany');
                                } else {
                                    // W przypadku błędu, przywrócenie poprzedniej wartości
                                    this.value = this.dataset.originalValue;
                                    alert('Wystąpił błąd podczas aktualizacji statusu: ' + (data.message || 'Nieznany błąd'));
                                }
                            })
                            .catch(error => {
                                console.error('Błąd:', error);
                                this.value = this.dataset.originalValue;
                                alert('Wystąpił błąd podczas aktualizacji statusu');
                            });
                        });
                    });
                });
            </script>

            <!-- Sekcja Historia Wiadomości -->
            <div id="historia-wiadomosci" class="dashboard-section" style="display: none;">
                <h2>Historia Wiadomości</h2>
                <div class="news-history-container">
                    <?php
                    // Pobieranie wszystkich wiadomości
                    $stmt = $conn->prepare("
                        SELECT n.*, u.imie, u.nazwisko 
                        FROM news n 
                        JOIN users u ON n.autor_id = u.id 
                        ORDER BY n.data_publikacji DESC
                    ");
                    $stmt->execute();
                    $wszystkie_wiadomosci = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($wszystkie_wiadomosci) > 0):
                        foreach ($wszystkie_wiadomosci as $wiadomosc):
                    ?>
                        <div class="news-history-card" data-news-id="<?php echo $wiadomosc['id']; ?>">
                            <div class="news-header">
                                <h4><?php echo htmlspecialchars($wiadomosc['tytul']); ?></h4>
                                <span class="news-date"><?php echo date('d.m.Y H:i', strtotime($wiadomosc['data_publikacji'])); ?></span>
                            </div>
                            <div class="news-content">
                                <p><?php echo nl2br(htmlspecialchars(substr($wiadomosc['tresc'], 0, 200))); ?>...</p>
                            </div>
                            <div class="news-meta">
                                <span class="news-author">Autor: <?php echo htmlspecialchars($wiadomosc['imie'] . ' ' . $wiadomosc['nazwisko']); ?></span>
                                <span class="news-status <?php echo strtolower($wiadomosc['status']); ?>">
                                    <?php echo ucfirst($wiadomosc['status']); ?>
                                </span>
                            </div>
                            <div class="news-actions">
                                <button class="btn-delete" onclick="deleteNews(<?php echo $wiadomosc['id']; ?>)">Usuń</button>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <p class="no-news">Brak wiadomości</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sekcja Wyniki Badań -->
            <div id="wyniki-badan" class="dashboard-section" style="display: none;">
                <h2>Wyniki Badań</h2>
                <div class="results-container">
                    <?php
                    // Pobieranie wszystkich wyników badań
                    $stmt = $conn->prepare("
                        SELECT r.*, 
                               pu.imie as pacjent_imie, pu.nazwisko as pacjent_nazwisko,
                               lu.imie as lekarz_imie, lu.nazwisko as lekarz_nazwisko
                        FROM results r
                        JOIN patients p ON r.pacjent_id = p.id
                        JOIN users pu ON p.uzytkownik_id = pu.id
                        JOIN doctors d ON r.lekarz_id = d.id
                        JOIN users lu ON d.uzytkownik_id = lu.id
                        ORDER BY r.data_wystawienia DESC
                    ");
                    $stmt->execute();
                    $wyniki_badan = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($wyniki_badan) > 0):
                        foreach ($wyniki_badan as $wynik):
                    ?>
                        <div class="result-card" data-result-id="<?php echo $wynik['id']; ?>">
                            <div class="result-header">
                                <h4>Badanie: <?php echo htmlspecialchars($wynik['typ_badania']); ?></h4>
                                <span class="result-date"><?php echo date('d.m.Y H:i', strtotime($wynik['data_wystawienia'])); ?></span>
                            </div>
                            <div class="result-details">
                                <p><strong>Pacjent:</strong> <?php echo htmlspecialchars($wynik['pacjent_imie'] . ' ' . $wynik['pacjent_nazwisko']); ?></p>
                                <p><strong>Lekarz:</strong> <?php echo htmlspecialchars($wynik['lekarz_imie'] . ' ' . $wynik['lekarz_nazwisko']); ?></p>
                                <p><strong>PIN:</strong> <?php echo htmlspecialchars($wynik['pin']); ?></p>
                            </div>
                            <div class="result-status">
                                <select class="status-select" data-result-id="<?php echo $wynik['id']; ?>" onchange="console.log('Zmiana statusu:', this.value, this.dataset.resultId)">
                                    <option value="oczekujący" <?php echo $wynik['status'] === 'oczekujący' ? 'selected' : ''; ?>>Oczekujący</option>
                                    <option value="gotowy" <?php echo $wynik['status'] === 'gotowy' ? 'selected' : ''; ?>>Gotowy</option>
                                </select>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <p class="no-results">Brak wyników badań</p>
                    <?php endif; ?>
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