// Sample data - Replace with API calls in production
const suppliers = [
  {
    supplier_id: 1,
    supplier_name: "Tech Supply Co.",
    phone: "+250788123456",
    address: "Kigali",
  },
  {
    supplier_id: 2,
    supplier_name: "Hardware Solutions Ltd",
    phone: "+250788654321",
    address: "Kigali",
  },
  {
    supplier_id: 3,
    supplier_name: "Global Parts Inc.",
    phone: "+250788999888",
    address: "Kigali",
  },
];

const categories = [
  { cat_id: 1, cat_name: "Electronics" },
  { cat_id: 2, cat_name: "Hardware" },
  { cat_id: 3, cat_name: "Tools" },
];

const items = [
  {
    item_id: 1,
    item_name: "Laptop Battery",
    cat_id: 1,
    supplier_id: 1,
    item_unit: "PCS",
    price: 50.0,
    current_stock: 45,
    item_status: 1,
  },
  {
    item_id: 2,
    item_name: "Screwdriver Set",
    cat_id: 3,
    supplier_id: 2,
    item_unit: "SET",
    price: 25.0,
    current_stock: 30,
    item_status: 1,
  },
  {
    item_id: 3,
    item_name: "Power Supply",
    cat_id: 1,
    supplier_id: 1,
    item_unit: "PCS",
    price: 80.0,
    current_stock: 120,
    item_status: 1,
  },
  {
    item_id: 4,
    item_name: "Wrench Set",
    cat_id: 3,
    supplier_id: 2,
    item_unit: "SET",
    price: 35.0,
    current_stock: 20,
    item_status: 1,
  },
  {
    item_id: 5,
    item_name: "LED Light",
    cat_id: 1,
    supplier_id: 3,
    item_unit: "PCS",
    price: 15.0,
    current_stock: 80,
    item_status: 1,
  },
];

const users = [
  { user_id: 1, fullname: "John Doe" },
  { user_id: 2, fullname: "Jane Smith" },
];

const purchaseRequests = [
  {
    request_id: 1,
    supplier_id: 1,
    request_date: "2024-01-15",
    status: "received",
    created_by: 1,
    items: [1, 3],
    quantities: [50, 100],
  },
  {
    request_id: 2,
    supplier_id: 2,
    request_date: "2024-02-10",
    status: "approved",
    created_by: 2,
    items: [2, 4],
    quantities: [30, 20],
  },
  {
    request_id: 3,
    supplier_id: 3,
    request_date: "2024-03-05",
    status: "pending",
    created_by: 1,
    items: [5],
    quantities: [100],
  },
  {
    request_id: 4,
    supplier_id: 1,
    request_date: "2024-03-20",
    status: "received",
    created_by: 2,
    items: [1],
    quantities: [25],
  },
];

const stockInTransactions = [
  {
    prog_id: 1,
    item_id: 1,
    date: "2024-01-20",
    in_qty: 50,
    out_qty: 0,
    new_price: 50.0,
    remark: "Purchase order #1",
    created_by: 1,
  },
  {
    prog_id: 2,
    item_id: 3,
    date: "2024-01-20",
    in_qty: 100,
    out_qty: 0,
    new_price: 80.0,
    remark: "Purchase order #1",
    created_by: 1,
  },
  {
    prog_id: 3,
    item_id: 2,
    date: "2024-02-15",
    in_qty: 30,
    out_qty: 0,
    new_price: 25.0,
    remark: "Purchase order #2",
    created_by: 2,
  },
  {
    prog_id: 4,
    item_id: 5,
    date: "2024-03-10",
    in_qty: 100,
    out_qty: 0,
    new_price: 15.0,
    remark: "Purchase order #3",
    created_by: 1,
  },
  {
    prog_id: 5,
    item_id: 1,
    date: "2024-03-25",
    in_qty: 25,
    out_qty: 0,
    new_price: 50.0,
    remark: "Restock",
    created_by: 2,
  },
];

let filteredData = {
  requests: [...purchaseRequests],
  stockIns: [...stockInTransactions],
  items: [...items],
};

// Initialize
function init() {
  setDefaultDates();
  loadSupplierFilter();
  updateStatistics();
  loadSupplierCards();
  loadPurchaseRequests();
  loadStockInHistory();
  loadSupplierItems();
  loadPerformanceAnalysis();
}

