<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Brak autoryzacji']);
    exit();
}

// Sprawdzenie czy podano wymagane parametry
if (!isset($_GET['data']) || !isset($_GET['lekarz_id'])) {
    echo json_encode(['error' => 'Brak wymaganych parametrów']);
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

    // Pobieranie zajętych godzin
    $stmt = $conn->prepare("
        SELECT TIME(data_wizyty) as godzina
        FROM visits
        WHERE lekarz_id = :lekarz_id
        AND DATE(data_wizyty) = :data
        AND status != 'anulowana'
    ");
    $stmt->bindParam(':lekarz_id', $_GET['lekarz_id']);
    $stmt->bindParam(':data', $_GET['data']);
    $stmt->execute();
    $zajete_godziny = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Generowanie wszystkich godzin od 8:00 do 16:00
    $dostepne_godziny = [];
    for ($hour = 8; $hour <= 16; $hour++) {
        $godzina = sprintf("%02d:00", $hour);
        if (!in_array($godzina, $zajete_godziny)) {
            $dostepne_godziny[] = $godzina;
        }
    }

    echo json_encode($dostepne_godziny);

} catch(PDOException $e) {
    echo json_encode(['error' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 