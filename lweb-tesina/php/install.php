<?php
require_once('dati-connessione.php');
// creiamo una connessione senza specificare il database
$connessione = new mysqli($hostname, $user, $password);

// verifica della connessione
if ($connessione->connect_error) {
    die("Connessione fallita: " . $connessione->connect_error);
}

// e successiva creazione del database
$sql = "CREATE DATABASE IF NOT EXISTS $db";
if ($connessione->query($sql) === TRUE) {
    echo "Database creato con successo o già esistente<br>";
} else {
    echo "Errore nella creazione del database: " . $connessione->error . "<br>";
}

// selezione del database
$connessione->select_db($db);

// disabilitiamo temporaneamente i controlli delle chiavi esterne (debug)
$connessione->query('SET FOREIGN_KEY_CHECKS = 0');

// eliminiamo le tabelle nell'ordine corretto
$tabelle = [
    'giudizi_recensioni',
    'recensioni',
    'bonus',
    'board_games',
    'utenti',
    'faq',
    'carrello'
];

foreach ($tabelle as $tabella) {
    $sql = "DROP TABLE IF EXISTS $tabella";
    if ($connessione->query($sql) === TRUE) {
        echo "Tabella $tabella eliminata se esistente<br>";
    } else {
        echo "Errore nell'eliminazione della tabella $tabella: " . $connessione->error . "<br>";
    }
}

// riabilita i controlli delle chiavi esterne
$connessione->query('SET FOREIGN_KEY_CHECKS = 1');

// creazione della tabella utenti
$sql = "CREATE TABLE utenti (
    nome VARCHAR(30) NOT NULL,
    cognome VARCHAR(30) NOT NULL,
    username VARCHAR(30) NOT NULL PRIMARY KEY,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(30) NOT NULL,
    tipo_utente ENUM('visitatore', 'cliente', 'gestore', 'admin') DEFAULT 'cliente',
    crediti DECIMAL(10,2) DEFAULT 0.00,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('attivo', 'bannato') DEFAULT 'attivo'
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella utenti creata con successo<br>";
} else {
    echo "Errore nella creazione della tabella utenti: " . $connessione->error . "<br>";
}

// creazione della tabella giochi da tavolo (se non esiste già)
$sql = "CREATE TABLE IF NOT EXISTS board_games (
    codice INT(5) NOT NULL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    prezzo_originale DOUBLE(6,2) NOT NULL,
    prezzo_attuale DOUBLE(6,2) NOT NULL,
    genere VARCHAR(30) NOT NULL,
    min_giocatori INT(2) NOT NULL,/*minimo numero di giocatori*/
    max_giocatori INT(2) NOT NULL,/*massimo numero di giocatori*/
    eta_minima INT(2) NOT NULL,/*età minima consigliata*/
    avg_durata INT(3) NOT NULL,/*durata media in minuti*/
    nome_editore VARCHAR(50) NOT NULL, /*editore o produttore del gioco*/
    autore VARCHAR(50) NOT NULL, /*autore del gioco*/
    descrizione TEXT,
    immagine VARCHAR(255)
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella videogiochi creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella videogiochi: " . $connessione->error . "<br>";
}

// creazione tabella recensioni
$sql = "CREATE TABLE IF NOT EXISTS recensioni (
    id_recensione INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30),
    codice_gioco INT(5),
    testo TEXT,
    data_recensione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES utenti(username) ON DELETE CASCADE,
    FOREIGN KEY (codice_gioco) REFERENCES board_games(codice) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella recensioni creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella recensioni: " . $connessione->error . "<br>";
}

// creazione tabella giudizi_recensioni
$sql = "CREATE TABLE IF NOT EXISTS giudizi_recensioni (
    id_giudizio INT AUTO_INCREMENT PRIMARY KEY,
    id_recensione INT,
    username_votante VARCHAR(30),
    supporto INT(1) CHECK (supporto BETWEEN 1 AND 3),
    utilita INT(1) CHECK (utilita BETWEEN 1 AND 5),
    FOREIGN KEY (id_recensione) REFERENCES recensioni(id_recensione) ON DELETE CASCADE,
    FOREIGN KEY (username_votante) REFERENCES utenti(username) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella giudizi_recensioni creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella giudizi_recensioni: " . $connessione->error . "<br>";
}

// creazione tabella bonus
$sql = "CREATE TABLE IF NOT EXISTS bonus (
    id_bonus INT AUTO_INCREMENT PRIMARY KEY,
    crediti_bonus DECIMAL(10,2) NOT NULL,
    codice_gioco INT(5),
    data_inizio DATE,
    data_fine DATE,
    FOREIGN KEY (codice_gioco) REFERENCES board_games(codice) ON DELETE CASCADE
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella bonus creata con successo o già esistente<br>";
} else {
    echo "Errore nella creazione della tabella bonus: " . $connessione->error . "<br>";
}

// inserimento admin
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) 
        VALUES ('Mario', 'Rossi', 'admin1', 'admin@gaming.it', 'Admin123!', 'admin', 0)";

if ($connessione->query($sql) === TRUE) {
    echo "Admin inserito con successo<br>";
} else {
    echo "Errore nell'inserimento admin: " . $connessione->error . "<br>";
}

// inserimento gestore
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) 
        VALUES ('Luigi', 'Verdi', 'gestore1', 'gestore@gaming.it', 'Gestore123!', 'gestore', 0)";

if ($connessione->query($sql) === TRUE) {
    echo "Gestore inserito con successo<br>";
} else {
    echo "Errore nell'inserimento gestore: " . $connessione->error . "<br>";
}

