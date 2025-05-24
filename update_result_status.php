<?php
session_start();

// Debugowanie
error_log('Otrzymane dane POST: ' . print_r($_POST, true));

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem lub administratorem
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['funkcja'], ['lekarz', 'administrator'])) {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy otrzymano wymagane dane
if (!isset($_POST['result_id']) || !isset($_POST['status'])) {
    error_log('Brak wymaganych danych: result_id=' . (isset($_POST['result_id']) ? $_POST['result_id'] : 'nie ustawiono') . 
              ', status=' . (isset($_POST['status']) ? $_POST['status'] : 'nie ustawiono'));
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

$result_id = $_POST['result_id'];
$status = $_POST['status'];

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Aktualizacja statusu wyniku badań
    $stmt = $conn->prepare("UPDATE results SET status = :status WHERE id = :result_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':result_id', $result_id);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    error_log('Błąd bazy danych: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 