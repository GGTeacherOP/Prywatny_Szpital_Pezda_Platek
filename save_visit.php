<?php
session_start();
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit;
}

// Sprawdzenie czy wszystkie wymagane pola są wypełnione
if (!isset($_POST['pacjent_id']) || !isset($_POST['data_wizyty']) || !isset($_POST['typ_wizyty']) || !isset($_POST['gabinet'])) {
    echo json_encode(['success' => false, 'message' => 'Wszystkie wymagane pola muszą być wypełnione']);
    exit;
}

try {
    // Połączenie z bazą danych
    $pdo = new PDO('mysql:host=localhost;dbname=szpital;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pobranie ID lekarza na podstawie ID użytkownika
    $stmt = $pdo->prepare('SELECT id FROM doctors WHERE uzytkownik_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $lekarz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lekarz) {
        throw new Exception('Nie znaleziono danych lekarza');
    }

    // Wstawienie wizyty do bazy danych
    $stmt = $pdo->prepare('
        INSERT INTO visits (
            pacjent_id, 
            lekarz_id, 
            data_wizyty, 
            typ_wizyty, 
            status, 
            gabinet, 
            opis, 
            diagnoza, 
            zalecenia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $_POST['pacjent_id'],
        $lekarz['id'],
        $_POST['data_wizyty'],
        $_POST['typ_wizyty'],
        'zaplanowana',
        $_POST['gabinet'],
        $_POST['opis'] ?? null,
        $_POST['diagnoza'] ?? null,
        $_POST['zalecenia'] ?? null
    ]);

    // Aktualizacja daty ostatniej wizyty pacjenta
    $stmt = $pdo->prepare('UPDATE patients SET data_ostatniej_wizyty = ? WHERE id = ?');
    $stmt->execute([$_POST['data_wizyty'], $_POST['pacjent_id']]);

    echo json_encode(['success' => true, 'message' => 'Wizyta została pomyślnie zapisana']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd: ' . $e->getMessage()]);
} 