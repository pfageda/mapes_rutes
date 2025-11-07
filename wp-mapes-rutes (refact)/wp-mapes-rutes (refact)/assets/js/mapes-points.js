/**
 * Mapes Points - Gestió de monuments
 */
class MapesPoints {
  constructor() {
    // Variables editMaps i editMarkers eliminades - ja no es necessiten
  }

  selectPoint(pointId) {
    console.log("Seleccionar monument:", pointId);
    const point = window.mapesCore.points.find((p) => p.id == pointId);
    if (!point) return;

    // AFEGIR: Ocultar panell d'edició si està obert
    const appId = window.mapesCore.currentAppId;
    const editPanel = document.getElementById(`edit-panel-${appId}`);
    if (editPanel && editPanel.style.display === "block") {
      editPanel.style.display = "none";
    }

    // Netejar markers existents
    window.mapesCore.clearMarkers();

    // Crear marker arrossegable
    const marker = new google.maps.Marker({
      position: { lat: parseFloat(point.lat), lng: parseFloat(point.lng) },
      map: window.mapesCore.map,
      title: point.title,
      draggable: true,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: window.getPointActivationColor
          ? window.getPointActivationColor(point)
          : "#FF0000",
        fillOpacity: 0.9,
        strokeColor: "#FFFFFF",
        strokeWeight: 3,
        scale: 12,
      },
    });

    // Event listener per quan es mou el marcador
    marker.addListener("dragend", () => {
      const newPos = marker.getPosition();
      const newLat = newPos.lat().toFixed(6);
      const newLng = newPos.lng().toFixed(6);

      // Actualitzar coordenades automàticament
      this.updatePointCoordinates(pointId, newLat, newLng);
    });

