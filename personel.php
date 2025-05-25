<?php
// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Pobieranie danych lekarzy
    $sql = "SELECT u.imie, u.nazwisko, d.specjalizacja, d.tytul_naukowy, d.opis, d.zdjecie, d.numer_licencji 
            FROM users u 
            INNER JOIN doctors d ON u.id = d.uzytkownik_id 
            WHERE u.funkcja = 'lekarz' AND u.status = 'aktywny'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pobieranie unikalnych specjalizacji
    $sql_spec = "SELECT DISTINCT specjalizacja FROM doctors WHERE specjalizacja IS NOT NULL AND specjalizacja != '' ORDER BY specjalizacja";
    $stmt_spec = $conn->prepare($sql_spec);
    $stmt_spec->execute();
    $specializations = $stmt_spec->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    $doctors = [];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Nasz Personel</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/personel.css'>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const staffCards = document.querySelectorAll('.staff-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Usuń klasę active ze wszystkich przycisków
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Dodaj klasę active do klikniętego przycisku
                    this.classList.add('active');

                    const specialization = this.getAttribute('data-specialization');

                    staffCards.forEach(card => {
                        const cardSpecialization = card.querySelector('.position-specialty').textContent.trim();
                        
                        if (specialization === 'all' || cardSpecialization === specialization) {
                            card.style.display = 'block';
                            card.classList.remove('hidden');
                        } else {
                            card.classList.add('hidden');
                            setTimeout(() => {
                                card.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
        });
    </script>
</head>
<body class="personel-page">
    <header class="header">
        <div class="logo">
            <img src="img/logo/logo.png" alt="Logo Szpitala">
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="index.html">Strona główna</a></li>
                <li><a href="o-nas.html">O nas</a></li>
                <li><a href="personel.php" class="active">Nasz Personel</a></li>
                <li><a href="aktualnosci.php">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="logowanie.php" class="btn-login">Zaloguj się</a>
        </div>
    </header>

    <main class="main-content">
        <section class="personel-section">
            <h1>Nasz Zespół</h1>
            <p class="page-subtitle">Poznaj ludzi, którzy każdego dnia dbają o Twoje zdrowie i dobre samopoczucie.</p>

            <div class="personel-category">
                <h2>Lekarze</h2>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-specialization="all"><span>Wszyscy</span></button>
                    <?php foreach($specializations as $spec): ?>
                        <button class="filter-btn" data-specialization="<?php echo htmlspecialchars($spec); ?>">
                            <span><?php echo htmlspecialchars($spec); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="staff-grid">
                    <?php foreach($doctors as $doctor): ?>
                    <div class="staff-card">
                        <img src="<?php echo $doctor['zdjecie'] ? 'data:image/jpeg;base64,'.base64_encode($doctor['zdjecie']) : 'img/about-us/placeholder-user.png'; ?>" alt="Lekarz">
                        <h3><?php echo htmlspecialchars($doctor['imie'] . ' ' . $doctor['nazwisko']); ?></h3>
                        <p class="academic-title"><?php echo htmlspecialchars($doctor['tytul_naukowy']); ?></p>
                        <p class="position-specialty"><?php echo htmlspecialchars($doctor['specjalizacja']); ?></p>
                        <p class="license">Nr licencji: <?php echo htmlspecialchars($doctor['numer_licencji']); ?></p>
                        <?php if (!empty($doctor['opis'])): ?>
                            <p class="details"><?php echo htmlspecialchars($doctor['opis']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
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