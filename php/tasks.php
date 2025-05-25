<?php
session_start();
require_once 'config.php';

// Sprawdzenie czy użytkownik jest zalogowany i jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['funkcja'] !== 'administrator') {
    header('Location: ../logowanie.php');
    exit();
}

// Funkcja do pobierania listy pracowników obsługi
function getStaffMembers($conn) {
    try {
        $sql = "SELECT s.id, u.imie, u.nazwisko, s.stanowisko 
                FROM staff s 
                JOIN users u ON s.uzytkownik_id = u.id 
                WHERE u.status = 'aktywny'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Błąd SQL: " . $e->getMessage());
        return [];
    }
}

// Funkcja do pobierania listy zadań
function getTasks($conn) {
    try {
        $sql = "SELECT t.*, u.imie, u.nazwisko, s.stanowisko 
                FROM tasks t 
                JOIN staff s ON t.pracownik_id = s.id 
                JOIN users u ON s.uzytkownik_id = u.id 
                ORDER BY t.data_zadania DESC, t.godzina_rozpoczecia";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Błąd SQL: " . $e->getMessage());
        return [];
    }
}

// Obsługa dodawania nowego zadania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    try {
        $pracownik_id = $_POST['pracownik_id'];
        $numer_pomieszczenia = $_POST['numer_pomieszczenia'];
        $typ_zadania = $_POST['typ_zadania'];
        $opis_zadania = $_POST['opis_zadania'];
        $data_zadania = $_POST['data_zadania'];
        $godzina_rozpoczecia = $_POST['godzina_rozpoczecia'];
        $godzina_zakonczenia = $_POST['godzina_zakonczenia'];

        $sql = "INSERT INTO tasks (pracownik_id, numer_pomieszczenia, typ_zadania, opis_zadania, 
                data_zadania, godzina_rozpoczecia, godzina_zakonczenia, status) 
                VALUES (:pracownik_id, :numer_pomieszczenia, :typ_zadania, :opis_zadania, 
                :data_zadania, :godzina_rozpoczecia, :godzina_zakonczenia, 'do_wykonania')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pracownik_id' => $pracownik_id,
            ':numer_pomieszczenia' => $numer_pomieszczenia,
            ':typ_zadania' => $typ_zadania,
            ':opis_zadania' => $opis_zadania,
            ':data_zadania' => $data_zadania,
            ':godzina_rozpoczecia' => $godzina_rozpoczecia,
            ':godzina_zakonczenia' => $godzina_zakonczenia
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Zadanie zostało dodane pomyślnie']);
    } catch (PDOException $e) {
        error_log("Błąd SQL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas dodawania zadania: ' . $e->getMessage()]);
    }
    exit();
}

// Obsługa aktualizacji statusu zadania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $task_id = $_POST['task_id'];
        $new_status = $_POST['status'];

        $sql = "UPDATE tasks SET status = :status WHERE id = :task_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':status' => $new_status,
            ':task_id' => $task_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Status zadania został zaktualizowany']);
    } catch (PDOException $e) {
        error_log("Błąd SQL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas aktualizacji statusu: ' . $e->getMessage()]);
    }
    exit();
}

// Pobieranie danych do wyświetlenia
$staff_members = getStaffMembers($conn);
$tasks = getTasks($conn);

// Zwracanie danych w formacie JSON dla żądań AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'staff' => $staff_members,
        'tasks' => $tasks
    ]);
    exit();
}
?> 