// inserimento cliente1
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) 
        VALUES ('Giuseppe', 'Bianchi', 'cliente1', 'giuseppe@email.it', 'Cliente123!', 'cliente', 100.50)";

if ($connessione->query($sql) === TRUE) {
    echo "Cliente1 inserito con successo<br>";
} else {
    echo "Errore nell'inserimento cliente1: " . $connessione->error . "<br>";
}

// inserimento altri utenti (dopo gli inserimenti esistenti)
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti) VALUES 
    ('Marco', 'Neri', 'cliente2', 'marco@email.it', 'Cliente123!', 'cliente', 75.00),
    ('Anna', 'Gialli', 'cliente3', 'anna@email.it', 'Cliente123!', 'cliente', 150.00),
    ('Paolo', 'Viola', 'gestore2', 'paolo@gaming.it', 'Gestore123!', 'gestore', 0),
    ('Laura', 'Rosa', 'admin2', 'laura@gaming.it', 'Admin123!', 'admin', 0),
    ('Sofia', 'Azzurri', 'visitatore1', 'sofia@email.it', 'Visitatore123!', 'visitatore', 0),
    ('Luca', 'Marrone', 'cliente4', 'luca@email.it', 'Cliente123!', 'cliente', 200.00),
    ('Mario', 'Rossi', 'admin', 'admin@gaming.it', 'Admin123?', 'admin', 0),
    ('Giuseppe', 'Bianchi', 'cliente123', 'giuseppe@email.it', 'Cliente123?', 'cliente', 100.50),
    ('Luigi', 'Verdi', 'gestore123', 'gestore@gaming.it', 'Gestore123?', 'gestore', 0)";
    
if ($connessione->query($sql) === TRUE) {
    echo "Utenti inseriti con successo<br>";
} else {
    echo "Errore nell'inserimento degli utenti: " . $connessione->error . "<br>";
}

// popolamento tabella videogiochi
$sql = "INSERT IGNORE INTO board_games (codice, nome, prezzo_originale, prezzo_attuale, genere, min_giocatori, max_giocatori, eta_minima, avg_durata, nome_editore, autore, descrizione, immagine) VALUES
    (1,'Monopoly', 30.00, 20.00, 'sociale', 2, 6, 8, 200, 'Hasbro', 'Elizabeth Magie', 'bello', 'https://logowik.com/content/uploads/images/monopoly512.logowik.com.webp'),
    (2,'Indovina Chi?', 30.00, 20.00, '	deduzione', 2, 2, 6, 20, 'Hasbro/Milton Bradley', 'Theo e Ora Coster', 'bello', 'https://hasbrocommunity.it/images/logos/300x300/indovina_chi.jpg?v=1'),
    (3,'Jenga', 35.00, 25.00, 'costruzioni', 2, 4, 4, 15, 'Hasbro', 'Leslie Scott', 'Non farla cadere!', 'https://logowik.com/content/uploads/images/jenga5734.logowik.com.webp')
    ";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella videogiochi<br>";
}


// popolamento tabella recensioni
$sql = "INSERT IGNORE INTO recensioni (username, codice_gioco, testo) VALUES
    ('cliente1', 10001, 'Gioco fantastico, grafica stupenda e trama coinvolgente!'),
    ('cliente2', 10001, 'Uno dei migliori giochi mai provati'),
    ('cliente3', 10002, 'Il miglior FIFA degli ultimi anni'),
    ('cliente1', 10003, 'Dopo le patch è diventato un ottimo gioco'),
    ('cliente2', 10004, 'Ambientazione norrena ben realizzata'),
    ('cliente2', 10006, 'Grafica spettacolare e combattimenti epici!'),
    ('cliente3', 10007, 'Il miglior gioco di Spider-Man di sempre'),
    ('cliente1', 10008, 'Storia coinvolgente e sistema di combattimento innovativo'),
    ('cliente4', 10009, 'Remake perfettamente riuscito'),
    ('cliente2', 10010, 'Mondo di gioco incredibile e ben realizzato'),
    ('cliente3', 10011, 'Difficile ma estremamente gratificante')";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella recensioni<br>";
}

// popolamento tabella giudizi_recensioni
$sql = "INSERT IGNORE INTO giudizi_recensioni (id_recensione, username_votante, supporto, utilita) VALUES
    (1, 'cliente2', 3, 5),
    (1, 'cliente3', 2, 4),
    (2, 'cliente1', 3, 5),
    (3, 'cliente1', 2, 3),
    (4, 'cliente2', 3, 4),
    (6, 'cliente1', 3, 5),
    (6, 'cliente4', 2, 4),
    (7, 'cliente2', 3, 5),
    (8, 'cliente3', 3, 4),
    (9, 'cliente1', 2, 3),
    (10, 'cliente4', 3, 5)";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella giudizi_recensioni<br>";
}

// popolamento tabella bonus
$sql = "INSERT IGNORE INTO bonus (crediti_bonus, codice_gioco, data_inizio, data_fine) VALUES
    (10.00, 10001, '2024-01-01', '2024-12-31'),
    (5.00, 10002, '2024-01-01', '2024-12-31'),
    (15.00, 10003, '2024-01-01', '2024-12-31'),
    (7.50, 10004, '2024-01-01', '2024-12-31')";

if ($connessione->query($sql) === TRUE) {
    echo "Dati inseriti nella tabella bonus<br>";
}

// chiudiamo la connessione
$connessione->close();
echo "Installazione completata!";
?>

