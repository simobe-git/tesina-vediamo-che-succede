<?php 
/* questa implementazione:
1- aggiunge una nuova sezione "Domande dal Forum" sotto la gestione FAQ esistente
2- mostra solo le domande che:
    - non sono già state elevate a FAQ
    - hanno almeno una risposta
3- per ogni domanda mostra:
    - il contenuto della domanda
    - l'autore e la data
    - tutte le risposte ricevute
4- per ogni risposta:
    - il contenuto
    - l'autore e la data
    - i punteggi di supporto e utilità
    - un bottone per elevare quella specifica risposta a FAQ
5- quando una risposta viene elevata a FAQ:
    - viene creata una nuova FAQ
    - viene mantenuto il riferimento al thread e alla risposta originale
    - la domanda non apparirà più in questa lista */

session_start();

// verifica che l'utente sia admin
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'admin') {
    header('Location: index.php');
    exit();
}

$xml_file = '../xml/faq.xml';
$forum_file = '../xml/domande.xml';

// caricamento del file XML
function caricaXML($file) {
    if (file_exists($file)) {
        return simplexml_load_file($file);
    }
    return false;
}

// salvataggio del file XML
function salvaXML($xml, $file) {
    $xml->asXML($file);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $xml = caricaXML($xml_file);
    
    if (isset($_POST['aggiungi_faq'])) {
        $nuova_faq = $xml->addChild('faq');
        $nuova_faq->addAttribute('id', time());
        $nuova_faq->addChild('domanda', $_POST['domanda']);
        $nuova_faq->addChild('risposta', $_POST['risposta']);
        $nuova_faq->addChild('data_creazione', date('Y-m-d'));
        $nuova_faq->addChild('fonte', 'admin');
        
        salvaXML($xml, $xml_file);
        $messaggio = "FAQ aggiunta con successo!";
    }
    
    elseif (isset($_POST['elimina_faq'])) {
        $id_da_eliminare = $_POST['id_faq'];
        $indice = 0;
        foreach ($xml->faq as $faq) {
            if ((string)$faq['id'] === $id_da_eliminare) {
                unset($xml->faq[$indice]);
                break;
            }
            $indice++;
        }
        salvaXML($xml, $xml_file);
        $messaggio = "FAQ eliminata con successo!";
    }
    
    elseif (isset($_POST['modifica_faq'])) {
        foreach ($xml->faq as $faq) {
            if ((string)$faq['id'] === $_POST['id_faq']) {
                $faq->domanda = $_POST['domanda'];
                $faq->risposta = $_POST['risposta'];
                break;
            }
        }
        salvaXML($xml, $xml_file);
        $messaggio = "FAQ modificata con successo!";
    }
    
    elseif (isset($_POST['eleva_a_faq'])) {
        $id_domanda = $_POST['id_domanda'];
        $id_risposta = $_POST['id_risposta'];
        
        // cerca la domanda e la risposta nel forum
        $forum = caricaXML($forum_file);
        if ($forum):
            foreach ($forum->thread as $thread) {
                if ((string)$thread['id'] === $id_domanda) {
                    $domanda = (string)$thread->contenuto;
                    foreach ($thread->risposte->risposta as $risposta) {
                        if ((string)$risposta['id'] === $id_risposta) {
                            
                            // crea nuova FAQ
                            $nuova_faq = $xml->addChild('faq');
                            $nuova_faq->addAttribute('id', time());
                            $nuova_faq->addChild('domanda', $domanda);
                            $nuova_faq->addChild('risposta', (string)$risposta->contenuto);
                            $nuova_faq->addChild('data_creazione', date('Y-m-d'));
                            $nuova_faq->addChild('fonte', 'forum');
                            $nuova_faq->addChild('id_thread', $id_domanda);
                            $nuova_faq->addChild('id_risposta', $id_risposta);
                            
                            salvaXML($xml, $xml_file);
                            $messaggio = "Domanda e risposta elevate a FAQ con successo!";
                            break 2;
                        }
                    }
                }
            }
        endif;
    }
}

