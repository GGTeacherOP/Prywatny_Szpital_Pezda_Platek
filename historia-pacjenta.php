<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    header("Location: logowanie.php");
    exit();
}

// Sprawdzenie czy podano ID pacjenta
if (!isset($_GET['id'])) {
    header("Location: panel-lekarza.php");
    exit();
}

$pacjent_id = $_GET['id'];

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
            u.data_urodzenia,
            p.grupa_krwi
        FROM patients p
        JOIN users u ON p.uzytkownik_id = u.id
        WHERE p.id = :pacjent_id
    ");
    $stmt->bindParam(':pacjent_id', $pacjent_id);
    $stmt->execute();
    $pacjent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pacjent) {
        header("Location: panel-lekarza.php");
        exit();
    }

    // Pobieranie historii wizyt
    $stmt = $conn->prepare("
        SELECT 
            v.*,
            u.imie as lekarz_imie,
            u.nazwisko as lekarz_nazwisko,
            d.specjalizacja
        FROM visits v
        JOIN doctors d ON v.lekarz_id = d.id
        JOIN users u ON d.uzytkownik_id = u.id
        WHERE v.pacjent_id = :pacjent_id
        ORDER BY v.data_wizyty DESC
    ");
    $stmt->bindParam(':pacjent_id', $pacjent_id);
    $stmt->execute();
    $historia = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Historia Wizyt - <?php echo htmlspecialchars($pacjent['imie'] . ' ' . $pacjent['nazwisko']); ?></title>
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
                <li><a href="aktualnosci.php">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="wyloguj.php" class="btn-login">Wyloguj się</a>
        </div>
    </header>

    <main class="main-content">
        <div class="patient-history-container">
            <div class="patient-info">
                <h1>Historia Wizyt</h1>
                <h2><?php echo htmlspecialchars($pacjent['imie'] . ' ' . $pacjent['nazwisko']); ?></h2>
                <p>Data urodzenia: <?php echo date('d.m.Y', strtotime($pacjent['data_urodzenia'])); ?></p>
                <p>Grupa krwi: <?php echo htmlspecialchars($pacjent['grupa_krwi']); ?></p>
            </div>

            <div class="history-table-container">
                <?php if (count($historia) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Lekarz</th>
                                <th>Specjalizacja</th>
                                <th>Typ wizyty</th>
                                <th>Status</th>
                                <th>Gabinet</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historia as $wizyta): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($wizyta['data_wizyty'])); ?></td>
                                    <td>Dr <?php echo htmlspecialchars($wizyta['lekarz_imie'] . ' ' . $wizyta['lekarz_nazwisko']); ?></td>
                                    <td><?php echo htmlspecialchars($wizyta['specjalizacja']); ?></td>
                                    <td><?php echo htmlspecialchars($wizyta['typ_wizyty']); ?></td>
                                    <td>
                                        <span class="visit-status <?php echo strtolower($wizyta['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($wizyta['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($wizyta['gabinet']); ?></td>
                                    <td>
                                        <?php if ($wizyta['status'] === 'zaplanowana'): ?>
                                            <a href="edytuj-wizyte.php?id=<?php echo $wizyta['id']; ?>" class="btn-start">Rozpocznij wizytę</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-history">Brak historii wizyt</p>
                <?php endif; ?>
            </div>

            <div class="back-button">
                <a href="panel-lekarza.php" class="btn-back">Powrót do panelu</a>
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