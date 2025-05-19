<?php
// Połączenie z bazą danych
$host = 'localhost';
$dbname = 'szpital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    exit();
}

// Sprawdzenie czy przekazano ID wiadomości
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: aktualnosci.php');
    exit();
}

$newsId = (int)$_GET['id'];

// Pobieranie wiadomości
$stmt = $pdo->prepare("SELECT n.*, u.imie, u.nazwisko 
                       FROM news n 
                       JOIN users u ON n.autor_id = u.id 
                       WHERE n.id = ? AND n.status = 'opublikowany'");
$stmt->execute([$newsId]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

// Jeśli wiadomość nie istnieje, przekieruj do listy aktualności
if (!$news) {
    header('Location: aktualnosci.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title><?php echo htmlspecialchars($news['tytul']); ?> - Szpital</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel="stylesheet" href="css/aktualnosci.css">
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
                <li><a href="personel.html">Nasz Personel</a></li>
                <li><a href="aktualnosci.php" class="active">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="logowanie.php" class="btn-login">Zaloguj się</a>
        </div>
    </header>

    <main class="main-content">
        <article class="news-detail">
            <div class="news-header">
                <h1><?php echo htmlspecialchars($news['tytul']); ?></h1>
                <div class="news-meta">
                    <span class="news-date"><?php echo date('d.m.Y', strtotime($news['data_publikacji'])); ?></span>
                    <span class="news-author">Autor: <?php echo htmlspecialchars($news['imie'] . ' ' . $news['nazwisko']); ?></span>
                </div>
            </div>

            <?php if ($news['zdjecie']): ?>
            <div class="news-image">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($news['zdjecie']); ?>" 
                     alt="<?php echo htmlspecialchars($news['tytul']); ?>">
            </div>
            <?php endif; ?>

            <div class="news-content">
                <?php echo nl2br(htmlspecialchars($news['tresc'])); ?>
            </div>

            <div class="news-footer">
                <a href="aktualnosci.php" class="btn-back">Powrót do aktualności</a>
            </div>
        </article>
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