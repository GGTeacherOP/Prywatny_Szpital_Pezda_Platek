<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit();
}

// Sprawdzenie czy otrzymano wymagane dane
if (!isset($_POST['staff_id']) || !isset($_POST['salary'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

$user_id = $_POST['staff_id']; // To jest teraz ID użytkownika
$salary = floatval($_POST['salary']);

// Debugowanie
error_log("Otrzymane staff_id: " . $user_id);
error_log("Otrzymane salary: " . $salary);

// Walidacja danych
if ($salary < 0) {
    echo json_encode(['success' => false, 'message' => 'Pensja nie może być ujemna']);
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

    // Sprawdzenie czy pracownik istnieje
    $stmt = $conn->prepare("
        SELECT id, funkcja
        FROM users
        WHERE id = :user_id AND funkcja IN ('lekarz', 'obsluga')
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pracownik = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pracownik) {
        echo json_encode(['success' => false, 'message' => 'Nie znaleziono pracownika']);
        exit();
    }

    // Sprawdzenie czy istnieje już wpis dla tego pracownika
    $stmt = $conn->prepare("SELECT id FROM salaries WHERE uzytkownik_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $existing_salary = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_salary) {
        // Aktualizacja istniejącego wpisu
        $stmt = $conn->prepare("
            UPDATE salaries 
            SET pensja = :salary 
            WHERE uzytkownik_id = :user_id
        ");
    } else {
        // Dodanie nowego wpisu
        $stmt = $conn->prepare("
            INSERT INTO salaries (uzytkownik_id, pensja) 
            VALUES (:user_id, :salary)
        ");
    }

    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':salary', $salary);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Pensja została zaktualizowana pomyślnie']);

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?> 