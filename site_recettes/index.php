<?php
session_start();
require_once 'config.php';
if (!isset($pdo) || !$pdo) {
    die('Erreur : la connexion à la base de données a échoué.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Identifiants invalides.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1>Connexion</h1>
            <?php if ($error): ?>
                <div class="login-error"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <form action="" method="post">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required autocomplete="username"><br>
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"><br>
                <button type="submit">Se connecter</button>
            </form>
            <div style="margin-top:18px;">
                <a href="inscription.php" style="color:#1976d2;">Créer un compte</a>
            </div>
        </div>
    </div>
</body>
</html>
