/**
 * Mapes Routes - Gestió de rutes
 */
class MapesRoutes {
  constructor() {
    // Variables editMaps, editMarkers i editLines eliminades - ja no es necessiten
  }

  selectRoute(appId, routeId) {
    console.log("Seleccionar ruta:", routeId);

    // Ocultar panell d'edició
    const editPanel = document.getElementById(`edit-panel-${appId}`);
    if (editPanel) {
      editPanel.style.display = "none";
    }

    // Netejar selecció actual
    document.querySelectorAll(".mapes-route-item").forEach((item) => {
      item.classList.remove("selected");
    });

    if (routeId === null) {
      // Mostrar tots els monuments
      window.mapesCore.resetView();
      document
        .querySelector('.mapes-route-item[onclick*="null"]')
        .classList.add("selected");
      // Assegurar que la llista de monuments es mostra
      const pointsList = document.getElementById(`points-list-${appId}`);
      if (pointsList) {
        pointsList.style.display = "block";
      }
    } else {
      // Mostrar ruta específica
      const route = window.mapesCore.routes.find((r) => r.id == routeId);
      if (route) {
        this.displayRoute(route);
        const routeItem = document.querySelector(`[onclick*="'${routeId}'"]`);
        if (routeItem) {
          routeItem.classList.add("selected");
        }
        // Ocultar llista de monuments quan es mostra una ruta
        const pointsList = document.getElementById(`points-list-${appId}`);
        if (pointsList) {
          pointsList.style.display = "none";
        }
      }
    }
  }

  displayRoute(route) {
    if (!route.points || route.points.length === 0) return;

    window.mapesCore.clearMarkers();

    // Ordenar monuments per ordre
    const routePoints = route.points
      .map((rp) => {
        const point = window.mapesCore.points.find((p) => p.id == rp.point_id);
        return point ? { ...point, order: rp.order_num } : null;
      })
      .filter((p) => p)
      .sort((a, b) => a.order - b.order);

    // Crear markers numerats
    routePoints.forEach((point, index) => {
      const marker = new google.maps.Marker({
        position: { lat: parseFloat(point.lat), lng: parseFloat(point.lng) },
        map: window.mapesCore.map,
        title: `${index + 1}. ${point.title}`,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          fillColor: route.color,
          fillOpacity: 0.9,
          strokeColor: "#FFFFFF",
          strokeWeight: 2,
          scale: 10,
        },
        label: {
          text: (index + 1).toString(),
          color: "#FFFFFF",
          fontWeight: "bold",
        },
      });
      window.mapesCore.markers.push(marker);
    });

    // Crear línia de ruta
    if (routePoints.length > 1) {
      const path = routePoints.map((point) => ({
        lat: parseFloat(point.lat),
        lng: parseFloat(point.lng),
      }));

      const routeLine = new google.maps.Polyline({
        path: path,
        geodesic: true,
        strokeColor: route.color,
        strokeOpacity: 0.8,
        strokeWeight: 4,
      });

      routeLine.setMap(window.mapesCore.map);
      window.mapesCore.markers.push(routeLine);
    }

