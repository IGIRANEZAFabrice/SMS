// Sample data (replace with API calls)
const categories = [
  { cat_id: 1, cat_name: "Electronics" },
  { cat_id: 2, cat_name: "Hardware" },
  { cat_id: 3, cat_name: "Tools" },
  { cat_id: 4, cat_name: "Parts" },
];

const stockData = [
  {
    item_id: 1,
    item_name: "Laptop Battery",
    cat_id: 1,
    qty: 45,
    item_unit: "PCS",
    price: 50.0,
  },
  {
    item_id: 2,
    item_name: "Screwdriver Set",
    cat_id: 3,
    qty: 8,
    item_unit: "SET",
    price: 25.0,
  },
  {
    item_id: 3,
    item_name: "Brake Pads",
    cat_id: 4,
    qty: 0,
    item_unit: "SET",
    price: 75.0,
  },
  {
    item_id: 4,
    item_name: "Power Supply",
    cat_id: 1,
    qty: 120,
    item_unit: "PCS",
    price: 80.0,
  },
  {
    item_id: 5,
    item_name: "Hammer",
    cat_id: 3,
    qty: 15,
    item_unit: "PCS",
    price: 20.0,
  },
  {
    item_id: 6,
    item_name: "Engine Oil Filter",
    cat_id: 4,
    qty: 5,
    item_unit: "PCS",
    price: 12.0,
  },
];

let filteredData = [...stockData];

// Load categories into filter
function loadCategoryFilter() {
  const select = document.getElementById("categoryFilter");
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
function updateStats() {
  document.getElementById("totalItems").textContent = stockData.length;

  const totalValue = stockData.reduce(
    (sum, item) => sum + item.qty * item.price,
    0
  );
  document.getElementById("totalValue").textContent = `$${totalValue.toFixed(
    2
  )}`;

  const lowStock = stockData.filter(
    (item) => item.qty > 0 && item.qty <= 10
  ).length;
  document.getElementById("lowStockItems").textContent = lowStock;

  const outOfStock = stockData.filter((item) => item.qty === 0).length;
  document.getElementById("outOfStock").textContent = outOfStock;
}

// Load stock table
function loadStockTable(data = filteredData) {
  const tbody = document.getElementById("stockTableBody");
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
                .padStart(4, "0")}</strong></td>
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
  const categoryFilter = document.getElementById("categoryFilter").value;
  const statusFilter = document.getElementById("statusFilter").value;
  const searchText = document.getElementById("searchStock").value.toLowerCase();

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

// Initialize
loadCategoryFilter();
updateStats();
loadStockTable();
