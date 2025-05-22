<?php
session_start();
require_once 'config.php';
if (!isset($pdo) || !$pdo) {
    die('Erreur : la connexion à la base de données a échoué. Vérifiez config.php.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($username && $password && $password2) {
        if ($password !== $password2) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            // Vérifier si l'utilisateur existe déjà
            $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Ce nom d\'utilisateur existe déjà.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO utilisateurs (username, password) VALUES (?, ?)');
                $stmt->execute([$username, $hash]);
                header('Location: index.php?register=1');
                exit;
            }
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
    <title>Inscription</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1>Inscription</h1>
            <?php if ($error): ?>
                <div class="login-error"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <form action="" method="post">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required autocomplete="username"><br>
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"><br>
                <label for="password2">Confirmer le mot de passe :</label>
                <input type="password" id="password2" name="password2" required autocomplete="new-password"><br>
                <button type="submit">S'inscrire</button>
            </form>
            <div style="margin-top:18px;">
                <a href="index.php" style="color:#1976d2;">Déjà un compte ? Connexion</a>
            </div>
        </div>
    </div>
</body>
</html>
