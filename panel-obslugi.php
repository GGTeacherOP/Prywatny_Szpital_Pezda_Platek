<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest obsługą
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'obsluga') {
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

    // Pobieranie danych pracownika obsługi
    $stmt = $conn->prepare("
        SELECT u.imie, u.nazwisko, s.stanowisko, s.sekcja 
        FROM users u 
        JOIN staff s ON u.id = s.uzytkownik_id 
        WHERE u.id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $pracownik = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pracownik) {
        // Jeśli nie znaleziono pracownika, dodaj domyślne dane
        $stmt = $conn->prepare("SELECT imie, nazwisko FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $pracownik = [
            'imie' => $user_data['imie'] ?? 'Nieznane',
            'nazwisko' => $user_data['nazwisko'] ?? 'Nieznane',
            'stanowisko' => 'Pracownik obsługi',
            'sekcja' => 'Nie przypisano'
        ];
    }

    // Pobieranie zadań pracownika
    $stmt = $conn->prepare("
        SELECT t.*, r.numer, r.typ, d.nazwa as oddzial
        FROM tasks t
        JOIN staff s ON t.pracownik_id = s.id
        JOIN users u ON s.uzytkownik_id = u.id
        LEFT JOIN rooms r ON t.numer_pomieszczenia = r.numer
        LEFT JOIN departments d ON r.oddzial_id = d.id
        WHERE t.status IN ('do_wykonania', 'w_trakcie')
        ORDER BY t.data_zadania ASC, t.godzina_rozpoczecia ASC
    ");
    $stmt->execute();
    $zadania = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobieranie historii zadań (zakończone i anulowane)
    $stmt = $conn->prepare("
        SELECT t.*, r.numer, r.typ, d.nazwa as oddzial
        FROM tasks t
        JOIN staff s ON t.pracownik_id = s.id
        JOIN users u ON s.uzytkownik_id = u.id
        LEFT JOIN rooms r ON t.numer_pomieszczenia = r.numer
        LEFT JOIN departments d ON r.oddzial_id = d.id
        WHERE t.status IN ('wykonane', 'anulowane')
        ORDER BY t.data_zadania DESC, t.godzina_rozpoczecia DESC
    ");
    $stmt->execute();
    $historia_zadan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobieranie aktualnych zadań (do wykonania i w trakcie)
    $stmt = $conn->prepare("
        SELECT t.*, r.numer, r.typ, d.nazwa as oddzial
        FROM tasks t
        JOIN staff s ON t.pracownik_id = s.id
        JOIN users u ON s.uzytkownik_id = u.id
        LEFT JOIN rooms r ON t.numer_pomieszczenia = r.numer
        LEFT JOIN departments d ON r.oddzial_id = d.id
        WHERE t.status IN ('do_wykonania', 'w_trakcie')
        ORDER BY t.data_zadania ASC, t.godzina_rozpoczecia ASC
    ");
    $stmt->execute();
    $aktualne_zadania = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobieranie statystyk zadań
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'wykonane' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'do_wykonania' THEN 1 ELSE 0 END) as pending_tasks
        FROM tasks
        WHERE pracownik_id = (
            SELECT id FROM staff WHERE uzytkownik_id = :user_id
        )
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $statystyki_zadan = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Panel Obsługi</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/panel-obslugi.css'>
    <script src='main.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Obsługa zakładek
            const navLinks = document.querySelectorAll('.staff-nav a');
            const sections = document.querySelectorAll('.dashboard-section');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Usuń klasę active ze wszystkich linków i sekcji
                    navLinks.forEach(l => l.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));
                    
                    // Dodaj klasę active do klikniętego linku
                    this.classList.add('active');
                    
                    // Pokaż odpowiednią sekcję
                    const sectionId = this.getAttribute('data-section');
                    document.getElementById(sectionId).classList.add('active');
                });
            });

            const statusSelects = document.querySelectorAll('.status-select[data-task-id]');
            
            statusSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const taskId = this.dataset.taskId;
                    const newStatus = this.value;
                    
                    fetch('update_task_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `task_id=${taskId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const taskCard = this.closest('.task-card');
                            taskCard.querySelector('.task-status').textContent = newStatus;
                            alert('Status został zaktualizowany');
                        } else {
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

    <nav class="staff-nav">
        <ul>
            <li><a href="#panel-glowny" class="active" data-section="panel-glowny">Panel główny</a></li>
            <li><a href="#moje-zadania" data-section="moje-zadania">Moje zadania</a></li>
            <li><a href="#historia-zadan" data-section="historia-zadan">Historia zadań</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="staff-dashboard">
            <div class="dashboard-header">
                <h1>Panel Obsługi</h1>
                <div class="staff-info">
                    <p>Witaj, <span class="staff-name"><?php echo $pracownik['imie'] . ' ' . $pracownik['nazwisko']; ?></span></p>
                    <p>Stanowisko: <?php echo ucfirst($pracownik['stanowisko']); ?></p>
                    <p>Sekcja: <?php echo $pracownik['sekcja']; ?></p>
                </div>
            </div>

            <!-- Sekcja Panel Główny -->
            <div id="panel-glowny" class="dashboard-section active">
                <h2>Panel Główny</h2>
                
                <!-- Sekcja Statystyki Zadań -->
                <div class="tasks-stats-section">
                    <h3>Statystyki Zadań</h3>
                    <div class="stats-container">
                        <div class="stat-card">
                            <h4>Wszystkie zadania</h4>
                            <p class="stat-number"><?php echo $statystyki_zadan['total_tasks']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h4>Wykonane</h4>
                            <p class="stat-number"><?php echo $statystyki_zadan['completed_tasks']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h4>Do wykonania</h4>
                            <p class="stat-number"><?php echo $statystyki_zadan['pending_tasks']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sekcja Dzisiejsze Zadania -->
                <div class="upcoming-tasks-section">
                    <h3>Dzisiejsze Zadania</h3>
                    <div class="tasks-container">
                        <?php if (count($zadania) > 0): ?>
                            <?php foreach ($zadania as $zadanie): ?>
                                <div class="task-card">
                                    <div class="task-header">
                                        <h4>Pomieszczenie <?php echo htmlspecialchars($zadanie['numer']); ?></h4>
                                        <span class="task-time"><?php echo date('H:i', strtotime($zadanie['godzina_rozpoczecia'])) . ' - ' . date('H:i', strtotime($zadanie['godzina_zakonczenia'])); ?></span>
                                    </div>
                                    <div class="task-details">
                                        <p><strong>Typ zadania:</strong> <?php echo ucfirst($zadanie['typ_zadania']); ?></p>
                                        <p><strong>Oddział:</strong> <?php echo htmlspecialchars($zadanie['oddzial']); ?></p>
                                        <p><strong>Opis:</strong> <?php echo nl2br(htmlspecialchars($zadanie['opis_zadania'])); ?></p>
                                    </div>
                                    <div class="task-status <?php echo strtolower($zadanie['status']); ?>">
                                        <select class="status-select" data-task-id="<?php echo $zadanie['id']; ?>">
                                            <option value="do_wykonania" <?php echo $zadanie['status'] == 'do_wykonania' ? 'selected' : ''; ?>>Do wykonania</option>
                                            <option value="w_trakcie" <?php echo $zadanie['status'] == 'w_trakcie' ? 'selected' : ''; ?>>W trakcie</option>
                                            <option value="wykonane" <?php echo $zadanie['status'] == 'wykonane' ? 'selected' : ''; ?>>Wykonane</option>
                                            <option value="anulowane" <?php echo $zadanie['status'] == 'anulowane' ? 'selected' : ''; ?>>Anulowane</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-tasks">Brak zadań na dzisiaj</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sekcja Moje Zadania -->
            <div id="moje-zadania" class="dashboard-section">
                <h2>Moje Zadania</h2>
                <div class="tasks-container">
                    <?php if (count($aktualne_zadania) > 0): ?>
                        <?php foreach ($aktualne_zadania as $zadanie): ?>
                            <div class="task-card">
                                <div class="task-header">
                                    <h4>Pomieszczenie <?php echo htmlspecialchars($zadanie['numer']); ?></h4>
                                    <span class="task-date"><?php echo date('d.m.Y', strtotime($zadanie['data_zadania'])); ?></span>
                                </div>
                                <div class="task-details">
                                    <p><strong>Typ zadania:</strong> <?php echo ucfirst($zadanie['typ_zadania']); ?></p>
                                    <p><strong>Oddział:</strong> <?php echo htmlspecialchars($zadanie['oddzial']); ?></p>
                                    <p><strong>Godziny:</strong> <?php echo date('H:i', strtotime($zadanie['godzina_rozpoczecia'])) . ' - ' . date('H:i', strtotime($zadanie['godzina_zakonczenia'])); ?></p>
                                    <p><strong>Opis:</strong> <?php echo nl2br(htmlspecialchars($zadanie['opis_zadania'])); ?></p>
                                </div>
                                <div class="task-status <?php echo strtolower($zadanie['status']); ?>">
                                    <select class="status-select" data-task-id="<?php echo $zadanie['id']; ?>">
                                        <option value="do_wykonania" <?php echo $zadanie['status'] == 'do_wykonania' ? 'selected' : ''; ?>>Do wykonania</option>
                                        <option value="w_trakcie" <?php echo $zadanie['status'] == 'w_trakcie' ? 'selected' : ''; ?>>W trakcie</option>
                                        <option value="wykonane" <?php echo $zadanie['status'] == 'wykonane' ? 'selected' : ''; ?>>Wykonane</option>
                                        <option value="anulowane" <?php echo $zadanie['status'] == 'anulowane' ? 'selected' : ''; ?>>Anulowane</option>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-tasks">Brak aktualnych zadań do wykonania</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sekcja Historia Zadań -->
            <div id="historia-zadan" class="dashboard-section">
                <h2>Historia Zadań</h2>
                <div class="tasks-container">
                    <?php if (count($historia_zadan) > 0): ?>
                        <?php foreach ($historia_zadan as $zadanie): ?>
                            <div class="task-card">
                                <div class="task-header">
                                    <h4>Pomieszczenie <?php echo htmlspecialchars($zadanie['numer']); ?></h4>
                                    <span class="task-date"><?php echo date('d.m.Y', strtotime($zadanie['data_zadania'])); ?></span>
                                </div>
                                <div class="task-details">
                                    <p><strong>Typ zadania:</strong> <?php echo ucfirst($zadanie['typ_zadania']); ?></p>
                                    <p><strong>Oddział:</strong> <?php echo htmlspecialchars($zadanie['oddzial']); ?></p>
                                    <p><strong>Godziny:</strong> <?php echo date('H:i', strtotime($zadanie['godzina_rozpoczecia'])) . ' - ' . date('H:i', strtotime($zadanie['godzina_zakonczenia'])); ?></p>
                                    <p><strong>Opis:</strong> <?php echo nl2br(htmlspecialchars($zadanie['opis_zadania'])); ?></p>
                                    <p><strong>Status:</strong> <span class="status-badge <?php echo strtolower($zadanie['status']); ?>"><?php echo ucfirst($zadanie['status']); ?></span></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-tasks">Brak historii zadań</p>
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