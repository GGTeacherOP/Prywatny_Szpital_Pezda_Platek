<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'config.php';

    // Próba połączenia z bazą danych
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pobieranie liczby pacjentów
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM patients p 
        JOIN users u ON p.uzytkownik_id = u.id 
        WHERE u.status = 'aktywny'
    ");
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie liczby wyników
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM results 
        WHERE status = 'gotowy'
    ");
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie liczby lekarzy
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM doctors d 
        JOIN users u ON d.uzytkownik_id = u.id 
        WHERE u.status = 'aktywny'
    ");
    $totalDoctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie liczby oddziałów
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM departments
    ");
    $totalDepartments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie listy specjalizacji
    $stmt = $pdo->query("
        SELECT DISTINCT specjalizacja 
        FROM doctors d 
        JOIN users u ON d.uzytkownik_id = u.id 
        WHERE u.status = 'aktywny' 
        ORDER BY specjalizacja
    ");
    $specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Pobieranie opinii i średniej oceny
    $stmt = $pdo->query("
        SELECT 
            r.*,
            u.imie,
            u.nazwisko,
            DATE_FORMAT(r.data_utworzenia, '%d.%m.%Y') as data
        FROM reviews r
        JOIN users u ON r.uzytkownik_id = u.id
        WHERE r.status = 'zatwierdzona'
        ORDER BY r.data_utworzenia DESC
        LIMIT 3
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobieranie średniej oceny
    $stmt = $pdo->query("
        SELECT AVG(ocena) as srednia 
        FROM reviews 
        WHERE status = 'zatwierdzona'
    ");
    $averageRating = round($stmt->fetch(PDO::FETCH_ASSOC)['srednia'], 1);

    $stats = [
        'patients' => (int)$totalPatients,
        'results' => (int)$totalResults,
        'doctors' => (int)$totalDoctors,
        'departments' => (int)$totalDepartments,
        'specializations' => $specializations,
        'reviews' => $reviews,
        'averageRating' => (float)$averageRating
    ];

    echo json_encode($stats, JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd bazy danych',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    error_log("Błąd ogólny: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd serwera',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 