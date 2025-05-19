<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

$errors = [];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sprawdzenie czy formularz został wysłany
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Pobranie danych z formularza
        $imie_nazwisko = trim($_POST['name']);
        $pesel = trim($_POST['pesel']);
        $data_urodzenia = $_POST['data_urodzenia'];
        $email = trim($_POST['email']);
        $haslo = $_POST['password'];
        $powtorz_haslo = $_POST['password-repeat'];
        $grupa_krwi = $_POST['grupa_krwi'];

        // Walidacja danych
        if (empty($imie_nazwisko)) $errors[] = "Imię i nazwisko są wymagane";
        if (empty($pesel)) $errors[] = "PESEL jest wymagany";
        if (empty($data_urodzenia)) $errors[] = "Data urodzenia jest wymagana";
        if (empty($email)) $errors[] = "Email jest wymagany";
        if (empty($haslo)) $errors[] = "Hasło jest wymagane";
        if (empty($grupa_krwi)) $errors[] = "Grupa krwi jest wymagana";
        if ($haslo !== $powtorz_haslo) $errors[] = "Hasła nie są identyczne";

        // Sprawdzenie czy PESEL ma odpowiednią długość
        if (strlen($pesel) !== 11 || !ctype_digit($pesel)) {
            $errors[] = "PESEL musi składać się z 11 cyfr";
        }

        // Sprawdzenie czy email jest poprawny
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Niepoprawny format adresu email";
        }

        // Sprawdzenie czy email już istnieje w bazie
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ten adres email jest już zarejestrowany";
        }

        // Sprawdzenie czy PESEL już istnieje w bazie
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE pesel = ?");
        $stmt->execute([$pesel]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ten PESEL jest już zarejestrowany";
        }

        // Jeśli nie ma błędów, dodaj użytkownika do bazy
        if (empty($errors)) {
            try {
                $conn->beginTransaction();

                // Rozdzielenie imienia i nazwiska
                $parts = explode(' ', $imie_nazwisko, 2);
                $imie = $parts[0];
                $nazwisko = isset($parts[1]) ? $parts[1] : '';

                // Dodanie użytkownika do tabeli users
                $stmt = $conn->prepare("
                    INSERT INTO users (imie, nazwisko, pesel, data_urodzenia, email, haslo, funkcja) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pacjent')
                ");
                $stmt->execute([$imie, $nazwisko, $pesel, $data_urodzenia, $email, $haslo]);
                
                $user_id = $conn->lastInsertId();

                // Dodanie pacjenta do tabeli patients
                $stmt = $conn->prepare("
                    INSERT INTO patients (uzytkownik_id, grupa_krwi) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$user_id, $grupa_krwi]);

                $conn->commit();

                // Przekierowanie do strony logowania z komunikatem o sukcesie
                header("Location: logowanie.php?success=1");
                exit();

            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = "Wystąpił błąd podczas rejestracji: " . $e->getMessage();
            }
        }
    }
} catch(PDOException $e) {
    $errors[] = "Błąd połączenia z bazą danych: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Szpital - Rejestracja</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <link rel="stylesheet" href="css/login.css">
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
                <li><a href="aktualnosci.php">Aktualności</a></li>
                <li><a href="dla-pacjenta.html">Dla pacjenta</a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="logowanie.php" class="btn-login">Zaloguj się</a>
        </div>
    </header>

    <div class="login-container">
        <div class="login-header">
            <h2>Rejestracja</h2>
            <p>Załóż nowe konto pacjenta</p>
        </div><br>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="rejestracja.php">
            <div class="form-group">
                <label for="name">Imię i nazwisko</label>
                <input type="text" id="name" name="name" required placeholder="Wprowadź imię i nazwisko" 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="pesel">PESEL</label>
                <input type="text" id="pesel" name="pesel" required placeholder="Wprowadź PESEL" 
                       value="<?php echo isset($_POST['pesel']) ? htmlspecialchars($_POST['pesel']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="data_urodzenia">Data urodzenia</label>
                <input type="date" id="data_urodzenia" name="data_urodzenia" required
                       value="<?php echo isset($_POST['data_urodzenia']) ? htmlspecialchars($_POST['data_urodzenia']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Wprowadź swój email"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" required placeholder="Wprowadź hasło">
            </div>

            <div class="form-group">
                <label for="password-repeat">Powtórz hasło</label>
                <input type="password" id="password-repeat" name="password-repeat" required placeholder="Powtórz hasło">
            </div>

            <div class="form-group">
                <label for="grupa_krwi">Grupa krwi</label>
                <select id="grupa_krwi" name="grupa_krwi" required>
                    <option value="">Wybierz grupę krwi</option>
                    <option value="A Rh+" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === 'A Rh+') ? 'selected' : ''; ?>>A Rh+</option>
                    <option value="A Rh-" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === 'A Rh-') ? 'selected' : ''; ?>>A Rh-</option>
                    <option value="B Rh+" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === 'B Rh+') ? 'selected' : ''; ?>>B Rh+</option>
                    <option value="B Rh-" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === 'B Rh-') ? 'selected' : ''; ?>>B Rh-</option>
                    <option value="AB Rh+" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === 'AB Rh+') ? 'selected' : ''; ?>>AB Rh+</option>
                    <option value="AB Rh-" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === 'AB Rh-') ? 'selected' : ''; ?>>AB Rh-</option>
                    <option value="0 Rh+" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === '0 Rh+') ? 'selected' : ''; ?>>0 Rh+</option>
                    <option value="0 Rh-" <?php echo (isset($_POST['grupa_krwi']) && $_POST['grupa_krwi'] === '0 Rh-') ? 'selected' : ''; ?>>0 Rh-</option>
                </select>
            </div>

            <button type="submit" class="form-login-button">Zarejestruj się</button>
            <div class="login-links">
                <a href="logowanie.php">Masz już konto? Zaloguj się</a>
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