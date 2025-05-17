<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit;
}

// Sprawdzenie czy podano ID pacjenta
if (!isset($_GET['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nie podano ID pacjenta']);
    exit;
}

try {
    // Połączenie z bazą danych
    $pdo = new PDO('mysql:host=localhost;dbname=szpital;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pobranie wyników badań pacjenta
    $stmt = $pdo->prepare('
        SELECT r.*, 
               CONCAT(u.imie, " ", u.nazwisko) as lekarz_nazwisko,
               d.specjalizacja as lekarz_specjalizacja
        FROM results r
        JOIN doctors d ON r.lekarz_id = d.id
        JOIN users u ON d.uzytkownik_id = u.id
        WHERE r.pacjent_id = ?
        ORDER BY r.data_wystawienia DESC
    ');
    
    $stmt->execute([$_GET['patient_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatowanie dat
    foreach ($results as &$result) {
        $result['data_wystawienia'] = date('d.m.Y H:i', strtotime($result['data_wystawienia']));
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Wystąpił nieoczekiwany błąd: ' . $e->getMessage()]);
}
?> 