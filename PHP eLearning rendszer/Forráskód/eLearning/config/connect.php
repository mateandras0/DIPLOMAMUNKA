<?php
 
$dbHost = 'localhost';  // Hol található az adatbázis szerver
$dbUser = 'SZBALAZS';       // Adatbázis felhasználó
$dbPass = '10543163';           // Jelszó
$dbName = 'elearning_new';     // Adatbázis neve
 
// Csatlakozás
$con = mysqli_connect($dbHost,$dbUser,$dbPass,$dbName) or die('Adatbázis hiba: '.mysqli_connect_error()) ;
mysqli_set_charset($con, 'utf8');

?>