$xml_faq = caricaXML($xml_file);
$xml_forum = caricaXML($forum_file);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione FAQ</title>
    <style>
        /* Stili generali */
        body {
            background-color: #f9f9f9; /* Sfondo chiaro */
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white; /* Sfondo bianco per il contenitore principale */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center; 
            color: #333;
        }

        nav {
            text-align: center; 
            margin: 20px 0; 
            background-color: #2196F3; 
            padding: 5px; 
            border-radius: 4px; 
        }

        nav a {
            margin: 0 15px; 
            text-decoration: none; 
            color: white; 
            font-weight: bold; 
            padding: 5px; 
        }

        nav a:hover {
            background-color: #d3d3d3; 
            color: #2196F3; 
            text-decoration: none; 
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group textarea {
            width: 100%;
            padding: 5px; 
            border-radius: 4px;
            background: #fff;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background: #2196F3;
            color: white;
            cursor: pointer;
        }

        .accordion-section {
            margin: 20px 0;
        }

        .accordion-header {
            background: #2196F3;
            color: white;
            padding: 15px;
            cursor: pointer;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .accordion-content {
            display: none;
            padding: 5px; 
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85em; 
        }

        .accordion-content div {
            margin-bottom: 5px;
        }

        .accordion-content strong {
            font-size: 0.9em; 
        }

        .accordion-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestione FAQ</h1>
        
        <nav style="width: 40%; margin-left: 30%; height: 5ex; display: flex; align-items: center; justify-content: center;">
            <a href="gestione_utenti.php">Gestione Utenti</a>
            <a href="admin_dashboard.php">Admin Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <h2>Aggiungi nuova FAQ</h2>
        <form method="POST">
            <div class="form-group">
                <label>Domanda:</label>
                <textarea name="domanda" required></textarea>
            </div>
            <div class="form-group">
                <label>Risposta:</label>
                <textarea name="risposta" required></textarea>
            </div>
            <button type="submit" name="aggiungi_faq" class="btn">Aggiungi FAQ</button>
        </form>

        <!-- Sezione FAQ esistenti -->
        <div class="accordion-section">
            <div class="accordion-header" onclick="toggleSection('faq-esistenti')">
                <h2>FAQ Esistenti</h2>
            </div>
            <div class="accordion-content" id="faq-esistenti">
                <?php if ($xml_faq): foreach ($xml_faq->faq as $faq): ?>
                    <div>
                        <strong>Domanda:</strong> <?php echo htmlspecialchars($faq->domanda); ?><br>
                        <strong>Risposta:</strong> <?php echo htmlspecialchars($faq->risposta); ?><br>
                        <strong>Fonte:</strong> <?php echo htmlspecialchars($faq->fonte); ?> - 
                        <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($faq->data_creazione)); ?>
                    </div>
                    <hr>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- sezione domande dal forum -->
        <div class="accordion-section">
            <div class="accordion-header" onclick="toggleSection('domande-forum')">
                <h2>Domande dal Forum</h2>
            </div>
            <div class="accordion-content" id="domande-forum">
                <?php 
                if ($xml_forum): foreach ($xml_forum->domanda as $domanda): 
                    if ($domanda->stato == 'attiva'):
                ?>
                    <div>
                        <strong>Titolo:</strong> <?php echo htmlspecialchars($domanda->titolo); ?><br>
                        <strong>Contenuto:</strong> <?php echo htmlspecialchars($domanda->contenuto); ?><br>
                        <strong>Autore:</strong> <?php echo htmlspecialchars($domanda->autore); ?> - 
                        <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($domanda->data)); ?>
                        <hr>
                    </div>
                <?php 
                    endif;
                endforeach; endif; 
                ?>
            </div>
        </div>
    </div>

    <script>
    function toggleSection(sectionId) {
        const content = document.getElementById(sectionId);
        
        // controlla se la sezione è già attiva
        if (content.classList.contains('active')) {
            // se è attiva, la chiudiamo
            content.classList.remove('active');
        } else {
            // altrimenti, chiudiamo tutte le sezioni
            const allContents = document.querySelectorAll('.accordion-content');
            allContents.forEach((item) => {
                item.classList.remove('active');
            });
            
            // aprimo solo la sezione cliccata
            content.classList.add('active');
        }
    }
    </script>
</body>
</html>
