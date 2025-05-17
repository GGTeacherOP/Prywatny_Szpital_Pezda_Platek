<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest lekarzem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'lekarz') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit;
}

// Sprawdzenie czy wszystkie wymagane pola są wypełnione
if (!isset($_POST['patient_id']) || !isset($_POST['typ_badania']) || !isset($_POST['data_badania']) || !isset($_POST['opis'])) {
    echo json_encode(['success' => false, 'message' => 'Wszystkie pola są wymagane']);
    exit;
}

try {
    // Połączenie z bazą danych
    $pdo = new PDO('mysql:host=localhost;dbname=szpital;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Przygotowanie do obsługi pliku
    $filePath = null;
    if (isset($_FILES['plik']) && $_FILES['plik']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($_FILES['plik']['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Niedozwolony typ pliku. Dozwolone formaty: PDF, DOC, DOCX, JPG, PNG']);
            exit;
        }

        if ($_FILES['plik']['size'] > $maxFileSize) {
            echo json_encode(['success' => false, 'message' => 'Plik jest za duży. Maksymalny rozmiar to 10MB']);
            exit;
        }

        $uploadDir = 'uploads/wyniki/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['plik']['name']);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['plik']['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Błąd podczas przesyłania pliku']);
            exit;
        }
    }

    // Generowanie unikalnego PIN
    $pin = sprintf('%04d-%04d-%04d', rand(0, 9999), rand(0, 9999), rand(0, 9999));

    // Wstawienie wyniku do bazy danych
    $stmt = $pdo->prepare('INSERT INTO results (pacjent_id, lekarz_id, typ_badania, data_wystawienia, pin, plik_wyniku, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        $_POST['patient_id'],
        $_SESSION['user_id'],
        $_POST['typ_badania'],
        $_POST['data_badania'],
        $pin,
        $filePath,
        'gotowy'
    ]);

    echo json_encode(['success' => true, 'message' => 'Wynik został zapisany pomyślnie']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Wystąpił nieoczekiwany błąd: ' . $e->getMessage()]);
}
?> 