<?php
session_start();
require_once('connessione.php');

//vVerifichiamo che l'utente è un gestore
$isGestore = isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'gestore';

if (!$isGestore) {
    header("Location: login.php"); // reindirizza se non è un gestore
    exit();
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gestore</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body{
            background-color: chartreuse;
        }
        .dashboard-container {
            display: flex;
            flex-wrap: wrap; 
            justify-content: space-around; 
            margin: 20px; 
        }

        .dashboard-box {
            background-color: #ccc; 
            color: #333; 
            padding: 15px; 
            margin: 10px; 
            border-radius: 5px; 
            text-align: center; 
            flex: 0 0 40%; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); 
            transition: transform 0.3s; 
        }

        .dashboard-box:hover {
            transform: scale(1.03); 
        }

        .dashboard-box a {
            color: #333; 
            text-decoration: none; 
            font-size: 18px; 
            display: inline-block; 
            padding: 10px 20px; 
            border-radius: 5px; 
            background-color: white; 
            transition: background-color 0.3s; 
        }

        .dashboard-box a:hover {
            background-color: #666; 
        }

        h1 {
            text-align: center; 
            color: #333;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h1>Lieto di rivederti, <strong style="color: blue;"><?php echo $_SESSION['username'] ?></strong></h1>
    <h1 style="color: red;">Gestisci la tua applicazione</h1>
    <div class="dashboard-container">
        <div class="dashboard-box">
            <h2>Gestione Offerte</h2>
            <p>Modifica e gestisci le offerte attive per i videogiochi.</p>
            <a href="gestione_offerte.php">Vai alla gestione</a>
        </div>
        <div class="dashboard-box">
            <h2>Profilo Clienti</h2>
            <p>Visualizza e gestisci i profili dei clienti registrati.</p>
            <a href="profilo_clienti.php">Vai al profilo</a>
        </div>
        <div class="dashboard-box">
            <h2>Moderazione Contributi</h2>
            <p>Controlla e approva i contributi degli utenti.</p>
            <a href="moderazione_contributi.php">Vai alla moderazione</a>
        </div>
        <div class="dashboard-box">
            <h2>Risposte a Domande</h2>
            <p>Gestisci le domande e fornisci risposte agli utenti.</p>
            <a href="domande.php">Vai alle domande</a>
        </div>
    </div>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>