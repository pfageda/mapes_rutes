/**
 * Mapes UI - Gestió d'interfície i modals
 */
class MapesUI {
  constructor() {
    this.activeModal = null;
    this.initEventListeners();
  }

  initEventListeners() {
    // Event listeners per tancar modals
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("mapes-modal")) {
        this.closeModal(e.target.id);
      }
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.activeModal) {
        this.closeModal(this.activeModal);
      }
    });
  }

  openModal(modalId) {
    console.log("Obrint modal:", modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "flex";
      this.activeModal = modalId;
    } else {
      console.error("Modal no trobat:", modalId);
    }
  }

  closeModal(modalId) {
    console.log("Tancant modal:", modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "none";
      if (this.activeModal === modalId) {
        this.activeModal = null;
      }
    }
  }

  toggleRoutes(appId) {
    const list = document.getElementById(`routes-list-${appId}`);
    const toggle = document.getElementById(`routes-toggle-${appId}`);
    const isVisible = list.style.display === "block";

    if (isVisible) {
      // Tancar desplegable de rutes
      list.style.display = "none";
      toggle.textContent = "+";

      // AQUESTA ÉS LA CORRECCIÓ CLAU:
      // Tornar a mostrar tots els monuments i el desplegable de monuments
      if (window.selectRoute) {
        window.selectRoute(appId, null);
      } else if (window.mapesRoutes) {
        window.mapesRoutes.selectRoute(appId, null);
      }
    } else {
      // Obrir desplegable de rutes
      list.style.display = "block";
      toggle.textContent = "−";
    }
  }

  togglePoints(appId) {
    // PRIMER: Verificar si estem a usuari-mapes i cridar la nova funcionalitat
    if (window.mapesUser && window.mapesUser.appId === appId) {
      window.mapesUser.togglePoints();
      return;
    }

    // FALLBACK: Codi original per a l'admin (mantenir funcionalitat existent)
    const list = document.getElementById(`points-list-${appId}`);
    const toggle = document.getElementById(`points-toggle-${appId}`);

    if (!list || !toggle) return;

    const isVisible = list.style.display === "block";

    if (isVisible) {
      // Tancar desplegable de monuments
      list.style.display = "none";
      toggle.textContent = "+";
    } else {
      // Obrir desplegable de monuments
      list.style.display = "block";
      toggle.textContent = "−";

      // Tancar el desplegable de rutes si està obert
      const routesList = document.getElementById(`routes-list-${appId}`);
      const routesToggle = document.getElementById(`routes-toggle-${appId}`);
      if (routesList && routesList.style.display === "block") {
        routesList.style.display = "none";
        routesToggle.textContent = "+";
      }
    }
  }

  toggleInputType(appId, type) {
    const locationInput = document.getElementById(`location-input-${appId}`);
    const coordInput = document.getElementById(`coordinates-input-${appId}`);
    const toggleBtns = document.querySelectorAll(
      `#modal-add-point-${appId} .mapes-input-toggle button`
    );

    if (type === "location") {
      locationInput.style.display = "block";
      coordInput.style.display = "none";
      toggleBtns[0].classList.add("active");
      toggleBtns[1].classList.remove("active");
    } else {
      locationInput.style.display = "none";
      coordInput.style.display = "block";
      toggleBtns[0].classList.remove("active");
      toggleBtns[1].classList.add("active");
    }
  }

  selectColor(element, color) {
    document
      .querySelectorAll(".mapes-color-btn")
      .forEach((btn) => btn.classList.remove("active"));
    element.classList.add("active");
    element.closest("form").querySelector('input[name="color"]').value = color;
  }

  togglePointControls(checkbox) {
    const parentItem = checkbox.closest(".mapes-route-point-item");
    const controls = parentItem.querySelector(".mapes-route-point-controls");
    if (controls) {
      controls.style.display = checkbox.checked ? "flex" : "none";
    }
  }

  showAlert(message, type = "info") {
    // Implementació simple amb alert per ara
    alert(message);

    // TODO: Implementar sistema de notifications més avançat
  }

  showConfirm(message) {
    return confirm(message);
  }
}

// Instància global
window.mapesUI = new MapesUI();

// Funcions globals per compatibilitat
function openModal(modalId) {
  window.mapesUI.openModal(modalId);
}
function closeModal(modalId) {
  window.mapesUI.closeModal(modalId);
}
function toggleRoutes(appId) {
  window.mapesUI.toggleRoutes(appId);
}
function toggleInputType(appId, type) {
  window.mapesUI.toggleInputType(appId, type);
}
function selectColor(element, color) {
  window.mapesUI.selectColor(element, color);
}
function togglePointControls(checkbox) {
  window.mapesUI.togglePointControls(checkbox);
}
function togglePoints(appId) {
  window.mapesUI.togglePoints(appId);
}
// FUNCIONS GLOBALS PER PUNTS USUARI
function toggleUserPoints(appId) {
  console.log("🎯 toggleUserPoints cridat per appId:", appId);
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.togglePoints();
  } else {
    console.error("❌ mapesUser no trobat o appId no coincideix");
  }
}

function filterUserPoints(appId, searchTerm) {
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.filterPoints(searchTerm);
  }
}

function selectUserPoint(appId, pointId) {
  console.log("🎯 selectUserPoint cridat:", appId, pointId);
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.selectPoint(pointId);
  }
}

function closeUserPointDetails(appId) {
  if (window.mapesUser && window.mapesUser.appId === appId) {
    window.mapesUser.closePointDetails();
  }
}
