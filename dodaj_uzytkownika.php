<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "szpital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tworzenie tabeli users jeśli nie istnieje
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        haslo VARCHAR(255) NOT NULL,
        funkcja VARCHAR(50) NOT NULL,
        status VARCHAR(20) DEFAULT 'aktywny'
    )";
    $conn->exec($sql);
    
    // Najpierw usuńmy starego użytkownika jeśli istnieje
    $stmt = $conn->prepare("DELETE FROM users WHERE email = :email");
    $stmt->bindParam(':email', 'lekarz@szpital.pl');
    $stmt->execute();
    
    // Hasło do zahaszowania
    $plain_password = "1234";
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    
    // Dodanie testowego użytkownika
    $stmt = $conn->prepare("INSERT INTO users (email, haslo, funkcja) VALUES (:email, :haslo, :funkcja)");
    $stmt->bindParam(':email', 'lekarz@szpital.pl');
    $stmt->bindParam(':haslo', $hashed_password);
    $stmt->bindParam(':funkcja', 'lekarz');
    
    $stmt->execute();
    echo "Testowy użytkownik został dodany pomyślnie!<br>";
    echo "Email: lekarz@szpital.pl<br>";
    echo "Hasło: 1234<br>";
    echo "Hash hasła: " . $hashed_password . "<br>";
    
} catch(PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}
?> 