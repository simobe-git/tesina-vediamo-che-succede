<?php
require_once("connessione.php");

session_start();
$numCrediti = 0;
if (isset($_SESSION['username'])) {
    $query = "SELECT crediti FROM utenti WHERE username = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $numCrediti = $row['crediti'];
    }
}

// query per selezionare 3 giochi casuali tra quelli presenti da mostrare a schermo
$sql = "SELECT * FROM videogiochi ORDER BY RAND() LIMIT 3";
$result = $connessione->query($sql);

$giochi = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $giochi[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Negozio di Videogiochi</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/menu.css">

    <style>
        .crediti-virtuali {
            position: absolute;
            top: 80px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }

        .icona-crediti {
            width: 32px;
            height: 32px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .crediti-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .crediti-label {
            color: #adb5bd;
            font-size: 0.9em;
            margin-bottom: 2px;
        }

        .crediti-amount {
            color: #ffd700;
            font-size: 1.2em;
            font-weight: bold;
        }

        .attribution {
            text-align: center;
        }

        .hero-section {
            position: relative;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 2rem;
            min-height: 500px;
            background: linear-gradient(to right, rgba(0,0,0,0.8), rgba(0,0,0,0.6));
            overflow: hidden;
        }

        .hero-content {
            flex: 1;
            z-index: 2;
            padding: 2rem;
            max-width: 600px;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .username-highlight {
            color: yellow;
            font-size: 120%;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: #fff;
        }

        .hero-image-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .cta-button {
            display: inline-block;
            padding: 1rem 2rem;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .cta-button:hover {
            background-color: #0056b3;
        }

        /* responsive design */
        @media (max-width: 1024px) {
            .hero-section {
                padding: 1rem;
            }

            .hero-content h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                flex-direction: column;
                text-align: center;
            }

            .hero-content {
                padding: 1rem;
                margin-bottom: 2rem;
            }

            .hero-image-container {
                width: 100%;
                margin-top: 1rem;
            }

            .hero-image {
                max-width: 90%;
            }

            .crediti-virtuali {
                position: relative;
                top: 0;
                right: 0;
                margin: 1rem auto;
                width: fit-content;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.5rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .cta-button {
                padding: 0.8rem 1.5rem;
            }
        }
    </style>

</head>
<body>
    <?php include('menu.php'); ?>

    <section class="hero-section">
        <?php if(isset($_SESSION['username'])): ?>
            <div class="crediti-virtuali">
                <img src="../isset/coin.png" alt="Crediti">
                <div class="crediti-info">
                    <span class="crediti-label">I tuoi Crediti:</span>
                    <span class="crediti-amount"><?php echo number_format($numCrediti, 2); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="hero-content">
            <?php if(!isset($_SESSION['username'])){ ?>
                <h1>Benvenuti su GameShop</h1>
            <?php }else{ ?>
                <h1>Bentornato su GameShop, <strong class="username-highlight"><?php echo $_SESSION['username']; }?></strong></h1>
            <p>Il miglior negozio di videogiochi online</p>
            <a href="catalogo.php" class="cta-button">Scopri di più</a>
        </div>
        <div class="hero-image-container">
            <img src="../isset/banner-image.jpg" alt="Immagine di Videogioco" class="hero-image">
        </div>
    </section>


    <section class="featured-games">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 2rem;">Giochi in Evidenza</h2>
        <div class="games-grid">
            <?php foreach ($giochi as $gioco): ?>
                <div class="game-card">
                    <img src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                        alt="<?php echo htmlspecialchars($gioco['nome']); ?>">
                    <div class="game-info">
                        <h3><?php echo htmlspecialchars($gioco['nome']); ?></h3>
                        <p class="descrizione"><?php echo htmlspecialchars($gioco['descrizione']); ?></p>
                        <div class="price-section" style="text-align: center;">
                            <?php 
                            // Calcola il prezzo in crediti
                            $prezzoInCrediti = $gioco['prezzo_originale'] * 2; // Prezzo originale in crediti
                            $prezzoAttualeInCrediti = $gioco['prezzo_attuale'] * 2; // Prezzo attuale in crediti
                            ?>
                            <?php if($prezzoAttualeInCrediti < $prezzoInCrediti): ?>
                                <p class="price">
                                    <span style="font-size: 1.4em; color: #2ecc71; font-weight: bold;">
                                        Crediti: <?php echo number_format($prezzoAttualeInCrediti, 2); ?>
                                    </span>
                                    <span style="font-size: 1.2em; color: #999; text-decoration: line-through; margin-left: 10px;">
                                        Crediti: <?php echo number_format($prezzoInCrediti, 2); ?>
                                    </span>
                                </p>
                            <?php else: ?>
                                <p class="price">
                                    <span style="font-size: 1.4em; color: #2ecc71; font-weight: bold;">
                                        Crediti: <?php echo number_format($prezzoInCrediti, 2); ?>
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>" 
                    style="display: block; 
                            width: 90%; 
                            margin: 10px auto; 
                            padding: 10px; 
                            background-color: #007bff; 
                            color: white; 
                            text-align: center; 
                            text-decoration: none; 
                            border-radius: 5px;">
                        Acquista ora
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
  
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Contattaci</h4>
                <p>Email: info@gameshop.com</p>
                <p>Telefono: +39 123 456 789</p>
            </div>
            <div class="footer-section">
                <h4>Seguici</h4>
                <p>
                    <a href="#">Facebook</a> | 
                    <a href="#">Twitter</a> | 
                    <a href="#">Instagram</a>
                </p>
            </div>
            <div class="footer-section">
                <h4>Copyright</h4>
                <p>&copy; 2024 GameShop. Tutti i diritti sono riservati.</p>
            </div>
        </div>
    </footer>
  
    <script>
        const hamburgerMenu = document.querySelector('.hamburger-menu');
        const navLinks = document.querySelector('.nav-links');

        hamburgerMenu.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    </script>

    <?php
    if(isset($_SESSION['username'])) {
        $imagePath = '../isset/coin.png';
        if(file_exists($imagePath)) {
            echo "<!-- L'immagine esiste nel percorso specificato -->";
        } else {
            echo "<!-- L'immagine non esiste nel percorso: $imagePath -->";
        }
    }
    ?>
</body>
</html>