    // Ajustar vista
    if (routePoints.length === 1) {
      window.mapesCore.map.setCenter({
        lat: parseFloat(routePoints[0].lat),
        lng: parseFloat(routePoints[0].lng),
      });
      window.mapesCore.map.setZoom(12);
    } else {
      const bounds = new google.maps.LatLngBounds();
      routePoints.forEach((point) => {
        bounds.extend({
          lat: parseFloat(point.lat),
          lng: parseFloat(point.lng),
        });
      });
      window.mapesCore.map.fitBounds(bounds);
    }
  }

  submitCreateRoute(appId, event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!data.code || data.code.trim() === "") {
      window.mapesUI.showAlert("El codi de ruta és obligatori");
      return;
    }

    if (!data.name || data.name.trim() === "") {
      window.mapesUI.showAlert("El nom de ruta és obligatori");
      return;
    }

    const selectedPoints = this.getSelectedPointsFromForm(
      `modal-create-route-${appId}`
    );

    if (selectedPoints.length < 2) {
      window.mapesUI.showAlert(
        "Cal seleccionar mínim 2 monuments per crear una ruta"
      );
      return;
    }

    const confirmMessage = `Crear ruta "${data.code} - ${data.name}"?\n${selectedPoints.length} monuments seleccionats`;
    if (!window.mapesUI.showConfirm(confirmMessage)) {
      return;
    }

    window.mapesCore
      .sendAjaxRequest("mapes_create_route", {
        code: data.code.trim(),
        name: data.name.trim(),
        color: data.color,
        points: JSON.stringify(selectedPoints),
      })
      .then(() => {
        window.mapesUI.showAlert(`Ruta "${data.code}" creada amb èxit!`);
        window.mapesUI.closeModal(`modal-create-route-${appId}`);
        location.reload();
      })
      .catch((error) => {
        window.mapesUI.showAlert(`Error creant ruta: ${error}`);
      });
  }

  getSelectedPointsFromForm(modalId) {
    const selectedPoints = [];
    const checkboxes = document.querySelectorAll(
      `#${modalId} input[name="points[]"]:checked`
    );

    checkboxes.forEach((checkbox, index) => {
      const controls = checkbox.parentElement.querySelector(
        ".mapes-route-point-controls"
      );
      let order = index + 1;
      let weight = 1;

      if (controls && controls.style.display !== "none") {
        const orderInput = controls.querySelector('input[placeholder="Ordre"]');
        const weightInput = controls.querySelector('input[placeholder="Pes"]');

        if (orderInput && orderInput.value) order = parseInt(orderInput.value);
        if (weightInput && weightInput.value)
          weight = parseFloat(weightInput.value);
      }

      selectedPoints.push({
        point_id: checkbox.value,
        order: order,
        weight: weight,
      });
    });

    return selectedPoints.sort((a, b) => a.order - b.order);
  }

  deleteRoute(routeId) {
    const route = window.mapesCore.routes.find((r) => r.id == routeId);
    const routeInfo = route
      ? `"${route.code} - ${route.name}"`
      : `ID: ${routeId}`;

    const confirmMessage = `Eliminar la ruta ${routeInfo}?\n\n⚠️ Aquesta acció no es pot desfer.\nEls monuments NO s'eliminaran.`;

    if (!window.mapesUI.showConfirm(confirmMessage)) {
      return;
    }

    window.mapesCore
      .sendAjaxRequest("mapes_delete_route", {
        id: routeId,
      })
      .then(() => {
        window.mapesUI.showAlert(`Ruta ${routeInfo} eliminada amb èxit!`);
        location.reload();
      })
      .catch((error) => {
        window.mapesUI.showAlert(`Error eliminant ruta: ${error}`);
      });
  }

  editRoute(routeId) {
    console.log("Editar ruta inline:", routeId);

    const route = window.mapesCore.routes.find((r) => r.id == routeId);
    if (!route) {
      window.mapesUI.showAlert("Ruta no trobada");
      return;
    }

    // Primer mostrar la ruta al mapa
    this.displayRoute(route);

    // Mostrar panell d'edició
    const appId = window.mapesCore.currentAppId;
    const editPanel = document.getElementById(`edit-panel-${appId}`);
    const editContent = document.getElementById(`edit-content-${appId}`);
    const editTitle = document.getElementById(`edit-title-${appId}`);

    if (!editPanel || !editContent) return;

    // Actualitzar nom
    editTitle.textContent = `Editar Ruta: ${route.code}`;

    // Generar llista de monuments
    const pointsListHtml = this.generateRoutePointsEditor(route);

    // Crear formulari inline
    editContent.innerHTML = `
      <form class="mapes-edit-form" onsubmit="mapesRoutes.submitInlineRouteEdit('${routeId}', event)">
        <div class="mapes-edit-form-left">
          <div class="mapes-form-group">
            <label>Codi Ruta *</label>
            <input type="text" name="code" value="${route.code}" required>
          </div>
          <div class="mapes-form-group">
            <label>Nom Ruta *</label>
            <input type="text" name="name" value="${route.name}" required>
          </div>
          <div class="mapes-form-group">
            <label>Color</label>
            <div class="mapes-color-picker">
              <button type="button" class="mapes-color-btn ${
                route.color === "#000000" ? "active" : ""
              }" 
                      style="background: #000000;" onclick="selectColor(this, '#000000')"></button>
              <button type="button" class="mapes-color-btn ${
                route.color === "#404040" ? "active" : ""
              }" 
                      style="background: #404040;" onclick="selectColor(this, '#404040')"></button>
              <button type="button" class="mapes-color-btn ${
                route.color === "#CC0000" ? "active" : ""
              }" 
                      style="background: #CC0000;" onclick="selectColor(this, '#CC0000')"></button>
              <button type="button" class="mapes-color-btn ${
                route.color === "#003366" ? "active" : ""
              }" 
                      style="background: #003366;" onclick="selectColor(this, '#003366')"></button>
              <button type="button" class="mapes-color-btn ${
                route.color === "#006600" ? "active" : ""
              }" 
                      style="background: #006600;" onclick="selectColor(this, '#006600')"></button>
            </div>
            <input type="hidden" name="color" value="${route.color}">
          </div>
          <div class="mapes-form-actions">
            <button type="submit" class="mapes-btn primary">Actualitzar Ruta</button>
            <button type="button" class="mapes-btn secondary" onclick="cancelEdit('${appId}')">Cancel·lar</button>
          </div>
        </div>
        <div class="mapes-edit-form-right">
          <div class="mapes-form-group">
            <label>Monuments de la Ruta</label>
            <div class="mapes-route-points-editor">
              ${pointsListHtml}
            </div>
            <small style="color: #666; margin-top: 5px; display: block;">
              💡 Selecciona/deselecciona monuments i ajusta l'ordre
            </small>
          </div>
        </div>
      </form>
    `;

    // Mostrar el panell
    editPanel.style.display = "block";

    // Scroll suau
    setTimeout(() => {
      editPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }, 100);
  }

  generateRoutePointsEditor(route) {
    let html = "";

    // Monuments actuals de la ruta
    if (route.points && route.points.length > 0) {
      html += '<h4 style="margin: 0 0 10px 0;">Monuments actuals:</h4>';
      route.points.forEach((routePoint) => {
        const point = window.mapesCore.points.find(
          (p) => p.id == routePoint.point_id
        );
        if (point) {
          html += `
            <div class="mapes-route-point-item">
              <div class="mapes-route-point-check">
                <input type="checkbox" name="points[]" value="${
                  point.id
                }" checked 
                       onchange="mapesRoutes.updateRoutePreview()">
              </div>
              <div class="mapes-route-point-name">
                <strong>${point.title}</strong>
              </div>
              <div class="mapes-route-point-controls">
                <div class="mapes-control-group">
                  <label>Ordre:</label>
                  <input type="number" min="1" max="100" value="${
                    routePoint.order_num || 1
                  }" 
                         onchange="mapesRoutes.updateRoutePreview()">
                </div>
                <div class="mapes-control-group">
                  <label>Pes:</label>
                  <input type="number" min="5" max="100" step="5" value="${
                    routePoint.weight >= 5 ? routePoint.weight : 5
                  }" 
                         onchange="mapesRoutes.updateRoutePreview()">
                </div>
              </div>
            </div>
          `;
        }
      });
    }

    // Monuments disponibles per afegir
    html += '<h4 style="margin: 15px 0 10px 0;">Monuments disponibles:</h4>';
    window.mapesCore.points.forEach((point, index) => {
      const isInRoute =
        route.points && route.points.some((rp) => rp.point_id == point.id);
      if (!isInRoute) {
        html += `
          <div class="mapes-route-point-item">
            <div class="mapes-route-point-check">
              <input type="checkbox" name="points[]" value="${point.id}" 
                     onchange="mapesRoutes.updateRoutePreview()">
            </div>
            <div class="mapes-route-point-name">
              ${point.title}
            </div>
            <div class="mapes-route-point-controls">
              <div class="mapes-control-group">
                <label>Ordre:</label>
                <input type="number" min="1" max="100" value="${index + 10}" 
                       onchange="mapesRoutes.updateRoutePreview()">
              </div>
              <div class="mapes-control-group">
                <label>Pes:</label>
                <input type="number" min="5" max="100" step="5" value="5" 
                       onchange="mapesRoutes.updateRoutePreview()">
              </div>
            </div>
          </div>
        `;
      }
    });

    return html;
  }

  updateRoutePreview() {
    const checkboxes = document.querySelectorAll(
      'input[name="points[]"]:checked'
    );
    const selectedPoints = [];

    checkboxes.forEach((checkbox) => {
      const pointId = checkbox.value;
      const point = window.mapesCore.points.find((p) => p.id == pointId);

      const parentItem = checkbox.closest(".mapes-route-point-item");
      const orderInput = parentItem.querySelector(
        '.mapes-control-group:first-child input[type="number"]'
      );

      if (point && orderInput) {
        selectedPoints.push({
          ...point,
          order: parseInt(orderInput.value) || 1,
        });
      }
    });

    selectedPoints.sort((a, b) => a.order - b.order);

    if (selectedPoints.length > 0) {
      const mockRoute = {
        color: document.querySelector('input[name="color"]').value,
        points: selectedPoints.map((p, i) => ({
          point_id: p.id,
          order_num: i + 1,
        })),
      };
      this.displayRoute(mockRoute);
    }
  }

  submitInlineRouteEdit(routeId, event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!data.code || !data.name) {
      window.mapesUI.showAlert("Codi i nom són obligatoris");
      return;
    }

    // Obtenir monuments seleccionats
    const selectedPoints = [];
    const checkboxes = document.querySelectorAll(
      'input[name="points[]"]:checked'
    );

    checkboxes.forEach((checkbox) => {
      const parentItem = checkbox.closest(".mapes-route-point-item");
      const orderInput = parentItem.querySelector(
        '.mapes-control-group:first-child input[type="number"]'
      );
      const weightInput = parentItem.querySelector(
        '.mapes-control-group:last-child input[type="number"]'
      );

      const orderValue = orderInput ? parseInt(orderInput.value) || 1 : 1;
      const weightValue = weightInput ? parseInt(weightInput.value) || 5 : 5;

      selectedPoints.push({
        point_id: checkbox.value,
        order: orderValue,
        weight: weightValue,
      });
    });

    if (selectedPoints.length < 2) {
      window.mapesUI.showAlert("Cal mínim 2 monuments per la ruta");
      return;
    }

    window.mapesCore
      .sendAjaxRequest("mapes_edit_route", {
        id: routeId,
        code: data.code.trim(),
        name: data.name.trim(),
        color: data.color,
        points: JSON.stringify(selectedPoints),
      })
      .then(() => {
        window.mapesUI.showAlert(`Ruta "${data.code}" actualitzada amb èxit!`);
        cancelEdit(window.mapesCore.currentAppId);
        location.reload();
      })
      .catch((error) => {
        window.mapesUI.showAlert(`Error actualitzant ruta: ${error}`);
      });
  }
}

// Instància global
window.mapesRoutes = new MapesRoutes();

// Funcions globals per compatibilitat
function selectRoute(appId, routeId) {
  window.mapesRoutes.selectRoute(appId, routeId);
}
function submitCreateRoute(appId, event) {
  window.mapesRoutes.submitCreateRoute(appId, event);
}
function deleteRoute(routeId) {
  window.mapesRoutes.deleteRoute(routeId);
}
function editRoute(routeId) {
  window.mapesRoutes.editRoute(routeId);
}
