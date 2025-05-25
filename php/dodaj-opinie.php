<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
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

    // Dodawanie opinii o szpitalu
    if (isset($_POST['ocena_szpital']) && isset($_POST['tresc_szpital'])) {
        $user_id = $_SESSION['user_id'];
        $ocena = $_POST['ocena_szpital'];
        $tresc = $_POST['tresc_szpital'];
        
        $sql = "INSERT INTO reviews (uzytkownik_id, ocena, tresc, data_utworzenia, status) 
                VALUES ($user_id, $ocena, '$tresc', NOW(), 'zatwierdzona')";
        $conn->exec($sql);
    }

    // Dodawanie opinii o lekarzu
    if (isset($_POST['lekarz_id']) && isset($_POST['ocena_lekarz']) && isset($_POST['tresc_lekarz'])) {
        $user_id = $_SESSION['user_id'];
        $lekarz_id = $_POST['lekarz_id'];
        $ocena = $_POST['ocena_lekarz'];
        $tresc = $_POST['tresc_lekarz'];
        
        $sql = "INSERT INTO doctor_reviews (uzytkownik_id, lekarz_id, ocena, tresc, data_utworzenia, status) 
                VALUES ($user_id, $lekarz_id, $ocena, '$tresc', NOW(), 'zatwierdzona')";
        $conn->exec($sql);
    }

    // Przekierowanie z powrotem do strony z opiniami
    header("Location: ../opinie.php");
    exit();

} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?> 