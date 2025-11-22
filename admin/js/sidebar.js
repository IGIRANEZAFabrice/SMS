const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const mobileMenuBtn = document.getElementById("mobileMenuBtn");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const menuItems = document.querySelectorAll(".menu-item");
const submenuItems = document.querySelectorAll(".submenu-item");
const pageTitle = document.getElementById("pageTitle");
const currentPage = document.getElementById("currentPage");

const pageNames = {
  home: "Home",
  sell: "Sell",
  stock: "Stock",
  "purchase-request": "Purchase Request",
  "add-stock": "Add Stock",
  "damaged-goods": "Damaged Goods",
  reports: "Reports",
  cogs: "Cost of Goods Sold",
  "damaged-report": "Damaged Goods",
  "supplier-report": "Supplier Report",
  settings: "Settings",
};

// Toggle sidebar on desktop
toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});

// Toggle sidebar on mobile
mobileMenuBtn.addEventListener("click", () => {
  sidebar.classList.toggle("mobile-open");
  sidebarOverlay.classList.toggle("active");
});

// Close sidebar when clicking overlay
sidebarOverlay.addEventListener("click", () => {
  sidebar.classList.remove("mobile-open");
  sidebarOverlay.classList.remove("active");
});

// Handle menu item clicks
menuItems.forEach((item) => {
  item.addEventListener("click", () => {
    const page = item.getAttribute("data-page");
    const isDropdown = item.getAttribute("data-dropdown");

    if (isDropdown) {
      // Toggle dropdown
      item.classList.toggle("open");
      const submenu = document.getElementById(`${page}-submenu`);
      submenu.classList.toggle("open");
    } else {
      // Update active state
      menuItems.forEach((mi) => mi.classList.remove("active"));
      submenuItems.forEach((si) => si.classList.remove("active"));
      item.classList.add("active");

      // Update page title
      pageTitle.textContent = pageNames[page];
      currentPage.textContent = pageNames[page];

      // Close mobile menu
      if (window.innerWidth <= 768) {
        sidebar.classList.remove("mobile-open");
        sidebarOverlay.classList.remove("active");
      }
    }
  });
});

// Handle submenu item clicks
submenuItems.forEach((item) => {
  item.addEventListener("click", (e) => {
    e.stopPropagation();
    const page = item.getAttribute("data-page");

    // Update active state
    menuItems.forEach((mi) => mi.classList.remove("active"));
    submenuItems.forEach((si) => si.classList.remove("active"));
    item.classList.add("active");

    // Update page title
    pageTitle.textContent = pageNames[page];
    currentPage.textContent = pageNames[page];

    // Close mobile menu
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("mobile-open");
      sidebarOverlay.classList.remove("active");
    }
  });
});
