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
    <style>
        .tasks-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tasks-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        button[type="submit"] {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        button[type="submit"]:hover {
            background: #0056b3;
        }

        .tasks-list {
            margin-top: 30px;
        }

        .task-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .task-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .task-card p {
            margin: 5px 0;
            color: #666;
        }

        .task-card p strong {
            color: #333;
        }

        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .day-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .day-toggle input[type="checkbox"] {
            margin: 0;
        }

        .day-toggle label {
            font-size: 0.9em;
            color: #666;
        }

        .time-inputs input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .search-container {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .salaries-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .salary-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .employee-info {
            margin-bottom: 15px;
        }

        .employee-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .employee-type {
            color: #666;
            margin: 0;
        }

        .salary-form {
            margin-top: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-update {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .btn-update:hover {
            background: #0056b3;
        }

        .prices-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .price-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .doctor-info {
            margin-bottom: 15px;
        }

        .doctor-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .doctor-specialization {
            color: #666;
            margin: 0;
        }

        .prices-form {
            margin-top: 15px;
        }

        .prices-form .form-group {
            margin-bottom: 15px;
        }

        .prices-form label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .prices-form input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .hours-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .hours-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .time-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .time-inputs input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .time-inputs span {
            color: #666;
        }

        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .day-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .day-toggle input[type="checkbox"] {
            margin: 0;
        }

        .day-toggle label {
            font-size: 0.9em;
            color: #666;
        }

        .time-inputs input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
    </style>
    <script src='main.js'></script>
    <script src='js/panel-admina.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Obsługa nawigacji
            const navLinks = document.querySelectorAll('.nav-list a');
            const sections = document.querySelectorAll('section');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    
                    sections.forEach(section => {
                        section.style.display = section.id === targetId ? 'block' : 'none';
                    });
                });
            });

            // Obsługa formularza zadań
            const tasksForm = document.getElementById('tasksForm');
            if (tasksForm) {
                tasksForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    formData.append('action', 'add_task');

                    fetch('php/tasks.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Zadanie zostało dodane pomyślnie!');
                            tasksForm.reset();
                            location.reload(); // Odświeżenie strony po dodaniu zadania
                        } else {
                            alert('Wystąpił błąd podczas dodawania zadania: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        alert('Wystąpił błąd podczas dodawania zadania.');
                    });
                });
            }

            // Funkcja do aktualizacji statusu zadania
            window.updateTaskStatus = function(taskId, newStatus) {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('task_id', taskId);
                formData.append('status', newStatus);

                fetch('php/tasks.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Odświeżenie strony po aktualizacji statusu
                    } else {
                        alert('Wystąpił błąd podczas aktualizacji statusu: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Błąd:', error);
                    alert('Wystąpił błąd podczas aktualizacji statusu.');
                });
            };

            // Obsługa przełączników dni
            const dayToggles = document.querySelectorAll('.day-toggle input[type="checkbox"]');
            dayToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const timeInputs = document.getElementById('time-inputs-' + this.id.replace('toggle-', ''));
                    const inputs = timeInputs.querySelectorAll('input[type="time"]');
                    
                    inputs.forEach(input => {
                        input.disabled = !this.checked;
                        if (!this.checked) {
                            input.value = '';
                        }
                    });
                });
            });

            // Obsługa formularzy aktualizacji godzin
            const hoursForms = document.querySelectorAll('.update-hours-form');
            hoursForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const doctorId = this.dataset.doctorId;
                    const formData = new FormData(this);
                    formData.append('doctor_id', doctorId);

                    // Dodanie informacji o włączonych/wyłączonych dniach
                    const days = ['poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek', 'sobota', 'niedziela'];
                    days.forEach(day => {
                        const toggle = this.querySelector(`input[name="hours[${day}][enabled]"]`);
                        if (!toggle.checked) {
                            formData.delete(`hours[${day}][start]`);
                            formData.delete(`hours[${day}][end]`);
                        }
                    });

                    fetch('php/update_doctor_hours.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Godziny przyjęć zostały zaktualizowane pomyślnie!');
                        } else {
                            alert('Wystąpił błąd podczas aktualizacji godzin: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        alert('Wystąpił błąd podczas aktualizacji godzin.');
                    });
                });
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
            <li><a href="#zadania-obsługi">Zadania obsługi</a></li>
            <li><a href="#historia-wiadomosci">Historia wiadomości</a></li>
            <li><a href="#wyniki-badan">Wyniki badań</a></li>
            <li><a href="#zarzadzanie-pensjami">Zarządzanie pensjami</a></li>
            <li><a href="#zarzadzanie-cenami">Zarządzanie cenami wizyt</a></li>
            <li><a href="#zarzadzanie-godzinami">Zarządzanie godzinami przyjęć</a></li>
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

            <!-- Sekcja Zadania Obsługi -->
            <div id="zadania-obsługi" class="dashboard-section" style="display: none;">
                <h2>Zarządzanie Zadaniami Obsługi</h2>
                <div class="tasks-section">
                    <form action="php/dodaj_zadanie.php" method="POST" class="tasks-form">
                        <div class="form-group">
                            <label for="pracownik">Pracownik:</label>
                            <select name="pracownik" required>
                                <?php
                                $stmt = $conn->query("
                                    SELECT s.id, u.imie, u.nazwisko 
                                    FROM staff s 
                                    JOIN users u ON s.uzytkownik_id = u.id 
                                    WHERE u.funkcja = 'obsluga'
                                ");
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . $row['id'] . "'>" . $row['imie'] . " " . $row['nazwisko'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pomieszczenie">Pomieszczenie:</label>
                            <input type="text" name="pomieszczenie" required>
                        </div>

                        <div class="form-group">
                            <label for="typ">Typ zadania:</label>
                            <select name="typ" required>
                                <option value="sprzątanie">Sprzątanie</option>
                                <option value="konserwacja">Konserwacja</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="opis">Opis:</label>
                            <textarea name="opis" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="data">Data:</label>
                            <input type="date" name="data" required>
                        </div>

                        <div class="form-group">
                            <label for="godzina_start">Godzina rozpoczęcia:</label>
                            <input type="time" name="godzina_start" required>
                        </div>

                        <div class="form-group">
                            <label for="godzina_koniec">Godzina zakończenia:</label>
                            <input type="time" name="godzina_koniec" required>
                        </div>

                        <button type="submit">Dodaj zadanie</button>
                    </form>

                    <div class="tasks-list">
                        <h3>Aktualne zadania</h3>
                        <?php
                        $stmt = $conn->query("
                            SELECT t.*, u.imie, u.nazwisko 
                            FROM tasks t 
                            JOIN staff s ON t.pracownik_id = s.id 
                            JOIN users u ON s.uzytkownik_id = u.id 
                            WHERE t.status != 'wykonane' 
                            ORDER BY t.data_zadania ASC, t.godzina_rozpoczecia ASC
                        ");
                        while($zadanie = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<div class='task-card'>";
                            echo "<h4>" . htmlspecialchars($zadanie['typ_zadania']) . "</h4>";
                            echo "<p>Pracownik: " . htmlspecialchars($zadanie['imie'] . " " . $zadanie['nazwisko']) . "</p>";
                            echo "<p>Pomieszczenie: " . htmlspecialchars($zadanie['numer_pomieszczenia']) . "</p>";
                            echo "<p>Data: " . htmlspecialchars($zadanie['data_zadania']) . "</p>";
                            echo "<p>Godziny: " . htmlspecialchars($zadanie['godzina_rozpoczecia'] . " - " . $zadanie['godzina_zakonczenia']) . "</p>";
                            echo "<p>Status: " . htmlspecialchars($zadanie['status']) . "</p>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Sekcja Zarządzanie Pensjami -->
            <div id="zarzadzanie-pensjami" class="dashboard-section" style="display: none;">
                <h2>Zarządzanie Pensjami</h2>
                
                <!-- Wyszukiwarka -->
                <div class="search-container">
                    <input type="text" id="searchEmployee" placeholder="Wyszukaj pracownika..." class="search-input">
                    <select id="employeeType" class="search-select">
                        <option value="all">Wszyscy pracownicy</option>
                        <option value="lekarz">Lekarze</option>
                        <option value="obsluga">Obsługa</option>
                    </select>
                </div>

                <div class="salaries-container">
                    <?php
                    // Pobieranie wszystkich pracowników z ich pensjami
                    $stmt = $conn->prepare("
                        SELECT 
                            u.id as user_id,
                            u.imie,
                            u.nazwisko,
                            u.funkcja,
                            COALESCE(sal.pensja, 0) as pensja
                        FROM users u
                        LEFT JOIN salaries sal ON u.id = sal.uzytkownik_id
                        WHERE u.funkcja IN ('lekarz', 'obsluga')
                        ORDER BY u.funkcja, u.nazwisko
                    ");
                    $stmt->execute();
                    $pracownicy = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($pracownicy as $pracownik):
                    ?>
                        <div class="salary-card" data-staff-id="<?php echo $pracownik['user_id']; ?>" data-type="<?php echo $pracownik['funkcja']; ?>">
                            <div class="employee-info">
                                <h4><?php echo htmlspecialchars($pracownik['imie'] . ' ' . $pracownik['nazwisko']); ?></h4>
                                <p class="employee-type"><?php echo ucfirst($pracownik['funkcja']); ?></p>
                            </div>
                            <div class="salary-form">
                                <form class="update-salary-form" data-staff-id="<?php echo $pracownik['user_id']; ?>">
                                    <div class="form-group">
                                        <label for="salary-<?php echo $pracownik['user_id']; ?>">Pensja (PLN):</label>
                                        <input type="number" 
                                               id="salary-<?php echo $pracownik['user_id']; ?>" 
                                               name="salary" 
                                               value="<?php echo $pracownik['pensja']; ?>" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                    </div>
                                    <button type="submit" class="btn-update">Aktualizuj pensję</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sekcja Zarządzanie Cenami Wizyt -->
            <div id="zarzadzanie-cenami" class="dashboard-section" style="display: none;">
                <h2>Zarządzanie Cenami Wizyt</h2>
                
                <!-- Wyszukiwarka -->
                <div class="search-container">
                    <input type="text" id="searchDoctor" placeholder="Wyszukaj lekarza..." class="search-input">
                </div>

                <div class="prices-container">
                    <?php
                    // Pobieranie wszystkich lekarzy z ich cenami wizyt
                    $stmt = $conn->prepare("
                        SELECT 
                            d.id as doctor_id,
                            u.imie,
                            u.nazwisko,
                            d.specjalizacja,
                            dvp.typ_wizyty,
                            dvp.cena
                        FROM doctors d
                        JOIN users u ON d.uzytkownik_id = u.id
                        LEFT JOIN doctor_visit_prices dvp ON d.id = dvp.lekarz_id
                        ORDER BY u.nazwisko, u.imie
                    ");
                    $stmt->execute();
                    $lekarze = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Grupowanie cen według lekarza
                    $lekarze_ceny = [];
                    foreach ($lekarze as $lekarz) {
                        if (!isset($lekarze_ceny[$lekarz['doctor_id']])) {
                            $lekarze_ceny[$lekarz['doctor_id']] = [
                                'imie' => $lekarz['imie'],
                                'nazwisko' => $lekarz['nazwisko'],
                                'specjalizacja' => $lekarz['specjalizacja'],
                                'ceny' => []
                            ];
                        }
                        if ($lekarz['typ_wizyty']) {
                            $lekarze_ceny[$lekarz['doctor_id']]['ceny'][$lekarz['typ_wizyty']] = $lekarz['cena'];
                        }
                    }

                    foreach ($lekarze_ceny as $doctor_id => $lekarz):
                    ?>
                        <div class="price-card" data-doctor-id="<?php echo $doctor_id; ?>">
                            <div class="doctor-info">
                                <h4><?php echo htmlspecialchars($lekarz['imie'] . ' ' . $lekarz['nazwisko']); ?></h4>
                                <p class="doctor-specialization"><?php echo htmlspecialchars($lekarz['specjalizacja']); ?></p>
                            </div>
                            <div class="prices-form">
                                <form class="update-prices-form" data-doctor-id="<?php echo $doctor_id; ?>">
                                    <?php
                                    $typy_wizyt = ['pierwsza', 'kontrolna', 'pogotowie', 'szczepienie', 'badanie'];
                                    foreach ($typy_wizyt as $typ):
                                        $cena = isset($lekarz['ceny'][$typ]) ? $lekarz['ceny'][$typ] : '';
                                    ?>
                                        <div class="form-group">
                                            <label for="price-<?php echo $doctor_id; ?>-<?php echo $typ; ?>">
                                                <?php echo ucfirst($typ); ?> wizyta (PLN):
                                            </label>
                                            <input type="number" 
                                                   id="price-<?php echo $doctor_id; ?>-<?php echo $typ; ?>" 
                                                   name="prices[<?php echo $typ; ?>]" 
                                                   value="<?php echo $cena; ?>" 
                                                   step="0.01" 
                                                   min="0" 
                                                   required>
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn-update">Aktualizuj ceny</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sekcja Zarządzanie Godzinami Przyjęć -->
            <div id="zarzadzanie-godzinami" class="dashboard-section" style="display: none;">
                <h2>Zarządzanie Godzinami Przyjęć</h2>
                
                <!-- Wyszukiwarka -->
                <div class="search-container">
                    <input type="text" id="searchDoctorHours" placeholder="Wyszukaj lekarza..." class="search-input">
                </div>

                <div class="hours-container">
                    <?php
                    // Pobieranie wszystkich lekarzy z ich godzinami przyjęć
                    $stmt = $conn->prepare("
                        SELECT 
                            d.id as doctor_id,
                            u.imie,
                            u.nazwisko,
                            d.specjalizacja,
                            dh.dzien_tygodnia,
                            dh.godzina_rozpoczecia,
                            dh.godzina_zakonczenia
                        FROM doctors d
                        JOIN users u ON d.uzytkownik_id = u.id
                        LEFT JOIN doctor_hours dh ON d.id = dh.lekarz_id
                        ORDER BY u.nazwisko, u.imie, FIELD(dh.dzien_tygodnia, 'poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek', 'sobota', 'niedziela')
                    ");
                    $stmt->execute();
                    $lekarze_godziny = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Grupowanie godzin według lekarza
                    $lekarze_godziny_grupowane = [];
                    foreach ($lekarze_godziny as $godzina) {
                        if (!isset($lekarze_godziny_grupowane[$godzina['doctor_id']])) {
                            $lekarze_godziny_grupowane[$godzina['doctor_id']] = [
                                'imie' => $godzina['imie'],
                                'nazwisko' => $godzina['nazwisko'],
                                'specjalizacja' => $godzina['specjalizacja'],
                                'godziny' => []
                            ];
                        }
                        if ($godzina['dzien_tygodnia']) {
                            $lekarze_godziny_grupowane[$godzina['doctor_id']]['godziny'][$godzina['dzien_tygodnia']] = [
                                'rozpoczecie' => $godzina['godzina_rozpoczecia'],
                                'zakonczenie' => $godzina['godzina_zakonczenia']
                            ];
                        }
                    }

                    foreach ($lekarze_godziny_grupowane as $doctor_id => $lekarz):
                    ?>
                        <div class="hours-card" data-doctor-id="<?php echo $doctor_id; ?>">
                            <div class="doctor-info">
                                <h4><?php echo htmlspecialchars($lekarz['imie'] . ' ' . $lekarz['nazwisko']); ?></h4>
                                <p class="doctor-specialization"><?php echo htmlspecialchars($lekarz['specjalizacja']); ?></p>
                            </div>
                            <div class="hours-form">
                                <form class="update-hours-form" data-doctor-id="<?php echo $doctor_id; ?>">
                                    <?php
                                    $dni_tygodnia = ['poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek', 'sobota', 'niedziela'];
                                    foreach ($dni_tygodnia as $dzien):
                                        $godziny = isset($lekarz['godziny'][$dzien]) ? $lekarz['godziny'][$dzien] : ['rozpoczecie' => '', 'zakonczenie' => ''];
                                    ?>
                                        <div class="form-group">
                                            <div class="day-header">
                                                <label for="hours-<?php echo $doctor_id; ?>-<?php echo $dzien; ?>">
                                                    <?php echo ucfirst($dzien); ?>:
                                                </label>
                                                <div class="day-toggle">
                                                    <input type="checkbox" 
                                                           id="toggle-<?php echo $doctor_id; ?>-<?php echo $dzien; ?>" 
                                                           name="hours[<?php echo $dzien; ?>][enabled]" 
                                                           <?php echo (!empty($godziny['rozpoczecie']) && !empty($godziny['zakonczenie'])) ? 'checked' : ''; ?>>
                                                    <label for="toggle-<?php echo $doctor_id; ?>-<?php echo $dzien; ?>">Przyjmuje</label>
                                                </div>
                                            </div>
                                            <div class="time-inputs" id="time-inputs-<?php echo $doctor_id; ?>-<?php echo $dzien; ?>">
                                                <input type="time" 
                                                       id="hours-<?php echo $doctor_id; ?>-<?php echo $dzien; ?>-start" 
                                                       name="hours[<?php echo $dzien; ?>][start]" 
                                                       value="<?php echo $godziny['rozpoczecie']; ?>" 
                                                       <?php echo (!empty($godziny['rozpoczecie']) && !empty($godziny['zakonczenie'])) ? '' : 'disabled'; ?>>
                                                <span>do</span>
                                                <input type="time" 
                                                       id="hours-<?php echo $doctor_id; ?>-<?php echo $dzien; ?>-end" 
                                                       name="hours[<?php echo $dzien; ?>][end]" 
                                                       value="<?php echo $godziny['zakonczenie']; ?>" 
                                                       <?php echo (!empty($godziny['rozpoczecie']) && !empty($godziny['zakonczenie'])) ? '' : 'disabled'; ?>>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn-update">Aktualizuj godziny</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
            // Obsługa wyszukiwarki
            const searchInput = document.getElementById('searchEmployee');
            const employeeType = document.getElementById('employeeType');
            const salaryCards = document.querySelectorAll('.salary-card');

            function filterEmployees() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedType = employeeType.value;

                salaryCards.forEach(card => {
                    const employeeName = card.querySelector('h4').textContent.toLowerCase();
                    const employeeType = card.dataset.type;
                    const matchesSearch = employeeName.includes(searchTerm);
                    const matchesType = selectedType === 'all' || employeeType === selectedType;

                    card.style.display = matchesSearch && matchesType ? 'block' : 'none';
                });
            }

            searchInput.addEventListener('input', filterEmployees);
            employeeType.addEventListener('change', filterEmployees);

            // Obsługa formularzy aktualizacji pensji
            const salaryForms = document.querySelectorAll('.update-salary-form');
            salaryForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const staffId = this.dataset.staffId;
                    const salary = this.querySelector('input[name="salary"]').value;

                    fetch('php/update_salary.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `staff_id=${staffId}&salary=${salary}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Pensja została zaktualizowana pomyślnie!');
                        } else {
                            alert('Wystąpił błąd podczas aktualizacji pensji: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        alert('Wystąpił błąd podczas aktualizacji pensji.');
                    });
                });
            });

            // Obsługa wyszukiwarki
            const searchDoctor = document.getElementById('searchDoctor');
            const priceCards = document.querySelectorAll('.price-card');

            function filterDoctors() {
                const searchTerm = searchDoctor.value.toLowerCase();

                priceCards.forEach(card => {
                    const doctorName = card.querySelector('h4').textContent.toLowerCase();
                    const matchesSearch = doctorName.includes(searchTerm);

                    card.style.display = matchesSearch ? 'block' : 'none';
                });
            }

            searchDoctor.addEventListener('input', filterDoctors);

            // Obsługa formularzy aktualizacji cen
            const priceForms = document.querySelectorAll('.update-prices-form');
            priceForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const doctorId = this.dataset.doctorId;
                    const formData = new FormData(this);
                    formData.append('doctor_id', doctorId);

                    fetch('php/update_visit_prices.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Ceny zostały zaktualizowane pomyślnie!');
                        } else {
                            alert('Wystąpił błąd podczas aktualizacji cen: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        alert('Wystąpił błąd podczas aktualizacji cen.');
                    });
                });
            });

            // Obsługa wyszukiwarki godzin przyjęć
            const searchDoctorHours = document.getElementById('searchDoctorHours');
            const hoursCards = document.querySelectorAll('.hours-card');

            function filterDoctorHours() {
                const searchTerm = searchDoctorHours.value.toLowerCase();

                hoursCards.forEach(card => {
                    const doctorName = card.querySelector('h4').textContent.toLowerCase();
                    const matchesSearch = doctorName.includes(searchTerm);

                    card.style.display = matchesSearch ? 'block' : 'none';
                });
            }

            searchDoctorHours.addEventListener('input', filterDoctorHours);
        });
    </script>
</body>
</html> 