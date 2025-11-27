let allItems = [];
let transactions = [];
let selectedItemId = null;

document.addEventListener("DOMContentLoaded", function () {
  loadItemsDropdown();
});

// Load items into dropdown
async function loadItemsDropdown() {
  const select = document.getElementById("itemSelect");
  try {
    const response = await fetch("../api/items_for_progress.php");
    allItems = await response.json();

    select.innerHTML =
      '<option value="">Choose an item to view progress...</option>';
    allItems.forEach((item) => {
      select.innerHTML += `<option value="${item.item_id}">${item.item_name} (${item.unit_name})</option>`;
    });
  } catch (error) {
    console.error("Error loading items:", error);
    select.innerHTML =
      '<option value="">Error loading items. Please try again.</option>';
  }
}

// Load item progress
async function loadItemProgress() {
  selectedItemId = parseInt(document.getElementById("itemSelect").value);

  if (!selectedItemId) {
    document.getElementById("progressContent").style.display = "none";
    document.getElementById("emptyState").style.display = "block";
    return;
  }

  try {
    const response = await fetch(
      `../api/progress.php?item_id=${selectedItemId}`
    );
    const data = await response.json();

    if (data.error) {
      throw new Error(data.error);
    }

    const { item_info, transactions: fetchedTransactions } = data;
    transactions = fetchedTransactions; // Store for filtering

    // Show content
    document.getElementById("emptyState").style.display = "none";
    document.getElementById("progressContent").style.display = "block";

    // Load item info
    document.getElementById("itemName").textContent = item_info.item_name;
    document.getElementById("itemCategory").textContent =
      item_info.cat_name || "N/A";
    document.getElementById(
      "currentStock"
    ).textContent = `${item_info.current_stock} ${item_info.unit_name}`;
    document.getElementById("itemPrice").textContent = `$${parseFloat(
      item_info.price
    ).toFixed(2)}`;

    // Calculate statistics
    const totalIn = transactions
      .filter((t) => t.trans_type === "in")
      .reduce((sum, t) => sum + t.quantity, 0);

    const totalOut = transactions
      .filter((t) => t.trans_type === "out")
      .reduce((sum, t) => sum + t.quantity, 0);

    document.getElementById(
      "totalIn"
    ).textContent = `${totalIn} ${item_info.unit_name}`;
    document.getElementById(
      "totalOut"
    ).textContent = `${totalOut} ${item_info.unit_name}`;
    document.getElementById("totalTransactions").textContent =
      transactions.length;

    if (transactions.length > 0) {
      const lastDate = new Date(transactions[0].trans_date); // Already sorted by API
      document.getElementById("lastTransaction").textContent =
        lastDate.toLocaleDateString();
    } else {
      document.getElementById("lastTransaction").textContent =
        "No transactions";
    }

    // Load timeline
    loadTimeline(transactions);
  } catch (error) {
    console.error("Error loading item progress:", error);
    alert("Could not load item progress. " + error.message);
    document.getElementById("progressContent").style.display = "none";
    document.getElementById("emptyState").style.display = "block";
  }
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

  const selectedItem = allItems.find((i) => i.item_id == selectedItemId);
  const selectedItemInfo = {
    ...selectedItem,
    ...(transactions.length > 0
      ? { price: transactions[0].price }
      : { price: 0 }),
  };

  data.forEach((trans) => {
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
                      ${badge} ${trans.reference || "N/A"}
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
                    <strong>Quantity:</strong> ${trans.quantity} ${
      selectedItem.unit_name
    }
                  </div>
                  <div class="timeline-detail">
                    <strong>Balance After:</strong> ${trans.balance} ${
      selectedItem.unit_name
    }
                  </div>
                  <div class="timeline-detail">
                    <strong>Value:</strong> $${(
                      trans.quantity * selectedItemInfo.price
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

  let filtered = [...transactions];

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

  const item = allItems.find((i) => i.item_id == selectedItemId);
  if (!item) {
    alert("Could not find item details for exporting.");
    return;
  }

  let csv = "Date,Type,Reference,Quantity,Balance After,Value,Remarks\n";

  const dataToExport = transactions; // Using the globally stored, unfiltered transactions for export
  const selectedItemInfo = {
    ...item,
    ...(transactions.length > 0
      ? { price: transactions[0].price }
      : { price: 0 }),
  };

  dataToExport.forEach((trans) => {
    const value = (trans.quantity * selectedItemInfo.price).toFixed(2);
    csv += `"${trans.trans_date}","${trans.trans_type.toUpperCase()}","${
      trans.reference || ""
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
