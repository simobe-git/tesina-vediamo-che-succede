<?php
session_start();
require_once('connessione.php');

// verifica che l'utente è un gestore
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'gestore') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // recuperiamo i dati dal modulo
    $nome = $_POST['nome'];
    $descrizione = $_POST['descrizione'];
    $prezzo_originale = $_POST['prezzo_originale'];
    $prezzo_attuale = $_POST['prezzo_attuale'];
    $genere = $_POST['genere'];
    $nome_editore = $_POST['nome_editore'];
    $data_rilascio = $_POST['data_rilascio'];
    $immagine = $_POST['immagine'];  // assicurati di gestire il caricamento dell'immagine

    // query per inserire il nuovo videogioco
    $query = "INSERT INTO videogiochi (nome, descrizione, prezzo_originale, prezzo_attuale, genere, nome_editore, data_rilascio, immagine) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("ssddssss", $nome, $descrizione, $prezzo_originale, $prezzo_attuale, $genere, $nome_editore, $data_rilascio, $immagine);
    
    if ($stmt->execute()) {
        header("Location: catalogo.php?success=1");
        exit();
    } else {
        $errore = "Errore durante l'aggiunta del videogioco.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi Videogioco</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            width: 100%;
        }

        button:hover {
            background-color: #1976D2;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Aggiungi Videogioco</h1>
    <form method="POST" action="aggiungi_gioco.php">
        <label>Nome:</label>
        <input type="text" name="nome" required>
        <label>Descrizione:</label>
        <textarea name="descrizione" required></textarea>
        <label>Prezzo Originale:</label>
        <input type="number" name="prezzo_originale" step="0.01" required>
        <label>Prezzo Attuale:</label>
        <input type="number" name="prezzo_attuale" step="0.01" required>
        <label>Genere:</label>
        <input type="text" name="genere" required>
        <label>Nome Editore:</label>
        <input type="text" name="nome_editore" required>
        <label>Data di Rilascio:</label>
        <input type="date" name="data_rilascio" required>
        <label>Immagine URL:</label>
        <input type="text" name="immagine" required>
        <button type="submit">Aggiungi</button>
    </form>
    <?php if (isset($errore)): ?>
        <p class="error-message"><?php echo $errore; ?></p>
    <?php endif; ?>
</body>
</html>