<?php
session_start();

// Liste des fichiers CSV dans le répertoire courant
$csvFiles = glob('*.csv');

// Fonction pour nettoyer le nom du fichier
function cleanFileName($filename) {
    // Supprimer l'extension .csv si présente
    $filename = preg_replace('/\.csv$/i', '', $filename);
    
    // Remplacer les caractères spéciaux par des tirets
    $filename = preg_replace('/[^a-zA-Z0-9]/', '-', $filename);
    
    // Supprimer les tirets multiples
    $filename = preg_replace('/-+/', '-', $filename);
    
    // Supprimer les tirets au début et à la fin
    $filename = trim($filename, '-');
    
    // Convertir en minuscules
    $filename = strtolower($filename);
    
    // Si le nom est vide après nettoyage, utiliser un nom par défaut
    if (empty($filename)) {
        $filename = 'anomalies';
    }
    
    // Ajouter l'extension .csv
    return $filename . '.csv';
}

// Gestion du renommage du fichier
if (isset($_POST['rename_file']) && !empty($_POST['old_file']) && !empty($_POST['new_file'])) {
    $old_file = $_POST['old_file'];
    $new_file = cleanFileName($_POST['new_file']);
    
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
        <h2>Espace de création</h2>
            <?php foreach ($csvFiles as $file): ?>
                <div class="file-item">
                    <div>
                        <p><strong><?php echo htmlspecialchars(pathinfo($file, PATHINFO_FILENAME)); ?></strong></p>
                        <?php if (isset($_SESSION['file_created'][$file])): ?>
                            <p class="file-date">Créé le <?php echo date('d/m/Y à H:i', $_SESSION['file_created'][$file]); ?></p>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['file_modified'][$file]) && 
                                (!isset($_SESSION['file_created'][$file]) || 
                                 $_SESSION['file_modified'][$file] > $_SESSION['file_created'][$file])): ?>
                            <p class="file-date">Dernière modification le <?php echo date('d/m/Y à H:i', $_SESSION['file_modified'][$file]); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="file-actions">
                        <form action="anomaly_editor.php" method="post" style="display: inline;">
                            <input type="hidden" name="base_url" value="<?php echo htmlspecialchars($file); ?>">
                            <input type="hidden" name="back_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
                            <input type="hidden" name="home_menu" value="1">
                            <button type="submit" class="btn"><svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<g id="SVGRepo_bgCarrier" stroke-width="0"/>
<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
<g id="SVGRepo_iconCarrier"> <path d="M11 4H7.2C6.0799 4 5.51984 4 5.09202 4.21799C4.71569 4.40974 4.40973 4.7157 4.21799 5.09202C4 5.51985 4 6.0799 4 7.2V16.8C4 17.9201 4 18.4802 4.21799 18.908C4.40973 19.2843 4.71569 19.5903 5.09202 19.782C5.51984 20 6.0799 20 7.2 20H16.8C17.9201 20 18.4802 20 18.908 19.782C19.2843 19.5903 19.5903 19.2843 19.782 18.908C20 18.4802 20 17.9201 20 16.8V12.5M15.5 5.5L18.3284 8.32843M10.7627 10.2373L17.411 3.58902C18.192 2.80797 19.4584 2.80797 20.2394 3.58902C21.0205 4.37007 21.0205 5.6364 20.2394 6.41745L13.3774 13.2794C12.6158 14.0411 12.235 14.4219 11.8012 14.7247C11.4162 14.9936 11.0009 15.2162 10.564 15.3882C10.0717 15.582 9.54378 15.6885 8.48793 15.9016L8 16L8.04745 15.6678C8.21536 14.4925 8.29932 13.9048 8.49029 13.3561C8.65975 12.8692 8.89125 12.4063 9.17906 11.9786C9.50341 11.4966 9.92319 11.0768 10.7627 10.2373Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/> </g>
</svg></button>
                        </form>
                        <button type="button" class="btn btn-rename" onclick="showRenameModal('<?php echo htmlspecialchars($file); ?>')"><svg width="16px" height="16px" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#ffffff">
