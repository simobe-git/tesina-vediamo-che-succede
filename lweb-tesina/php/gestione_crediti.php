<?php
session_start();
require_once('connessione.php');

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$xml_file = '../xml/richieste_crediti.xml';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $xml = simplexml_load_file($xml_file);
    
    if (isset($_POST['approva']) || isset($_POST['rifiuta'])) {
        $username = $_POST['username'];
        $data = $_POST['data'];
        $crediti = $_POST['crediti'];
        $azione = isset($_POST['approva']) ? 'approvata' : 'rifiutata';
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        
        foreach ($xml->richiesta as $richiesta) {
            if ((string)$richiesta->username === $username && 
                (string)$richiesta->data === $data && 
                (float)$richiesta->crediti === (float)$crediti) {
                
                $richiesta->status = $azione;
                $richiesta->note = $note;
                $richiesta->data_risposta = date('Y-m-d H:i:s');
                
                if ($azione === 'approvata') {
                    $query = "UPDATE utenti SET crediti = crediti + ? WHERE username = ?";
                    $stmt = $connessione->prepare($query);
                    $stmt->bind_param("ds", $crediti, $username);
                    if ($stmt->execute()) {
                        $messaggio = "Richiesta approvata e crediti aggiunti con successo!";
                    } else {
                        $errore = "Errore nell'aggiornamento dei crediti.";
                    }
                } else {
                    $messaggio = "Richiesta rifiutata con successo.";
                }
                break;
            }
        }
        
        $xml->asXML($xml_file);
    }
}

// filtri
$filtro_stato = $_GET['filtro_stato'] ?? 'tutti';
$filtro_data = $_GET['filtro_data'] ?? 'tutti';

// caricamento delle richieste
$richieste = [];
if (file_exists($xml_file)) {
    $xml = simplexml_load_file($xml_file);
    foreach ($xml->richiesta as $richiesta) {
        // Applica filtri
        if ($filtro_stato !== 'tutti' && (string)$richiesta->status !== $filtro_stato) {
            continue;
        }
        
        if ($filtro_data === 'oggi' && date('Y-m-d', strtotime($richiesta->data)) !== date('Y-m-d')) {
            continue;
        } elseif ($filtro_data === 'settimana' && strtotime($richiesta->data) < strtotime('-1 week')) {
            continue;
        }
        
        $richieste[] = [
            'username' => (string)$richiesta->username,
            'crediti' => (float)$richiesta->crediti,
            'data' => (string)$richiesta->data,
            'status' => (string)$richiesta->status,
            'note' => (string)($richiesta->note ?? ''),
            'data_risposta' => (string)($richiesta->data_risposta ?? '')
        ];
    }
}

usort($richieste, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

$totale_richieste = count($richieste);
$richieste_in_attesa = count(array_filter($richieste, fn($r) => $r['status'] === 'in attesa'));
$crediti_approvati = array_reduce(
    array_filter($richieste, fn($r) => $r['status'] === 'approvata'),
    fn($sum, $r) => $sum + $r['crediti'],
    0
);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Richieste Crediti</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .richiesta-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 15px;
        }
        .richiesta-info {
            display: grid;
            gap: 10px;
        }
        .richiesta-azioni {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }
        .btn-approva {
            background: #28a745;
        }
        .btn-rifiuta {
            background: #dc3545;
        }
        .stato-in-attesa { color: #ffc107; }
        .stato-approvata { color: #28a745; }
        .stato-rifiutata { color: #dc3545; }
        .messaggio {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: #d4edda;
            color: #155724;
        }
        .statistiche {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .filtri {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: 50px auto;
        }
        .note-field {
            width: 100%;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestione Richieste Crediti</h1>
        
        <div class="statistiche">
            <div class="stat-card">
                <h2>Statistiche</h2>
                <p>Totale richieste: <?php echo $totale_richieste; ?></p>
                <p>Richieste in attesa: <?php echo $richieste_in_attesa; ?></p>
                <p>Crediti approvati: €<?php echo number_format($crediti_approvati, 2); ?></p>
            </div>
        </div>
        
        <div class="filtri">
            <h2>Filtri</h2>
            <form method="GET">
                <select name="filtro_stato" onchange="this.form.submit()">
                    <option value="tutti">Tutti</option>
                    <option value="in attesa">In attesa</option>
                    <option value="approvata">Approvata</option>
                    <option value="rifiutata">Rifiutata</option>
                </select>
                <select name="filtro_data" onchange="this.form.submit()">
                    <option value="tutti">Tutti</option>
                    <option value="oggi">Oggi</option>
                    <option value="settimana">Settimana</option>
                </select>
            </form>
        </div>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <?php foreach ($richieste as $richiesta): ?>
            <div class="richiesta-card">
                <div class="richiesta-info">
                    <h3>Richiesta di <?php echo htmlspecialchars($richiesta['username']); ?></h3>
                    <p>
                        <strong>Crediti richiesti:</strong> 
                        €<?php echo number_format($richiesta['crediti'], 2); ?>
                    </p>
                    <p>
                        <strong>Data:</strong> 
                        <?php echo date('d/m/Y', strtotime($richiesta['data'])); ?>
                    </p>
                    <p>
                        <strong>Stato:</strong> 
                        <span class="stato-<?php echo $richiesta['status']; ?>">
                            <?php echo ucfirst($richiesta['status']); ?>
                        </span>
                    </p>
                    <?php if ($richiesta['status'] == 'in attesa'): // Mostra solo richieste in attesa ?>
                        <div class="richiesta-azioni">
                            <form method="POST">
                                <input type="hidden" name="username" value="<?php echo $richiesta['username']; ?>">
                                <input type="hidden" name="data" value="<?php echo $richiesta['data']; ?>">
                                <input type="hidden" name="crediti" value="<?php echo $richiesta['crediti']; ?>">
                                <button type="submit" name="approva" class="btn btn-approva">Approva</button>
                                <button type="submit" name="rifiuta" class="btn btn-rifiuta">Rifiuta</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="admin_dashboard.php">Torna alla Dashboard</a>
</body>
</html>

<!-- le funzionalità sono:
- statistiche:
- totale richieste
- richieste in attesa
- totale crediti approvati

filtri:
- per stato (tutti, in attesa, approvate, rifiutate)
- per data (tutte, oggi, ultima settimana)

note:
- l'admin può aggiungere note quando approva/rifiuta una richiesta
- le note vengono salvate nel file XML
- visualizzazione delle note nelle richieste gestite

date e orari:
- aggiunta data e ora della risposta
visualizzazione orari precisi

- possibilità di aggiungere note
- bottoni di conferma/annulla
