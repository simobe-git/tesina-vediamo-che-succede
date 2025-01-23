<?php
session_start();
require_once('connessione.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Recupera i crediti attuali dell'utente
$query = "SELECT crediti FROM utenti WHERE username = ?";
$stmt = $connessione->prepare($query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$crediti_attuali = $result->fetch_assoc()['crediti'];

// Array delle offerte disponibili
$offerte_crediti = [
    // Pacchetti base (1€ = 2 crediti)
    ['crediti' => 20, 'prezzo' => 10],   // Base: 20 crediti
    ['crediti' => 40, 'prezzo' => 20],   // Base: 40 crediti
    ['crediti' => 60, 'prezzo' => 30],   // Base: 60 crediti
    ['crediti' => 100, 'prezzo' => 50],  // Base: 100 crediti
    
    // Pacchetti con bonus
    ['crediti' => 220, 'prezzo' => 100],  // Bonus: 20 crediti extra
    ['crediti' => 450, 'prezzo' => 200],  // Bonus: 50 crediti extra
    ['crediti' => 700, 'prezzo' => 300],  // Bonus: 100 crediti extra
    ['crediti' => 1200, 'prezzo' => 500]  // Bonus: 200 crediti extra
];

// Aggiungi questa funzione per mostrare il tipo di pacchetto
function getTipoPacchetto($crediti, $prezzo) {
    $crediti_base = $prezzo * 2; // conversione standard
    return $crediti_base === $crediti ? 'Pacchetto Base' : 'Pacchetto Premium';
}

// Aggiungi questa funzione per calcolare i crediti bonus
function calcolaBonusCrediti($crediti, $prezzo) {
    $crediti_base = $prezzo * 2; // conversione standard
    $bonus = $crediti - $crediti_base;
    return $bonus > 0 ? $bonus : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acquista_crediti'])) {
    $indice_offerta = $_POST['offerta'];
    if (isset($offerte_crediti[$indice_offerta])) {
        $offerta = $offerte_crediti[$indice_offerta];
        
        // Aggiorna i crediti nel database
        $query = "UPDATE utenti SET crediti = crediti + ? WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ds", $offerta['crediti'], $_SESSION['username']);
        
        if ($stmt->execute()) {
            // Aggiorna i crediti attuali per la visualizzazione
            $crediti_attuali += $offerta['crediti'];
            
            // Aggiungi la richiesta al file XML
            $xml_file = '../xml/richieste_crediti.xml';
            if (file_exists($xml_file)) {
                $xml = simplexml_load_file($xml_file);
            } else {
                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><richiesteCrediti></richiesteCrediti>');
            }
            
            $richiesta = $xml->addChild('richiesta');
            $richiesta->addChild('username', $_SESSION['username']);
            $richiesta->addChild('crediti', $offerta['crediti']);
            $richiesta->addChild('data', date('Y-m-d'));
            $richiesta->addChild('status', 'approvata');
            $richiesta->addChild('prezzo', $offerta['prezzo']);
            $xml->asXML($xml_file);
            
            $messaggio_successo = "<h2 style='text-align: center; font-size: 150%; color: red;'>Hai acquistato con successo {$offerta['crediti']} crediti!</h2>";
        } else {
            $errore = "Si è verificato un errore durante l'acquisto dei crediti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richiedi Crediti - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        /* Stili specifici per la pagina richiesta crediti */
        .container {
            margin-top: 100px; /* Per evitare che il contenuto finisca sotto la navbar fissa */
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .offerte-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .offerta-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .offerta-card:hover {
            transform: translateY(-5px);
        }

        .crediti {
            font-size: 2em;
            color: #2ecc71;
            margin: 15px 0;
            font-weight: bold;
        }

        .prezzo {
            font-size: 1.4em;
            color: #333;
            margin: 15px 0;
        }

        .btn-acquista {
            display: block;
            width: 90%;
            margin: 20px auto 10px;
            padding: 12px;
            background-color: #ff6347;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-acquista:hover {
            background-color: #ff4500;
        }

        .messaggio {
            text-align: center;
            padding: 15px;
            margin: 20px auto;
            max-width: 600px;
            border-radius: 5px;
        }

        .bonus {
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
            font-size: 1.1em;
        }
        
        /* Aggiungi animazione al bonus */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .bonus {
            animation: pulse 2s infinite;
        }

        .tipo-pacchetto {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .tipo-pacchetto.base {
            background-color: #3498db;
            color: white;
        }

        .tipo-pacchetto.premium {
            background-color: #f1c40f;
            color: #2c3e50;
        }

        .offerta-card {
            position: relative;
            /* ... resto degli stili esistenti ... */
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>
    
    <div class="container">
        <h1 style="text-align: center;">Richiedi Crediti</h1>
        
        <?php if (isset($messaggio_successo)): ?>
            <div class="messaggio successo"><?php echo $messaggio_successo; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errore)): ?>
            <div class="messaggio errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <div class="offerte-grid">
            <?php foreach ($offerte_crediti as $indice => $offerta): ?>
                <div class="offerta-card">
                    <div class="tipo-pacchetto <?php echo getTipoPacchetto($offerta['crediti'], $offerta['prezzo']) === 'Pacchetto Premium' ? 'premium' : 'base'; ?>">
                        <?php echo getTipoPacchetto($offerta['crediti'], $offerta['prezzo']); ?>
                    </div>
                    <h2 class="crediti"><?php echo $offerta['crediti']; ?> crediti</h2>
                    <p class="prezzo">Prezzo: <?php echo $offerta['prezzo']; ?> €</p>
                    <?php 
                    $bonus = calcolaBonusCrediti($offerta['crediti'], $offerta['prezzo']);
                    if ($bonus > 0): 
                    ?>
                        <p class="bonus">Bonus: +<?php echo $bonus; ?> crediti extra!</p>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="offerta" value="<?php echo $indice; ?>">
                        <button type="submit" name="acquista_crediti" class="btn-acquista">Acquista</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
