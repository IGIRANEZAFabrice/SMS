// Mock data - Replace with PHP/MySQL fetch later
let allItems = [
  { item_id: 1, name: "Laptop Dell XPS", system_stock: 15 },
  { item_id: 2, name: "iPhone 14 Pro", system_stock: 8 },
  { item_id: 3, name: "Samsung Galaxy S23", system_stock: 12 },
  { item_id: 4, name: "iPad Air", system_stock: 20 },
  { item_id: 5, name: "AirPods Pro", system_stock: 3 },
  { item_id: 6, name: "Magic Mouse", system_stock: 25 },
  { item_id: 7, name: "Mechanical Keyboard", system_stock: 10 },
  { item_id: 8, name: "USB-C Hub", system_stock: 30 },
  { item_id: 9, name: "Webcam HD", system_stock: 18 },
  { item_id: 10, name: "Wireless Charger", system_stock: 40 },
];

let countedItems = [];
let currentItemId = null;

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  loadItems();
  setupEventListeners();
});

function setupEventListeners() {
  document.getElementById("searchInput").addEventListener("input", filterItems);
  document.getElementById("saveCountBtn").addEventListener("click", saveCount);
  document.getElementById("saveBtn").addEventListener("click", saveStockTake);
  document.getElementById("clearBtn").addEventListener("click", clearAll);
  document
    .getElementById("physicalCountInput")
    .addEventListener("input", updateModalDisplay);

  // Enter key to save in modal
  document
    .getElementById("physicalCountInput")
    .addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        saveCount();
      }
    });
}

