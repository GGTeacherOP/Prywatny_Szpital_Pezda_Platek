<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Pobranie danych z żądania
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['news_id'])) {
    echo json_encode(['success' => false, 'message' => 'Brak ID wiadomości']);
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

    // Pobranie informacji o zdjęciu przed usunięciem wiadomości
    $stmt = $conn->prepare("SELECT zdjecie FROM news WHERE id = :news_id");
    $stmt->bindParam(':news_id', $data['news_id']);
    $stmt->execute();
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    // Usunięcie wiadomości
    $stmt = $conn->prepare("DELETE FROM news WHERE id = :news_id");
    $stmt->bindParam(':news_id', $data['news_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Jeśli wiadomość została usunięta i miała zdjęcie, usuń je
        if ($news && $news['zdjecie'] && file_exists($news['zdjecie'])) {
            unlink($news['zdjecie']);
        }
        echo json_encode(['success' => true, 'message' => 'Wiadomość została usunięta']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie znaleziono wiadomości o podanym ID']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 