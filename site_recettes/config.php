<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'petitescyrilremy.mysql.db';      // exactement comme ça
$dbname = 'petitescyrilremy';             // idem
$user = 'petitescyrilremy';               // sans @ ni suffixe
$pass = 'Boulangerie2006';           // celui que tu viens de définir sur OVH

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
