/**
 * Mapes Core - Funcionalitat base del mapa
 */
class MapesCore {
  constructor() {
    this.map = null;
    this.markers = [];
    this.routes = [];
    this.points = [];
    this.currentAppId = null;
    this.config = window.mapesConfig || {};

    this.init();
  }

  init() {
    document.addEventListener("DOMContentLoaded", () => {
      console.log("=== MAPES CORE INICIALITZAT ===");
      this.loadGoogleMaps();
    });
  }

  loadGoogleMaps() {
    if (typeof google === "undefined") {
      const script = document.createElement("script");
      script.src = `https://maps.googleapis.com/maps/api/js?key=${this.config.apiKey}&language=ca&region=ES`;
      script.onload = () => this.initializeMap();
      script.onerror = () => console.error("Error carregant Google Maps API");
      document.head.appendChild(script);
    } else {
      this.initializeMap();
    }
  }

  initializeMap() {
    const mapElement = document.getElementById(`map-${this.currentAppId}`);
    if (!mapElement) {
      console.error("Element mapa no trobat");
      return;
    }

    this.map = new google.maps.Map(mapElement, {
      center: { lat: 41.6, lng: 1.5 },
      zoom: 8,
      mapTypeId: "roadmap",
      streetViewControl: false,
      streetViewControlOptions: {
        position: google.maps.ControlPosition.LEFT_TOP,
      },
    });

    this.createAllMarkers();
    this.addMapClickListener();
    console.log("✅ Mapa inicialitzat correctament");
  }

  createAllMarkers() {
    this.clearMarkers();

    this.points.forEach((point) => {
      this.createMarker(point);
    });
  }

  createMarker(point, options = {}) {
    const lat = parseFloat(point.lat);
    const lng = parseFloat(point.lng);

    if (isNaN(lat) || isNaN(lng)) return null;

    // ⭐ NOVA LÒGICA: Usar colors segons estat d'activació
    const activationColor = this.getPointActivationColor(point);

    const defaultOptions = {
      fillColor: activationColor,
      fillOpacity: 0.9,
      strokeColor: "#FFFFFF",
      strokeWeight: 2,
      scale: 8,
    };

    const markerOptions = { ...defaultOptions, ...options };

    const marker = new google.maps.Marker({
      position: { lat, lng },
      map: this.map,
      title: `${point.title} (${point.activation_status || "desconegut"})`, // ⭐ Mostrar estat al tooltip
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        ...markerOptions,
      },
    });

    this.markers.push(marker);
    return marker;
  }

  clearMarkers() {
    this.markers.forEach((marker) => {
      if (marker.setMap) marker.setMap(null);
    });
    this.markers = [];
  }

  addMapClickListener() {
    this.map.addListener("click", (event) => {
      // Ocultar panell d'edició si està obert
      const editPanel = document.getElementById(
        `edit-panel-${this.currentAppId}`
      );
      if (editPanel && editPanel.style.display === "block") {
        editPanel.style.display = "none";
      }
      const title = prompt("Nom del monument:");
      if (title && title.trim()) {
        this.createPointFromClick(
          title.trim(),
          event.latLng.lat(),
          event.latLng.lng()
        );
      }
    });
  }

  createPointFromClick(title, lat, lng) {
    this.sendAjaxRequest("mapes_add_point", {
      title: title,
      description: "",
      lat: lat,
      lng: lng,
    }).then(() => {
      location.reload();
    });
  }

  sendAjaxRequest(action, data = {}) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", this.config.ajaxUrl, true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      xhr.onreadystatechange = () => {
        if (xhr.readyState === 4) {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                resolve(response.data);
              } else {
                reject(response.data);
              }
            } catch (e) {
              reject("Error parseant resposta");
            }
          } else {
            reject(`Error HTTP: ${xhr.status}`);
          }
        }
      };

      const params = new URLSearchParams({
        action: action,
        nonce: this.config.nonce,
        ...data,
      });

      xhr.send(params.toString());
    });
  }

  setAppData(appId, points, routes) {
    this.currentAppId = appId;
    this.points = points || [];
    this.routes = routes || [];
  }

  resetView() {
    this.createAllMarkers();
    this.map.setCenter({ lat: 41.6, lng: 1.5 });
    this.map.setZoom(7);

    // AFEGIR: Ocultar panell d'edició
    const editPanel = document.getElementById(
      `edit-panel-${this.currentAppId}`
    );
    if (editPanel) {
      editPanel.style.display = "none";
    }
  }
  /**
   * Determina el color d'un monument segons el seu estat d'activació
   */
  getPointActivationColor(point) {
    // ⭐ DEBUG TEMPORAL - AFEGIR AQUESTES LÍNIES
    console.log("🔍 DEBUG PUNT:", point.title);
    console.log("🔍 Vegades_activat:", point.Vegades_activat);
    console.log("🔍 activation_status:", point.activation_status);
    console.log("🔍 Darrera_Activacio:", point.Darrera_Activacio);
    console.log("------------------------");

    const colors = {
      never_activated: "#32CD32", // 🟢 VERD - mai activat
      confirmed: "#FF4444", // 🔴 VERMELL - confirmat ⭐ AQUESTA LÍNIA IMPORTANT
      pending: "#808000", // 🔘 VER OLIVA - pendent
      confirmed_recent: "#FF4444", // 🔴 VERMELL - confirmat recent
      confirmed_old: "#FFD700", // 🟡 GROC - confirmat antic
      pending_confirmation: "#CCCCCC", // 🔘 GRIS - pendent confirmació
      default: "#4285F4", // 🔵 BLAU - per defecte
    };
    const status = point.status || point.activation_status || "default";
    const color = colors[status] || colors["default"];

    // Debug temporal per veure els colors aplicats
    console.log(`🎨 Point ${point.title}: status=${status}, color=${color}`);

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
}

// Instància global
window.mapesCore = new MapesCore();

window.getPointActivationColor = function (point) {
  return window.mapesCore.getPointActivationColor(point);
};

window.getMarkerIconColor = function (point) {
  return window.mapesCore.getMarkerIconColor(point);
};
