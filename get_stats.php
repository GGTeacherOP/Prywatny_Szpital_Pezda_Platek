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

    $stats = [
        'patients' => $totalPatients,
        'results' => $totalResults,
        'doctors' => $totalDoctors,
        'departments' => $totalDepartments,
        'specializations' => $specializations
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($stats);

} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => 'Błąd bazy danych',
        'message' => 'Nie można połączyć się z bazą danych. Sprawdź ustawienia połączenia.'
    ]);
} catch(Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => 'Błąd konfiguracji',
        'message' => $e->getMessage()
    ]);
}
?> 