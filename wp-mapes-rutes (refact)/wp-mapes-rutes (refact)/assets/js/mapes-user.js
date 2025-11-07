/**
 * Mapes User - Interfície d'usuari per activitats
 */
class MapesUser {
  constructor() {
    this.currentRoute = null;
    this.markers = [];
    this.map = null;
    this.allPoints = [];
    this.filteredPoints = [];
    this.selectedPoint = null;
    this.pointsVisible = false;
  }

  setAppData(appId, points, routes) {
    console.log("=== SETAPPDATA DEBUG ===");
    console.log("AppId:", appId);
    console.log("Points rebuts:", points);
    console.log("Routes rebudes:", routes);
    this.appId = appId;
    this.points = points;
    this.routes = routes;
    this.allPoints = this.points || [];
    this.filteredPoints = this.allPoints;

    console.log("Points processats:", this.points.length);
    console.log("Routes processades:", this.routes.length);

    this.updatePointsCount();

    // INICIALITZAR RESPONSIVITAT
    this.initResponsiveMap();

    this.loadGoogleMaps();
  }
  loadGoogleMaps() {
    if (typeof google !== "undefined" && google.maps) {
      this.initializeUserMap();
      return;
    }

    const apiKey = mapesUserConfig.apiKey;
    if (!apiKey) {
      console.error("API key no configurada");
      return;
    }

    const script = document.createElement("script");
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=initUserMapCallback`;
    script.async = true;
    script.defer = true;

    window.initUserMapCallback = () => {
      this.initializeUserMap();
    };

    document.head.appendChild(script);
  }

  initializeUserMap() {
    const mapElement = document.getElementById(`map-${this.appId}`);
    if (!mapElement || typeof google === "undefined") return;

    this.map = new google.maps.Map(mapElement, {
      center: { lat: 41.6, lng: 1.5 },
      zoom: 8,
      streetViewControl: false,
      mapTypeId: "roadmap",
    });

    this.markers = [];
    this.showAllRoutes();
  }
  // ✅ SOLUCIÓ - MOSTRAR TOTS ELS PUNTS DE TOTES LES RUTES:
  showAllRoutes() {
    console.log("=== SHOWALLROUTES DEBUG ===");
    console.log("Routes disponibles:", this.routes);
    console.log("Points disponibles:", this.points);
    console.log(
      "Punts totals:",
      this.points ? this.points.length : "UNDEFINED"
    );
    console.log(
      "Rutes totals:",
      this.routes ? this.routes.length : "UNDEFINED"
    );

    this.clearMarkers();

    if (!this.routes || this.routes.length === 0) {
      console.log("❌ NO HI HA RUTES!");
      return;
    }

    this.routes.forEach((route, routeIndex) => {
      console.log(`--- RUTA ${routeIndex}: ${route.code} ---`);
      console.log("Punts de la ruta:", route.points);

      if (!route.points || route.points.length === 0) {
        console.log("❌ Ruta sense punts!");
        return;
      }

      route.points.forEach((rp, index) => {
        console.log(`Processant punt ${index}:`, rp);
        const point = this.points.find((p) => p.id === rp.point_id);

        if (!point) {
          console.log("❌ Punt no trobat!", rp);
          return;
        }

        console.log("✅ Punt trobat:", point.title);

        // CREAR MARKER amb logs
        const marker = new google.maps.Marker({
          position: { lat: parseFloat(point.lat), lng: parseFloat(point.lng) },
          map: this.map,
          title: `${route.code} - ${point.title}`,
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
            fillColor: this.getPointActivationColor
              ? this.getPointActivationColor(point)
              : "#FF0000",
            fillOpacity: 0.9,
            strokeColor: "#FFFFFF",
            strokeWeight: 2,
            scale: 8,
          },
        });

        this.markers.push(marker);
        console.log("✅ Marker creat per:", point.title);
      });
    });

    console.log("=== FI DEBUG - Markers totals:", this.markers.length);
  }

  /* showAllRoutes() {
    this.clearMarkers();

    this.routes.forEach((route) => {
      if (route.points && route.points.length > 0) {
        // Mostrar només primer i últim monument de cada ruta
        const firstRoutePoint = route.points[0];
        const lastRoutePoint = route.points[route.points.length - 1];

        // Trobar el monument complet amb activation_status
        const firstPoint =
          this.points.find((p) => p.id == firstRoutePoint.point_id) ||
          firstRoutePoint;
        const lastPoint =
          this.points.find((p) => p.id == lastRoutePoint.point_id) ||
          lastRoutePoint;

        // Primer monument (inici)
        const startMarker = new google.maps.Marker({
          position: {
            lat: parseFloat(firstPoint.lat),
            lng: parseFloat(firstPoint.lng),
          },
          map: this.map,
          title: `${route.code} - Inici`,
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
            fillColor: this.getPointActivationColor(firstPoint),
            fillOpacity: 0.9,
            strokeColor: "#FFFFFF",
            strokeWeight: 2,
            scale: 8,
          },
        });

        // Últim monument (final) si és diferent
        if (route.points.length > 1) {
          const endMarker = new google.maps.Marker({
            position: {
              lat: parseFloat(lastPoint.lat),
              lng: parseFloat(lastPoint.lng),
            },
            map: this.map,
            title: `${route.code} - Final`,
            icon: {
              path: google.maps.SymbolPath.CIRCLE,
              fillColor: this.getPointActivationColor(lastPoint),
              fillOpacity: 0.9,
              strokeColor: "#FFFFFF",
              strokeWeight: 2,
              scale: 8,
            },
          });
          this.markers.push(endMarker);
        }

        this.markers.push(startMarker);
      }
    });
  }*/

  selectRoute(routeId) {
    const route = this.routes.find((r) => r.id == routeId);
    if (!route) return;

    this.currentRoute = route;
    this.displayRouteDetails(route);
    this.showRouteOnMap(route);

    // Actualitzar interfície
    document.querySelectorAll(".mapes-route-item-user").forEach((item) => {
      item.classList.remove("selected");
    });
    event.target.closest(".mapes-route-item-user").classList.add("selected");
  }

  displayRouteDetails(route) {
    // PART 1: ACTUALITZAR PANELL D'ACCIONS (SIDEBAR) - MANTENIR CODI EXISTENT
    const routeNameSpan = document.getElementById(
      `selected-route-name-${this.appId}`
    );
    if (routeNameSpan) {
      routeNameSpan.textContent = `${route.code} - ${route.name}`;
    }

    // PART 2: NOUS DETALLS SOTA DEL MAPA (SI EXISTEIXEN ELS ELEMENTS NOUS)
    const detailsPanel = document.getElementById(
      `route-details-panel-${this.appId}`
    );
    if (detailsPanel) {
      // NOVA ESTRUCTURA - Usar els nous elements
      const detailsColor = document.getElementById(
        `route-details-color-${this.appId}`
      );
      const detailsName = document.getElementById(
        `route-details-name-${this.appId}`
      );
      const detailsPointsCount = document.getElementById(
        `route-details-points-count-${this.appId}`
      );
      const detailsTotalWeight = document.getElementById(
        `route-details-total-weight-${this.appId}`
      );
      const detailsDesc = document.getElementById(
        `route-details-desc-${this.appId}`
      );
      const pointsContainer = document.getElementById(
        `route-points-container-${this.appId}`
      );

      // Mostrar el panell
      detailsPanel.style.display = "block";

      // Actualitzar elements si existeixen
      if (detailsColor) detailsColor.style.background = route.color;
      if (detailsName)
        detailsName.textContent = `${route.code} - ${route.name}`;

      const totalPoints = route.points ? route.points.length : 0;
      const totalWeight = route.points
        ? route.points.reduce((sum, rp) => sum + parseFloat(rp.weight || 1), 0)
        : 0;

      if (detailsPointsCount)
        detailsPointsCount.textContent = `${totalPoints} monuments`;
      if (detailsTotalWeight)
        detailsTotalWeight.textContent = `Pes: ${totalWeight}`;

      if (detailsDesc) {
        detailsDesc.innerHTML = `
        <strong>Codi:</strong> ${route.code}<br>
        <strong>Nom:</strong> ${route.name}<br>
        <strong>Color:</strong> <span style="display:inline-block;width:20px;height:15px;background:${route.color};border:1px solid #ccc;margin-left:5px;"></span>
      `;
      }

      // Mostrar llista de monuments (dins de displayRouteDetails())
      if (pointsContainer && route.points) {
        pointsContainer.innerHTML = route.points
          .map((rp, index) => {
            const point = this.points.find((p) => p.id == rp.point_id);
            if (!point) return "";

            return `
      <div class="route-point-item" onclick="openPointInGoogleMaps('${
        point.title
      }', '${point.Poblacio}')">
        <div class="route-point-name">${index + 1}. ${point.title}</div>
        <div class="route-point-coords">
          ${parseFloat(point.lat).toFixed(4)}, ${parseFloat(point.lng).toFixed(
              4
            )}
        </div>
       <div class="route-point-weight">Pes: ${parseFloat(
         rp.weight || 1
       ).toFixed(2)}</div>

