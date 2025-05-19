<?php
require_once 'config.php';

try {
    // Sprawdzenie czy plik config.php został poprawnie załadowany
    if (!isset($host) || !isset($dbname) || !isset($username) || !isset($password)) {
        throw new Exception('Brak wymaganych danych konfiguracyjnych bazy danych.');
    }

    // Próba połączenia z bazą danych
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");

    // Test połączenia i struktury tabeli
    $testQuery = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($testQuery->rowCount() === 0) {
        throw new Exception('Tabela reviews nie istnieje w bazie danych');
    }

    // Sprawdzenie struktury tabeli reviews
    $structureQuery = $pdo->query("DESCRIBE reviews");
    $structure = $structureQuery->fetchAll(PDO::FETCH_ASSOC);
    error_log("Struktura tabeli reviews: " . print_r($structure, true));

    // Sprawdzenie wszystkich opinii
    $allReviewsQuery = $pdo->query("SELECT * FROM reviews");
    $allReviews = $allReviewsQuery->fetchAll(PDO::FETCH_ASSOC);
    error_log("Wszystkie opinie: " . print_r($allReviews, true));

    // Pobieranie liczby pacjentów
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie liczby wyników
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM results");
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie liczby lekarzy
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors");
    $totalDoctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie liczby unikalnych specjalizacji (oddziałów)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT specjalizacja) as total FROM doctors");
    $totalDepartments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pobieranie listy wszystkich specjalizacji
    $stmt = $pdo->query("SELECT DISTINCT specjalizacja FROM doctors ORDER BY specjalizacja");
    $specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Pobieranie średniej oceny - najpierw sprawdzamy wszystkie opinie
    $checkReviewsQuery = $pdo->query("
        SELECT 
            COUNT(*) as total_count,
            COUNT(CASE WHEN status = 'zatwierdzona' THEN 1 END) as approved_count,
            AVG(CASE WHEN status = 'zatwierdzona' THEN ocena END) as avg_rating
        FROM reviews
    ");
    $reviewStats = $checkReviewsQuery->fetch(PDO::FETCH_ASSOC);
    error_log("Statystyki opinii: " . print_r($reviewStats, true));

    // Pobieranie średniej oceny
    $stmt = $pdo->query("
        SELECT 
            COALESCE(AVG(ocena), 0) as average,
            COUNT(*) as total_reviews
        FROM reviews 
        WHERE status = 'zatwierdzona'
    ");
    $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
    $averageRating = round($ratingData['average'], 1);
    $totalReviews = $ratingData['total_reviews'];

    // Pobieranie ostatnich 3 zatwierdzonych opinii
    $stmt = $pdo->query("
        SELECT 
            r.ocena,
            r.tresc,
            u.imie,
            u.nazwisko,
            r.data_utworzenia,
            r.status
        FROM reviews r 
        LEFT JOIN users u ON r.uzytkownik_id = u.id 
        WHERE r.status = 'zatwierdzona' 
        ORDER BY r.data_utworzenia DESC 
        LIMIT 3
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Pobrane opinie: " . print_r($reviews, true));

    $stats = [
        'patients' => $totalPatients,
        'results' => $totalResults,
        'doctors' => $totalDoctors,
        'departments' => $totalDepartments,
        'specializations' => $specializations,
        'averageRating' => $averageRating,
        'totalReviews' => $totalReviews,
        'reviews' => $reviews,
        'debug' => [
            'reviewStats' => $reviewStats,
            'allReviewsCount' => count($allReviews)
        ]
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($stats);

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => 'Błąd bazy danych',
        'message' => 'Nie można połączyć się z bazą danych. Sprawdź ustawienia połączenia.',
        'debug' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("Błąd konfiguracji: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => 'Błąd konfiguracji',
        'message' => $e->getMessage()
    ]);
}
?> 