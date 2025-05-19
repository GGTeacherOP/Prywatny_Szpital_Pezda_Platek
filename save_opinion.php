<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest pacjentem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'pacjent') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy wszystkie wymagane pola zostały wypełnione
if (!isset($_POST['ocena']) || !isset($_POST['komentarz'])) {
    echo json_encode(['success' => false, 'message' => 'Wszystkie pola są wymagane']);
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

    // Sprawdzenie czy opinia już istnieje
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM reviews 
        WHERE uzytkownik_id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Już wystawiłeś opinię o szpitalu']);
        exit();
    }

    // Zapisywanie opinii
    $stmt = $conn->prepare("
        INSERT INTO reviews (uzytkownik_id, ocena, tresc, data_utworzenia, status)
        VALUES (:user_id, :ocena, :komentarz, NOW(), 'oczekujaca')
    ");
    
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':ocena', $_POST['ocena']);
    $stmt->bindParam(':komentarz', $_POST['komentarz']);
    
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Opinia została zapisana']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 