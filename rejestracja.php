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
        $numer_telefonu = isset($_POST['numer_telefonu']) ? trim($_POST['numer_telefonu']) : '';
        $adres = isset($_POST['adres']) ? trim($_POST['adres']) : '';
        $kod_pocztowy = isset($_POST['kod_pocztowy']) ? trim($_POST['kod_pocztowy']) : '';
        $miasto = isset($_POST['miasto']) ? trim($_POST['miasto']) : '';
        $alergia = isset($_POST['alergia']) ? trim($_POST['alergia']) : '';
        $choroby_przewlekle = isset($_POST['choroby_przewlekle']) ? trim($_POST['choroby_przewlekle']) : '';
        $przyjmowane_leki = isset($_POST['przyjmowane_leki']) ? trim($_POST['przyjmowane_leki']) : '';
        $ubezpieczenie = isset($_POST['ubezpieczenie']) ? trim($_POST['ubezpieczenie']) : '';
        $numer_ubezpieczenia = isset($_POST['numer_ubezpieczenia']) ? trim($_POST['numer_ubezpieczenia']) : '';

        // Walidacja danych
        if (empty($imie_nazwisko)) $errors[] = "Proszę podać imię i nazwisko";
        if (empty($pesel)) $errors[] = "Proszę podać numer PESEL";
        if (empty($data_urodzenia)) $errors[] = "Proszę wybrać datę urodzenia";
        if (empty($email)) $errors[] = "Proszę podać adres email";
        if (empty($haslo)) $errors[] = "Proszę wymyślić hasło";
        if (empty($grupa_krwi)) $errors[] = "Proszę wybrać grupę krwi";
        if (empty($numer_telefonu)) $errors[] = "Proszę podać numer telefonu";
        if (empty($adres)) $errors[] = "Proszę podać adres zamieszkania";
        if (empty($kod_pocztowy)) $errors[] = "Proszę podać kod pocztowy";
        if (empty($miasto)) $errors[] = "Proszę podać miasto";
        if ($haslo !== $powtorz_haslo) $errors[] = "Wprowadzone hasła nie są identyczne. Proszę sprawdzić i spróbować ponownie";

        // Sprawdzenie czy PESEL ma odpowiednią długość
        if (strlen($pesel) !== 11 || !ctype_digit($pesel)) {
            $errors[] = "Numer PESEL musi składać się z dokładnie 11 cyfr";
        }

        // Sprawdzenie czy email jest poprawny
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Wprowadzony adres email jest nieprawidłowy. Proszę sprawdzić format";
        }

        // Sprawdzenie czy email już istnieje w bazie
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ten adres email jest już zarejestrowany w naszym systemie. Proszę użyć innego adresu lub zalogować się na istniejące konto";
        }

        // Sprawdzenie czy PESEL już istnieje w bazie
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE pesel = ?");
        $stmt->execute([$pesel]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ten numer PESEL jest już zarejestrowany w naszym systemie. Jeśli to Twój PESEL, proszę zalogować się na istniejące konto";
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
                    INSERT INTO users (imie, nazwisko, pesel, data_urodzenia, email, haslo, funkcja, numer_telefonu, adres, kod_pocztowy, miasto) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pacjent', ?, ?, ?, ?)
                ");
                $stmt->execute([$imie, $nazwisko, $pesel, $data_urodzenia, $email, $haslo, $numer_telefonu, $adres, $kod_pocztowy, $miasto]);
                
                $user_id = $conn->lastInsertId();

                // Dodanie pacjenta do tabeli patients
                $stmt = $conn->prepare("
                    INSERT INTO patients (uzytkownik_id, grupa_krwi, alergia, choroby_przewlekle, przyjmowane_leki, ubezpieczenie, numer_ubezpieczenia) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $grupa_krwi, $alergia, $choroby_przewlekle, $przyjmowane_leki, $ubezpieczenie, $numer_ubezpieczenia]);

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
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            font-size: 14px;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .alert i {
            margin-right: 10px;
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
            <div class="alert-container">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error">
                        <i>⚠️</i> <?php echo htmlspecialchars($error); ?>
                    </div>
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
                <label for="numer_telefonu">Numer telefonu</label>
                <input type="tel" id="numer_telefonu" name="numer_telefonu" required placeholder="Wprowadź numer telefonu" 
                       value="<?php echo isset($_POST['numer_telefonu']) ? htmlspecialchars($_POST['numer_telefonu']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="adres">Adres zamieszkania</label>
                <input type="text" id="adres" name="adres" required placeholder="Wprowadź adres zamieszkania" 
                       value="<?php echo isset($_POST['adres']) ? htmlspecialchars($_POST['adres']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="kod_pocztowy">Kod pocztowy</label>
                <input type="text" id="kod_pocztowy" name="kod_pocztowy" required placeholder="Wprowadź kod pocztowy (np. 39-300)" 
                       pattern="[0-9]{2}-[0-9]{3}" value="<?php echo isset($_POST['kod_pocztowy']) ? htmlspecialchars($_POST['kod_pocztowy']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="miasto">Miasto</label>
                <input type="text" id="miasto" name="miasto" required placeholder="Wprowadź miasto" 
                       value="<?php echo isset($_POST['miasto']) ? htmlspecialchars($_POST['miasto']) : ''; ?>">
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

            <div class="form-group">
                <label for="alergia">Alergie (opcjonalnie)</label>
                <textarea id="alergia" name="alergia" placeholder="Wprowadź informacje o alergiach" rows="3"><?php echo isset($_POST['alergia']) ? htmlspecialchars($_POST['alergia']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="choroby_przewlekle">Choroby przewlekłe (opcjonalnie)</label>
                <textarea id="choroby_przewlekle" name="choroby_przewlekle" placeholder="Wprowadź informacje o chorobach przewlekłych" rows="3"><?php echo isset($_POST['choroby_przewlekle']) ? htmlspecialchars($_POST['choroby_przewlekle']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="przyjmowane_leki">Przyjmowane leki (opcjonalnie)</label>
                <textarea id="przyjmowane_leki" name="przyjmowane_leki" placeholder="Wprowadź informacje o przyjmowanych lekach" rows="3"><?php echo isset($_POST['przyjmowane_leki']) ? htmlspecialchars($_POST['przyjmowane_leki']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="ubezpieczenie">Ubezpieczenie</label>
                <select id="ubezpieczenie" name="ubezpieczenie" required>
                    <option value="">Wybierz ubezpieczenie</option>
                    <option value="NFZ" <?php echo (isset($_POST['ubezpieczenie']) && $_POST['ubezpieczenie'] === 'NFZ') ? 'selected' : ''; ?>>NFZ</option>
                    <option value="Prywatne" <?php echo (isset($_POST['ubezpieczenie']) && $_POST['ubezpieczenie'] === 'Prywatne') ? 'selected' : ''; ?>>Prywatne</option>
                    <option value="Brak" <?php echo (isset($_POST['ubezpieczenie']) && $_POST['ubezpieczenie'] === 'Brak') ? 'selected' : ''; ?>>Brak</option>
                </select>
            </div>

            <div class="form-group">
                <label for="numer_ubezpieczenia">Numer ubezpieczenia</label>
                <input type="text" id="numer_ubezpieczenia" name="numer_ubezpieczenia" required placeholder="Wprowadź numer ubezpieczenia" 
                       value="<?php echo isset($_POST['numer_ubezpieczenia']) ? htmlspecialchars($_POST['numer_ubezpieczenia']) : ''; ?>">
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