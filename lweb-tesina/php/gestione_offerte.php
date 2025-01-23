<?php
session_start();
require_once('connessione.php');

// verifica che l'utente sia un gestore
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'gestore') {
    header('Location: login.php');
    exit();
}

// recuperiamo tutti i videogiochi per il campo di selezione
$query = "SELECT codice, nome, prezzo_originale FROM videogiochi ORDER BY nome";
$risultatoVideogiochi = $connessione->query($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione'])) {
        switch ($_POST['azione']) {
            case 'modifica':
                // controlliamo se il prezzo attuale è valido
                if ($_POST['prezzo_attuale'] < 0) {
                    echo "<script>alert('Il prezzo attuale non può essere negativo.');</script>";
                } elseif ($_POST['prezzo_attuale'] > $_POST['prezzo_originale']) {
                    echo "<script>alert('Il prezzo attuale non può essere maggiore del prezzo originale.');</script>";
                } else {
                    // logica per modificare il prezzo originale
                    $query = "UPDATE videogiochi SET 
                             prezzo_originale = ?, 
                             prezzo_attuale = ?
                             WHERE nome = ?";
                    $stmt = $connessione->prepare($query);
                    $stmt->bind_param("dds", 
                        $_POST['prezzo_attuale'],   // salviamo il nuovo prezzo attuale come prezzo originale
                        $_POST['prezzo_attuale'],   // aggiorniamo anche il prezzo attuale
                        $_POST['nome']      // usa il nome del videogioco per identificare quale aggiornare
                    );
                    $stmt->execute();
                }
                break;

            case 'elimina':
                // logica per eliminare un'offerta
                $query = "DELETE FROM videogiochi WHERE codice = ?";
                $stmt = $connessione->prepare($query);
                $stmt->bind_param("i", $_POST['codice']);
                $stmt->execute();
                break;
        }
    }
}

// recuperiamo tutte le offerte
$query = "SELECT * FROM videogiochi ORDER BY nome";
$risultato = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Offerte</title>
    <link rel="stylesheet" href="../css/gestione_offerte.css">
    <style>
        .navbar {
            background-color: #333; 
            overflow: hidden; 
            text-align: center; 
        }

        .navbar a {
            display: inline-block; 
            color: white; 
            text-align: center; 
            padding: 14px 20px; 
            text-decoration: none; 
        }

        .navbar a:hover {
            background-color: #ddd; 
            color: black; 
        }

        .container {
            padding: 10px 20px; 
        }

        h1 {
            margin-top: 10px; 
            text-align: center; 
        }
    </style>
    <script>
        function aggiornaPrezzo() {
            const select = document.getElementById('nome');
            const prezzoOriginaleInput = document.getElementById('prezzo_originale');
            const selectedOption = select.options[select.selectedIndex];

            // aggiorna il campo prezzo originale con il valore selezionato
            if (selectedOption) {
                prezzoOriginaleInput.value = selectedOption.getAttribute('data-prezzo-originale');
            } else {
                prezzoOriginaleInput.value = '';
            }
        }

        function validaPrezzoAttuale() {
            const prezzoAttualeInput = document.getElementById('prezzo_attuale');
            const prezzoOriginaleInput = document.getElementById('prezzo_originale');

            if (parseFloat(prezzoAttualeInput.value) < 0) {
                alert('Il prezzo attuale non può essere negativo.');
                prezzoAttualeInput.value = 0; // impostiamo a 0 se è negativo
            } else if (parseFloat(prezzoAttualeInput.value) > parseFloat(prezzoOriginaleInput.value)) {
                alert('Il prezzo attuale non può essere maggiore del prezzo originale.');
                prezzoAttualeInput.value = prezzoOriginaleInput.value; // impostiamo al prezzo originale di quello specifico videogioco
            }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <a href="profilo_clienti.php">Profilo Clienti</a>
        <a href="moderazione_contributi.php">Moderazione Contributi</a>
        <a href="domande.php">Domande</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h1>Gestione Offerte</h1>

        <!-- form per aggiungere/modificare offerte -->
        <form id="offertaForm" method="POST" class="form-offerta" onsubmit="validaPrezzoAttuale()">
            <input type="hidden" name="azione" value="modifica">
            <input type="hidden" name="codice" id="codiceGioco">

            <div class="form-group">
                <label for="nome">Nome Gioco:</label>
                <select id="nome" name="nome" required onchange="aggiornaPrezzo()">
                    <option value="">Seleziona un videogioco</option>
                    <?php while ($gioco = $risultatoVideogiochi->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($gioco['nome']); ?>" data-prezzo-originale="<?php echo htmlspecialchars($gioco['prezzo_originale']); ?>">
                            <?php echo htmlspecialchars($gioco['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="prezzo_originale">Prezzo Originale:</label>
                <input type="number" id="prezzo_originale" name="prezzo_originale" step="0.01" required readonly>
            </div>

            <div class="form-group">
                <label for="prezzo_attuale">Prezzo Attuale:</label>
                <input type="number" id="prezzo_attuale" name="prezzo_attuale" step="0.01" required oninput="validaPrezzoAttuale()">
            </div>

            <button type="submit">Salva Offerta</button>
        </form>

        <table class="tabella-offerte">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Prezzo Originale</th>
                    <th>Prezzo Attuale</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($gioco = $risultato->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($gioco['nome']); ?></td>
                        <td>€<?php echo number_format($gioco['prezzo_originale'], 2); ?></td>
                        <td>€<?php echo number_format($gioco['prezzo_attuale'], 2); ?></td>
                        <td>
                            <button onclick="eliminaOfferta(<?php echo $gioco['codice']; ?>)">Elimina</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="../js/gestione_offerte.js"></script>
</body>
</html>