    // Centrar mapa en el monument
    window.mapesCore.map.setCenter({
      lat: parseFloat(point.lat),
      lng: parseFloat(point.lng),
    });
    window.mapesCore.map.setZoom(15);
  }

  updatePointCoordinates(pointId, lat, lng) {
    const point = window.mapesCore.points.find((p) => p.id == pointId);
    if (!point) {
      console.error("Monument no trobat per actualitzar coordenades");
      window.mapesUI.showAlert("Error: Monument no trobat");
      return;
    }

    const data = {
      id: pointId,
      title: point.title || "",
      description: point.description || "",
      lat: parseFloat(lat),
      lng: parseFloat(lng),
      // CAMPS OBLIGATORIS PELS VALIDACIONS DEL SERVIDOR
      dme: parseInt(point.DME) || 0, // ⭐ CONVERTIR A NUMBER
      poblacio: (point.Poblacio || "No especificada").trim(),
      provincia: point.Provincia || "Barcelona",
      fitxa_monument: point.Fitxa_Monument || "",
      vegades_activat: parseInt(point.Vegades_activat) || 0, // ⭐ CONVERTIR A NUMBER
      indicatiu_activacio: point.Indicatiu_activacio || "",
    };

    if (point.Darrera_Activacio) {
      data.darrera_activacio = point.Darrera_Activacio;
    }

    console.log("Dades enviades per drag:", data);

    window.mapesCore
      .sendAjaxRequest("mapes_edit_point", data)
      .then(() => {
        console.log(`✅ Coordenades actualitzades via drag: ${lat}, ${lng}`);

        // ⭐ AFEGIR RECARREGA DE PÀGINA (com fa submitInlineEdit)
        setTimeout(() => {
          location.reload();
        }, 1500); // Donar temps per veure la notificació

        // MOSTRAR NOTIFICACIÓ D'ÈXIT
        const notification = document.createElement("div");
        notification.innerHTML = `✅ Coordenades actualitzades: ${lat}, ${lng}<br><small>Recarregant pàgina...</small>`;
        notification.style.cssText =
          "position:fixed; top:20px; right:20px; background:#00a32a; color:white; padding:10px 15px; border-radius:4px; z-index:9999; font-size:13px; text-align:center;";
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
      })
      .catch((error) => {
        console.error("Error actualitzant coordenades:", error);
        window.mapesUI.showAlert("Error actualitzant coordenades: " + error);
      });
  }

  editPoint(pointId) {
    console.log("Editar monument inline:", pointId);

    const point = window.mapesCore.points.find((p) => p.id == pointId);
    if (!point) {
      window.mapesUI.showAlert("Monument no trobat");
      return;
    }

    // Primer, centrar el monument al mapa
    this.selectPoint(pointId);

    // Mostrar panell d'edició
    const appId = window.mapesCore.currentAppId;
    const editPanel = document.getElementById(`edit-panel-${appId}`);
    const editContent = document.getElementById(`edit-content-${appId}`);
    const editTitle = document.getElementById(`edit-title-${appId}`);

    if (!editPanel || !editContent) return;

    // Actualitzar nom
    editTitle.textContent = `Editar: ${point.title}`;

    // Crear formulari inline COMPLET (basat en el HTML que m'has mostrat)
    editContent.innerHTML = `
    <form class="mapes-edit-form" onsubmit="mapesPoints.submitInlineEdit('${pointId}', event)">
        <div class="mapes-edit-form-left">
            <div class="mapes-form-group">
                <label>Nom *</label>
                <input type="text" name="title" value="${
                  point.title || ""
                }" required>
            </div>
             <div class="mapes-form-group">
            <label>Descripció</label>
            <textarea name="description" rows="3" placeholder="Descripció del monument">${
              point.description || ""
            }</textarea>
        </div>
            <div class="mapes-form-group">
                <label>DME</label>
                <input type="number" name="dme" value="${point.DME || 0}">
            </div>
            <div class="mapes-coordinates-grid">
                <div>
                    <label>Població *</label>
                    <input type="text" name="poblacio" value="${
                      point.Poblacio || "No especificada"
                    }" required>
                </div>
                <div>
                    <label>Província *</label>
                    <select name="provincia" required>
                        <option value="Barcelona" ${
                          point.Provincia === "Barcelona" ? "selected" : ""
                        }>Barcelona</option>
                        <option value="Girona" ${
                          point.Provincia === "Girona" ? "selected" : ""
                        }>Girona</option>
                        <option value="Lleida" ${
                          point.Provincia === "Lleida" ? "selected" : ""
                        }>Lleida</option>
                        <option value="Tarragona" ${
                          point.Provincia === "Tarragona" ? "selected" : ""
                        }>Tarragona</option>
                    </select>
                </div>
            </div>
            <div class="mapes-form-group">
                <label>Indicatiu Activació</label>
                <input type="text" name="indicatiu_activacio" value="${
                  point.Indicatiu_activacio || ""
                }">
            </div>
        </div>
        <div class="mapes-edit-form-right">
            <div class="mapes-form-group">
                <label>Coordenades</label>
                <div class="mapes-coordinates-grid">
                    <div>
                        <label>Latitud</label>
                        <input type="number" step="any" name="lat" value="${parseFloat(
                          point.lat
                        ).toFixed(6)}">
                    </div>
                    <div>
                        <label>Longitud</label>
                        <input type="number" step="any" name="lng" value="${parseFloat(
                          point.lng
                        ).toFixed(6)}">
                    </div>
                </div>
            </div>
            <div class="mapes-form-group">
                <label>Fitxa Monument</label>
                <input type="url" name="fitxa_monument" value="${
                  point.Fitxa_Monument || ""
                }">
            </div>
            <div class="mapes-coordinates-grid">
                <div>
                    <label>Vegades Activat</label>
                    <input type="number" name="vegades_activat" value="${
                      point.Vegades_activat || 0
                    }" min="0">
                </div>
                <div>
                    <label>Darrera Activació</label>
                    <input type="datetime-local" name="darrera_activacio" value="${
                      point.Darrera_Activacio
                        ? point.Darrera_Activacio.replace(" ", "T")
                        : ""
                    }">
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualitzar</button>
            <button type="button" class="btn btn-secondary" onclick="cancelEdit('${appId}')">Cancel·lar</button>
        </div>
    </form>`;

    // Mostrar el panell
    editPanel.style.display = "block";

    // Scroll suau cap al panell d'edició
    setTimeout(() => {
      editPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }, 100);
  }

  saveEditPoint(appId) {
    console.log("=== GUARDANT EDICIÓ Monument ===");

    const editPanel = document.getElementById(`edit-panel-${appId}`);
    const pointId = editPanel.dataset.editingPointId;

    if (!pointId) {
      window.mapesUI.showAlert("No hi ha cap monument seleccionat per editar");
      return;
    }

    // Obtenir dades del formulari
    const title = document.getElementById(`edit-point-title-${appId}`).value;
    const description = document.getElementById(
      `edit-point-description-${appId}`
    ).value;
    const lat = document.getElementById(`edit-point-lat-${appId}`).value;
    const lng = document.getElementById(`edit-point-lng-${appId}`).value;

    // Validar dades
    if (!title.trim()) {
      window.mapesUI.showAlert("El nom és obligatori");
      return;
    }

    if (!lat || !lng) {
      window.mapesUI.showAlert("Les coordenades són obligatòries");
      return;
    }

    // Enviar dades
    const data = {
      id: pointId,
      title: title.trim(),
      description: description.trim(),
      lat: parseFloat(lat),
      lng: parseFloat(lng),
    };

    window.mapesCore
      .sendAjaxRequest("mapes_edit_point", data)
      .then(() => {
        window.mapesUI.showAlert("Monument actualitzat correctament");
        editPanel.style.display = "none";
        location.reload();
      })
      .catch((error) => {
        console.error("Error actualitzant monument:", error);
        window.mapesUI.showAlert("Error actualitzant monument: " + error);
      });
  }

  cancelEditPoint(appId) {
    const editPanel = document.getElementById(`edit-panel-${appId}`);
    if (editPanel) {
      editPanel.style.display = "none";
    }
  }

  deletePoint(pointId) {
    if (!confirm("Estàs segur que vols eliminar aquest monument?")) {
      return;
    }

    console.log("=== ELIMINANT Monument ===", pointId);

    window.mapesCore
      .sendAjaxRequest("mapes_delete_point", { id: pointId })
      .then(() => {
        window.mapesUI.showAlert("Monument eliminat correctament");
        location.reload();
      })
      .catch((error) => {
        console.error("Error eliminant monument:", error);
        window.mapesUI.showAlert("Error eliminant monument: " + error);
      });
  }

  // FUNCIÓ PRINCIPAL MODIFICADA
  submitAddPoint(appId, event) {
    console.log("=== AFEGINT NOU Monument ===");
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Validar nom
    if (!data.title || data.title.trim() === "") {
      window.mapesUI.showAlert("El nom és obligatori");
      return;
    }

    // Detectar mode (coordenades vs ubicació)
    const coordButton = document.querySelector(
      `#modal-add-point-${appId} .mapes-input-toggle button:nth-child(2)`
    );
    const isCoordinatesMode =
      coordButton && coordButton.classList.contains("active");

    if (isCoordinatesMode) {
      this.processCoordinatesMode(data, appId);
    } else {
      this.processLocationMode(data, appId);
    }
  }

  submitInlineEdit(pointId, event) {
    console.log("=== SUBMIT INLINE EDIT Monument ===", pointId);
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Validar dades obligatòries
    if (!data.title || data.title.trim() === "") {
      window.mapesUI.showAlert("El nom és obligatori");
      return;
    }

    if (!data.lat || !data.lng) {
      window.mapesUI.showAlert("Les coordenades són obligatòries");
      return;
    }

    // Preparar dades completes per enviar
    const pointData = {
      id: pointId,
      title: data.title.trim(),
      description: data.description ? data.description.trim() : "",
      lat: parseFloat(data.lat),
      lng: parseFloat(data.lng),
      dme: data.dme ? parseInt(data.dme) : 0,
      poblacio: data.poblacio ? data.poblacio.trim() : "No especificada",
      provincia: data.provincia,
      fitxa_monument: data.fitxa_monument ? data.fitxa_monument.trim() : "",
      vegades_activat: data.vegades_activat
        ? parseInt(data.vegades_activat)
        : 0,
      indicatiu_activacio: data.indicatiu_activacio
        ? data.indicatiu_activacio.trim()
        : "",
    };

    if (data.darrera_activacio && data.darrera_activacio.trim()) {
      pointData.darrera_activacio = data.darrera_activacio.trim();
    }

    console.log("Dades a enviar:", pointData);

    // Enviar petició AJAX
    window.mapesCore
      .sendAjaxRequest("mapes_edit_point", pointData)
      .then(() => {
        window.mapesUI.showAlert("Monument actualitzat correctament!");

        // Ocultar panell d'edició
        const appId = window.mapesCore.currentAppId;
        const editPanel = document.getElementById(`edit-panel-${appId}`);
        if (editPanel) {
          editPanel.style.display = "none";
        }

        // Recarregar la pàgina per mostrar els canvis
        location.reload();
      })
      .catch((error) => {
        console.error("Error actualitzant monument:", error);
        window.mapesUI.showAlert("Error actualitzant monument: " + error);
      });
  }

  // NOVA FUNCIÓ: MODE COORDENADES
  processCoordinatesMode(data, appId) {
    console.log("=== MODE COORDENADES ===");

    // Validació de coordenades
    if (
      !data.lat ||
      !data.lng ||
      data.lat.trim() === "" ||
      data.lng.trim() === ""
    ) {
      window.mapesUI.showAlert(
        "Les coordenades són obligatòries en mode coordenades"
      );
      return;
    }

    const lat = parseFloat(data.lat);
    const lng = parseFloat(data.lng);

    // Validar format numèric
    if (isNaN(lat) || isNaN(lng)) {
      window.mapesUI.showAlert("Coordenades no vàlides");
      return;
    }

    // Validar rang de coordenades
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
      window.mapesUI.showAlert("Coordenades fora del rang vàlid");
      return;
    }

    // Assignar coordenades validades
    data.lat = lat;
    data.lng = lng;

    // Enviar dades del monument
    this.sendPointData(data, appId);
  }

  // NOVA FUNCIÓ: MODE UBICACIÓ
  processLocationMode(data, appId) {
    console.log("=== MODE UBICACIÓ ===");

    // Validació del nom de lloc
    if (!data.location_name || data.location_name.trim() === "") {
      window.mapesUI.showAlert(
        "El nom del lloc és obligatori en mode ubicació"
      );
      return;
    }

    const locationName = data.location_name.trim();

    // Geocodificació amb Google Maps
    if (typeof google !== "undefined" && google.maps && google.maps.Geocoder) {
      const geocoder = new google.maps.Geocoder();

      geocoder.geocode(
        {
          address: locationName + ", Catalunya, Espanya",
        },
        (results, status) => {
          if (status === "OK" && results[0]) {
            // Geocodificació exitosa
            data.lat = results[0].geometry.location.lat();
            data.lng = results[0].geometry.location.lng();
            console.log(
              `Geocodificat: ${locationName} -> ${data.lat}, ${data.lng}`
            );
          } else {
            // Si falla, coordenades per defecte de Barcelona
            console.warn(
              "Geocodificació fallida, usant coordenades per defecte"
            );
            data.lat = 41.3851;
            data.lng = 2.1734;
          }

          // Enviar dades amb coordenades
          this.sendPointData(data, appId);
        }
      );
    } else {
      // No hi ha Google Maps disponible, usar coordenades per defecte
      console.warn("Google Maps no disponible, usant coordenades per defecte");
      data.lat = 41.3851;
      data.lng = 2.1734;

      this.sendPointData(data, appId);
    }
  }

  // NOVA FUNCIÓ: ENVIAR DADES DEL Monument
  sendPointData(data, appId) {
    console.log("=== ENVIANT DADES DEL Monument ===", data);

    // Només enviar els 4 camps essencials
    const pointData = {
      title: data.title.trim(),
      description: (data.description || "").trim(),
      lat: parseFloat(data.lat),
      lng: parseFloat(data.lng),
      poblacio: (data.poblacio || "").trim(),
      provincia: data.provincia || "",
    };

    //DEBUG PER VEURE QUÈ S'ENVIA
    console.log("Dades enviades al servidor:", pointData);

    window.mapesCore
      .sendAjaxRequest("mapes_add_point", pointData)
      .then(() => {
        window.mapesUI.showAlert(
          `Monument "${pointData.title}" afegit correctament!`
        );
        closeModal(`modal-add-point-${appId}`);
        location.reload();
      })
      .catch((error) => {
        console.error("Error afegint monument:", error);
        window.mapesUI.showAlert("Error afegint monument: " + error);
      });
  }
}

// Instanciar globalment
window.mapesPoints = new MapesPoints();

// FUNCIONS GLOBALS per cridar des del HTML
function selectPoint(pointId) {
  window.mapesPoints.selectPoint(pointId);
}

function editPoint(pointId) {
  window.mapesPoints.editPoint(pointId);
}

function deletePoint(pointId) {
  window.mapesPoints.deletePoint(pointId);
}

function submitAddPoint(appId, event) {
  window.mapesPoints.submitAddPoint(appId, event);
}
function submitInlineEdit(pointId, event) {
  window.mapesPoints.submitInlineEdit(pointId, event);
}

function saveEditPoint(appId) {
  window.mapesPoints.saveEditPoint(appId);
}

function cancelEditPoint(appId) {
  window.mapesPoints.cancelEditPoint(appId);
}
