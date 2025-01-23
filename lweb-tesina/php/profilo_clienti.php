<?php
session_start();
require_once('connessione.php');
require_once('funzioni_reputazioni.php');

// verifichiamo dapprima che l'utente sia un gestore
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'gestore') {
    header('Location: login.php');
    exit();
}

// recuperiamo tutti gli utenti
$query = "SELECT * FROM utenti WHERE tipo_utente = 'cliente'";
$result = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo Clienti</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .utente-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px;
            display: inline-block;
            width: calc(33% - 40px);
            vertical-align: top;
        }
        .utente-header {
            font-weight: bold;
            font-size: 1.2em;
        }
        .reputazione {
            margin: 10px 0;
        }
        .btn {
            padding: 8px 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .navbar {
            display: flex; 
            justify-content: flex-end; 
            background-color: #333; 
            padding: 10px 0; 
        }

        .navbar a {
            color: white; 
            text-align: center; 
            padding: 10px 15px; 
            text-decoration: none; 
            margin: 0 5px; 
        }

        .navbar a:hover {
            background-color: #ddd; 
            color: black; 
        }

        .spazio {
            margin-top: 50px; 
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="moderazione_contributi.php">Moderazione Contributi</a>
        <a href="domande.php">Domande</a>
        <a href="gestione_offerte.php">Gestione Offerte</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container spazio">
        <h1 style="text-align: center;">Profilo Clienti</h1>
        <div class="utenti-grid">
            <?php while ($utente = $result->fetch_assoc()): ?>
                <?php $reputazione = calcolaReputazione($utente['username']); ?>
                <div class="utente-card">
                    <div class="utente-header"><?php echo htmlspecialchars($utente['username']); ?></div>
                    <div class="reputazione">Reputazione: <?php echo round($reputazione['punteggio'], 2); ?> (<?php echo $reputazione['numero_giudizi']; ?> giudizi)</div>
                    <button class="btn" onclick="window.location.href='storico_acquisti.php?username=<?php echo urlencode($utente['username']); ?>'">Visualizza Storico Acquisti</button>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>