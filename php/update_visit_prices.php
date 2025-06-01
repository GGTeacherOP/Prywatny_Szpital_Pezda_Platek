<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
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

    // Pobranie danych z formularza
    $doctor_id = $_POST['doctor_id'];
    $prices = $_POST['prices'];

    // Rozpoczęcie transakcji
    $conn->beginTransaction();

    // Usunięcie istniejących cen dla danego lekarza
    $stmt = $conn->prepare("DELETE FROM doctor_visit_prices WHERE lekarz_id = :doctor_id");
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->execute();

    // Dodanie nowych cen
    $stmt = $conn->prepare("INSERT INTO doctor_visit_prices (lekarz_id, typ_wizyty, cena) VALUES (:doctor_id, :typ_wizyty, :cena)");
    
    foreach ($prices as $typ_wizyty => $cena) {
        if (!empty($cena)) {
            $stmt->bindParam(':doctor_id', $doctor_id);
            $stmt->bindParam(':typ_wizyty', $typ_wizyty);
            $stmt->bindParam(':cena', $cena);
            $stmt->execute();
        }
    }

    // Zatwierdzenie transakcji
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Ceny zostały zaktualizowane pomyślnie']);

} catch(PDOException $e) {
    // W przypadku błędu, wycofanie transakcji
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji cen: ' . $e->getMessage()]);
}
?> 