      </div>
    `;
          })
          .join("");
      }
    } else {
      // PART 3: FALLBACK A L'ESTRUCTURA ANTIGA (SI NO EXISTEIXEN ELS NOUS ELEMENTS)
      const sidebarPanel = document.getElementById(
        `route-info-panel-${this.appId}`
      );
      const sidebarTitle = document.getElementById(
        `route-info-title-${this.appId}`
      );
      const sidebarContent = document.getElementById(
        `route-info-content-${this.appId}`
      );

      if (sidebarTitle) {
        sidebarTitle.textContent = `${route.code} - ${route.name}`;
      }

      if (sidebarContent) {
        sidebarContent.innerHTML = `
        <div class="route-sidebar-info">
          <div class="info-compact-grid">
            <div><strong>Codi:</strong> ${route.code}</div>
            <div><strong>Monuments:</strong> ${route.points.length}</div>
            <div><strong>Color:</strong> <span style="display:inline-block;width:16px;height:16px;background:${
              route.color
            };border-radius:50%;"></span></div>
          </div>
          
          <div class="route-points-compact">
            <strong>Monuments:</strong>
            <table class="route-points-table-sidebar">
              <thead>
                <tr><th>#</th><th>Monument</th><th>Pes</th></tr>
              </thead>
              <tbody>
                ${route.points
                  .map((rp, i) => {
                    const point = this.points.find((p) => p.id == rp.point_id);
                    return point
                      ? `
                    <tr>
                      <td>${i + 1}</td>
                      <td>${point.title}</td>
                      <td><span class="weight-badge-small">${
                        rp.weight || "1"
                      }</span></td>
                    </tr>
                  `
                      : "";
                  })
                  .join("")}
              </tbody>
            </table>
          </div>
        </div>
      `;
      }

      if (sidebarPanel) {
        sidebarPanel.style.display = "block";
      }
    }

    // ⭐ MOSTRAR PANELL D'ACCIONS (COMÚ A AMBDUES ESTRUCTURES)
    const actionsPanel = document.getElementById(
      `route-actions-panel-${this.appId}`
    );
    if (actionsPanel) {
      actionsPanel.style.display = "block";
    }

    // Guardar dades de ruta
    localStorage.setItem("selectedRouteId", route.id);
    localStorage.setItem("selectedRouteCode", route.code);
    localStorage.setItem("selectedRouteName", route.name);
    // Al final de displayRouteDetails(), afegeix:
    const crearBtn = document.getElementById(
      `crear-activitat-btn-${this.appId}`
    );
    const selectedRouteInfo = document.getElementById("selected-route-info");

    if (crearBtn) {
      crearBtn.style.display = "inline-block"; // Mostrar botó crear quan hi ha ruta
    }

    if (selectedRouteInfo) {
      selectedRouteInfo.style.display = "block"; // Mostrar info de ruta
    }
  }

  showRouteOnMap(route) {
    this.clearMarkers();

    if (!route.points || route.points.length === 0) return;

    // Ordenar monuments per ordre i crear markers numerats
    const routePoints = route.points
      .map((rp) => {
        const point = this.points.find((p) => p.id == rp.point_id);
        return point ? { ...point, order: rp.order_num } : null;
      })
      .filter((p) => p)
      .sort((a, b) => a.order - b.order);

    routePoints.forEach((point, index) => {
      const marker = new google.maps.Marker({
        position: { lat: parseFloat(point.lat), lng: parseFloat(point.lng) },
        map: this.map,
        title: `${index + 1}. ${point.title}`,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          fillColor: this.getPointActivationColor(point),
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

      marker.addListener("click", () => {
        // Crear cerca textual del monument + ubicació
        const poblacio = point.Poblacio || "";
        if (
          !poblacio ||
          poblacio.trim() === "" ||
          poblacio.trim().toLowerCase() === "no especificada"
        ) {
          // MOSTRAR ALERTA SI NO HI HA POBLACIÓ VÀLIDA
          alert(
            `${point.title}\n\nNo es pot obrir a Google Maps perquè la població no està especificada. Contacta amb l'administrador per completar aquesta informació.`
          );
          return;
        }
        const searchQuery = `${point.title} ${poblacio || ""}`.trim();
        const googleMapsUrl = `https://www.google.com/maps/search/${encodeURIComponent(
          searchQuery
        )}?hl=ca&gl=ES`;

        // Obrir en nova pestanya
        window.open(googleMapsUrl, "_blank");
      });
      this.markers.push(marker);
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

      routeLine.setMap(this.map);
      this.markers.push(routeLine);
    }

    // Ajustar vista
    const bounds = new google.maps.LatLngBounds();
    routePoints.forEach((point) => {
      bounds.extend({ lat: parseFloat(point.lat), lng: parseFloat(point.lng) });
    });
    this.map.fitBounds(bounds);
  }

  submitActivity(routeId, event) {
    event.preventDefault();
    // Implementar enviament d'activitat via AJAX
    alert("Funcionalitat d'enviament en desenvolupament");
  }

  clearMarkers() {
    if (this.markers && this.markers.length > 0) {
      this.markers.forEach((marker) => {
        marker.setMap(null);
      });
      this.markers = [];
    }
  }

  /**
   * Determina el color d'un monument segons el seu estat d'activació
   */
  getPointActivationColor(point) {
    const colors = {
      never_activated: "#32CD32", // 🟢 VERD - mai activat
      confirmed: "#FF4444", // 🔴 VERMELL - confirmat ⭐ AQUESTA LÍNIA IMPORTANT
      pending: "#808000", // 🔘 GRIS - pendent
      confirmed_recent: "#FF4444", // 🔴 VERMELL - confirmat recent
      confirmed_old: "#FFD700", // 🟡 GROC - confirmat antic
      pending_confirmation: "#CCCCCC", // 🔘 GRIS - pendent confirmació
      default: "#4285F4", // 🔵 BLAU - per defecte
    };

    const status = point.status || point.activation_status || "default";
    const color = colors[status] || colors["default"];

    return color;
  }

  /**
   * Converteix color hex a nom d'icona de Google Maps
   */
  getMarkerIconColor(point) {
    const color = this.getPointActivationColor(point);

    const colorMap = {
      "#32CD32": "green", // Verd -> green
      "#FF4444": "red", // Vermell -> red
      "#808000": "grey", // Gris -> grey
      "#FFD700": "yellow", // Groc -> yellow
      "#4285F4": "blue", // Blau -> blue (defecte)
    };

    return colorMap[color] || "blue";
  }

  /**
   * Actualitza el comptador de monuments
   */
  updatePointsCount() {
    const countElement = document.getElementById(
      `points-count-user-${this.appId}`
    );
    if (countElement) {
      countElement.textContent = this.allPoints.length;
    }
  }

  /**
   * Alternar visibilitat del selector de monuments
   */
  togglePoints() {
    this.pointsVisible = !this.pointsVisible;
    const searchContainer = document.getElementById(
      `points-search-container-${this.appId}`
    );
    const listContainer = document.getElementById(
      `points-list-container-${this.appId}`
    );

    if (this.pointsVisible) {
      searchContainer.style.display = "block";
      listContainer.style.display = "block";
      this.renderPointsList();
    } else {
      searchContainer.style.display = "none";
      listContainer.style.display = "none";
    }
  }

  /**
   * Filtrar monuments per cercador
   */
  filterPoints(searchTerm) {
    const term = searchTerm.toLowerCase().trim();

    if (term === "") {
      this.filteredPoints = this.allPoints;
    } else {
      this.filteredPoints = this.allPoints.filter(
        (point) =>
          point.title.toLowerCase().includes(term) ||
          (point.Poblacio && point.Poblacio.toLowerCase().includes(term)) ||
          (point.description && point.description.toLowerCase().includes(term))
      );
    }

    this.renderPointsList();
  }

  /**
   * Renderitzar llista de monuments
   */
  renderPointsList() {
    const container = document.getElementById(`points-list-${this.appId}`);
    if (!container) return;

    if (this.filteredPoints.length === 0) {
      container.innerHTML =
        '<div class="no-points-found">Cap monument trobat</div>';
      return;
    }

    container.innerHTML = this.filteredPoints
      .map((point) => this.createPointItemHTML(point))
      .join("");
  }

  /**
   * Crear HTML d'un element monument
   */
  createPointItemHTML(point) {
    const statusColor = this.getPointActivationColor(point);
    const location = point.Poblacio ? ` (${point.Poblacio})` : "";

    return `
    <div class="point-item-user" onclick="window.mapesUser.selectPoint(${
      point.id
    })">
      <div class="point-info-user">
        <div class="point-name-user">${this.escapeHtml(point.title)}</div>
        <div class="point-location-user">${this.escapeHtml(location)}</div>
      </div>
      <div class="point-status-indicator-user" style="background-color: ${statusColor}"></div>
    </div>
  `;
  }

  /**
   * Seleccionar monument individual
   */
  selectPoint(pointId) {
    const point = this.allPoints.find((p) => p.id == pointId);
    if (!point) return;

    this.selectedPoint = point;

    // Mostrar monument al mapa
    this.showPointOnMap(point);

    // Mostrar detalls
    this.showPointDetails(point);

    // Tancar llista de monuments
    this.pointsVisible = false;
    document.getElementById(
      `points-search-container-${this.appId}`
    ).style.display = "none";
    document.getElementById(
      `points-list-container-${this.appId}`
    ).style.display = "none";
  }

  /**
   * Mostrar monument al mapa
   */
  showPointOnMap(point) {
    this.clearMarkers();

    // Crear marker del monument
    const marker = new google.maps.Marker({
      position: { lat: parseFloat(point.lat), lng: parseFloat(point.lng) },
      map: this.map,
      title: point.title,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: this.getPointActivationColor(point),
        fillOpacity: 1.0,
        strokeColor: "#FFFFFF",
        strokeWeight: 3,
        scale: 15,
      },
    });

    // Centrar mapa
    this.map.setCenter({
      lat: parseFloat(point.lat),
      lng: parseFloat(point.lng),
    });
    this.map.setZoom(15);

    // Afegir event click per obrir Google Maps
    marker.addListener("click", () => {
      window.openPointInGoogleMaps(point.title, point.Poblacio);
    });

    // Guardar marker
    this.markers = [marker];
  }

  /**
   * Mostrar detalls del monument - VERSIÓ DEFINITIVA AMB PANELL D'ACCIONS VISIBLE
   */
  showPointDetails(point) {
    // Amagar detalls de rutes si estan oberts
    const routePanel = document.getElementById(
      `route-details-panel-${this.appId}`
    );
    if (routePanel) routePanel.style.display = "none";

    const routeInfoPanel = document.getElementById(
      `route-info-panel-${this.appId}`
    );
    if (routeInfoPanel) routeInfoPanel.style.display = "none";

    // ✅ GESTIÓ INTEL·LIGENT DEL PANELL D'ACCIONS - AQUESTA ÉS LA CLAU!!!
    const routeActionsPanel = document.getElementById(
      `route-actions-panel-${this.appId}`
    );
    const selectedRouteInfo = document.getElementById("selected-route-info");

    if (routeActionsPanel) {
      // Comprovar si hi ha una ruta seleccionada
      const hasSelectedRoute =
        selectedRouteInfo &&
        (selectedRouteInfo.style.display === "block" ||
          selectedRouteInfo.textContent.trim() !== "");

      if (hasSelectedRoute) {
        routeActionsPanel.style.display = "block"; // ✅ MANTENIR VISIBLE
        console.log(
          "🎯 Mantenint panell d'accions visible - ruta seleccionada"
        );
      } else {
        routeActionsPanel.style.display = "none"; // Amagar si no hi ha ruta
        console.log("🎯 Amagant panell d'accions - cap ruta seleccionada");
      }
    }

    // ✅ ACTUALITZAR TÍTOL AMB INDICADOR DE COLOR
    const titleElement = document.getElementById(
      `point-details-name-${this.appId}`
    );
    if (titleElement) {
      const pointColor = this.getPointActivationColor(point);
      titleElement.innerHTML = `
      <span class="point-color-indicator" style="display: inline-block; width: 20px; height: 20px; background-color: ${pointColor}; border-radius: 50%; margin-right: 6px; border: 1px solid #fff; vertical-align: middle;"></span>
      ${this.escapeHtml(point.title)}
    `;
    }

    // ✅ ACTUALITZAR DESCRIPCIÓ PRINCIPAL
    const descElement = document.getElementById(
      `point-details-desc-${this.appId}`
    );
    if (descElement) {
      descElement.textContent =
        point.description || "Sense descripció disponible.";
    }

    // ✅ ACTUALITZAR ESTADÍSTIQUES DEL HEADER
    const activityElement = document.getElementById(
      `point-details-activity-${this.appId}`
    );
    if (activityElement) {
      activityElement.textContent = `Activitat: ${point.activity || "--"}`;
    }

    const weightElement = document.getElementById(
      `point-details-weight-${this.appId}`
    );
    if (weightElement) {
      weightElement.textContent = `Pes: ${point.weight || "1"}`;
    }

    // ✅ OMPLIR INFORMACIÓ DETALLADA
    const infoContainer = document.getElementById(
      `point-details-info-${this.appId}`
    );
    if (infoContainer) {
      infoContainer.innerHTML = this.generateDetailedPointHTML(point);
    }

    // ✅ MOSTRAR EL PANELL DE DETALLS DEL PUNT
    const pointPanel = document.getElementById(
      `point-details-panel-${this.appId}`
    );
    if (pointPanel) {
      pointPanel.style.display = "block";
      this.adjustMapHeight(true); // Ajustar alçada del mapa
    }

    console.log("🎯 Mostrant detalls del monument:", point.title);
  }

  /**
   * Generar HTML detallat per al monument - PER AL NOU SISTEMA
   */
  generateDetailedPointHTML(point) {
    const statusText = this.getPointStatusText(point);
    const statusColor = this.getPointActivationColor(point);

    return `
    <div class="point-detail-item">
      <strong class="point-detail-label">Estat:</strong>
      <span class="point-detail-value">
        <span style="color: ${statusColor};">●</span> ${statusText}
      </span>
    </div>
    
    <div class="point-detail-item">
      <strong class="point-detail-label">Nom Complet:</strong>
      <span class="point-detail-value">${this.escapeHtml(point.title)}</span>
    </div>
    
    ${
      point.Poblacio
        ? `
    <div class="point-detail-item">
      <strong class="point-detail-label">Població:</strong>
      <span class="point-detail-value">${this.escapeHtml(point.Poblacio)}</span>
    </div>
    `
        : ""
    }
    
    ${
      point.activity
        ? `
    <div class="point-detail-item">
      <strong class="point-detail-label">Tipus d'Activitat:</strong>
      <span class="point-detail-value">${this.escapeHtml(point.activity)}</span>
    </div>
    `
        : ""
    }
    
    <div class="point-detail-item">
      <strong class="point-detail-label">Coordenades:</strong>
      <span class="point-detail-value">${point.lat}, ${point.lng}</span>
    </div>
    
    <div class="point-detail-item">
      <strong class="point-detail-label">Pes del Monument:</strong>
      <span class="point-detail-value">${point.weight || "1"}</span>
    </div>
    
    <div class="point-detail-item">
      <strong class="point-detail-label">Activacions Total:</strong>
      <span class="point-detail-value">${
        point.Vegades_activat || 0
      } vegades</span>
    </div>
    
    ${
      point.Darrera_Activacio
        ? `
    <div class="point-detail-item">
      <strong class="point-detail-label">Última Activació:</strong>
      <span class="point-detail-value">${this.formatDate(
        point.Darrera_Activacio
      )}</span>
    </div>
    `
        : ""
    }
  `;
  }

  /**
   * Sistema antic (manté el codi existent com a fallback)
   */
  showPointDetailsOldSystem(point) {
    // Crear/mostrar panell antic
    let pointPanel = document.getElementById(
      `point-details-panel-${this.appId}`
    );
    if (!pointPanel) {
      pointPanel = this.createPointDetailsPanel();
      document
        .querySelector(`#map-${this.appId}`)
        .parentNode.appendChild(pointPanel);
    }

    pointPanel.style.display = "block";
    pointPanel.innerHTML = this.generatePointDetailsHTML(point); // Usa la funció original

    // AJUSTAR ALÇADA DEL MAPA
    this.adjustMapHeight(true);
  }

  /**
   * Crear panell de detalls del monument
   */
  createPointDetailsPanel() {
    const panel = document.createElement("div");
    panel.id = `point-details-panel-${this.appId}`;
    panel.className = "point-details-panel";
    panel.style.display = "none";
    return panel;
  }

  /**
   * Generar HTML dels detalls del monument
   */
  generatePointDetailsHTML(point) {
    const statusText = this.getPointStatusText(point);
    const statusColor = this.getPointActivationColor(point);

    return `
    <div class="point-details-title">
      <h3>📍 ${this.escapeHtml(point.title)}</h3>
      <button class="point-close-btn" onclick="window.mapesUser.closePointDetails()">✕</button>
    </div>
    
    <div class="point-details-content">
      <div class="point-detail-item">
        <span class="point-detail-label">Estat:</span>
        <span class="point-detail-value">
          <span style="color: ${statusColor};">●</span> ${statusText}
        </span>
      </div>
      
      ${
        point.Poblacio
          ? `
      <div class="point-detail-item">
        <span class="point-detail-label">Població:</span>
        <span class="point-detail-value">${this.escapeHtml(
          point.Poblacio
        )}</span>
      </div>
      `
          : ""
      }
      
      ${
        point.description
          ? `
      <div class="point-detail-item">
        <span class="point-detail-label">Descripció:</span>
        <span class="point-detail-value">${this.escapeHtml(
          point.description
        )}</span>
      </div>
      `
          : ""
      }
      
      <div class="point-detail-item">
        <span class="point-detail-label">Activacions:</span>
        <span class="point-detail-value">${
          point.Vegades_activat || 0
        } vegades</span>
      </div>
      
      ${
        point.Darrera_Activacio
          ? `
      <div class="point-detail-item">
        <span class="point-detail-label">Última:</span>
        <span class="point-detail-value">${this.formatDate(
          point.Darrera_Activacio
        )}</span>
      </div>
      `
          : ""
      }
      
      <div class="point-detail-item">
        <span class="point-detail-label">Coordenades:</span>
        <span class="point-detail-value">${point.lat}, ${point.lng}</span>
      </div>
    </div>
  `;
  }

  /**
   * Tancar detalls del monument - ACTUALITZAT
   */
  closePointDetails() {
    const panel = document.getElementById(`point-details-panel-${this.appId}`);
    if (panel) {
      panel.style.display = "none";
      // ⭐ RESTAURAR ALÇADA DEL MAPA
      this.adjustMapHeight(false);
    }
  }
  /**
   * Obtenir text de l'estat del monument
   */
  getPointStatusText(point) {
    const status = point.activation_status || point.status || "never_activated";
    const statusMap = {
      never_activated: "Mai activat",
      pending: "Pendent confirmació",
      confirmed: "Confirmat",
      confirmed_recent: "Confirmat recent",
      confirmed_old: "Confirmat antic",
    };
    return statusMap[status] || "Desconegut";
  }

  /**
   * Escapar HTML per seguretat
   */
  escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Formatejar data
   */
  formatDate(dateString) {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return date.toLocaleDateString("ca-ES", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  /**
   * Ajustar alçada del mapa segons pantalla i detalls
   */
  adjustMapHeight(showDetails = false) {
    const mapElement = document.getElementById(`map-${this.appId}`);
    const mapContainer = mapElement?.parentElement;
    const userApp = document.getElementById(this.appId);

    if (!mapContainer || !mapElement || !userApp) return;

    // DETECTAR MIDA DE PANTALLA
    const screenHeight = window.innerHeight;
    const screenWidth = window.innerWidth;
    const isMobile = screenWidth < 768;
    const isTablet = screenWidth >= 768 && screenWidth < 1024;

    let mapHeight;

    if (showDetails) {
      // AMB DETALLS DE PUNT OBERTS
      if (isMobile) {
        mapHeight = Math.min(250, screenHeight * 0.4); // Mòbil: 40% pantalla, màx 250px
      } else if (isTablet) {
        mapHeight = Math.min(300, screenHeight * 0.45); // Tablet: 45% pantalla, màx 300px
      } else {
        mapHeight = Math.min(450, screenHeight * 0.5); // Desktop: 50% pantalla, màx 350px
      }
    } else {
      // SENSE DETALLS (MAPA NORMAL)
      if (isMobile) {
        mapHeight = Math.min(400, screenHeight * 0.5); // Mòbil: 50% pantalla, màx 400px
      } else if (isTablet) {
        mapHeight = Math.min(450, screenHeight * 0.55); // Tablet: 55% pantalla, màx 450px
      } else {
        mapHeight = Math.min(500, screenHeight * 0.6); // Desktop: 60% pantalla, màx 500px
      }
    }

    // APLICAR ALÇADES
    mapContainer.style.height = `${mapHeight}px`;
    mapContainer.style.maxHeight = `${mapHeight}px`;
    mapElement.style.height = `${mapHeight}px`;

    // AJUSTAR CONTENIDOR PRINCIPAL
    if (isMobile && showDetails) {
      // En mòbil amb detalls, assegurar que tot sigui visible
      userApp.style.height = "auto";
      userApp.style.maxHeight = `${screenHeight - 100}px`; // Deixar 100px per header/footer
      userApp.style.overflowY = "auto";
    } else {
      userApp.style.height = "auto";
      userApp.style.maxHeight = "none";
      userApp.style.overflowY = "visible";
    }

    // TRIGGER RESIZE PER GOOGLE MAPS
    if (this.map) {
      setTimeout(() => {
        google.maps.event.trigger(this.map, "resize");
        // Recentrar el mapa si hi ha monument seleccionat
        if (this.selectedPoint) {
          this.map.setCenter({
            lat: parseFloat(this.selectedPoint.lat),
            lng: parseFloat(this.selectedPoint.lng),
          });
        }
      }, 200);
    }

    console.log(
      `🗺️ Mapa ajustat: ${mapHeight}px, pantalla: ${screenWidth}x${screenHeight}, detalls: ${showDetails}`
    );
  }

  /**
   * Funció per ajustar en canvis de mida de finestra
   */
  initResponsiveMap() {
    let resizeTimer;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        const hasDetails =
          document.getElementById(`point-details-panel-${this.appId}`)?.style
            .display === "block";
        this.adjustMapHeight(hasDetails);
      }, 250);
    });
  }
}

