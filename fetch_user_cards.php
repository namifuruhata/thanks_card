<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

$email = $_POST['email'] ?? '';

$userCards = [];
if ($email != '') {
    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if (isset($row[3]) && $row[3] == $email) {
            $userCards[] = ['name' => $row[0], 'email' => $row[1], 'point' => $row[2]];
        }
    }
    fclose($file);
}

header('Content-Type: application/json');
echo json_encode($userCards);



?>
