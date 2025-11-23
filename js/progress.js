// Sample data
const items = [
  {
    item_id: 1,
    item_name: "Laptop Battery",
    cat_id: 1,
    item_unit: "PCS",
    price: 50.0,
    current_stock: 45,
  },
  {
    item_id: 2,
    item_name: "Screwdriver Set",
    cat_id: 2,
    item_unit: "SET",
    price: 25.0,
    current_stock: 8,
  },
  {
    item_id: 3,
    item_name: "Power Supply",
    cat_id: 1,
    item_unit: "PCS",
    price: 80.0,
    current_stock: 120,
  },
];

const categories = [
  { cat_id: 1, cat_name: "Electronics" },
  { cat_id: 2, cat_name: "Hardware" },
];

// Sample transaction data (tbl_progress)
const transactions = [
  {
    trans_id: 1,
    item_id: 1,
    trans_type: "in",
    quantity: 50,
    balance: 50,
    trans_date: "2024-01-15 10:30:00",
    reference: "PO-001",
    remarks: "Opening Stock",
  },
  {
    trans_id: 2,
    item_id: 1,
    trans_type: "out",
    quantity: 5,
    balance: 45,
    trans_date: "2024-01-20 14:15:00",
    reference: "SALE-001",
    remarks: "Sold to customer",
  },
  {
    trans_id: 3,
    item_id: 1,
    trans_type: "in",
    quantity: 20,
    balance: 65,
    trans_date: "2024-02-05 09:00:00",
    reference: "PO-002",
    remarks: "Restock from supplier",
  },
  {
    trans_id: 4,
    item_id: 1,
    trans_type: "out",
    quantity: 20,
    balance: 45,
    trans_date: "2024-02-10 16:30:00",
    reference: "SALE-002",
    remarks: "Bulk sale",
  },
  {
    trans_id: 5,
    item_id: 2,
    trans_type: "in",
    quantity: 30,
    balance: 30,
    trans_date: "2024-01-10 11:00:00",
    reference: "PO-003",
    remarks: "Opening Stock",
  },
  {
    trans_id: 6,
    item_id: 2,
    trans_type: "out",
    quantity: 12,
    balance: 18,
    trans_date: "2024-01-25 15:00:00",
    reference: "SALE-003",
    remarks: "Workshop use",
  },
  {
    trans_id: 7,
    item_id: 2,
    trans_type: "out",
    quantity: 10,
    balance: 8,
    trans_date: "2024-02-15 10:30:00",
    reference: "SALE-004",
    remarks: "Customer order",
  },
];

let selectedItemId = null;
let filteredTransactions = [];

// Load items into dropdown
function loadItemsDropdown() {
  const select = document.getElementById("itemSelect");
  items.forEach((item) => {
    select.innerHTML += `<option value="${item.item_id}">${item.item_name} (${item.item_unit})</option>`;
  });
}

// Load item progress
function loadItemProgress() {
  selectedItemId = parseInt(document.getElementById("itemSelect").value);

  if (!selectedItemId) {
    document.getElementById("progressContent").style.display = "none";
    document.getElementById("emptyState").style.display = "block";
    return;
  }

  const item = items.find((i) => i.item_id === selectedItemId);
  if (!item) return;

  // Show content
  document.getElementById("emptyState").style.display = "none";
  document.getElementById("progressContent").style.display = "block";

  // Load item info
  const category = categories.find((c) => c.cat_id === item.cat_id);
  document.getElementById("itemName").textContent = item.item_name;
  document.getElementById("itemCategory").textContent = category
    ? category.cat_name
    : "N/A";
  document.getElementById(
    "currentStock"
  ).textContent = `${item.current_stock} ${item.item_unit}`;
  document.getElementById("itemPrice").textContent = `$${item.price.toFixed(
    2
  )}`;

  // Filter transactions for this item
  filteredTransactions = transactions.filter(
    (t) => t.item_id === selectedItemId
  );

  // Calculate statistics
  const totalIn = filteredTransactions
    .filter((t) => t.trans_type === "in")
    .reduce((sum, t) => sum + t.quantity, 0);

  const totalOut = filteredTransactions
    .filter((t) => t.trans_type === "out")
    .reduce((sum, t) => sum + t.quantity, 0);

  document.getElementById(
    "totalIn"
  ).textContent = `${totalIn} ${item.item_unit}`;
  document.getElementById(
    "totalOut"
  ).textContent = `${totalOut} ${item.item_unit}`;
  document.getElementById("totalTransactions").textContent =
    filteredTransactions.length;

  if (filteredTransactions.length > 0) {
    const lastDate = new Date(
      filteredTransactions[filteredTransactions.length - 1].trans_date
    );
    document.getElementById("lastTransaction").textContent =
      lastDate.toLocaleDateString();
  } else {
    document.getElementById("lastTransaction").textContent = "No transactions";
  }

  // Load timeline
  loadTimeline(filteredTransactions);
}

