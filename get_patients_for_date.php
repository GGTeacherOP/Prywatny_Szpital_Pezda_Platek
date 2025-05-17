<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy data została przekazana
if (!isset($_POST['date'])) {
    echo json_encode(['success' => false, 'message' => 'Brak daty']);
    exit();
}

$date = $_POST['date'];

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Pobieranie pacjentów dla wybranej daty
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id, u.imie, u.nazwisko, u.pesel
        FROM patients p
        JOIN users u ON p.uzytkownik_id = u.id
        JOIN visits v ON p.id = v.pacjent_id
        JOIN doctors d ON v.lekarz_id = d.id
        WHERE d.uzytkownik_id = :user_id
        AND DATE(v.data_wizyty) = :date
        ORDER BY u.nazwisko, u.imie
    ");
    
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'patients' => $patients
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Błąd bazy danych: ' . $e->getMessage()
    ]);
}
?> 