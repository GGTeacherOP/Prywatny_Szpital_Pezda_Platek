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
    
    // Zapytanie SQL łączące tabele users i doctors
    $sql = "SELECT u.imie, u.nazwisko, d.specjalizacja, d.tytul_naukowy, d.opis, d.zdjecie, d.numer_licencji 
            FROM users u 
            INNER JOIN doctors d ON u.id = d.uzytkownik_id 
            WHERE u.funkcja = 'lekarz' AND u.status = 'aktywny'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Pobieranie wyników
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pobieranie unikalnych specjalizacji
    $sql_spec = "SELECT DISTINCT specjalizacja FROM doctors WHERE specjalizacja IS NOT NULL AND specjalizacja != ''";
    $stmt_spec = $conn->prepare($sql_spec);
    $stmt_spec->execute();
    $specializations = $stmt_spec->fetchAll(PDO::FETCH_COLUMN);
    
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

    // Dodajemy informację o liczbie znalezionych lekarzy i specjalizacjach
    $response = [
        'success' => true,
        'count' => count($doctors),
        'doctors' => $doctors,
        'specializations' => $specializations
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