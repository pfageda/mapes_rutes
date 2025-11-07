/**
 * Mapes Admin Scripts
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("Mapes Admin carregat");

  // Validació del formulari d'API Key
  const apiKeyForm = document.querySelector('form[method="post"]');
  if (apiKeyForm) {
    apiKeyForm.addEventListener("submit", function (e) {
      const apiKeyInput = document.querySelector(
        'input[name="google_api_key"]'
      );
      if (apiKeyInput && apiKeyInput.value.trim() === "") {
        if (
          !confirm(
            "Vols guardar amb API Key buida? El plugin no funcionarà correctament."
          )
        ) {
          e.preventDefault();
        }
      }
    });
  }

  // Copiar shortcode al clipboard
  const shortcodeElements = document.querySelectorAll("code");
  shortcodeElements.forEach(function (element) {
    element.addEventListener("click", function () {
      const text = this.textContent;
      navigator.clipboard.writeText(text).then(function () {
        // Crear notificació temporal
        const notification = document.createElement("div");
        notification.textContent = "Shortcode copiat!";
        notification.style.cssText = `
                    position: fixed;
                    top: 30px;
                    right: 30px;
                    background: #00a32a;
                    color: white;
                    padding: 10px 15px;
                    border-radius: 4px;
                    z-index: 9999;
                    font-size: 14px;
                `;
        document.body.appendChild(notification);

        setTimeout(function () {
          notification.remove();
        }, 2000);
      });
    });

    // Afegir cursor pointer
    element.style.cursor = "pointer";
    element.title = "Clic per copiar";
  });
});

// FUNCIONS PER GESTIÓ D'ACTIVACIONS
function confirmarActivacio(activationId) {
  if (
    !confirm("Confirmar aquesta activació? Aquesta acció no es pot desfer.")
  ) {
    return;
  }

  fetch(ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "mapes_confirm_activation",
      activation_id: activationId,
      nonce: mapesAdminNonce || "",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("✅ Activació confirmada correctament!");
        location.reload();
      } else {
        alert("❌ Error: " + (data.data || "Error desconegut"));
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("❌ Error de connexió");
    });
}

function rebutjarActivacio(activationId) {
  const motiu = prompt("Motiu del rebuig (opcional):");
  if (motiu === null) return;

  fetch(ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "mapes_reject_activation",
      activation_id: activationId,
      rejection_reason: motiu || "",
      nonce: mapesAdminNonce || "",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("✅ Activació rebutjada.");
        location.reload();
      } else {
        alert("❌ Error: " + (data.data || "Error desconegut"));
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("❌ Error de connexió");
    });
}

function viewDetails(activationId) {
  // Simple implementació - expandir detalls
  const detailsRow = document.querySelector(
    `[data-activation="${activationId}"]`
  );
  if (detailsRow) {
    detailsRow.style.display =
      detailsRow.style.display === "none" ? "" : "none";
  }
}
