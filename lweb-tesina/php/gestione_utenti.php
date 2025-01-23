<?php
session_start();
require_once('connessione.php');

if (!isset($_SESSION['ruolo']) || ($_SESSION['ruolo'] != 'admin' && $_SESSION['ruolo'] != 'gestore')) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    
    if (isset($_POST['ban_utente']) || isset($_POST['attiva_utente'])) {
        $nuovo_stato = isset($_POST['ban_utente']) ? 'bannato' : 'attivo';
        $query = "UPDATE utenti SET stato = ? WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ss", $nuovo_stato, $username);
        if ($stmt->execute()) {
            $messaggio = "Stato utente aggiornato con successo";
            
            // log dell'azione
            logAzioneAdmin($_SESSION['username'], $nuovo_stato == 'bannato' ? 'ban' : 'attivazione', $username);
            
            // se l'utente viene bannato, terminiamo subito la sua sessione
            if ($nuovo_stato == 'bannato') {
                // forziamo il logout dell'utente bannato se è online
                session_start();
                if (isset($_SESSION['username']) && $_SESSION['username'] == $username) {
                    session_destroy();
                }
            }
        }
    }
    
    elseif (isset($_POST['modifica_dati'])) {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $email = $_POST['email'];
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        
        if ($password) {
            $query = "UPDATE utenti SET nome = ?, cognome = ?, email = ?, password = ? WHERE username = ?";
            $stmt = $connessione->prepare($query);
            $stmt->bind_param("sssss", $nome, $cognome, $email, $password, $username);
        } else {
            $query = "UPDATE utenti SET nome = ?, cognome = ?, email = ? WHERE username = ?";
            $stmt = $connessione->prepare($query);
            $stmt->bind_param("ssss", $nome, $cognome, $email, $username);
        }
        
        if ($stmt->execute()) {
            $messaggio = "Dati utente aggiornati con successo";
            logAzioneAdmin($_SESSION['username'], 'modifica_dati', $username);
        }
    }
}

// sostituiamo la funzione logAzioneAdmin con una versione che usa XML  ?????
function logAzioneAdmin($admin, $azione, $target) {
    $log_file = '../xml/admin_log.xml';
    
    // se il file non esiste, lo creiamo con la struttura base
    if (!file_exists($log_file)) {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><admin_log></admin_log>');
        $xml->asXML($log_file);
    }
    
    $xml = simplexml_load_file($log_file);
    
    // aggiungiamo la nuova azione
    $log_entry = $xml->addChild('azione');
    $log_entry->addChild('admin', $admin);
    $log_entry->addChild('tipo_azione', $azione);
    $log_entry->addChild('utente_target', $target);
    $log_entry->addChild('data', date('Y-m-d H:i:s'));
    
    $xml->asXML($log_file);
}

// gestione filtri
$filtro_stato = isset($_GET['stato']) ? $_GET['stato'] : 'tutti';
$ricerca = isset($_GET['ricerca']) ? $_GET['ricerca'] : '';

// costruziamo delle query in base ai filtri
$query = "SELECT * FROM utenti WHERE tipo_utente = 'cliente'";
if ($filtro_stato != 'tutti') {
    $query .= " AND stato = '$filtro_stato'";
}
if ($ricerca) {
    $query .= " AND (username LIKE '%$ricerca%' OR nome LIKE '%$ricerca%' 
                OR cognome LIKE '%$ricerca%' OR email LIKE '%$ricerca%')";
}
$query .= " ORDER BY data_registrazione DESC";

// paginazione
$per_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $per_pagina;

$total_query = str_replace("SELECT *", "SELECT COUNT(*)", $query);
$total_result = $connessione->query($total_query);
$total_utenti = $total_result->fetch_row()[0];
$total_pagine = ceil($total_utenti / $per_pagina);

