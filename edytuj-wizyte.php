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

    // Pobieranie danych wizyty
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("
            SELECT 
                v.*,
                u.imie,
                u.nazwisko,
                p.id as pacjent_id
            FROM visits v
            JOIN patients p ON v.pacjent_id = p.id
            JOIN users u ON p.uzytkownik_id = u.id
            WHERE v.id = :visit_id
            AND v.lekarz_id = (
                SELECT id FROM doctors WHERE uzytkownik_id = :user_id
            )
        ");
        $stmt->bindParam(':visit_id', $_GET['id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $wizyta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wizyta) {
            header("Location: panel-lekarza.php");
            exit();
        }
    } else {
        header("Location: panel-lekarza.php");
        exit();
    }

    // Obsługa formularza
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $conn->prepare("
            UPDATE visits 
            SET 
                diagnoza = :diagnoza,
                zalecenia = :zalecenia,
                status = 'zakończona'
            WHERE id = :visit_id
        ");
        
        $stmt->bindParam(':diagnoza', $_POST['diagnoza']);
        $stmt->bindParam(':zalecenia', $_POST['zalecenia']);
        $stmt->bindParam(':visit_id', $_GET['id']);
        $stmt->execute();

        header("Location: panel-lekarza.php?success=1");
        exit();
    }

} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Edycja Wizyty</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/panel-lekarza.css'>
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

    <main class="main-content">
        <div class="visit-edit-container">
            <h2>Edycja Wizyty</h2>
            
            <div class="patient-info">
                <h3>Dane Pacjenta</h3>
                <p>Imię i nazwisko: <?php echo htmlspecialchars($wizyta['imie'] . ' ' . $wizyta['nazwisko']); ?></p>
                <p>Data wizyty: <?php echo date('d.m.Y H:i', strtotime($wizyta['data_wizyty'])); ?></p>
                <p>Typ wizyty: <?php echo ucfirst(htmlspecialchars($wizyta['typ_wizyty'])); ?></p>
                <p>Gabinet: <?php echo htmlspecialchars($wizyta['gabinet']); ?></p>
            </div>

            <form action="edytuj-wizyte.php?id=<?php echo $_GET['id']; ?>" method="POST" class="visit-form">
                <div class="form-group">
                    <label for="diagnoza">Diagnoza:</label>
                    <textarea name="diagnoza" id="diagnoza" rows="5" required><?php echo htmlspecialchars($wizyta['diagnoza'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="zalecenia">Zalecenia:</label>
                    <textarea name="zalecenia" id="zalecenia" rows="5" required><?php echo htmlspecialchars($wizyta['zalecenia'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Zakończ wizytę</button>
                    <a href="panel-lekarza.php" class="btn-cancel">Anuluj</a>
                </div>
            </form>
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