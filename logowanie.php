<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Błąd połączenia: " . $e->getMessage();
}

// Obsługa formularza logowania
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error = "";
    
    try {
        // Najpierw sprawdźmy czy użytkownik w ogóle istnieje
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = "Nie znaleziono konta o podanym adresie email";
        } else {
            // Sprawdźmy czy konto jest aktywne
            if ($user['status'] !== 'aktywny') {
                $error = "To konto jest nieaktywne";
            }
            // Sprawdźmy hasło
            else if ($password !== $user['haslo']) {
                $error = "Nieprawidłowe hasło";
            }
            // Jeśli wszystko OK, zaloguj i przekieruj do odpowiedniego panelu
            else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['funkcja'] = $user['funkcja'];
                
                // Przekierowanie w zależności od funkcji użytkownika
                if ($user['funkcja'] === 'lekarz') {
                    header("Location: panel-lekarza.php");
                } else if ($user['funkcja'] === 'pacjent') {
                    header("Location: panel-pacjenta.php");
                } else if ($user['funkcja'] === 'administrator') {
                    header("Location: panel-admina.php");
                } else {
                    $error = "Nieznany typ konta";
                }
                exit();
            }
        }
    } catch(PDOException $e) {
        $error = "Wystąpił błąd podczas logowania. Spróbuj ponownie później.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Logowanie</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/logowanie.css'>
    <script src='main.js'></script>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .error-message {
            color: #d32f2f;
            font-size: 0.9rem;
            margin: 1rem 0;
            text-align: center;
            padding: 0.5rem;
            background-color: #ffebee;
            border-radius: 4px;
            border: 1px solid #ffcdd2;
            display: block !important;
        }
    </style>
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
                <li><a href="aktualnosci.html">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="logowanie.php" class="btn-login">Zaloguj się</a>
        </div>
    </header>

    <div class="login-container">
        <div class="login-header">
            <h2>Logowanie</h2>
            <p>Zaloguj się do swojego konta</p>
        </div>
        <form class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Wprowadź swój email">
            </div>
            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" required placeholder="Wprowadź swoje hasło">
            </div>
            <?php if(isset($error) && !empty($error)) { ?>
                <div class="error-message" style="display: block !important;"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>
            <button type="submit" class="form-login-button">Zaloguj się</button>
            <div class="login-links">
                <a href="rejestracja.php">Nie masz konta? Zarejestruj się</a>
            </div>
        </form>
        <a href="index.html" class="back-to-home">← Powrót do strony głównej</a>
    </div>

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