function loadItems(filter = "") {
  const itemsList = document.getElementById("itemsList");
  const filteredItems = allItems.filter((item) =>
    item.name.toLowerCase().includes(filter.toLowerCase())
  );

  if (filteredItems.length === 0) {
    itemsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>No items found</p>
                    </div>
                `;
    return;
  }

  itemsList.innerHTML = filteredItems
    .map((item) => {
      const isCounted = countedItems.some((c) => c.item_id === item.item_id);
      return `
                    <div class="item-card ${
                      isCounted ? "counted" : ""
                    }" onclick="openCountModal(${item.item_id})">
                        <div class="item-info">
                            <div class="item-name"><i class="fas fa-box"></i> ${
                              item.name
                            }</div>
                            <div class="item-stock">
                                <i class="fas fa-database"></i> System Stock: ${
                                  item.system_stock
                                }
                            </div>
                        </div>
                        ${
                          isCounted
                            ? '<div class="counted-badge"><i class="fas fa-check"></i> Counted</div>'
                            : ""
                        }
                    </div>
                `;
    })
    .join("");
}

function filterItems() {
  const searchTerm = document.getElementById("searchInput").value;
  loadItems(searchTerm);
}

function openCountModal(itemId) {
  const item = allItems.find((i) => i.item_id === itemId);
  if (!item) return;

  currentItemId = itemId;
  const existingCount = countedItems.find((c) => c.item_id === itemId);

  document.getElementById("modalItemName").textContent = item.name;
  document.getElementById("modalSystemStock").textContent = item.system_stock;
  document.getElementById("physicalCountInput").value = existingCount
    ? existingCount.physical_count
    : "";
  document.getElementById("modalPhysicalCount").textContent = existingCount
    ? existingCount.physical_count
    : "0";

  document.getElementById("countModal").classList.add("show");
  setTimeout(() => {
    document.getElementById("physicalCountInput").focus();
  }, 100);
}

function updateModalDisplay() {
  const value =
    parseFloat(document.getElementById("physicalCountInput").value) || 0;
  document.getElementById("modalPhysicalCount").textContent = value.toFixed(2);
}

function saveCount() {
  const physicalCount = parseFloat(
    document.getElementById("physicalCountInput").value
  );

  if (isNaN(physicalCount) || physicalCount < 0) {
    showAlert("Please enter a valid physical count", "error");
    return;
  }

  const item = allItems.find((i) => i.item_id === currentItemId);
  if (!item) return;

  // Remove existing count if any
  countedItems = countedItems.filter((c) => c.item_id !== currentItemId);

  // Add new count
  countedItems.push({
    item_id: currentItemId,
    name: item.name,
    system_stock: item.system_stock,
    physical_count: physicalCount,
    difference: physicalCount - item.system_stock,
  });

  updateCountedList();
  loadItems(document.getElementById("searchInput").value);
  closeModal();
  showAlert(`${item.name} counted successfully`, "success");
}

function updateCountedList() {
  const countedList = document.getElementById("countedList");
  const countedCount = document.getElementById("countedCount");
  const saveBtn = document.getElementById("saveBtn");

  countedCount.textContent = countedItems.length;
  saveBtn.disabled = countedItems.length === 0;

  if (countedItems.length === 0) {
    countedList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No items counted yet</p>
                    </div>
                `;
    return;
  }

  countedList.innerHTML = countedItems
    .map((item) => {
      const diffClass =
        item.difference > 0
          ? "positive"
          : item.difference < 0
          ? "negative"
          : "";
      return `
                    <div class="counted-item" onclick="openCountModal(${
                      item.item_id
                    })">
                        <div class="counted-item-header">
                            <span class="counted-item-name">${item.name}</span>
                            <button class="remove-btn" onclick="removeCount(event, ${
                              item.item_id
                            })">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="counted-details">
                            <div class="detail-item">
                                <div class="detail-label">System</div>
                                <div class="detail-value system">${
                                  item.system_stock
                                }</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Counted</div>
                                <div class="detail-value counted">${
                                  item.physical_count
                                }</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Difference</div>
                                <div class="detail-value diff ${diffClass}">
                                    ${
                                      item.difference > 0 ? "+" : ""
                                    }${item.difference.toFixed(2)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
    })
    .join("");
}

function removeCount(event, itemId) {
  event.stopPropagation();
  countedItems = countedItems.filter((c) => c.item_id !== itemId);
  updateCountedList();
  loadItems(document.getElementById("searchInput").value);
  showAlert("Item removed from count", "success");
}

function clearAll() {
  if (countedItems.length === 0) return;

  if (confirm("Clear all counted items?")) {
    countedItems = [];
    updateCountedList();
    loadItems();
    document.getElementById("searchInput").value = "";
    showAlert("All counts cleared", "success");
  }
}

function saveStockTake() {
  if (countedItems.length === 0) {
    showAlert("No items to save", "error");
    return;
  }

  // Prepare data for PHP/MySQL
  const stockTakeData = countedItems.map((item) => ({
    item_id: item.item_id,
    qty: item.physical_count,
    status: 1,
    system_stock: item.system_stock,
    difference: item.difference,
    created_by: 1, // Replace with actual user ID from session
  }));

  console.log("Stock Take Data:", stockTakeData);

  /* 
            TODO: Send to PHP via fetch/AJAX
            This should:
            1. Insert into stock_take table
            2. Update tbl_item_stock (set qty to physical_count)
            3. Insert into tbl_progress:
               - If difference > 0: in_qty = difference
               - If difference < 0: out_qty = abs(difference)
               - last_qty = system_stock
               - end_qty = physical_count
               - remark = "Stock Take Adjustment"
            
            fetch('save_stock_take.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(stockTakeData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showAlert('Stock take saved successfully!', 'success');
                    // Update system stock
                    countedItems.forEach(counted => {
                        const item = allItems.find(i => i.item_id === counted.item_id);
                        if(item) item.system_stock = counted.physical_count;
                    });
                    countedItems = [];
                    updateCountedList();
                    loadItems();
                }
            });
            */

  // Mock success
  setTimeout(() => {
    showAlert(
      `Stock take saved successfully! ${countedItems.length} items updated.`,
      "success"
    );

    // Update system stock
    countedItems.forEach((counted) => {
      const item = allItems.find((i) => i.item_id === counted.item_id);
      if (item) item.system_stock = counted.physical_count;
    });

    countedItems = [];
    updateCountedList();
    loadItems(document.getElementById("searchInput").value);
  }, 500);
}

function closeModal() {
  document.getElementById("countModal").classList.remove("show");
}

function showAlert(message, type) {
  const alert = document.getElementById("alert");
  alert.innerHTML = `<i class="fas fa-${
    type === "success" ? "check-circle" : "exclamation-circle"
  }"></i> ${message}`;
  alert.className = `alert alert-${type} show`;

  setTimeout(() => {
    alert.classList.remove("show");
  }, 3000);
}

// Close modal when clicking outside
document.getElementById("countModal").addEventListener("click", function (e) {
  if (e.target === this) {
    closeModal();
  }
});
