<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Pobranie danych z żądania
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['review_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

// Walidacja statusu
$allowed_statuses = ['oczekujaca', 'zatwierdzona', 'odrzucona'];
if (!in_array($data['status'], $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy status']);
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

    // Aktualizacja statusu opinii
    $stmt = $conn->prepare("
        UPDATE reviews 
        SET status = :status 
        WHERE id = :review_id
    ");

    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':review_id', $data['review_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status opinii został zaktualizowany']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie znaleziono opinii o podanym ID']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 