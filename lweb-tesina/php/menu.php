<?php
$current_page = basename($_SERVER['PHP_SELF']);

// array contenente tutte le voci del menu base con icone
$menu_items = [
    
    'catalogo.php' => ['label' => 'Catalogo'],
    'offerte.php' => ['label' => 'Offerte']
];

// aggiungiamo le voci condizionali in base al login
if (isset($_SESSION['statoLogin'])) {
    $menu_items['profilo.php'] = ['label' => '<i class="fa-solid fa-user"></i>'];
} else {
    $menu_items['login.php'] = ['label' => 'Login'];
}

// pagine che dovrebbero escludere determinate voci
$profile_pages = ['profilo.php', 'modifica_profilo.php', 'modifica_password.php'];
$auth_pages = ['login.php', 'registration.php', 'reset-password.php'];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Assicurati di avere un file CSS per lo stile -->
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <a href="home.php"><i class="fa-solid fa-house"></i> GameShop</a>
    </div>
    <ul class="nav-links">
        <?php
        foreach ($menu_items as $page => $data) {
            if ($page !== $current_page && 
                !($page === 'profilo.php' && in_array($current_page, $profile_pages)) &&
                !($page === 'login.php' && in_array($current_page, $auth_pages))) {
                
                if (!($page === 'login.php' && isset($_SESSION['statoLogin']))) {
                    echo "<li><a href='$page'>{$data['label']}</a></li>";
                }
            }
        }

        if (isset($_SESSION['statoLogin'])) {
            if (isset($_SESSION['ruolo'])) {
                if ($_SESSION['ruolo'] === 'cliente') {
                    echo "<li><a href='carrello.php'><i class='fa-solid fa-cart-shopping'></i></a></li>";
                } elseif ($_SESSION['ruolo'] === 'admin') {
                    echo "<li><a href='admin_dashboard.php'>Dashboard</a></li>";
                }
            }
            echo "<li><a href='logout.php'><i class='fas fa-sign-out-alt'></i></a></li>";
        }
        ?>
    </ul>
    <div class="hamburger-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav>
</body>
</html>