function setDefaultDates() {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

  document.getElementById("dateFrom").valueAsDate = firstDay;
  document.getElementById("dateTo").valueAsDate = today;
}

function loadSupplierFilter() {
  const select = document.getElementById("supplierFilter");
  suppliers.forEach((sup) => {
    select.innerHTML += `<option value="${sup.supplier_id}">${sup.supplier_name}</option>`;
  });
}

function updateStatistics() {
  document.getElementById("totalSuppliers").textContent = suppliers.length;
  document.getElementById("totalRequests").textContent =
    filteredData.requests.length;
  document.getElementById("totalItems").textContent = filteredData.items.length;

  const totalValue = filteredData.stockIns.reduce((sum, trans) => {
    return sum + trans.in_qty * trans.new_price;
  }, 0);
  document.getElementById(
    "totalValue"
  ).textContent = `$${totalValue.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function loadSupplierCards() {
  const grid = document.getElementById("suppliersGrid");
  grid.innerHTML = "";

  const supplierStats = suppliers.map((supplier) => {
    const supplierItems = filteredData.items.filter(
      (item) => item.supplier_id === supplier.supplier_id
    );
    const requests = filteredData.requests.filter(
      (req) => req.supplier_id === supplier.supplier_id
    );
    const stockIns = filteredData.stockIns.filter((trans) => {
      const item = items.find((i) => i.item_id === trans.item_id);
      return item && item.supplier_id === supplier.supplier_id;
    });

    const totalValue = stockIns.reduce(
      (sum, trans) => sum + trans.in_qty * trans.new_price,
      0
    );

    return {
      ...supplier,
      itemCount: supplierItems.length,
      requestCount: requests.length,
      totalValue: totalValue,
    };
  });

  supplierStats.sort((a, b) => b.totalValue - a.totalValue);

  document.getElementById(
    "suppliersCount"
  ).textContent = `Showing ${supplierStats.length} supplier(s)`;

  supplierStats.forEach((supplier, index) => {
    const isTop = index < 3;
    const card = document.createElement("div");
    card.className = "supplier-card";
    card.innerHTML = `
            <div class="supplier-header">
              <div>
                <div class="supplier-name">${supplier.supplier_name}</div>
                <div class="supplier-contact">
                  <i class="fas fa-phone"></i> ${supplier.phone}<br>
                  <i class="fas fa-map-marker-alt"></i> ${supplier.address}
                </div>
              </div>
              ${
                isTop
                  ? '<span class="supplier-badge badge-top"><i class="fas fa-star"></i> Top Supplier</span>'
                  : '<span class="supplier-badge badge-active">Active</span>'
              }
            </div>
            <div class="supplier-stats">
              <div class="mini-stat">
                <div class="mini-stat-value">${supplier.itemCount}</div>
                <div class="mini-stat-label">Items</div>
              </div>
              <div class="mini-stat">
                <div class="mini-stat-value">${supplier.requestCount}</div>
                <div class="mini-stat-label">Requests</div>
              </div>
              <div class="mini-stat" style="grid-column: 1 / -1;">
                <div class="mini-stat-value">${supplier.totalValue.toLocaleString(
                  "en-US",
                  { minimumFractionDigits: 2, maximumFractionDigits: 2 }
                )}</div>
                <div class="mini-stat-label">Total Supply Value</div>
              </div>
            </div>
          `;
    grid.appendChild(card);
  });
}

function loadPurchaseRequests() {
  const tbody = document.getElementById("purchaseRequestsBody");
  tbody.innerHTML = "";

  if (filteredData.requests.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-file-alt"></i><h3>No Purchase Requests</h3><p>No purchase requests found for the selected filters</p></div></td></tr>';
    return;
  }

  filteredData.requests.forEach((req) => {
    const supplier = suppliers.find((s) => s.supplier_id === req.supplier_id);
    const user = users.find((u) => u.user_id === req.created_by);
    const totalQty = req.quantities.reduce((sum, q) => sum + q, 0);

    const estimatedValue = req.items.reduce((sum, itemId, idx) => {
      const item = items.find((i) => i.item_id === itemId);
      return sum + (item ? item.price * req.quantities[idx] : 0);
    }, 0);

    const statusBadge =
      req.status === "received"
        ? '<span class="badge badge-received"><i class="fas fa-check-circle"></i> Received</span>'
        : req.status === "approved"
        ? '<span class="badge badge-approved"><i class="fas fa-thumbs-up"></i> Approved</span>'
        : '<span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>';

    tbody.innerHTML += `
            <tr>
              <td><strong>#PR${req.request_id
                .toString()
                .padStart(4, "0")}</strong></td>
              <td>${supplier ? supplier.supplier_name : "N/A"}</td>
              <td>${new Date(req.request_date).toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
              })}</td>
              <td>${req.items.length} item(s)</td>
              <td class="text-right"><strong>${totalQty}</strong></td>
              <td class="text-right"><strong>${estimatedValue.toLocaleString(
                "en-US",
                { minimumFractionDigits: 2, maximumFractionDigits: 2 }
              )}</strong></td>
              <td>${statusBadge}</td>
              <td>${user ? user.fullname : "N/A"}</td>
            </tr>
          `;
  });
}

function loadStockInHistory() {
  const tbody = document.getElementById("stockInBody");
  tbody.innerHTML = "";

  if (filteredData.stockIns.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-arrow-down"></i><h3>No Stock In Records</h3><p>No stock in transactions found for the selected filters</p></div></td></tr>';
    return;
  }

  filteredData.stockIns.forEach((trans) => {
    const item = items.find((i) => i.item_id === trans.item_id);
    const supplier = item
      ? suppliers.find((s) => s.supplier_id === item.supplier_id)
      : null;
    const user = users.find((u) => u.user_id === trans.created_by);
    const totalValue = trans.in_qty * trans.new_price;

    tbody.innerHTML += `
            <tr>
              <td>${new Date(trans.date).toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
              })}</td>
              <td>
                <div class="item-row">
                  <div class="item-image"><i class="fas fa-box"></i></div>
                  <strong>${item ? item.item_name : "N/A"}</strong>
                </div>
              </td>
              <td>${supplier ? supplier.supplier_name : "N/A"}</td>
              <td class="text-right"><strong>${trans.in_qty}</strong> ${
      item ? item.item_unit : ""
    }</td>
              <td class="text-right">${trans.new_price.toFixed(2)}</td>
              <td class="text-right"><strong>${totalValue.toLocaleString(
                "en-US",
                { minimumFractionDigits: 2, maximumFractionDigits: 2 }
              )}</strong></td>
              <td>${user ? user.fullname : "N/A"}</td>
              <td>${trans.remark || "N/A"}</td>
            </tr>
          `;
  });
}

function loadSupplierItems() {
  const tbody = document.getElementById("supplierItemsBody");
  tbody.innerHTML = "";

  if (filteredData.items.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-boxes"></i><h3>No Items Found</h3><p>No items found for the selected filters</p></div></td></tr>';
    return;
  }

  filteredData.items.forEach((item) => {
    const supplier = suppliers.find((s) => s.supplier_id === item.supplier_id);
    const category = categories.find((c) => c.cat_id === item.cat_id);
    const stockValue = item.current_stock * item.price;

    const statusBadge =
      item.item_status === 1
        ? '<span class="badge badge-received">Active</span>'
        : '<span class="badge badge-pending">Inactive</span>';

    tbody.innerHTML += `
            <tr>
              <td>${supplier ? supplier.supplier_name : "N/A"}</td>
              <td>
                <div class="item-row">
                  <div class="item-image"><i class="fas fa-box"></i></div>
                  <strong>${item.item_name}</strong>
                </div>
              </td>
              <td>${category ? category.cat_name : "N/A"}</td>
              <td>${item.item_unit}</td>
              <td class="text-right"><strong>${item.current_stock}</strong></td>
              <td class="text-right">${item.price.toFixed(2)}</td>
              <td class="text-right"><strong>${stockValue.toLocaleString(
                "en-US",
                { minimumFractionDigits: 2, maximumFractionDigits: 2 }
              )}</strong></td>
              <td>${statusBadge}</td>
            </tr>
          `;
  });
}

function loadPerformanceAnalysis() {
  const received = filteredData.requests.filter(
    (r) => r.status === "received"
  ).length;
  const pending = filteredData.requests.filter(
    (r) => r.status === "pending"
  ).length;
  const total = filteredData.requests.length;
  const completionRate = total > 0 ? ((received / total) * 100).toFixed(1) : 0;

  const totalValue = filteredData.stockIns.reduce(
    (sum, trans) => sum + trans.in_qty * trans.new_price,
    0
  );
  const avgValue = received > 0 ? totalValue / received : 0;

  document.getElementById("receivedCount").textContent = received;
  document.getElementById("pendingCount").textContent = pending;
  document.getElementById("completionRate").textContent = `${completionRate}%`;
  document.getElementById(
    "avgOrderValue"
  ).textContent = `${avgValue.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;

  const tbody = document.getElementById("performanceBody");
  tbody.innerHTML = "";

  const performanceData = suppliers.map((supplier) => {
    const requests = filteredData.requests.filter(
      (r) => r.supplier_id === supplier.supplier_id
    );
    const completed = requests.filter((r) => r.status === "received").length;
    const pending = requests.filter((r) => r.status === "pending").length;

    const stockIns = filteredData.stockIns.filter((trans) => {
      const item = items.find((i) => i.item_id === trans.item_id);
      return item && item.supplier_id === supplier.supplier_id;
    });

    const totalValue = stockIns.reduce(
      (sum, trans) => sum + trans.in_qty * trans.new_price,
      0
    );
    const avgOrderValue = completed > 0 ? totalValue / completed : 0;
    const completionRate =
      requests.length > 0
        ? ((completed / requests.length) * 100).toFixed(1)
        : 0;

    return {
      supplier,
      totalOrders: requests.length,
      completed,
      pending,
      totalValue,
      avgOrderValue,
      completionRate,
    };
  });

  performanceData.sort((a, b) => b.totalValue - a.totalValue);

  performanceData.forEach((data) => {
    tbody.innerHTML += `
            <tr>
              <td><strong>${data.supplier.supplier_name}</strong></td>
              <td class="text-right">${data.totalOrders}</td>
              <td class="text-right"><span class="badge badge-received">${
                data.completed
              }</span></td>
              <td class="text-right"><span class="badge badge-pending">${
                data.pending
              }</span></td>
              <td class="text-right"><strong>${data.totalValue.toLocaleString(
                "en-US",
                { minimumFractionDigits: 2, maximumFractionDigits: 2 }
              )}</strong></td>
              <td class="text-right">${data.avgOrderValue.toLocaleString(
                "en-US",
                { minimumFractionDigits: 2, maximumFractionDigits: 2 }
              )}</td>
              <td class="text-right"><strong>${
                data.completionRate
              }%</strong></td>
            </tr>
          `;
  });
}

