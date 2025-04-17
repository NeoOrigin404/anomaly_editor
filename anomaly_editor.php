<?php
// Récupère la variable POST 'base_url' et charge dans csvContent le fichier CSV s'il existe.
$base_url = "";
$csvContent = "";
if (isset($_POST['base_url'])) {
    $base_url = $_POST['base_url'];
    if (file_exists($base_url)) {
        $csvContent = file_get_contents($base_url);
    }
}

// Fonction pour enregistrer le CSV (sera appelée quand le formulaire sera soumis)
if (isset($_POST['save_csv'])) {
    $content = $_POST['content'];
    $save_url = $_POST['save_url'] ?? $base_url;
    
    // Enregistrement du fichier
    if (file_put_contents($save_url, $content)) {
        $success = true;
        $message = "Fichier enregistré avec succès !";
        // Stocker le chemin du fichier pour l'option de téléchargement
        $saved_file_path = $save_url;
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
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.basename($file_to_download).'"');
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
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.basename($file_to_export).'"');
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
            <a href="index.php" class="btn btn-secondary">Retour à la liste</a>
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
                        <a href="?download=1&file=<?php echo urlencode($saved_file_path); ?>" class="btn btn-primary">Télécharger le fichier</a>
                        <a href="index.php" class="btn btn-secondary">Retour à la liste</a>
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
                <button type="button" id="addField" class="btn btn-success">Ajouter un champ</button>
                <button type="button" id="saveCSV" class="btn btn-primary">Enregistrer</button>
                <?php if (!empty($base_url) && file_exists($base_url)): ?>
                    <a href="?download=1&file=<?php echo urlencode($base_url); ?>" class="btn btn-primary">Télécharger</a>
                    <button type="button" id="deleteFileBtn" class="btn btn-danger">Supprimer le fichier</button>
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
                            <input type="text" class="option-text" placeholder="Option ${i + 1}" value="${options[i] ? options[i].text : ""}">
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
                                options[optionIndex] = {
                                    checked: cells[colIndex].trim() !== "",
                                    text: cells[colIndex].trim() || `Option ${optionIndex + 1}`
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