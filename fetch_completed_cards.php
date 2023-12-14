<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if (isset($_POST['action']) && $_POST['action'] == 'fetch_completed_cards') {
    $email = $_POST['email'];
    $completedCards = [];

    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if ($row[1] == $email && intval($row[2]) >= 18) {
            $completedCards[] = $row[0];
        }
    }
    fclose($file);

    header('Content-Type: application/json');
    echo json_encode($completedCards);
}
?>
