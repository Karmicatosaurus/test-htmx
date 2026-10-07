<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Envoie fichier</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css" />
    <script src="https://unpkg.com/htmx.org@2.0.2" integrity="sha384-Y7hw+L/jvKeWIRRkqWYfPcvVxHzVzn5REgzbawhxAuQGwX1XWe70vji+VSeHOThJ" crossorigin="anonymous"></script>
    <style>
        form {
            display: block;
            margin-top: 20px;
            margin-left: auto;
            margin-right: auto;
            width: 640px;
        }

        #resultat {
            display: block;
            width: 820px;
            margin-left: auto;
            margin-right: auto;
            border:1px dotted #000000;
            border-radius: 5px;   
            padding:2px;         
        }
    </style>
</head>
<body>

    <form hx-post="fichier.php" hx-encoding="multipart/form-data" hx-target="#resultat" hx-swap="innerHTML" hx-indicator="#chargement">
        <label for="img">Fichier</label>
        <input type="file" name="img" id="img">
        <button type="submit">Envoyer</button>
    </form>

    <div id="chargement" class="htmx-indicator">Envoie en cours ...</div>

    <div id="resultat"></div>
</body>
</html>