<g id="SVGRepo_bgCarrier" stroke-width="0"/>
<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
<g id="SVGRepo_iconCarrier"> <title>rename</title> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Combined-Shape" fill="#000000" transform="translate(42.666667, 64.000000)"> <path d="M362.666667,1.42108547e-14 L362.666667,21.3333333 L320,21.333 L320,362.666 L362.666667,362.666667 L362.666667,384 L320,383.999 L320,384 L298.666667,384 L298.666,383.999 L256,384 L256,362.666667 L298.666,362.666 L298.666,21.333 L256,21.3333333 L256,1.42108547e-14 L362.666667,1.42108547e-14 Z M426.666667,64 L426.666667,320 L341.333333,320 L341.333333,277.333333 L384,277.333333 L384,106.666667 L341.333333,106.666667 L341.333333,64 L426.666667,64 Z M277.333333,64 L277.333333,320 L3.55271368e-14,320 L3.55271368e-14,64 L277.333333,64 Z M179.2,89.6 L149.333333,89.6 L149.333333,234.666667 C149.333333,248 148.5,256.333333 147.875,264.354167 L147.792993,265.422171 L147.792993,265.422171 L147.714003,266.48894 C147.417695,270.579012 147.2,274.696296 147.2,279.466667 L147.2,279.466667 L177.066667,279.466667 L177.066667,260.266667 C184.941497,273.926888 199.708077,282.130544 215.466667,281.6 C229.540046,281.805757 242.921593,275.508559 251.733333,264.533333 C263.162478,248.989677 269.832496,230.461848 270.933333,211.2 C270.933333,170.666667 249.6,142.933333 217.6,142.933333 C202.507405,142.999748 188.308689,150.099106 179.2,162.133333 L179.2,162.133333 L179.2,89.6 Z M119.466667,162.133333 C107.961824,149.843793 91.4322333,143.546807 74.6666667,145.066667 C57.6785115,144.485924 40.8138255,148.15216 25.6,155.733333 L25.6,155.733333 L34.1333333,177.066667 C45.3979052,171.147831 57.7246848,167.522308 70.4,166.4 C78.5613135,165.511423 86.6853595,168.371259 92.4903835,174.176283 C98.2954074,179.981307 101.155244,188.105353 100.266667,196.266667 L100.266667,196.266667 L100.266667,198.4 L78.9333333,198.4 C65.8181975,197.679203 52.705771,199.864608 40.5333333,204.8 C26.2806563,210.950309 17.6507691,225.621117 19.2,241.066667 C19.0625857,252.057651 23.6679763,262.574827 31.8381493,269.927982 C40.0083223,277.281138 50.9508304,280.757072 61.8666667,279.466667 C77.2795695,280.291768 92.2192911,274.001359 102.4,262.4 L102.4,262.4 L102.4,277.333333 L130.133333,277.333333 C128.292479,266.054406 127.577851,254.620365 128,243.2 L128,243.2 L128,204.8 C129.999138,190.023932 126.995128,175.003882 119.466667,162.133333 Z M98.1333333,213.333333 L98.1333333,238.933333 C92.082572,249.988391 80.836024,257.218314 68.2666667,258.133333 C63.0655139,258.520242 57.9538681,256.621996 54.2659359,252.934064 C50.5780036,249.246132 48.6797582,244.134486 49.0666667,238.933333 C49.0666667,224 59.7333333,215.466667 85.3333333,213.333333 L85.3333333,213.333333 L98.1333333,213.333333 Z M209.066667,166.4 C226.133333,166.4 238.933333,183.466667 238.933333,211.2 C238.933333,238.933333 228.266667,256 211.2,256 C197.298049,255.69869 184.825037,247.383349 179.2,234.666667 L179.2,234.666667 L179.2,187.733333 C185.154203,176.240507 196.263981,168.304951 209.066667,166.4 Z"> </path> </g> </g> </g>
</svg></button>
                        <button type="button" class="btn btn-delete" onclick="showDeleteModal('<?php echo htmlspecialchars($file); ?>')"><svg width="16" height="16" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"/>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
                        <g id="SVGRepo_iconCarrier">
                            <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                <g id="Icon-Set" sketch:type="MSLayerGroup" transform="translate(-259.000000, -203.000000)" fill="#ffffff">
                                    <path d="M282,211 L262,211 C261.448,211 261,210.553 261,210 C261,209.448 261.448,209 262,209 L282,209 C282.552,209 283,209.448 283,210 C283,210.553 282.552,211 282,211 L282,211 Z M281,231 C281,232.104 280.104,233 279,233 L265,233 C263.896,233 263,232.104 263,231 L263,213 L281,213 L281,231 L281,231 Z M269,206 C269,205.447 269.448,205 270,205 L274,205 C274.552,205 275,205.447 275,206 L275,207 L269,207 L269,206 L269,206 Z M283,207 L277,207 L277,205 C277,203.896 276.104,203 275,203 L269,203 C267.896,203 267,203.896 267,205 L267,207 L261,207 C259.896,207 259,207.896 259,209 L259,211 C259,212.104 259.896,213 261,213 L261,231 C261,233.209 262.791,235 265,235 L279,235 C281.209,235 283,233.209 283,231 L283,213 C284.104,213 285,212.104 285,211 L285,209 C285,207.896 284.104,207 283,207 L283,207 Z M272,231 C272.552,231 273,230.553 273,230 L273,218 C273,217.448 272.552,217 272,217 C271.448,217 271,217.448 271,218 L271,230 C271,230.553 271.448,231 272,231 L272,231 Z M267,231 C267.552,231 268,230.553 268,230 L268,218 C268,217.448 267.552,217 267,217 C266.448,217 266,217.448 266,218 L266,230 C266,230.553 266.448,231 267,231 L267,231 Z M277,231 C277.552,231 278,230.553 278,230 L278,218 C278,217.448 277.552,217 277,217 C276.448,217 276,217.448 276,218 L276,230 C276,230.553 276.448,231 277,231 L277,231 Z" id="trash" sketch:type="MSShapeGroup"></path>
                                </g>
                            </g>
                        </g>
                    </svg></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Aucun fichier trouvé dans le répertoire.</p>
    <?php endif; ?>
    
    <div class="new-file">
        <h2>Créer un nouveau fichier</h2>
        <form action="anomaly_editor.php" method="post" onsubmit="return validateFileName(this);">
            <input type="text" name="base_url" placeholder="Nom du nouveau fichier (ex: anomalies)" required>
            <input type="hidden" name="back_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
            <input type="hidden" name="home_menu" value="1">
            <button type="submit">
                <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 1H6V6L1 6V10H6V15H10V10H15V6L10 6V1Z" fill="#ffffff"/>
                </svg>
            </button>
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

        function validateFileName(form) {
            const fileNameInput = form.querySelector('input[name="base_url"]');
            let fileName = fileNameInput.value.trim();
            
            // Nettoyer le nom du fichier côté client
            fileName = fileName.replace(/[^a-zA-Z0-9]/g, '-')
                             .replace(/-+/g, '-')
                             .replace(/^-|-$/g, '')
                             .toLowerCase();
            
            if (!fileName) {
                fileName = 'anomalies';
            }
            
            fileNameInput.value = fileName;
            return true;
        }
        
        // Attacher la fonction à l'événement de soumission du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const newFileForm = document.querySelector('.new-file form');
            if (newFileForm) {
                newFileForm.addEventListener('submit', function(e) {
                    validateFileName(this);
                });
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