// Load timeline
function loadTimeline(data) {
  const timeline = document.getElementById("timeline");
  timeline.innerHTML = "";

  if (data.length === 0) {
    timeline.innerHTML = `
            <div class="empty-state">
              <i class="fas fa-history"></i>
              <h3>No Transactions Found</h3>
              <p>This item has no transaction history yet</p>
            </div>
          `;
    return;
  }

  // Sort by date descending (newest first)
  const sortedData = [...data].sort(
    (a, b) => new Date(b.trans_date) - new Date(a.trans_date)
  );

  sortedData.forEach((trans) => {
    const item = items.find((i) => i.item_id === selectedItemId);
    const badge =
      trans.trans_type === "in"
        ? '<span class="badge badge-in">Stock In</span>'
        : '<span class="badge badge-out">Stock Out</span>';

    const timelineItem = document.createElement("div");
    timelineItem.className = "timeline-item";
    timelineItem.innerHTML = `
            <div class="timeline-marker ${trans.trans_type}"></div>
            <div class="timeline-content">
              <div class="timeline-header">
                <div>
                  <div class="timeline-title">
                    ${badge} ${trans.reference}
                  </div>
                </div>
                <div class="timeline-date">
                  <i class="fas fa-clock"></i> ${formatDateTime(
                    trans.trans_date
                  )}
                </div>
              </div>
              <div class="timeline-details">
                <div class="timeline-detail">
                  <strong>Quantity:</strong> ${trans.quantity} ${item.item_unit}
                </div>
                <div class="timeline-detail">
                  <strong>Balance After:</strong> ${trans.balance} ${
      item.item_unit
    }
                </div>
                <div class="timeline-detail">
                  <strong>Value:</strong> $${(
                    trans.quantity * item.price
                  ).toFixed(2)}
                </div>
                <div class="timeline-detail" style="grid-column: 1 / -1;">
                  <strong>Remarks:</strong> ${trans.remarks || "N/A"}
                </div>
              </div>
            </div>
          `;
    timeline.appendChild(timelineItem);
  });
}

// Filter transactions
function filterTransactions() {
  if (!selectedItemId) return;

  const typeFilter = document.getElementById("typeFilter").value;
  const dateFrom = document.getElementById("dateFrom").value;
  const dateTo = document.getElementById("dateTo").value;

  let filtered = transactions.filter((t) => t.item_id === selectedItemId);

  // Filter by type
  if (typeFilter) {
    filtered = filtered.filter((t) => t.trans_type === typeFilter);
  }

  // Filter by date range
  if (dateFrom) {
    filtered = filtered.filter(
      (t) => new Date(t.trans_date) >= new Date(dateFrom)
    );
  }
  if (dateTo) {
    filtered = filtered.filter(
      (t) => new Date(t.trans_date) <= new Date(dateTo + " 23:59:59")
    );
  }

  loadTimeline(filtered);
}

// Format date time
function formatDateTime(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Export progress
function exportProgress() {
  if (!selectedItemId) {
    alert("Please select an item first");
    return;
  }

  const item = items.find((i) => i.item_id === selectedItemId);
  let csv = "Date,Type,Reference,Quantity,Balance After,Value,Remarks\n";

  filteredTransactions.forEach((trans) => {
    const value = (trans.quantity * item.price).toFixed(2);
    csv += `"${trans.trans_date}","${trans.trans_type.toUpperCase()}","${
      trans.reference
    }",${trans.quantity},${trans.balance},${value},"${trans.remarks || ""}"\n`;
  });

  const blob = new Blob([csv], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `${item.item_name}_progress_${new Date()
    .toISOString()
    .slice(0, 10)}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);
}

// Initialize
loadItemsDropdown();