// Instància global
window.mapesUser = new MapesUser();
window.mapesUserCore = window.mapesUser;

// Funcions globals
function selectUserRoute(appId, routeId) {
  window.mapesUser.selectRoute(routeId);
}

function closeRouteInfo(appId) {
  document.getElementById(`route-info-panel-${appId}`).style.display = "none";
}

window.closeActivityForm = function (appId) {
  document.getElementById(`activity-form-panel-${appId}`).style.display =
    "none";
};

window.closeSidebarRouteInfo = function (appId) {
  document.getElementById(`route-info-panel-${appId}`).style.display = "none";
  document.getElementById(`activity-form-panel-${appId}`).style.display =
    "none";
};

window.crearActivitat = function (appId) {
  const routeId = localStorage.getItem("selectedRouteId");
  const routeCode = localStorage.getItem("selectedRouteCode");

  if (routeId) {
    // Navegar a pàgina de formulari d'activitat
    window.location.href = `/formulari-activitat/?route=${routeId}&code=${routeCode}`;
  } else {
    alert("Error: No s'ha seleccionat cap ruta");
  }
};

window.finalitzarActivitat = function (appId) {
  // Obrir modal directament (sense necessitat de ruta)
  openModal("modal-finalize-activity");
};

// Funció per obrir monument a Google Maps (reutilitzant lògica dels markers)
window.openPointInGoogleMaps = function (pointTitle, pointPoblacio) {
  console.log(`Obrint Google Maps per: ${pointTitle}`);

  // Validar població (igual que fa als markers)
  if (
    !pointPoblacio ||
    pointPoblacio.trim() === "" ||
    pointPoblacio.trim().toLowerCase() === "no especificada"
  ) {
    alert(
      `${pointTitle}\n\nNo es pot obrir a Google Maps perquè la població no està especificada. Contacta amb l'administrador per completar aquesta informació.`
    );
    return;
  }

  // Reutilitzar la mateixa lògica dels markers
  const searchQuery = `${pointTitle} ${pointPoblacio}`.trim();
  const googleMapsUrl = `https://www.google.com/maps/search/${encodeURIComponent(
    searchQuery
  )}?hl=ca&gl=ES`;

  // Obrir en nova pestanya
  window.open(googleMapsUrl, "_blank");
};
window.getPointActivationColor = function (point) {
  return window.mapesUser.getPointActivationColor(point);
};
window.getMarkerIconColor = function (point) {
  return window.mapesUser.getMarkerIconColor(point);
};

