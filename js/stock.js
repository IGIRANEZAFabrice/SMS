let categories = [];
let stockData = [];
let filteredData = [];

// API Base URL
const API_URL = "api/items.php";

// Load categories into filter
function loadCategoryFilter() {
  const select = document.getElementById("categoryFilter");
  if (!select) return; // Ensure element exists

  select.innerHTML = '<option value="">All Categories</option>'; // Clear existing options and add default

  categories.forEach((cat) => {
    const option = document.createElement("option");
    option.value = cat.cat_id;
    option.textContent = cat.cat_name;
    select.appendChild(option);
  });
}

// Get category name
function getCategoryName(catId) {
  const cat = categories.find((c) => c.cat_id === catId);
  return cat ? cat.cat_name : "Unknown";
}

// Get stock status
function getStockStatus(qty) {
  if (qty === 0) return { badge: "badge-danger", text: "Out of Stock" };
  if (qty <= 10) return { badge: "badge-warning", text: "Low Stock" };
  return { badge: "badge-success", text: "In Stock" };
}

// Calculate statistics
function updateStats(stats) {
  document.getElementById("totalItems").textContent = stats.totalItems;
  document.getElementById("totalValue").textContent = `$${stats.totalValue.toFixed(
    2
  )}`;
  document.getElementById("lowStockItems").textContent = stats.lowStockItems;
  document.getElementById("outOfStock").textContent = stats.outOfStock;
}

// Load stock table
function loadStockTable(data = filteredData) {
  const tbody = document.getElementById("stockTableBody");
  if (!tbody) return; // Ensure element exists

  tbody.innerHTML = "";

  if (data.length === 0) {
    tbody.innerHTML = `
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <i class="fas fa-boxes"></i>
                  <p>No items found</p>
                </div>
              </td>
            </tr>
          `;
    document.getElementById("grandTotal").textContent = "$0.00";
    return;
  }

  let grandTotal = 0;

  data.forEach((item) => {
    const status = getStockStatus(item.qty);
    const totalValue = item.qty * item.price;
    grandTotal += totalValue;

    const row = `
            <tr>
              <td><strong>#${item.item_id
                .toString()
                .padStart(4, "0")}
</strong></td>
              <td>${item.item_name}</td>
              <td>${getCategoryName(item.cat_id)}</td>
              <td class="text-right"><strong>${item.qty}</strong></td>
              <td>${item.item_unit}</td>
              <td class="text-right">$${item.price.toFixed(2)}</td>
              <td class="text-right"><strong>$${totalValue.toFixed(
                2
              )}</strong></td>
              <td><span class="badge ${status.badge}">${status.text}</span></td>
            </tr>
          `;
    tbody.innerHTML += row;
  });

  document.getElementById("grandTotal").textContent = `$${grandTotal.toFixed(
    2
  )}`;
}

// Search table
function searchTable(searchText) {
  filteredData = stockData.filter((item) => {
    const itemText = `${item.item_name} ${getCategoryName(item.cat_id)} ${
      item.item_unit
    }`.toLowerCase();
    return itemText.includes(searchText.toLowerCase());
  });
  applyFilters();
}

// Filter by category
function filterByCategory() {
  applyFilters();
}

// Filter by status
function filterByStatus() {
  applyFilters();
}

// Apply all filters
function applyFilters() {
  const categoryFilter = document.getElementById("categoryFilter")?.value;
  const statusFilter = document.getElementById("statusFilter")?.value;
  const searchText = document.getElementById("searchStock")?.value.toLowerCase() || '';

  filteredData = stockData.filter((item) => {
    // Search filter
    const itemText = `${item.item_name} ${getCategoryName(item.cat_id)} ${
      item.item_unit
    }`.toLowerCase();
    const matchesSearch = itemText.includes(searchText);

    // Category filter
    const matchesCategory = !categoryFilter || item.cat_id == categoryFilter;

    // Status filter
    let matchesStatus = true;
    if (statusFilter === "out-of-stock") {
      matchesStatus = item.qty === 0;
    } else if (statusFilter === "low-stock") {
      matchesStatus = item.qty > 0 && item.qty <= 10;
    } else if (statusFilter === "in-stock") {
      matchesStatus = item.qty > 10;
    }

    return matchesSearch && matchesCategory && matchesStatus;
  });

  loadStockTable(filteredData);
}

// Export stock
function exportStock() {
  let csv =
    "Item Code,Item Name,Category,Quantity,Unit,Unit Price,Total Value,Status\n";

  stockData.forEach((item) => {
    const status = getStockStatus(item.qty);
    const totalValue = item.qty * item.price;
    csv += `#${item.item_id.toString().padStart(4, "0")},"${
      item.item_name
    }","${getCategoryName(item.cat_id)}",${item.qty},"${
      item.item_unit
    }",${item.price.toFixed(2)},${totalValue.toFixed(2)},"${status.text}"\n`;
  });

  const blob = new Blob([csv], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `stock_report_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);
}

// Initialize function to fetch data and set up the page
async function initStockPage() {
  try {
    // Fetch categories
    const categoriesResponse = await fetch(`${API_URL}?action=getCategories`);
    const categoriesResult = await categoriesResponse.json();
    if (categoriesResult.success) {
      categories = categoriesResult.categories;
      loadCategoryFilter();
    } else {
      console.error("Failed to fetch categories:", categoriesResult.message);
    }

    // Fetch stock data
    const stockResponse = await fetch(`${API_URL}?action=getStockData`);
    const stockResult = await stockResponse.json();
    if (stockResult.success) {
      stockData = stockResult.stockData;
      filteredData = [...stockData]; // Initialize filtered data
      loadStockTable();
    } else {
      console.error("Failed to fetch stock data:", stockResult.message);
    }

    // Fetch stats
    const statsResponse = await fetch(`${API_URL}?action=getStockStats`);
    const statsResult = await statsResponse.json();
    if (statsResult.success) {
      updateStats(statsResult.stats);
    } else {
      console.error("Failed to fetch stock stats:", statsResult.message);
    }
  } catch (error) {
    console.error("Error initializing stock page:", error);
    // Display an error message to the user if needed
  }
}

// Call the initialize function when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", initStockPage);