<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy wszystkie wymagane pola są wypełnione
if (!isset($_POST['patient_id']) || !isset($_POST['typ_badania']) || !isset($_POST['data_badania']) || !isset($_FILES['plik'])) {
    echo json_encode(['success' => false, 'message' => 'Wszystkie pola są wymagane']);
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

    // Pobieranie ID lekarza
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE uzytkownik_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $lekarz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lekarz) {
        echo json_encode(['success' => false, 'message' => 'Nie znaleziono ID lekarza']);
        exit();
    }

    // Generowanie unikalnego PIN-u
    $pin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Obsługa pliku
    $file = $_FILES['plik'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Sprawdzenie rozszerzenia pliku
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileExt, $allowedExt)) {
        echo json_encode(['success' => false, 'message' => 'Niedozwolony format pliku']);
        exit();
    }

    // Sprawdzenie rozmiaru pliku (max 10MB)
    if ($fileSize > 10485760) {
        echo json_encode(['success' => false, 'message' => 'Plik jest za duży (max 10MB)']);
        exit();
    }

    // Generowanie unikalnej nazwy pliku
    $newFileName = uniqid('wynik_') . '.' . $fileExt;
    $uploadPath = 'uploads/wyniki/' . $newFileName;

    // Tworzenie katalogu jeśli nie istnieje
    if (!file_exists('uploads/wyniki')) {
        mkdir('uploads/wyniki', 0777, true);
    }

    // Przeniesienie pliku
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        // Zapisywanie wyniku do bazy danych
        $stmt = $conn->prepare("
            INSERT INTO results (
                pacjent_id, 
                lekarz_id, 
                typ_badania, 
                data_wystawienia, 
                pin, 
                plik_wyniku, 
                status
            ) VALUES (
                :pacjent_id,
                :lekarz_id,
                :typ_badania,
                :data_wystawienia,
                :pin,
                :plik_wyniku,
                'oczekujący'
            )
        ");

        $stmt->bindParam(':pacjent_id', $_POST['patient_id']);
        $stmt->bindParam(':lekarz_id', $lekarz['id']);
        $stmt->bindParam(':typ_badania', $_POST['typ_badania']);
        $stmt->bindParam(':data_wystawienia', $_POST['data_badania']);
        $stmt->bindParam(':pin', $pin);
        $stmt->bindParam(':plik_wyniku', $newFileName);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Wynik został pomyślnie zapisany']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Błąd podczas zapisywania do bazy danych']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Błąd podczas przesyłania pliku']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 