function switchTab(tab) {
  const tabs = document.querySelectorAll(".tab-btn");
  const contents = document.querySelectorAll(".tab-content");

  tabs.forEach((t) => t.classList.remove("active"));
  contents.forEach((c) => c.classList.remove("active"));

  event.target.closest(".tab-btn").classList.add("active");
  document.getElementById(tab + "Tab").classList.add("active");
}

function applyFilters() {
  const dateFrom = document.getElementById("dateFrom").value;
  const dateTo = document.getElementById("dateTo").value;
  const supplierId =
    parseInt(document.getElementById("supplierFilter").value) || null;
  const status = document.getElementById("statusFilter").value;

  filteredData.requests = purchaseRequests.filter((req) => {
    let match = true;

    if (dateFrom && req.request_date < dateFrom) match = false;
    if (dateTo && req.request_date > dateTo) match = false;
    if (supplierId && req.supplier_id !== supplierId) match = false;
    if (status && req.status !== status) match = false;

    return match;
  });

  filteredData.stockIns = stockInTransactions.filter((trans) => {
    let match = true;

    if (dateFrom && trans.date < dateFrom) match = false;
    if (dateTo && trans.date > dateTo) match = false;

    if (supplierId) {
      const item = items.find((i) => i.item_id === trans.item_id);
      if (!item || item.supplier_id !== supplierId) match = false;
    }

    return match;
  });

  filteredData.items = items.filter((item) => {
    if (supplierId && item.supplier_id !== supplierId) return false;
    return true;
  });

  updateStatistics();
  loadSupplierCards();
  loadPurchaseRequests();
  loadStockInHistory();
  loadSupplierItems();
  loadPerformanceAnalysis();
}

