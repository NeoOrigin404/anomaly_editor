<?php
session_start();

// Récupère la variable POST 'base_url' et charge dans csvContent le fichier CSV s'il existe.
$base_url = "";
$csvContent = "";
$back_url = $_POST['back_url'] ?? $_GET['back_url'] ?? 'index.php';
$home_menu = $_POST['home_menu'] ?? $_GET['home_menu'] ?? 0;
if (isset($_POST['base_url'])) {
    $base_url = $_POST['base_url'];
    
    // S'assurer que le fichier a l'extension .csv
    if (!empty($base_url) && !preg_match('/\.csv$/i', $base_url)) {
        $base_url .= '.csv';
    }
    
    if (file_exists($base_url)) {
        $csvContent = file_get_contents($base_url);
    }
}

// Fonction pour enregistrer le CSV (sera appelée quand le formulaire sera soumis)
if (isset($_POST['save_csv'])) {
    $content = $_POST['content'];
    $save_url = $_POST['save_url'] ?? $base_url;
    $back_url = $_POST['back_url'] ?? $back_url;
    $home_menu = $_POST['home_menu'] ?? $home_menu;
    
    // S'assurer que le fichier a l'extension .csv
    if (!empty($save_url) && !preg_match('/\.csv$/i', $save_url)) {
        $save_url .= '.csv';
    }
    
    // Vérifier si c'est une création ou une modification
    $is_new_file = !file_exists($save_url);
    
    // Enregistrement du fichier
    if (file_put_contents($save_url, $content)) {
        $success = true;
        $message = "Fichier enregistré avec succès !";
        
        // Stocker le chemin du fichier pour l'option de téléchargement
        $saved_file_path = $save_url;
        
        // Mettre à jour les dates dans la session
        if ($is_new_file) {
            // Nouveau fichier : définir la date de création
            $_SESSION['file_created'][$save_url] = time();
            $_SESSION['file_modified'][$save_url] = time();
        } else {
            // Fichier existant : mettre à jour la date de modification
            $_SESSION['file_modified'][$save_url] = time();
        }
    } else {
        $success = false;
        $message = "Erreur lors de l'enregistrement du fichier.";
    }
}

// Gestion de la suppression du fichier
if (isset($_POST['delete_file']) && !empty($_POST['file_to_delete'])) {
    $file_to_delete = $_POST['file_to_delete'];
    if (file_exists($file_to_delete) && unlink($file_to_delete)) {
        header('Location: index.php?deleted='.urlencode($file_to_delete));
        exit;
    } else {
        $delete_error = "Impossible de supprimer le fichier.";
    }
}

