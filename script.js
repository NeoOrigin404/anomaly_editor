document.addEventListener("DOMContentLoaded", () => {
  const fieldsContainer = document.getElementById("fieldsContainer");
  const addFieldButton = document.getElementById("addField");
  const saveCSVButton = document.getElementById("saveCSV");

  let fieldCounter = 0;

  // Fonction pour créer un nouveau champ
  function createField() {
    const fieldId = `field-${fieldCounter++}`;
    const fieldContainer = document.createElement("div");
    fieldContainer.className = "field-container";
    fieldContainer.id = fieldId;

    const fieldHTML = `
              <button type="button" class="remove-field" onclick="removeField('${fieldId}')">×</button>
              <div>
                  <label class="form-label">Titre du champ</label>
                  <input type="text" class="field-name" required>
              </div>
              <div class="options-container">
                  ${Array.from(
                    { length: 8 },
                    (_, i) => `
                      <div class="option-row">
                          <input type="checkbox" name="option-${i}">
                          <input type="text" class="option-text" placeholder="Option ${
                            i + 1
                          }">
                      </div>
                  `
                  ).join("")}
              </div>
              <div class="additional-options">
                  <div class="form-check">
                      <input type="checkbox" id="other-${fieldId}">
                      <label for="other-${fieldId}">Autre</label>
                      <input type="text" class="other-text" disabled>
                  </div>
                  <div class="form-check">
                      <input type="checkbox" id="precision-${fieldId}">
                      <label for="precision-${fieldId}">Précision</label>
                      <input type="text" class="precision-text" disabled>
                  </div>
              </div>
          `;

    fieldContainer.innerHTML = fieldHTML;
    fieldsContainer.appendChild(fieldContainer);

    // Gestion des champs supplémentaires
    const otherCheckbox = fieldContainer.querySelector(`#other-${fieldId}`);
    const precisionCheckbox = fieldContainer.querySelector(
      `#precision-${fieldId}`
    );
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

  // Fonction pour générer le CSV
  function generateCSV() {
    const fields = Array.from(document.querySelectorAll(".field-container"));
    const csvData = [];

    const headers = ["Champ", "Réponse", "Autre", "Précision"];
    csvData.push(headers);

    for (const field of fields) {
      const fieldName = field.querySelector(".field-name").value;

      const options = Array.from(field.querySelectorAll(".option-row"))
        .map((row) => {
          const checkbox = row.querySelector('input[type="checkbox"]');
          const text = row.querySelector(".option-text").value;
          return checkbox.checked ? text : null;
        })
        .filter(Boolean);

      const otherCheckbox = field.querySelector(
        '.form-check input[type="checkbox"]'
      );
      const otherText = field.querySelector(".other-text").value;
      const precisionText = field.querySelector(".precision-text").value;

      const row = [
        fieldName,
        options.join("; "),
        otherCheckbox.checked ? otherText : "",
        precisionText,
      ];

      csvData.push(row);
    }

    // Convertir en format CSV
    const csvContent = csvData
      .map((row) =>
        row.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(",")
      )
      .join("\n");

    return csvContent;
  }

  // Événements des boutons
  addFieldButton.addEventListener("click", createField);

  saveCSVButton.addEventListener("click", () => {
    const csv = generateCSV();

    fetch("api.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ content: csvContent, base_url: base_url }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Fichier enregistré avec succès !");
        } else {
          alert(`Erreur lors de l'enregistrement : ${data.message}`);
        }
      })
      .catch((error) => {
        alert(`Erreur lors de l'enregistrement : ${error.message}`);
      });
  });

  // Créer le premier champ par défaut
  createField();
});
