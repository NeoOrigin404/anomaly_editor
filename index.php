<?php
session_start();

// Liste des fichiers CSV dans le répertoire courant
$csvFiles = glob('*.csv');

// Gestion du renommage du fichier
if (isset($_POST['rename_file']) && !empty($_POST['old_file']) && !empty($_POST['new_file'])) {
    $old_file = $_POST['old_file'];
    $new_file = $_POST['new_file'];
    
    // S'assurer que le nouveau nom a l'extension .csv
    if (!preg_match('/\.csv$/i', $new_file)) {
        $new_file .= '.csv';
    }
    
    if (file_exists($old_file) && !file_exists($new_file)) {
        if (rename($old_file, $new_file)) {
            // Mettre à jour la session si le fichier était marqué comme modifié
            if (isset($_SESSION['file_modified'][$old_file])) {
                $_SESSION['file_modified'][$new_file] = $_SESSION['file_modified'][$old_file];
                unset($_SESSION['file_modified'][$old_file]);
            }
            header('Location: index.php?renamed='.urlencode($old_file).'&to='.urlencode($new_file));
            exit;
        } else {
            $rename_error = "Impossible de renommer le fichier.";
        }
    } else {
        $rename_error = "Le fichier n'existe pas ou le nouveau nom est déjà utilisé.";
    }
}

// Gestion de la suppression du fichier
$deleteMessage = '';
if (isset($_POST['delete_file']) && !empty($_POST['file_to_delete'])) {
    $file_to_delete = $_POST['file_to_delete'];
    if (file_exists($file_to_delete) && unlink($file_to_delete)) {
        $deleteMessage = "Le fichier \"$file_to_delete\" a été supprimé avec succès.";
    } else {
        $deleteMessage = "Impossible de supprimer le fichier \"$file_to_delete\".";
    }
    // Rafraîchir la liste des fichiers après suppression
    $csvFiles = glob('*.csv');
} else if (isset($_GET['deleted'])) {
    $deletedFile = $_GET['deleted'];
    $deleteMessage = "Le fichier \"$deletedFile\" a été supprimé avec succès.";
}

// Obtenir les paramètres de l'URL ou de la configuration actuelle
$baseDomain = 'https://www.linkappsentreprise.ovh';
$path = $_GET['path'] ?? 'qualicladeweb/afficheEntite.php';
$baseUrl = $baseDomain . '/' . $path;

$params = [
    'ID' => $_GET['ID'] ?? '4dab98a2-e602-42cc-8427-2d63425ccd61',
    'LIEN' => $_GET['LIEN'] ?? '..%2Fwww%2Fmairie3%2F',
    'TYPE' => $_GET['TYPE'] ?? 'tools'
];

