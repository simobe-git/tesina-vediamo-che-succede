<?php
session_start();
require_once('connessione.php');

// Verifica se l'utente è un gestore
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'gestore') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codice_gioco = $_POST['codice_gioco'];

    // Query per eliminare il videogioco
    $query = "DELETE FROM videogiochi WHERE codice = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $codice_gioco);
    
    if ($stmt->execute()) {
        // Reindirizza al catalogo con un messaggio di successo
        header("Location: catalogo.php?success=1");
        exit();
    } else {
        $errore = "Errore durante l'eliminazione del videogioco.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Elimina Videogioco</title>
</head>
<body>
    <h1>Elimina Videogioco</h1>
    <?php if (isset($errore)): ?>
        <p><?php echo $errore; ?></p>
    <?php else: ?>
        <p>Videogioco eliminato con successo.</p>
    <?php endif; ?>
    <a href="catalogo.php">Torna al Catalogo</a>
</body>
</html>