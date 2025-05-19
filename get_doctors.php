<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");

    // Pobieranie danych lekarzy wraz z informacjami o użytkownikach
    $stmt = $conn->prepare("
        SELECT 
            d.id,
            d.specjalizacja,
            d.tytul_naukowy,
            d.opis,
            d.zdjecie,
            u.imie,
            u.nazwisko
        FROM doctors d
        JOIN users u ON d.uzytkownik_id = u.id
        WHERE u.status = 'aktywny'
        ORDER BY d.specjalizacja, u.nazwisko
    ");
    
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Konwersja zdjęć na base64 jeśli istnieją
    foreach ($doctors as &$doctor) {
        if ($doctor['zdjecie']) {
            // Sprawdź czy dane są w formacie binarnym
            if (is_resource($doctor['zdjecie'])) {
                $doctor['zdjecie'] = stream_get_contents($doctor['zdjecie']);
            }
            
            // Sprawdź czy dane są w formacie base64
            if (base64_encode(base64_decode($doctor['zdjecie'], true)) === $doctor['zdjecie']) {
                $doctor['zdjecie'] = 'data:image/jpeg;base64,' . $doctor['zdjecie'];
            } else {
                // Konwertuj dane binarne na base64
                $doctor['zdjecie'] = 'data:image/jpeg;base64,' . base64_encode($doctor['zdjecie']);
            }
        } else {
            $doctor['zdjecie'] = 'img/about-us/placeholder-user.png';
        }
    }

    // Dodajemy informację o liczbie znalezionych lekarzy
    $response = [
        'success' => true,
        'count' => count($doctors),
        'doctors' => $doctors
    ];

    echo json_encode($response);

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Błąd bazy danych',
        'message' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("Błąd ogólny: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Błąd ogólny',
        'message' => $e->getMessage()
    ]);
}
?> 