// Construire l'URL de retour
$returnUrl = $baseUrl . '?' . http_build_query($params);
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-date {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .file-actions {
            display: flex;
            gap: 10px;
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
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .btn-rename {
            background-color: #ffc107;
            color: #000;
        }
        .btn-rename:hover {
            background-color: #e0a800;
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
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 400px;
            border-radius: 5px;
        }
        .modal-title {
            margin-top: 0;
        }
        .modal-buttons {
            margin-top: 20px;
            text-align: right;
        }
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
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
        <h2><?= count($csvFiles) >= 2 ? 'Fichiers existants' : 'Fichier existant' ?></h2>
            <?php foreach ($csvFiles as $file): ?>
                <div class="file-item">
                    <div>
                        <p><strong><?php echo htmlspecialchars(pathinfo($file, PATHINFO_FILENAME)); ?></strong></p>
                        <p class="file-date">Créé le <?php echo date('d/m/Y à H:i', filectime($file)); ?></p>
                        <?php if (isset($_SESSION['file_modified'][$file]) && $_SESSION['file_modified'][$file]): ?>
                            <p class="file-date">Dernière modification le <?php echo date('d/m/Y à H:i', filemtime($file)); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="file-actions">
                        <form action="anomaly_editor.php" method="post" style="display: inline;">
                            <input type="hidden" name="base_url" value="<?php echo htmlspecialchars($file); ?>">
                            <button type="submit" class="btn">Modifier</button>
                        </form>
                        <button type="button" class="btn btn-rename" onclick="showRenameModal('<?php echo htmlspecialchars($file); ?>')">Renommer</button>
                        <button type="button" class="btn btn-delete" onclick="showDeleteModal('<?php echo htmlspecialchars($file); ?>')">Supprimer</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Aucun fichier trouvé dans le répertoire.</p>
    <?php endif; ?>
    
    <div class="new-file">
        <h2>Créer un nouveau fichier</h2>
        <form action="anomaly_editor.php" method="post">
            <input type="text" name="base_url" placeholder="Nom du nouveau fichier (ex: anomalies.csv)" required>
            <button type="submit">Créer</button>
        </form>
    </div>

    <div class="header-actions">
        <button onclick="window.location.href='<?php echo htmlspecialchars($returnUrl); ?>'" class="btn btn-secondary">Retour</button>
    </div>
    
    <!-- Modal de confirmation pour la suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 class="modal-title">Confirmer la suppression</h3>
            <p>Êtes-vous sûr de vouloir supprimer le fichier <strong id="fileName-to-delete"></strong> ?</p>
            <p>Cette action est irréversible.</p>
            <div class="modal-buttons">
                <form method="POST">
                    <input type="hidden" name="file_to_delete" id="file-to-delete-input" value="">
                    <button type="button" class="btn close-modal-btn">Annuler</button>
                    <button type="submit" name="delete_file" class="btn btn-delete">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de renommage -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 class="modal-title">Renommer le fichier</h3>
            <form method="POST" id="renameForm">
                <input type="hidden" name="old_file" id="old-file-input" value="">
                <div class="form-group">
                    <label for="newFileName">Nouveau nom du fichier :</label>
                    <input type="text" id="newFileName" name="new_file" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn close-modal-btn">Annuler</button>
                    <button type="submit" name="rename_file" class="btn btn-primary">Renommer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal de suppression
        const deleteModal = document.getElementById("deleteModal");
        const fileNameToDelete = document.getElementById("fileName-to-delete");
        const fileToDeleteInput = document.getElementById("file-to-delete-input");
        const closeModalBtns = document.querySelectorAll(".close-modal, .close-modal-btn");
        
        // Fonction pour afficher le modal de suppression
        function showDeleteModal(fileName) {
            deleteModal.style.display = "block";
            fileNameToDelete.textContent = fileName;
            fileToDeleteInput.value = fileName;
        }
        
        // Gestion de la fermeture du modal
        closeModalBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                deleteModal.style.display = "none";
            });
        });
        
        window.addEventListener("click", (event) => {
            if (event.target === deleteModal) {
                deleteModal.style.display = "none";
            }
        });

        function validateFileName() {
    const fileNameInput = document.querySelector('input[name="file"]');
    let fileName = fileNameInput.value.trim();
    
    // Si le nom n'est pas vide et ne se termine pas par .csv, ajouter l'extension
    if (fileName && !fileName.toLowerCase().endsWith('.csv')) {
        fileName += '.csv';
        fileNameInput.value = fileName;
    }
    
    return true;
}

// Attacher la fonction à l'événement de soumission du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const newFileForm = document.querySelector('.new-file form');
    if (newFileForm) {
        newFileForm.addEventListener('submit', validateFileName);
    }
});

        // Modal de renommage
        const renameModal = document.getElementById("renameModal");
        const oldFileInput = document.getElementById("old-file-input");
        const newFileNameInput = document.getElementById("newFileName");
        
        function showRenameModal(fileName) {
            renameModal.style.display = "block";
            oldFileInput.value = fileName;
            newFileNameInput.value = pathinfo(fileName, PATHINFO_FILENAME);
        }
        
        // Fonction pour extraire le nom du fichier sans extension
        function pathinfo(filename, option) {
            const parts = filename.split('.');
            if (option === 'PATHINFO_FILENAME') {
                return parts[0];
            }
            return parts[1];
        }
        
        // Gestion de la fermeture du modal de renommage
        document.querySelectorAll('.close-modal, .close-modal-btn').forEach(btn => {
            btn.addEventListener("click", () => {
                renameModal.style.display = "none";
            });
        });
        
        // Validation du formulaire de renommage
        document.getElementById('renameForm').addEventListener('submit', function(e) {
            const newFileName = document.getElementById('newFileName').value.trim();
            if (!newFileName) {
                e.preventDefault();
                alert('Veuillez entrer un nom de fichier');
            }
        });
    </script>
</body>
</html>