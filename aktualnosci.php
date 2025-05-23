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

// Pobierz aktualną stronę
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Upewnij się, że strona nie jest mniejsza niż 1

// Liczba wiadomości na stronę
$newsPerPage = 7; // 1 główna + 2 wyróżnione + 4 pozostałe

// Oblicz offset dla zapytania SQL
$offset = ($currentPage - 1) * $newsPerPage;

// Pobieranie głównej wiadomości dla aktualnej strony
$stmt = $pdo->prepare("SELECT n.*, u.imie, u.nazwisko 
                     FROM news n 
                     JOIN users u ON n.autor_id = u.id 
                     WHERE n.status = 'opublikowany' 
                     ORDER BY n.data_publikacji DESC 
                     LIMIT ?, 1");
$stmt->bindValue(1, $offset, PDO::PARAM_INT);
$stmt->execute();
$mainNews = $stmt->fetch(PDO::FETCH_ASSOC);

// Pobieranie dwóch wyróżnionych wiadomości
$featuredNews = [];
if ($mainNews) {
    $stmt = $pdo->prepare("SELECT n.*, u.imie, u.nazwisko 
                         FROM news n 
                         JOIN users u ON n.autor_id = u.id 
                         WHERE n.status = 'opublikowany' 
                         AND n.id != ? 
                         ORDER BY n.data_publikacji DESC 
                         LIMIT 2");
    $stmt->execute([$mainNews['id']]);
    $featuredNews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pobieranie pozostałych wiadomości
$otherNews = [];
if ($mainNews && !empty($featuredNews)) {
    $featuredIds = array_column($featuredNews, 'id');
    $featuredIds[] = $mainNews['id'];
    $placeholders = str_repeat('?,', count($featuredIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT n.*, u.imie, u.nazwisko 
                         FROM news n 
                         JOIN users u ON n.autor_id = u.id 
                         WHERE n.status = 'opublikowany' 
                         AND n.id NOT IN ($placeholders) 
                         ORDER BY n.data_publikacji DESC 
                         LIMIT 4");
    $stmt->execute($featuredIds);
    $otherNews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pobierz całkowitą liczbę wiadomości dla paginacji
$stmt = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'opublikowany'");
$totalNews = $stmt->fetchColumn();
$totalPages = ceil($totalNews / $newsPerPage);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Aktualności</title>
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
        <h1 class="page-title">Aktualności</h1>
        
        <?php if (!$mainNews && empty($featuredNews) && empty($otherNews)): ?>
            <div class="no-news-message">
                <h2>Brak aktualności</h2>
                <p>W tej chwili nie ma żadnych aktualności do wyświetlenia. Zapraszamy wkrótce!</p>
            </div>
        <?php else: ?>
            <!-- Główne wiadomości -->
            <section class="featured-news">
                <div class="featured-news-grid">
                    <!-- Główna wiadomość -->
                    <?php if ($mainNews): ?>
                    <article class="featured-news-main">
                        <div class="news-image">
                            <?php if ($mainNews['zdjecie']): ?>
                                <img src="<?php echo htmlspecialchars($mainNews['zdjecie']); ?>" alt="<?php echo htmlspecialchars($mainNews['tytul']); ?>">
                            <?php else: ?>
                                <img src="img/news/placeholder.png" alt="Brak zdjęcia">
                            <?php endif; ?>
                        </div>
                        <div class="news-content">
                            <span class="news-date"><?php echo date('d.m.Y', strtotime($mainNews['data_publikacji'])); ?></span>
                            <h2><?php echo htmlspecialchars($mainNews['tytul']); ?></h2>
                            <p><?php echo htmlspecialchars(substr($mainNews['tresc'], 0, 200)) . '...'; ?></p>
                            <a href="aktualnosc.php?id=<?php echo $mainNews['id']; ?>" class="read-more">Czytaj więcej</a>
                        </div>
                    </article>
                    <?php endif; ?>

                    <!-- Dodatkowe wyróżnione wiadomości -->
                    <?php foreach ($featuredNews as $news): ?>
                    <article class="featured-news-secondary">
                        <div class="news-image">
                            <?php if ($news['zdjecie']): ?>
                                <img src="<?php echo htmlspecialchars($news['zdjecie']); ?>" alt="<?php echo htmlspecialchars($news['tytul']); ?>">
                            <?php else: ?>
                                <img src="img/news/placeholder.png" alt="Brak zdjęcia">
                            <?php endif; ?>
                        </div>
                        <div class="news-content">
                            <span class="news-date"><?php echo date('d.m.Y', strtotime($news['data_publikacji'])); ?></span>
                            <h3><?php echo htmlspecialchars($news['tytul']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($news['tresc'], 0, 100)) . '...'; ?></p>
                            <a href="aktualnosc.php?id=<?php echo $news['id']; ?>" class="read-more">Czytaj więcej</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Pozostałe wiadomości -->
            <?php if (!empty($otherNews)): ?>
            <section class="news-grid">
                <?php foreach ($otherNews as $news): ?>
                <article class="news-item">
                    <div class="news-image">
                        <?php if ($news['zdjecie']): ?>
                            <img src="<?php echo htmlspecialchars($news['zdjecie']); ?>" alt="<?php echo htmlspecialchars($news['tytul']); ?>">
                        <?php else: ?>
                            <img src="img/news/placeholder.png" alt="Brak zdjęcia">
                        <?php endif; ?>
                    </div>
                    <div class="news-content">
                        <span class="news-date"><?php echo date('d.m.Y', strtotime($news['data_publikacji'])); ?></span>
                        <h3><?php echo htmlspecialchars($news['tytul']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($news['tresc'], 0, 100)) . '...'; ?></p>
                        <a href="aktualnosc.php?id=<?php echo $news['id']; ?>" class="read-more">Czytaj więcej</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <!-- Paginacja -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-link">&laquo; Poprzednia</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);

                if ($startPage > 1) {
                    echo '<a href="?page=1">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="pagination-dots">...</span>';
                    }
                }

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo ($i == $currentPage ? 'active' : ''); ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor;

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="pagination-dots">...</span>';
                    }
                    echo '<a href="?page=' . $totalPages . '">' . $totalPages . '</a>';
                }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-link">Następna &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
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