$query .= " LIMIT $offset, $per_pagina";
$result = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Utenti</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        .nav-menu {
            background: #2196F3;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-menu .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            padding: 0 20px;
        }

        .nav-menu h1 {
            color: white;
            margin: 0;
            font-size: 2.5em;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .nav-links a.logout {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links a.logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .nav-links i {
            font-size: 1.1em;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .utenti-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .utente-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .utente-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .utente-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .utente-info {
            flex-grow: 1;
        }

        .utente-username {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin: 0;
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #2196F3;
            outline: none;
        }

        .stato-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .stato-attivo {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .stato-bannato {
            background-color: #ffebee;
            color: #c62828;
        }

        .azioni-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background: #2196F3;
            color: white;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .filtri {
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-box {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        
        .stato-filter {
            padding: 8px;
            margin-left: 10px;
        }
        
        .pagination {
            margin: 20px 0;
            text-align: center;
        }
        
        .pagination a {
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination a.active {
            background: #2196F3;
            color: white;
            border-color: #2196F3;
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
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="nav-menu">
        <div class="container">
            <h1>Gestione Utenti</h1>
            <div class="nav-links">
                <a href="admin_dashboard.php">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="gestione_faq.php">
                    <i class="fas fa-question-circle"></i> FAQ
                </a>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($messaggio)): ?>
            <div class="alert alert-success"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <div class="filtri">
            <form method="GET" class="search-form">
                <input type="text" name="ricerca" placeholder="Cerca utenti..." 
                       class="search-box" value="<?php echo htmlspecialchars($ricerca); ?>">
                
                <select name="stato" class="stato-filter">
                    <option value="tutti" <?php echo $filtro_stato == 'tutti' ? 'selected' : ''; ?>>
                        Tutti gli utenti
                    </option>
                    <option value="attivo" <?php echo $filtro_stato == 'attivo' ? 'selected' : ''; ?>>
                        Utenti attivi
                    </option>
                    <option value="bannato" <?php echo $filtro_stato == 'bannato' ? 'selected' : ''; ?>>
                        Utenti bannati
                    </option>
                </select>
                
                <button type="submit" class="btn btn-primary">Filtra</button>
            </form>
        </div>

        <div class="utenti-grid">
            <?php while ($utente = $result->fetch_assoc()): ?>
                <div class="utente-card">
                    <div class="utente-header">
                        <div class="utente-info">
                            <h3 class="utente-username"><?php echo htmlspecialchars($utente['username']); ?></h3>
                            <span class="stato-badge stato-<?php echo $utente['stato']; ?>">
                                <?php echo ucfirst($utente['stato']); ?>
                            </span>
                        </div>
                    </div>

                    <form method="POST" id="form-<?php echo $utente['username']; ?>">
                        <input type="hidden" name="username" value="<?php echo $utente['username']; ?>">
                        
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($utente['nome']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Cognome</label>
                            <input type="text" name="cognome" value="<?php echo htmlspecialchars($utente['cognome']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($utente['email']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Nuova Password (lasciare vuoto per non modificare)</label>
                            <input type="password" name="password" placeholder="••••••••">
                        </div>
                        
                        <div class="azioni-container">
                            <button type="submit" name="modifica_dati" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salva
                            </button>
                            
                            <?php if ($utente['stato'] == 'attivo'): ?>
                                <button type="button" class="btn btn-danger" 
                                        onclick="confermaBan('<?php echo htmlspecialchars($utente['username']); ?>')">
                                    <i class="fas fa-ban"></i> Banna
                                </button>
                            <?php else: ?>
                                <button type="submit" name="attiva_utente" class="btn btn-success">
                                    <i class="fas fa-user-check"></i> Attiva
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pagine; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>&stato=<?php echo $filtro_stato; ?>&ricerca=<?php echo urlencode($ricerca); ?>" 
                   class="<?php echo $pagina == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <script>
    function confermaBan(username) {
        if (confirm('Sei sicuro di voler bannare l\'utente ' + username + '?')) {
            // se l'admin conferma, invia il form
            const form = document.getElementById('form-' + username);
            const banInput = document.createElement('input');
            banInput.type = 'hidden';
            banInput.name = 'ban_utente';
            banInput.value = '1';
            form.appendChild(banInput);
            form.submit();
        }
    }
    </script>
</body>
</html>