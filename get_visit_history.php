<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    http_response_code(403);
    echo json_encode(['error' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy podano ID pacjenta
if (!isset($_GET['patient_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Brak ID pacjenta']);
    exit();
}

$patient_id = $_GET['patient_id'];

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Pobieranie historii wizyt pacjenta z dodatkowymi informacjami
    $stmt = $conn->prepare("SELECT 
        v.id,
        v.data_wizyty,
        v.typ_wizyty,
        v.status,
        v.opis,
        d.specjalizacja,
        u.imie as lekarz_imie,
        u.nazwisko as lekarz_nazwisko
        FROM visits v 
        JOIN doctors d ON v.lekarz_id = d.id 
        JOIN users u ON d.uzytkownik_id = u.id
        WHERE v.pacjent_id = :patient_id 
        AND d.uzytkownik_id = :user_id 
        ORDER BY v.data_wizyty DESC");
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $wizyty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatowanie daty dla każdej wizyty
    foreach ($wizyty as &$wizyta) {
        $wizyta['data_wizyty'] = date('Y-m-d H:i:s', strtotime($wizyta['data_wizyty']));
    }
    
    echo json_encode($wizyty);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 