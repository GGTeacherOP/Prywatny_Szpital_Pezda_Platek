<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy otrzymano wymagane dane
if (!isset($_POST['doctor_id']) || !isset($_POST['hours'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

$doctor_id = $_POST['doctor_id'];
$hours = $_POST['hours'];

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Rozpoczęcie transakcji
    $conn->beginTransaction();
    
    // Usunięcie istniejących godzin dla danego lekarza
    $stmt = $conn->prepare("DELETE FROM doctor_hours WHERE lekarz_id = :doctor_id");
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->execute();
    
    // Dodanie nowych godzin
    $stmt = $conn->prepare("
        INSERT INTO doctor_hours (lekarz_id, dzien_tygodnia, godzina_rozpoczecia, godzina_zakonczenia)
        VALUES (:doctor_id, :dzien_tygodnia, :godzina_rozpoczecia, :godzina_zakonczenia)
    ");
    
    foreach ($hours as $dzien => $godziny) {
        if (!empty($godziny['start']) && !empty($godziny['end']) && isset($godziny['enabled']) && $godziny['enabled'] === 'on') {
            $stmt->bindParam(':doctor_id', $doctor_id);
            $stmt->bindParam(':dzien_tygodnia', $dzien);
            $stmt->bindParam(':godzina_rozpoczecia', $godziny['start']);
            $stmt->bindParam(':godzina_zakonczenia', $godziny['end']);
            $stmt->execute();
        }
    }
    
    // Zatwierdzenie transakcji
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Godziny przyjęć zostały zaktualizowane pomyślnie']);
    
} catch(PDOException $e) {
    // Wycofanie transakcji w przypadku błędu
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji godzin: ' . $e->getMessage()]);
}
?> 