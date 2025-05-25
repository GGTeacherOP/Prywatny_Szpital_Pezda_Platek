<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest obsługą
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'obsluga') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy otrzymano wymagane dane
if (!isset($_POST['task_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

$task_id = $_POST['task_id'];
$new_status = $_POST['status'];

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sprawdzenie czy zadanie należy do zalogowanego pracownika
    $stmt = $conn->prepare("
        SELECT t.id 
        FROM tasks t
        JOIN staff s ON t.pracownik_id = s.id
        WHERE t.id = :task_id AND s.uzytkownik_id = :user_id
    ");
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Brak uprawnień do tego zadania']);
        exit();
    }

    // Aktualizacja statusu zadania
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET status = :status 
        WHERE id = :task_id
    ");
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Status zadania został zaktualizowany']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 