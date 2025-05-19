<?php
// Połączenie z bazą danych
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sprawdzenie czy formularz został wysłany
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $pin = $_POST['pin'];

        // Pobieranie wyniku na podstawie emaila i PINu
        $stmt = $conn->prepare("
            SELECT r.*, u.email 
            FROM results r
            JOIN patients p ON r.pacjent_id = p.id
            JOIN users u ON p.uzytkownik_id = u.id
            WHERE u.email = :email AND r.pin = :pin
        ");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pin', $pin);
        $stmt->execute();
        $wynik = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($wynik) {
            // Jeśli wynik jest gotowy, pobierz plik
            if ($wynik['status'] === 'gotowy' && $wynik['plik_wyniku']) {
                $plik = $wynik['plik_wyniku'];
                $sciezka_pliku = 'uploads/wyniki/' . $plik;

                if (file_exists($sciezka_pliku)) {
                    // Ustawienie nagłówków do pobrania pliku
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="wynik_' . $wynik['typ_badania'] . '_' . date('Y-m-d', strtotime($wynik['data_wystawienia'])) . '.pdf"');
                    header('Content-Length: ' . filesize($sciezka_pliku));
                    
                    // Wysłanie pliku
                    readfile($sciezka_pliku);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Plik z wynikami nie został znaleziony']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Wyniki nie są jeszcze gotowe']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Nie znaleziono wyników dla podanego adresu email i PINu']);
        }
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 