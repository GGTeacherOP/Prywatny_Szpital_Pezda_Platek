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

$review_id = $_POST['review_id'];
$status = $_POST['status'];

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Aktualizacja statusu opinii
    $stmt = $conn->prepare("UPDATE doctor_reviews SET status = :status WHERE id = :review_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':review_id', $review_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Status został zaktualizowany']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas aktualizacji statusu']);
}
?> 