<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

header('Content-Type: application/json');

// Logowanie danych wejściowych
error_log("Otrzymano dane POST: " . print_r($_POST, true));

// Sprawdzenie czy użytkownik jest zalogowany i jest pacjentem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'pacjent') {
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
    error_log("Połączono z bazą danych");

    // Sprawdzenie czy wszystkie wymagane pola są wypełnione
    $required_fields = ['lekarz_id', 'data_wizyty', 'godzina_wizyty', 'typ_wizyty', 'gabinet', 'pacjent_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            error_log("Brak wymaganego pola: " . $field);
            throw new Exception("Pole $field jest wymagane");
        }
    }

    // Łączenie daty i godziny
    $data_wizyty = $_POST['data_wizyty'] . ' ' . $_POST['godzina_wizyty'];
    error_log("Data wizyty: " . $data_wizyty);

    // Sprawdzenie czy data wizyty nie jest z przeszłości
    if (strtotime($data_wizyty) < strtotime('now')) {
        error_log("Próba umówienia wizyty w przeszłości: " . $data_wizyty);
        throw new Exception("Nie można umówić wizyty w przeszłości");
    }

    // Sprawdzenie czy lekarz jest dostępny w wybranym terminie
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM visits 
        WHERE lekarz_id = :lekarz_id 
        AND data_wizyty = :data_wizyty 
        AND status != 'anulowana'
    ");
    $stmt->bindParam(':lekarz_id', $_POST['lekarz_id']);
    $stmt->bindParam(':data_wizyty', $data_wizyty);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Sprawdzanie dostępności lekarza: " . print_r($result, true));

    if ($result['count'] > 0) {
        error_log("Wybrany termin jest już zajęty");
        throw new Exception("Wybrany termin jest już zajęty");
    }

    // Dodawanie wizyty do bazy danych
    $stmt = $conn->prepare("
        INSERT INTO visits (
            pacjent_id, 
            lekarz_id, 
            data_wizyty, 
            typ_wizyty, 
            status, 
            gabinet,
            opis,
            diagnoza,
            zalecenia
        ) VALUES (
            :pacjent_id,
            :lekarz_id,
            :data_wizyty,
            :typ_wizyty,
            :status,
            :gabinet,
            :opis,
            :diagnoza,
            :zalecenia
        )
    ");

    $stmt->bindParam(':pacjent_id', $_POST['pacjent_id']);
    $stmt->bindParam(':lekarz_id', $_POST['lekarz_id']);
    $stmt->bindParam(':data_wizyty', $data_wizyty);
    $stmt->bindParam(':typ_wizyty', $_POST['typ_wizyty']);
    $stmt->bindParam(':status', $_POST['status']);
    $stmt->bindParam(':gabinet', $_POST['gabinet']);
    $stmt->bindParam(':opis', $_POST['opis']);
    $stmt->bindParam(':diagnoza', $_POST['diagnoza']);
    $stmt->bindParam(':zalecenia', $_POST['zalecenia']);
    
    $stmt->execute();
    error_log("Wizyta została pomyślnie dodana do bazy danych");

    header("Location: panel-pacjenta.php?success=1");
    exit();

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    header("Location: panel-pacjenta.php?error=" . urlencode('Błąd bazy danych: ' . $e->getMessage()));
    exit();
} catch(Exception $e) {
    error_log("Wystąpił błąd: " . $e->getMessage());
    header("Location: panel-pacjenta.php?error=" . urlencode($e->getMessage()));
    exit();
}
?> 