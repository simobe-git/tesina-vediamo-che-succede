<?php 

session_start();
if(isset($_SESSION['statoLogin'])){
    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="login-container">
        <div class="login-form">
            <h2>SIGN <span class="highlight">IN</span></h2>
            <form action="login-form.php" method="POST">
                <?php if(isset($_GET['error'])): ?>
                    <div class="error-message">
                        <?php 
                        if($_GET['error'] === 'banned') {
                            echo "Il tuo account è stato bannato. Contatta l'amministratore per maggiori informazioni.";
                        } else {
                            echo "Email o password non corretti.";
                        }
                        ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group check-forgot">
                    <a href="reset-password.php">Password dimenticata?</a>
                </div>
                <button type="submit" name="login" class="cta-button">SIGN IN</button>
                <p class="signup-link">Non sei iscritto? <a href="registration.php">Sign Up</a></p>
            </form>
        </div>
        <div class="login-image">
            <img src="../isset/background-login.jpg" alt="background">
        </div>
    </div>
</body>
</html>