// Gestion du téléchargement
if (isset($_GET['download']) && !empty($_GET['file'])) {
    $file_to_download = $_GET['file'];
    if (file_exists($file_to_download)) {
        // Extraction du nom de fichier sans l'extension
        $filename_without_ext = pathinfo(basename($file_to_download), PATHINFO_FILENAME);
        
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        // Forcer l'extension .csv lors du téléchargement
        header('Content-Disposition: attachment; filename="'.$filename_without_ext.'.csv"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
}

// Gestion de l'export CSV (téléchargement avec boîte de dialogue du navigateur)
if (isset($_GET['export']) && !empty($_GET['file'])) {
    $file_to_export = $_GET['file'];
    if (file_exists($file_to_export)) {
        // Extraction du nom de fichier sans l'extension
        $filename_without_ext = pathinfo(basename($file_to_export), PATHINFO_FILENAME);
        
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        // Forcer l'extension .csv lors du téléchargement
        header('Content-Disposition: attachment; filename="'.$filename_without_ext.'.csv"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_to_export));
        readfile($file_to_export);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur de déclaration d'anomalie</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .field-container {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .remove-field {
            position: absolute;
            right: 10px;
            top: 10px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
        }
        .options-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .option-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .buttons {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .additional-options {
            margin-top: 15px;
        }
        .form-check {
            margin-top: 10px;
        }
        .general-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            max-width: 500px;
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
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-export {
            background-color: #17a2b8;
            color: white;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        .field-description {
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-actions">
            <h1>Déclaration d'anomalie</h1>
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-secondary" style="display: flex; align-items: center; gap: 5px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Retour à la fiche
            </a>
        </div>
        
        <?php if (isset($delete_error)): ?>
            <div class="alert alert-danger">
                <?php echo $delete_error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Informations générales -->
        <div class="general-info">
            <div class="form-group">
                <label class="form-label">Nom de la fiche</label>
                <input type="text" id="fileName" value="">
            </div>
        </div>
        
        <!-- Affichage des messages de réussite/erreur -->
        <?php if (isset($success)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
                <?php if ($success && isset($saved_file_path)): ?>
                    <div class="action-buttons">
                        <a href="?download=1&file=<?php echo urlencode($saved_file_path); ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M419.955 297.684H92.044c-25.353-0.008-48.476 10.33-65.076 26.954C10.338 341.23 0 364.368 0 389.721c0 25.353 10.338 48.491 26.968 65.084 16.585 16.63 39.723 26.968 65.076 26.96h327.91c50.86-0.008 92.045-41.206 92.045-92.044 0-50.837-41.169-92.029-92.029-92.037zM457.2 426.959c-9.6 9.562-22.63 15.415-37.246 15.423H92.044c-14.6-0.008-27.645-5.861-37.23-15.423-9.57-9.592-15.416-22.63-15.431-37.238 0.015-14.6 5.861-27.638 15.431-37.23 9.584-9.562 22.63-15.415 37.23-15.423h327.91c14.616 0.008 27.646 5.861 37.246 15.423 9.554 9.592 15.416 22.631 15.416 37.23 0 14.608-5.862 27.646-15.416 37.238z" fill="#ffffff"/>
                                <path d="M339.271 366.406c-12.876 0-23.322 10.438-23.322 23.315 0 12.876 10.446 23.315 23.322 23.315 12.877 0 23.315-10.438 23.315-23.315 0-12.877-10.438-23.315-23.315-23.315zM414.74 366.406c-12.877 0-23.338 10.438-23.338 23.315 0 12.876 10.461 23.315 23.338 23.315 12.877 0 23.308-10.438 23.308-23.315 0-12.877-10.43-23.315-23.308-23.315zM242.658 263.585c3.4 3.777 8.262 5.93 13.338 5.93 5.092 0 9.938-2.154 13.338-5.93l68.23-75.822c4.754-5.261 5.938-12.83 3.061-19.307-2.876-6.469-9.307-10.638-16.384-10.638H292.18V48.18c0-9.907-8.046-17.946-17.954-17.946h-36.461c-9.907 0-17.938 8.038-17.938 17.946v109.637h-32.061c-7.077 0-13.508 4.169-16.4 10.638-2.876 6.476-1.676 14.046 3.062 19.307l68.23 75.822z" fill="#ffffff"/>
                            </svg>
                        </a>
                        <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-secondary">Retour à la fiche</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire côté client -->
        <form id="anomalyForm">
            <input type="hidden" id="baseUrl" value="<?php echo htmlspecialchars($base_url); ?>">
            <div id="fieldsContainer">
                <!-- Les champs seront ajoutés ici par JavaScript -->
            </div>

            <div class="buttons">
                <button type="button" id="addField" class="btn btn-success"><svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 1H6V6L1 6V10H6V15H10V10H15V6L10 6V1Z" fill="#ffffff"/>
                </svg></button>
                <button type="button" id="saveCSV" class="btn btn-primary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M18.1716 1C18.702 1 19.2107 1.21071 19.5858 1.58579L22.4142 4.41421C22.7893 4.78929 23 5.29799 23 5.82843V20C23 21.6569 21.6569 23 20 23H4C2.34315 23 1 21.6569 1 20V4C1 2.34315 2.34315 1 4 1H18.1716ZM4 3C3.44772 3 3 3.44772 3 4V20C3 20.5523 3.44772 21 4 21L5 21L5 15C5 13.3431 6.34315 12 8 12L16 12C17.6569 12 19 13.3431 19 15V21H20C20.5523 21 21 20.5523 21 20V6.82843C21 6.29799 20.7893 5.78929 20.4142 5.41421L18.5858 3.58579C18.2107 3.21071 17.702 3 17.1716 3H17V5C17 6.65685 15.6569 8 14 8H10C8.34315 8 7 6.65685 7 5V3H4ZM17 21V15C17 14.4477 16.5523 14 16 14L8 14C7.44772 14 7 14.4477 7 15L7 21L17 21ZM9 3H15V5C15 5.55228 14.5523 6 14 6H10C9.44772 6 9 5.55228 9 5V3Z" fill="#ffffff"/>
                </svg></button>
                <?php if (!empty($base_url) && file_exists($base_url)): ?>
                    <a href="?download=1&file=<?php echo urlencode($base_url); ?>" class="btn btn-primary"><svg width="16" height="16" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M419.955 297.684H92.044c-25.353-0.008-48.476 10.33-65.076 26.954C10.338 341.23 0 364.368 0 389.721c0 25.353 10.338 48.491 26.968 65.084 16.585 16.63 39.723 26.968 65.076 26.96h327.91c50.86-0.008 92.045-41.206 92.045-92.044 0-50.837-41.169-92.029-92.029-92.037zM457.2 426.959c-9.6 9.562-22.63 15.415-37.246 15.423H92.044c-14.6-0.008-27.645-5.861-37.23-15.423-9.57-9.592-15.416-22.63-15.431-37.238 0.015-14.6 5.861-27.638 15.431-37.23 9.584-9.562 22.63-15.415 37.23-15.423h327.91c14.616 0.008 27.646 5.861 37.246 15.423 9.554 9.592 15.416 22.631 15.416 37.23 0 14.608-5.862 27.646-15.416 37.238z" fill="#ffffff"/>
                                <path d="M339.271 366.406c-12.876 0-23.322 10.438-23.322 23.315 0 12.876 10.446 23.315 23.322 23.315 12.877 0 23.315-10.438 23.315-23.315 0-12.877-10.438-23.315-23.315-23.315zM414.74 366.406c-12.877 0-23.338 10.438-23.338 23.315 0 12.876 10.461 23.315 23.338 23.315 12.877 0 23.308-10.438 23.308-23.315 0-12.877-10.43-23.315-23.308-23.315zM242.658 263.585c3.4 3.777 8.262 5.93 13.338 5.93 5.092 0 9.938-2.154 13.338-5.93l68.23-75.822c4.754-5.261 5.938-12.83 3.061-19.307-2.876-6.469-9.307-10.638-16.384-10.638H292.18V48.18c0-9.907-8.046-17.946-17.954-17.946h-36.461c-9.907 0-17.938 8.038-17.938 17.946v109.637h-32.061c-7.077 0-13.508 4.169-16.4 10.638-2.876 6.476-1.676 14.046 3.062 19.307l68.23 75.822z" fill="#ffffff"/>
                            </svg></a>
                    <button type="button" id="deleteFileBtn" class="btn btn-danger"><svg width="16" height="16" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000">
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
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">Retour</a>
            </div>
        </form>
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
                    <button type="button" class="btn btn-secondary close-modal-btn">Annuler</button>
                    <button type="submit" name="delete_file" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const fieldsContainer = document.getElementById("fieldsContainer");
        const addFieldButton = document.getElementById("addField");
        const saveCSVButton = document.getElementById("saveCSV");
        const baseUrl = document.getElementById("baseUrl").value;
        const fileName = document.getElementById("fileName");
        
        // Modal de suppression
        const deleteModal = document.getElementById("deleteModal");
        const deleteFileBtn = document.getElementById("deleteFileBtn");
        const fileNameToDelete = document.getElementById("fileName-to-delete");
        const fileToDeleteInput = document.getElementById("file-to-delete-input");
        const closeModalBtns = document.querySelectorAll(".close-modal, .close-modal-btn");
        
        // Gestion du modal de suppression
        if (deleteFileBtn) {
            deleteFileBtn.addEventListener("click", () => {
                deleteModal.style.display = "block";
                fileNameToDelete.textContent = baseUrl;
                fileToDeleteInput.value = baseUrl;
            });
        }
        
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

        let fieldCounter = 0;

        // Fonction pour créer un nouveau champ
        function createField(title = "", description = "", options = [], otherChecked = false, otherValue = "", precisionChecked = false, precisionValue = "") {
            const fieldId = `field-${fieldCounter++}`;
            const fieldContainer = document.createElement("div");
            fieldContainer.className = "field-container";
            fieldContainer.id = fieldId;

            const fieldHTML = `
                <button type="button" class="remove-field" onclick="removeField('${fieldId}')">×</button>
                <div>
                    <label class="form-label">Titre du champ</label>
                    <input type="text" class="field-name" value="${title}" required>
                </div>
                <div class="field-description">
                    <label class="form-label">Descriptif</label>
                    <input type="text" class="field-description-text" value="${description}">
                </div>
                <div class="options-container">
                    ${Array.from({ length: 8 }, (_, i) => `
                        <div class="option-row">
                            <input type="checkbox" name="option-${i}" ${options[i] && options[i].checked ? 'checked' : ''}>
                            <input type="text" class="option-text" placeholder="Option ${i + 1}" value="${options[i] && options[i].text ? options[i].text : ''}">
                        </div>
                    `).join("")}
                </div>
                <div class="additional-options">
                    <div class="form-check">
                        <input type="checkbox" id="other-${fieldId}" ${otherChecked ? 'checked' : ''}>
                        <label for="other-${fieldId}">Autre</label>
                        <input type="text" class="other-text" value="${otherValue}" ${!otherChecked ? 'disabled' : ''}>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="precision-${fieldId}" ${precisionChecked ? 'checked' : ''}>
                        <label for="precision-${fieldId}">Précision</label>
                        <input type="text" class="precision-text" value="${precisionValue}" ${!precisionChecked ? 'disabled' : ''}>
                    </div>
                </div>
            `;

            fieldContainer.innerHTML = fieldHTML;
            fieldsContainer.appendChild(fieldContainer);

            // Gestion des champs supplémentaires
            const otherCheckbox = fieldContainer.querySelector(`#other-${fieldId}`);
            const precisionCheckbox = fieldContainer.querySelector(`#precision-${fieldId}`);
            const otherText = fieldContainer.querySelector(".other-text");
            const precisionText = fieldContainer.querySelector(".precision-text");

            otherCheckbox.addEventListener("change", () => {
                otherText.disabled = !otherCheckbox.checked;
            });

            precisionCheckbox.addEventListener("change", () => {
                precisionText.disabled = !precisionCheckbox.checked;
            });
        }

        // Fonction pour supprimer un champ
        window.removeField = (fieldId) => {
            document.getElementById(fieldId).remove();
        };

        // Fonction pour analyser le CSV et créer les champs correspondants
        function parseCSV(csvContent) {
            if (!csvContent) return;
            
            // On utilise le point-virgule comme séparateur au lieu de la virgule
            const lines = csvContent.split('\n');
            if (lines.length < 2) return;
            
            // Vérifier si nous avons une ligne "Nom fiche :" dans le CSV
            let fileNameLine = -1;
            for (let i = 0; i < lines.length; i++) {
                if (lines[i].startsWith("Nom fiche :")) {
                    fileNameLine = i;
                    const cells = lines[i].split(';').map(cell => cell.trim());
                    if (cells.length > 1) {
                        fileName.value = cells[1] || '';
                    }
                    break;
                }
            }
            
            // Récupérer les champs depuis le fichier CSV
            if (lines.length > 3) {
                // La première ligne contient les titres des colonnes
                const headers = lines[0].split(';').map(h => h.trim());
                
                // La deuxième ligne contient les descriptions
                const descriptions = lines[1].split(';').map(d => d.trim());
                
                // Pour chaque colonne (sauf la première qui contient les labels de ligne et la dernière "Fin")
                for (let colIndex = 1; colIndex < headers.length - 1; colIndex++) {
                    // Créer le champ
                    const title = headers[colIndex];
                    if (!title || title === "Fin") continue; // Ignorer les colonnes vides ou "Fin"
                    
                    // Récupérer la description
                    const description = descriptions[colIndex] || "Descriptif";
                    
                    const options = [];
                    
                    // Récupérer les options pour ce champ à partir des lignes suivantes
                    for (let i = 2; i < lines.length; i++) {
                        // Ignorer les lignes spéciales
                        if (lines[i].startsWith("Autre :") || lines[i].startsWith("Precision :") || 
                            lines[i].startsWith("Nom fiche :") || !lines[i].trim()) {
                            continue;
                        }
                        
                        const cells = lines[i].split(';');
                        if (cells.length > colIndex && cells[0].startsWith("Option ")) {
                            const optionIndex = parseInt(cells[0].split(" ")[1]) - 1;
                            if (optionIndex >= 0 && optionIndex < 8) {
                                const cellValue = cells[colIndex].trim();
                                options[optionIndex] = {
                                    checked: cellValue !== "",
                                    text: cellValue
                                };
                            }
                        }
                    }
                    
                    // Chercher les valeurs "Autre" et "Précision" pour ce champ
                    let otherValue = "";
                    let precisionValue = "";
                    
                    // Chercher la ligne "Autre :"
                    for (let i = 0; i < lines.length; i++) {
                        if (lines[i].startsWith("Autre :")) {
                            const cells = lines[i].split(';');
                            if (cells.length > colIndex) {
                                otherValue = cells[colIndex].trim();
                            }
                            break;
                        }
                    }
                    
                    // Chercher la ligne "Precision :"
                    for (let i = 0; i < lines.length; i++) {
                        if (lines[i].startsWith("Precision :")) {
                            const cells = lines[i].split(';');
                            if (cells.length > colIndex) {
                                precisionValue = cells[colIndex].trim();
                            }
                            break;
                        }
                    }
                    
                    createField(
                        title,
                        description,
                        options, 
                        otherValue !== "", 
                        otherValue, 
                        precisionValue !== "", 
                        precisionValue
                    );
                }
            }
        }

        // Fonction pour générer le CSV
        function generateCSV() {
            // Récupérer tous les champs
            const fields = Array.from(document.querySelectorAll(".field-container"));
            const csvData = [];
            
            // La première colonne contient "Titre", suivie des titres de champs, puis "Fin"
            const titles = ["Titre"];
            fields.forEach(field => {
                titles.push(field.querySelector(".field-name").value);
            });
            titles.push("Fin");
            csvData.push(titles);
            
            // Ligne de description - première cellule "Descriptif", puis les descriptions pour chaque champ, puis "Fin"
            const descriptions = ["Descriptif"];
            fields.forEach(field => {
                descriptions.push(field.querySelector(".field-description-text").value || "Descriptif");
            });
            descriptions.push("Fin");
            csvData.push(descriptions);
            
            // Générer les lignes d'options
            for (let optionIndex = 0; optionIndex < 8; optionIndex++) {
                const optionRow = [`Option ${optionIndex + 1}`];
                
                // Pour chaque champ
                for (let fieldIndex = 0; fieldIndex < fields.length; fieldIndex++) {
                    const field = fields[fieldIndex];
                    const optionRows = field.querySelectorAll(".option-row");
                    if (optionIndex < optionRows.length) {
                        const checkbox = optionRows[optionIndex].querySelector('input[type="checkbox"]');
                        const text = optionRows[optionIndex].querySelector(".option-text").value;
                        optionRow.push(checkbox.checked ? text : "");
                    } else {
                        optionRow.push("");
                    }
                }
                optionRow.push("Fin"); // Ajouter "Fin" à la dernière colonne
                csvData.push(optionRow);
            }
            
            // Ligne "Autre :"
            const otherRow = ["Autre :"];
            for (const field of fields) {
                const otherCheckbox = field.querySelector('input[id^="other-"]');
                const otherText = field.querySelector(".other-text").value;
                otherRow.push(otherCheckbox.checked ? otherText : "");
            }
            otherRow.push("Fin"); // Ajouter "Fin" à la dernière colonne
            csvData.push(otherRow);
            
            // Ligne "Precision :"
            const precisionRow = ["Precision :"];
            for (const field of fields) {
                const precisionCheckbox = field.querySelector('input[id^="precision-"]');
                const precisionText = field.querySelector(".precision-text").value;
                precisionRow.push(precisionCheckbox.checked ? precisionText : "");
            }
            precisionRow.push("Fin"); // Ajouter "Fin" à la dernière colonne
            csvData.push(precisionRow);
            
            // Ligne "Nom fiche :"
            const fileNameRow = ["Nom fiche :", fileName.value || ""];
            // Ajouter des cellules vides pour compléter la ligne
            for (let i = 2; i < titles.length - 1; i++) {
                fileNameRow.push("");
            }
            fileNameRow.push("Fin"); // Ajouter "Fin" à la dernière colonne
            csvData.push(fileNameRow);
            
            // Convertir en format CSV avec point-virgule comme séparateur
            const csvContent = csvData
                .map((row) => row.join(";"))
                .join("\n");

            return csvContent;
        }

        // Événements des boutons
        addFieldButton.addEventListener("click", () => createField());

        saveCSVButton.addEventListener("click", () => {
            const csvContent = generateCSV();
            
            // Créer un formulaire pour soumettre les données
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Ajouter les champs du formulaire
            const contentInput = document.createElement('input');
            contentInput.type = 'hidden';
            contentInput.name = 'content';
            contentInput.value = csvContent;
            form.appendChild(contentInput);
            
            const baseUrlInput = document.createElement('input');
            baseUrlInput.type = 'hidden';
            baseUrlInput.name = 'save_url';
            baseUrlInput.value = baseUrl || 'anomalies.csv'; // Utiliser l'URL existante ou un nom par défaut
            form.appendChild(baseUrlInput);
            
            const backUrlInput = document.createElement('input');
            backUrlInput.type = 'hidden';
            backUrlInput.name = 'back_url';
            backUrlInput.value = '<?php echo htmlspecialchars($back_url); ?>';
            form.appendChild(backUrlInput);
            
            const homeMenuInput = document.createElement('input');
            homeMenuInput.type = 'hidden';
            homeMenuInput.name = 'home_menu';
            homeMenuInput.value = '<?php echo htmlspecialchars($home_menu); ?>';
            form.appendChild(homeMenuInput);
            
            const saveAction = document.createElement('input');
            saveAction.type = 'hidden';
            saveAction.name = 'save_csv';
            saveAction.value = '1';
            form.appendChild(saveAction);
            
            // Ajouter et soumettre le formulaire
            document.body.appendChild(form);
            form.submit();
        });

        // Initialiser avec les données CSV si disponibles
        parseCSV(`<?php echo addslashes($csvContent); ?>`);
        
        // Créer le premier champ par défaut si aucun champ n'existe
        if (fieldsContainer.children.length === 0) {
            createField();
        }
    });
    </script>
</body>
</html>