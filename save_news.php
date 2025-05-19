<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy wszystkie wymagane pola zostały wypełnione
if (!isset($_POST['tytul']) || !isset($_POST['tresc']) || !isset($_POST['status'])) {
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

    // Przygotowanie danych
    $tytul = trim($_POST['tytul']);
    $tresc = trim($_POST['tresc']);
    $status = $_POST['status'];
    $data_publikacji = date('Y-m-d H:i:s');
    $autor_id = $_SESSION['user_id'];

    // Obsługa zdjęcia
    $zdjecie_path = null;
    if (isset($_FILES['zdjecie']) && $_FILES['zdjecie']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['zdjecie']['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Niedozwolony typ pliku. Dozwolone formaty: JPG, PNG, GIF']);
            exit();
        }

        if ($_FILES['zdjecie']['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Plik jest zbyt duży. Maksymalny rozmiar: 5MB']);
            exit();
        }

        $upload_dir = 'uploads/news/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['zdjecie']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $zdjecie_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['zdjecie']['tmp_name'], $zdjecie_path)) {
            echo json_encode(['success' => false, 'message' => 'Błąd podczas przesyłania pliku']);
            exit();
        }
    }

    // Wstawienie wiadomości do bazy danych
    $stmt = $conn->prepare("
        INSERT INTO news (tytul, tresc, zdjecie, status, data_publikacji, autor_id)
        VALUES (:tytul, :tresc, :zdjecie, :status, :data_publikacji, :autor_id)
    ");

    $stmt->bindParam(':tytul', $tytul);
    $stmt->bindParam(':tresc', $tresc);
    $stmt->bindParam(':zdjecie', $zdjecie_path);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':data_publikacji', $data_publikacji);
    $stmt->bindParam(':autor_id', $autor_id);

    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Wiadomość została zapisana pomyślnie']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 