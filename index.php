<?php
// Liste des fichiers CSV dans le répertoire courant
$csvFiles = glob('*.csv');

// Message de suppression
$deleteMessage = '';
if (isset($_GET['deleted'])) {
    $deletedFile = $_GET['deleted'];
    $deleteMessage = "Le fichier \"$deletedFile\" a été supprimé avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélecteur de fichier CSV d'anomalie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        .file-list {
            margin: 20px 0;
        }
        .file-item {
            margin: 10px 0;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .new-file {
            margin-top: 30px;
            padding: 20px;
            background-color: #e9f7ef;
            border-radius: 5px;
        }
        button, .btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }
        button:hover, .btn:hover {
            background-color: #45a049;
        }
        input[type="text"] {
            padding: 8px;
            width: 300px;
            margin-right: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <h1>Éditeur de fichiers de déclaration d'anomalie</h1>
    
    <?php if (!empty($deleteMessage)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($deleteMessage); ?>
        </div>
    <?php endif; ?>
    
    <?php if (count($csvFiles) > 0): ?>
        <div class="file-list">
            <h2>Fichiers existants</h2>
            <?php foreach ($csvFiles as $file): ?>
                <div class="file-item">
                    <form action="anomaly_editor.php" method="post">
                        <input type="hidden" name="base_url" value="<?php echo htmlspecialchars($file); ?>">
                        <p><strong><?php echo htmlspecialchars($file); ?></strong></p>
                        <button type="submit">Modifier</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Aucun fichier CSV trouvé dans le répertoire.</p>
    <?php endif; ?>
    
    <div class="new-file">
        <h2>Créer un nouveau fichier</h2>
        <form action="anomaly_editor.php" method="post">
            <input type="text" name="base_url" placeholder="Nom du nouveau fichier (ex: anomalies.csv)" required>
            <button type="submit">Créer</button>
        </form>
    </div>
</body>
</html>