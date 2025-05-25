<?php
session_start();

// Włącz logowanie błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Sprawdzenie czy otrzymano wymagane parametry
if (!isset($_POST['lekarz_id']) || !isset($_POST['data']) || !isset($_POST['dzien'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$lekarz_id = $_POST['lekarz_id'];
$data = $_POST['data'];
$dzien = $_POST['dzien'];

// Logowanie otrzymanych parametrów
error_log("Otrzymane parametry: lekarz_id=$lekarz_id, data=$data, dzien=$dzien");

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pobierz godziny pracy lekarza dla danego dnia tygodnia
    $stmt = $conn->prepare("
        SELECT godzina_rozpoczecia, godzina_zakonczenia
        FROM doctor_hours
        WHERE lekarz_id = :lekarz_id
        AND dzien_tygodnia = :dzien
    ");
    
    $stmt->bindParam(':lekarz_id', $lekarz_id);
    $stmt->bindParam(':dzien', $dzien);
    $stmt->execute();
    
    $doctorHours = $stmt->fetch(PDO::FETCH_ASSOC);

    // Logowanie wyników zapytania
    error_log("Godziny pracy lekarza: " . print_r($doctorHours, true));

    if (!$doctorHours) {
        echo json_encode(['error' => 'Lekarz nie przyjmuje w tym dniu']);
        exit();
    }

    // Pobierz zajęte godziny dla wybranego lekarza i daty
    $stmt = $conn->prepare("
        SELECT TIME_FORMAT(data_wizyty, '%H:00') as godzina
        FROM visits
        WHERE lekarz_id = :lekarz_id
        AND DATE(data_wizyty) = :data
        AND status != 'anulowana'
    ");
    
    $stmt->bindParam(':lekarz_id', $lekarz_id);
    $stmt->bindParam(':data', $data);
    $stmt->execute();
    
    $bookedHours = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Logowanie zajętych godzin
    error_log("Zajęte godziny: " . print_r($bookedHours, true));

    // Generuj listę dostępnych godzin
    $availableHours = [];
    $startHour = (int)substr($doctorHours['godzina_rozpoczecia'], 0, 2);
    $endHour = (int)substr($doctorHours['godzina_zakonczenia'], 0, 2);

    // Logowanie zakresu godzin
    error_log("Zakres godzin: $startHour - $endHour");

    for ($hour = $startHour; $hour < $endHour; $hour++) {
        $timeString = sprintf("%02d:00", $hour);
        if (!in_array($timeString, $bookedHours)) {
            $availableHours[] = $timeString;
        }
    }
    
    // Logowanie dostępnych godzin
    error_log("Dostępne godziny: " . print_r($availableHours, true));
    
    // Zwróć dostępne godziny jako JSON
    header('Content-Type: application/json');
    echo json_encode(['availableHours' => $availableHours]);

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 