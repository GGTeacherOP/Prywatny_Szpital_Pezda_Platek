<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Brak autoryzacji']);
    exit();
}

// Sprawdzenie czy podano wymagane parametry
if (!isset($_POST['lekarz_id']) || !isset($_POST['typ_wizyty'])) {
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

    // Pobieranie ceny wizyty
    $stmt = $conn->prepare("
        SELECT cena
        FROM doctor_visit_prices
        WHERE lekarz_id = :lekarz_id
        AND typ_wizyty = :typ_wizyty
    ");
    
    $stmt->bindParam(':lekarz_id', $_POST['lekarz_id']);
    $stmt->bindParam(':typ_wizyty', $_POST['typ_wizyty']);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['price' => number_format($result['cena'], 2)]);
    } else {
        echo json_encode(['error' => 'Nie znaleziono ceny dla wybranego typu wizyty']);
    }

} catch(PDOException $e) {
    echo json_encode(['error' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 