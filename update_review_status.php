<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy otrzymano wymagane dane
if (!isset($_POST['review_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

$review_id = filter_var($_POST['review_id'], FILTER_VALIDATE_INT);
$status = $_POST['status'];

// Walidacja danych wejściowych
if ($review_id === false) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID opinii']);
    exit();
}

// Walidacja statusu
$allowed_statuses = ['oczekujaca', 'zatwierdzona', 'odrzucona'];
if (!in_array($status, $allowed_statuses)) {
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

    // Sprawdzenie czy opinia istnieje
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE id = :review_id");
    $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Nie znaleziono opinii o podanym ID']);
        exit();
    }

    // Aktualizacja statusu opinii
    $stmt = $conn->prepare("UPDATE reviews SET status = :status WHERE id = :review_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status został zaktualizowany']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie udało się zaktualizować statusu']);
    }

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas aktualizacji statusu']);
}
?> 