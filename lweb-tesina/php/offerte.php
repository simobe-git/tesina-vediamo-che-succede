<?php

session_start();
// non viene eseguito il controllo sullo stato login poiché un utente 
// può accedere al catalogo in modo anonimo ma per effettuare acquisti 
// dovrà necessariamente identificarsi

require_once("connessione.php");

// gestione dell'ordinamento
$ordinamento = isset($_GET['ordinamento']) ? $_GET['ordinamento'] : 'nome'; // default ordinamento per nome
$direzione = isset($_GET['direzione']) ? $_GET['direzione'] : 'ASC'; // default crescente

// parametri di ordinamento
$ordinamenti_permessi = ['nome', 'prezzo', 'data_rilascio'];
$direzioni_permesse = ['ASC', 'DESC'];

if (!in_array($ordinamento, $ordinamenti_permessi)) {
    $ordinamento = 'nome';
}
if (!in_array($direzione, $direzioni_permesse)) {
    $direzione = 'ASC';
}

// query per ottenere generi ed editori (univoci)
$query_generi = "SELECT DISTINCT genere FROM videogiochi WHERE genere IS NOT NULL ORDER BY genere";
$risultato_generi = $connessione->query($query_generi);

$query_editori = "SELECT DISTINCT nome_editore FROM videogiochi WHERE nome_editore IS NOT NULL ORDER BY nome_editore";
$risultato_editori = $connessione->query($query_editori);

// gestione dei filtri
$genere = isset($_GET['genere']) ? $_GET['genere'] : '';
$editore = isset($_GET['editore']) ? $_GET['editore'] : '';

// query con i filtri
$query = "SELECT *, 
          CASE 
            WHEN prezzo_attuale IS NOT NULL AND prezzo_attuale < prezzo_originale 
            THEN prezzo_attuale 
            ELSE prezzo_originale 
          END AS prezzo_effettivo 
          FROM videogiochi 
          WHERE 1=1";  // condizione sempre vera per concatenare la AND

if ($genere) {
    $query .= " AND genere = '" . $connessione->real_escape_string($genere) . "'";
}
if ($editore) {
    $query .= " AND nome_editore = '" . $connessione->real_escape_string($editore) . "'";
}

$query .= " ORDER BY ";

// gestione ordinamento con prezzo effettivo
if ($ordinamento === 'prezzo') {
    $query .= "prezzo_effettivo";
} else {
    $query .= $ordinamento;
}
$query .= " " . $direzione;

$risultato = $connessione->query($query);

$query_offerte = "SELECT * FROM videogiochi WHERE prezzo_attuale <> prezzo_originale";
$risultato = mysqli_query($connessione,$query_offerte);

// gestione dell'aggiunta al carrello
if(isset($_POST['aggiungi_al_carrello']) && isset($_POST['codice_gioco'])) {
    if(!isset($_SESSION['username'])) {

        // se l'utente non è loggato, reindirizza al login
        header('Location: login.php');
        exit();
    }
    
    $codice_gioco = $_POST['codice_gioco'];
    $username = $_SESSION['username'];
    
    // query per inserire il gioco nel carrello
    $query = "INSERT IGNORE INTO carrello (username, codice_gioco) VALUES (?, ?)";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("si", $username, $codice_gioco);
    $stmt->execute();
    
    // reindirizza al carrello dopo l'aggiunta
    header('Location: carrello.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutti gli Articoli del Negozio</title>
    <link rel="stylesheet" href="../css/giochi.css">
    <link rel="stylesheet" href="../css/menu.css">
    <script src="../js/filtri.js"></script>
</head>
<body>
    <?php include('menu.php'); ?>

    <!-- titolo della pagina -->
    <header class="shop-header">
        <h1>Tutti gli Articoli</h1>
    </header>

    <!-- filtri per la ricerca dei giochi -->
    <div class="filtri-sezione">
        <div class="filtri-wrapper">
            <div class="filtro-box">
                <span class="filtro-label">Ordina per:</span>
                <select class="filtro-select" id="ordinamento" onchange="applicaFiltri()">
                    <option value="nome" <?php echo $ordinamento === 'nome' ? 'selected' : ''; ?>>Nome</option>
                    <option value="prezzo" <?php echo $ordinamento === 'prezzo' ? 'selected' : ''; ?>>Prezzo</option>
                    <option value="data_rilascio" <?php echo $ordinamento === 'data_rilascio' ? 'selected' : ''; ?>>Anno di uscita</option>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Ordine:</span>
                <select class="filtro-select" id="direzione" onchange="applicaFiltri()">
                    <option value="ASC" <?php echo $direzione === 'ASC' ? 'selected' : ''; ?>>Crescente ↑</option>
                    <option value="DESC" <?php echo $direzione === 'DESC' ? 'selected' : ''; ?>>Decrescente ↓</option>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Genere:</span>
                <select class="filtro-select" id="genere" onchange="applicaFiltri()">
                    <option value="">Tutti i generi</option>
                    <?php while($genere = $risultato_generi->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($genere['genere']); ?>"
                                <?php echo isset($_GET['genere']) && $_GET['genere'] === $genere['genere'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genere['genere']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Editore:</span>
                <select class="filtro-select" id="editore" onchange="applicaFiltri()">
                    <option value="">Tutti gli editori</option>
                    <?php while($editore = $risultato_editori->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($editore['nome_editore']); ?>"
                                <?php echo isset($_GET['editore']) && $_GET['editore'] === $editore['nome_editore'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($editore['nome_editore']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- griglia dei videogiochi -->
    <div class="product-grid">
        <?php
        if ($risultato->num_rows > 0) {
            while ($gioco = $risultato->fetch_assoc()) {
                ?>
                <div class="product-item">
                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>">
                        <img src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                             alt="<?php echo htmlspecialchars($gioco['nome']); ?>">
                    </a>
                    <h2><?php echo htmlspecialchars($gioco['nome']); ?></h2>
                    <p class="descrizione"><?php echo htmlspecialchars($gioco['descrizione']); ?></p>
                    
                    <div class="prezzi">
                        <div class="prezzo-container">
                            <div class="prezzo-originale"><?php echo number_format($gioco['prezzo_originale'] * 2, 2); ?> crediti</div>
                            <div class="prezzo-scontato">
                                <?php echo number_format($gioco['prezzo_attuale'] * 2, 2); ?> crediti
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="carrello.php">
                        <input type="hidden" name="codice_gioco" value="<?php echo $gioco['codice']; ?>">
                        <button type="submit" name="aggiungi" class="btn-acquista">Aggiungi al Carrello</button>
                    </form>
                </div>
            <?php }
        } else {
            echo "<p>Nessun gioco trovato nel catalogo.</p>";
        }
        ?>
    </div>

    <?php include('footer.php'); //visualizza footer?>
    
    <script>
        const menuHamburger = document.querySelector('.hamburger-menu');
        const linkNav = document.querySelector('.nav-links');

        menuHamburger.addEventListener('click', () => {
            linkNav.classList.toggle('attivo');
        });
    </script>
    
</body>
</html>
