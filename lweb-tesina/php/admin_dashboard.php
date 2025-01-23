<?php
session_start();
require_once('connessione.php');

// verifica se l'utente è loggato e se è un admin
if (!isset($_SESSION['username']) || $_SESSION['ruolo'] !== 'admin') {
    // se non è un admin, reindirizza al login
    header("Location: login.php");
    exit();
}

// funzione che mostra tutte le richieste di crediti dal file XML
function mostraRichiesteCrediti() {
    $xml_file = '../xml/richieste_crediti.xml';
    if (!file_exists($xml_file)) {
        echo "<p>Nessuna richiesta di crediti presente.</p>";
        return;
    }

    $xml = simplexml_load_file($xml_file);
    $richieste_in_attesa = [];
    $richieste_processate = [];
    
    // dividiamo le richieste in due array
    foreach ($xml->richiesta as $richiesta) {
        if ((string)$richiesta->status === 'in attesa') {
            $richieste_in_attesa[] = $richiesta;
        } else {
            $richieste_processate[] = $richiesta;
        }
    }

    // sezione richieste in attesa
    echo "<h2 style=\"text-align: center;\">Richieste in Attesa di Approvazione</h2>";
    if (empty($richieste_in_attesa)) {
        echo "<p>Non ci sono richieste in attesa.</p>";
    } else {
        echo "<div class='richieste-container'>";
        foreach ($richieste_in_attesa as $richiesta) {
            echo "<div class='richiesta-card status-in-attesa'>";
            echo "<h3>Richiesta da: " . (string)$richiesta->username . "</h3>";
            echo "<p>Crediti richiesti: €" . number_format((float)$richiesta->crediti, 2) . "</p>";
            echo "<p>Data richiesta: " . date('d/m/Y', strtotime((string)$richiesta->data)) . "</p>";
            
            echo "<form method='POST' class='azioni-form'>";
            echo "<input type='hidden' name='username' value='" . (string)$richiesta->username . "'>";
            echo "<input type='hidden' name='crediti' value='" . (float)$richiesta->crediti . "'>";
            echo "<input type='hidden' name='data' value='" . (string)$richiesta->data . "'>";
            echo "<button type='submit' name='approva' class='btn approva'>Approva</button>";
            echo "<button type='submit' name='rifiuta' class='btn rifiuta'>Rifiuta</button>";
            echo "</form>";
            echo "</div>";
        }
        echo "</div>";
    }

    // sezione delle richieste processate (espandibile)
    echo "<div class='richieste-processate-section'>";
    echo "<button class='toggle-btn' onclick='toggleProcessate()'>
            Mostra Cronologia Richieste Processate 
            <span id='toggle-icon'>▼</span>
          </button>";
    
    echo "<div id='richieste-processate' class='richieste-container hidden'>";
    if (empty($richieste_processate)) {
        echo "<p>Non ci sono richieste processate.</p>";
    } else {
        foreach ($richieste_processate as $richiesta) {
            $status = (string)$richiesta->status;
            echo "<div class='richiesta-card status-$status'>";
            echo "<h3>Richiesta da: " . (string)$richiesta->username . "</h3>";
            echo "<p>Crediti richiesti: €" . number_format((float)$richiesta->crediti, 2) . "</p>";
            echo "<p>Data richiesta: " . date('d/m/Y', strtotime((string)$richiesta->data)) . "</p>";
            echo "<p>Stato: " . ucfirst($status) . "</p>";
            echo "</div>";
        }
    }
    echo "</div>";
    echo "</div>";
}

// gestione delle azioni sui crediti
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approva']) || isset($_POST['rifiuta'])) {
        $xml_file = '../xml/richieste_crediti.xml';
        $xml = simplexml_load_file($xml_file);
        
        $username = $_POST['username'];
        $crediti = $_POST['crediti'];
        $data = $_POST['data'];
        $nuovoStatus = isset($_POST['approva']) ? 'approvata' : 'rifiutata';
        
        foreach ($xml->richiesta as $richiesta) {
            if ((string)$richiesta->username === $username && 
                (string)$richiesta->data === $data && 
                (float)$richiesta->crediti === (float)$crediti) {
                
                $richiesta->status = $nuovoStatus;
                
                // se viene approvata, aggiorna i crediti nel database
                if ($nuovoStatus === 'approvata') {
                    $query = "UPDATE utenti SET crediti = crediti + ? WHERE username = ?";
                    $stmt = $connessione->prepare($query);
                    $stmt->bind_param("ds", $crediti, $username);
                    $stmt->execute();
                }
                
                break;
            }
        }
        
        $xml->asXML($xml_file);
        header("Location: admin_dashboard.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <style>
        .richieste-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .richiesta-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-in-attesa { border-left: 4px solid #ffd700; }
        .status-approvata { border-left: 4px solid #4CAF50; }
        .status-rifiutata { border-left: 4px solid #f44336; }
        
        .azioni-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }
        
        .approva { background-color: #4CAF50; }
        .rifiuta { background-color: #f44336; }
        
        h1, h2 { color: #333; }
        
        .nav-menu {
            background: #2196F3;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-menu .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .nav-menu h1 {
            color: white;
            margin: 0;
            font-size: 24px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links a.logout {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav-links a.logout:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .richieste-processate-section {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .toggle-btn {
            width: 100%;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: left;
            font-size: 1.1em;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }
        
        .toggle-btn:hover {
            background: #e9ecef;
        }
        
        .hidden {
            display: none;
        }
        
        #toggle-icon {
            float: right;
            transition: transform 0.3s ease;
        }
        
        .rotate {
            transform: rotate(180deg);
        }
        
        h2 {
            margin-top: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2196F3;
        }
    </style>
</head>
<body>
    <nav class="nav-menu">
        <div class="container">
            <h1>Dashboard Amministratore</h1>
            <div class="nav-links">
                <a href="gestione_utenti.php">
                    <i class="fas fa-users"></i> Gestione Utenti
                </a>
                <a href="gestione_faq.php">
                    <i class="fas fa-question-circle"></i> Gestione FAQ
                </a>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <?php mostraRichiesteCrediti(); ?>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <script>
        function toggleProcessate() {
            const richiesteProcessate = document.getElementById('richieste-processate');
            const toggleIcon = document.getElementById('toggle-icon');
            richiesteProcessate.classList.toggle('hidden');
            toggleIcon.classList.toggle('rotate');
        }
    </script>
</body>
</html>
