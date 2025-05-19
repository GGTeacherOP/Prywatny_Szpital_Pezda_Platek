<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany i jest pacjentem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'pacjent') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
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

    // Sprawdzenie czy wszystkie wymagane pola są wypełnione
    $required_fields = ['lekarz_id', 'data_wizyty', 'godzina_wizyty', 'typ_wizyty', 'pacjent_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Pole $field jest wymagane");
        }
    }

    // Łączenie daty i godziny
    $data_wizyty = $_POST['data_wizyty'] . ' ' . $_POST['godzina_wizyty'];

    // Sprawdzenie czy data wizyty nie jest z przeszłości
    if (strtotime($data_wizyty) < strtotime('now')) {
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

    if ($result['count'] > 0) {
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
            opis
        ) VALUES (
            :pacjent_id,
            :lekarz_id,
            :data_wizyty,
            :typ_wizyty,
            :status,
            :gabinet,
            :opis
        )
    ");

    $stmt->bindParam(':pacjent_id', $_POST['pacjent_id']);
    $stmt->bindParam(':lekarz_id', $_POST['lekarz_id']);
    $stmt->bindParam(':data_wizyty', $data_wizyty);
    $stmt->bindParam(':typ_wizyty', $_POST['typ_wizyty']);
    $stmt->bindParam(':status', $_POST['status']);
    $stmt->bindParam(':gabinet', $_POST['gabinet']);
    $stmt->bindParam(':opis', $_POST['opis']);
    
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Wizyta została pomyślnie umówiona']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 