function resetFilters() {
  document.getElementById("dateFrom").value = "";
  document.getElementById("dateTo").value = "";
  document.getElementById("supplierFilter").value = "";
  document.getElementById("statusFilter").value = "";

  filteredData = {
    requests: [...purchaseRequests],
    stockIns: [...stockInTransactions],
    items: [...items],
  };

  setDefaultDates();
  updateStatistics();
  loadSupplierCards();
  loadPurchaseRequests();
  loadStockInHistory();
  loadSupplierItems();
  loadPerformanceAnalysis();
}

function exportReport() {
  let csv = "SUPPLIER REPORT\n";
  csv += `Generated: ${new Date().toLocaleString()}\n\n`;

  csv += "=== PURCHASE REQUESTS ===\n";
  csv +=
    "Request ID,Supplier,Request Date,Items Count,Total Quantity,Estimated Value,Status,Created By\n";
  filteredData.requests.forEach((req) => {
    const supplier = suppliers.find((s) => s.supplier_id === req.supplier_id);
    const user = users.find((u) => u.user_id === req.created_by);
    const totalQty = req.quantities.reduce((sum, q) => sum + q, 0);
    const estimatedValue = req.items.reduce((sum, itemId, idx) => {
      const item = items.find((i) => i.item_id === itemId);
      return sum + (item ? item.price * req.quantities[idx] : 0);
    }, 0);
    csv += `PR${req.request_id.toString().padStart(4, "0")},"${
      supplier ? supplier.supplier_name : "N/A"
    }","${req.request_date}",${
      req.items.length
    },${totalQty},${estimatedValue.toFixed(2)},"${req.status}","${
      user ? user.fullname : "N/A"
    }"\n`;
  });

  csv += "\n=== STOCK IN HISTORY ===\n";
  csv +=
    "Date,Item,Supplier,Quantity,Unit Price,Total Value,Created By,Remark\n";
  filteredData.stockIns.forEach((trans) => {
    const item = items.find((i) => i.item_id === trans.item_id);
    const supplier = item
      ? suppliers.find((s) => s.supplier_id === item.supplier_id)
      : null;
    const user = users.find((u) => u.user_id === trans.created_by);
    const totalValue = trans.in_qty * trans.new_price;
    csv += `"${trans.date}","${item ? item.item_name : "N/A"}","${
      supplier ? supplier.supplier_name : "N/A"
    }",${trans.in_qty},${trans.new_price.toFixed(2)},${totalValue.toFixed(
      2
    )},"${user ? user.fullname : "N/A"}","${trans.remark || ""}"\n`;
  });

  csv += "\n=== ITEMS BY SUPPLIER ===\n";
  csv +=
    "Supplier,Item Name,Category,Unit,Current Stock,Unit Price,Stock Value,Status\n";
  filteredData.items.forEach((item) => {
    const supplier = suppliers.find((s) => s.supplier_id === item.supplier_id);
    const category = categories.find((c) => c.cat_id === item.cat_id);
    const stockValue = item.current_stock * item.price;
    const status = item.item_status === 1 ? "Active" : "Inactive";
    csv += `"${supplier ? supplier.supplier_name : "N/A"}","${
      item.item_name
    }","${category ? category.cat_name : "N/A"}","${item.item_unit}",${
      item.current_stock
    },${item.price.toFixed(2)},${stockValue.toFixed(2)},"${status}"\n`;
  });

  csv += "\n=== SUPPLIER PERFORMANCE ===\n";
  csv +=
    "Supplier,Total Orders,Completed,Pending,Total Value,Avg Order Value,Completion Rate\n";
  suppliers.forEach((supplier) => {
    const requests = filteredData.requests.filter(
      (r) => r.supplier_id === supplier.supplier_id
    );
    const completed = requests.filter((r) => r.status === "received").length;
    const pending = requests.filter((r) => r.status === "pending").length;

    const stockIns = filteredData.stockIns.filter((trans) => {
      const item = items.find((i) => i.item_id === trans.item_id);
      return item && item.supplier_id === supplier.supplier_id;
    });

    const totalValue = stockIns.reduce(
      (sum, trans) => sum + trans.in_qty * trans.new_price,
      0
    );
    const avgOrderValue = completed > 0 ? totalValue / completed : 0;
    const completionRate =
      requests.length > 0
        ? ((completed / requests.length) * 100).toFixed(1)
        : 0;

    csv += `"${supplier.supplier_name}",${
      requests.length
    },${completed},${pending},${totalValue.toFixed(2)},${avgOrderValue.toFixed(
      2
    )},${completionRate}%\n`;
  });

  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `supplier_report_${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}

init();