/**
 * Alternar selector de monuments - VERSIÓ NETA
 */
window.toggleUserPoints = function (appId) {
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.togglePoints();
  }
};

/**
 * Filtrar monuments des del cercador
 */
window.filterUserPoints = function (appId, searchTerm) {
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.filterPoints(searchTerm);
  }
};

/**
 * Seleccionar monument des de la llista
 */
window.selectUserPoint = function (appId, pointId) {
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.selectPoint(pointId);
  }
};

/**
 * Tancar detalls del monument
 */
window.closeUserPointDetails = function (appId) {
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.closePointDetails();
  }
};
// AJAX amb jQuery (WordPress estàndard)
function submitFinalizeActivity(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  const email = formData.get("email");
  const activationCode = formData.get("activationcode");

  // Mostrar loading
  const submitBtn = event.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = "⏳ Validant...";
  submitBtn.disabled = true;

  // AJAX amb jQuery (WordPress estàndard)
  jQuery
    .post(window.mapesAjaxConfig.ajaxUrl, {
      action: "mapes_validate_activitat",
      email: email,
      activation_code: activationCode,
      nonce: window.mapesAjaxConfig.nonce,
    })
    .done(function (data) {
      const resultDiv = document.getElementById("finalize-result");
      const messageDiv = document.getElementById("finalize-message");

      if (data.success) {
        // Èxit
        resultDiv.style.display = "block";
        resultDiv.style.background = "#d4edda";
        resultDiv.style.borderColor = "#c3e6cb";
        resultDiv.style.color = "#155724";
        messageDiv.innerHTML = `
               <strong>✅ Activitat validada correctament!</strong><br>
        <small>Redirigint a la pàgina de documentació...</small>
            `;

        // Redirigir a la pàgina de documentació
        setTimeout(() => {
          const activitatId = data.data.activitat.id;
          const email = document.getElementById("finalize-email").value;
          const activationCode = document.getElementById(
            "finalize-activation-code"
          ).value;

          const documentationUrl = `/enviar-documentacio/?activitat=${activitatId}&email=${encodeURIComponent(
            email
          )}&code=${encodeURIComponent(activationCode)}`;
          window.location.href = documentationUrl;
        }, 2000);
      } else {
        // Error
        resultDiv.style.display = "block";
        resultDiv.style.background = "#f8d7da";
        resultDiv.style.borderColor = "#f5c6cb";
        resultDiv.style.color = "#721c24";
        messageDiv.innerHTML = `
                <strong>❌ Error:</strong> ${
                  data.data || "No s'ha pogut validar l'activitat."
                }
            `;
      }
    })
    .fail(function () {
      const resultDiv = document.getElementById("finalize-result");
      const messageDiv = document.getElementById("finalize-message");
      resultDiv.style.display = "block";
      resultDiv.style.background = "#f8d7da";
      resultDiv.style.borderColor = "#f5c6cb";
      resultDiv.style.color = "#721c24";
      messageDiv.innerHTML =
        "<strong>❌ Error de connexió.</strong> Torneu-ho a provar.";
    })
    .always(function () {
      // Restaurar botó
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
}
