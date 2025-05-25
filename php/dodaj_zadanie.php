<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    header("Location: ../logowanie.php");
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

    // Pobieranie danych z formularza
    $pracownik_id = $_POST['pracownik']; // To jest już ID z tabeli staff
    $pomieszczenie = $_POST['pomieszczenie'];
    $typ = $_POST['typ'];
    $opis = $_POST['opis'];
    $data = $_POST['data'];
    $godzina_start = $_POST['godzina_start'];
    $godzina_koniec = $_POST['godzina_koniec'];

    // Dodawanie zadania do bazy danych
    $sql = "INSERT INTO tasks (pracownik_id, numer_pomieszczenia, typ_zadania, opis_zadania, 
            data_zadania, godzina_rozpoczecia, godzina_zakonczenia, status) 
            VALUES (:pracownik_id, :pomieszczenie, :typ, :opis, :data, :godzina_start, :godzina_koniec, 'do_wykonania')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':pracownik_id' => $pracownik_id,
        ':pomieszczenie' => $pomieszczenie,
        ':typ' => $typ,
        ':opis' => $opis,
        ':data' => $data,
        ':godzina_start' => $godzina_start,
        ':godzina_koniec' => $godzina_koniec
    ]);

    // Przekierowanie z powrotem do panelu administratora
    header("Location: ../panel-admina.php");
    exit();

} catch(Exception $e) {
    echo "Błąd: " . $e->getMessage();
}
?> 