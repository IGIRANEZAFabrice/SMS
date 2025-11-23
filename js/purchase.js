// Mock data - Replace with PHP/MySQL fetch later
let suppliers = [
  { supplier_id: 1, name: "Tech Suppliers Ltd" },
  { supplier_id: 2, name: "Office Supplies Co" },
  { supplier_id: 3, name: "Electronics Hub" },
  { supplier_id: 4, name: "Hardware Store Inc" },
];

let items = [
  { item_id: 1, name: "Laptop Dell XPS", stock: 15 },
  { item_id: 2, name: "iPhone 14 Pro", stock: 8 },
  { item_id: 3, name: "Samsung Galaxy S23", stock: 12 },
  { item_id: 4, name: "iPad Air", stock: 20 },
  { item_id: 5, name: "AirPods Pro", stock: 3 },
  { item_id: 6, name: "Magic Mouse", stock: 25 },
  { item_id: 7, name: "Mechanical Keyboard", stock: 10 },
  { item_id: 8, name: "USB-C Hub", stock: 30 },
  { item_id: 9, name: "Webcam HD", stock: 18 },
  { item_id: 10, name: "Wireless Charger", stock: 40 },
];

let selectedItems = [];

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  loadSuppliers();
  loadItems();
  setupEventListeners();
  setDefaultDate();
});

function setupEventListeners() {
  document.getElementById("itemSearch").addEventListener("input", filterItems);
  document.getElementById("submitBtn").addEventListener("click", submitRequest);
  document.getElementById("clearBtn").addEventListener("click", clearAll);
}

function setDefaultDate() {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("requestDate").value = today;
}

function loadSuppliers() {
  const select = document.getElementById("supplierSelect");
  suppliers.forEach((supplier) => {
    const option = document.createElement("option");
    option.value = supplier.supplier_id;
    option.textContent = supplier.name;
    select.appendChild(option);
  });
}

function loadItems(filter = "") {
  const itemsList = document.getElementById("itemsList");
  const filteredItems = items.filter((item) =>
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
      const isAdded = selectedItems.some((si) => si.item_id === item.item_id);
      return `
                    <div class="item-option">
                        <div class="item-info">
                            <div class="item-name"><i class="fas fa-box"></i> ${
                              item.name
                            }</div>
                            <div class="item-stock">Current Stock: ${
                              item.stock
                            }</div>
                        </div>
                        <button class="item-add-btn" onclick="addItem(${
                          item.item_id
                        })" ${
        isAdded ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ""
      }>
                            <i class="fas ${
                              isAdded ? "fa-check" : "fa-plus"
                            }"></i>
                            ${isAdded ? "Added" : "Add"}
                        </button>
                    </div>
                `;
    })
    .join("");
}

function filterItems() {
  const searchTerm = document.getElementById("itemSearch").value;
  loadItems(searchTerm);
}

function addItem(itemId) {
  const item = items.find((i) => i.item_id === itemId);
  if (!item) return;

  const exists = selectedItems.some((si) => si.item_id === itemId);
  if (exists) {
    showAlert("Item already added", "error");
    return;
  }

  selectedItems.push({
    item_id: item.item_id,
    name: item.name,
    qty_requested: 1,
  });

  updateSelectedItems();
  loadItems(document.getElementById("itemSearch").value);
  showAlert(`${item.name} added`, "success");
}

function updateSelectedItems() {
  const container = document.getElementById("selectedItemsList");
  const count = document.getElementById("selectedCount");
  const submitBtn = document.getElementById("submitBtn");

  count.textContent = selectedItems.length;

  if (selectedItems.length === 0) {
    container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No items selected</p>
                    </div>
                `;
    submitBtn.disabled = true;
  } else {
    container.innerHTML = selectedItems
      .map(
        (item, index) => `
                    <div class="selected-item">
                        <div class="selected-item-header">
                            <span class="selected-item-name">${item.name}</span>
                            <button class="remove-btn" onclick="removeItem(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="qty-input-group">
                            <span class="qty-label">Quantity:</span>
                            <input type="number" class="qty-input" value="${item.qty_requested}" 
                                   min="1" onchange="updateQty(${index}, this.value)">
                        </div>
                    </div>
                `
      )
      .join("");
    submitBtn.disabled = false;
  }
}

function updateQty(index, value) {
  const qty = parseInt(value) || 1;
  selectedItems[index].qty_requested = qty < 1 ? 1 : qty;
  updateSelectedItems();
}

function removeItem(index) {
  selectedItems.splice(index, 1);
  updateSelectedItems();
  loadItems(document.getElementById("itemSearch").value);
  showAlert("Item removed", "success");
}

function clearAll() {
  if (selectedItems.length === 0) return;

  if (confirm("Clear all selected items?")) {
    selectedItems = [];
    updateSelectedItems();
    loadItems();
    document.getElementById("itemSearch").value = "";
    showAlert("All items cleared", "success");
  }
}

function submitRequest() {
  const supplierId = document.getElementById("supplierSelect").value;
  const requestDate = document.getElementById("requestDate").value;

  if (!supplierId) {
    showAlert("Please select a supplier", "error");
    return;
  }

  if (!requestDate) {
    showAlert("Please select a request date", "error");
    return;
  }

  if (selectedItems.length === 0) {
    showAlert("Please add at least one item", "error");
    return;
  }

  // Prepare data for PHP/MySQL
  const requestData = {
    supplier_id: supplierId,
    request_date: requestDate,
    status: "pending",
    created_by: 1, // Replace with actual user ID from session
    items: selectedItems,
  };

  console.log("Purchase Request Data:", requestData);

  /* 
            TODO: Send to PHP via fetch/AJAX
            fetch('create_purchase_request.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showAlert('Purchase request created successfully!', 'success');
                    // Reset form
                    document.getElementById('supplierSelect').value = '';
                    document.getElementById('itemSearch').value = '';
                    selectedItems = [];
                    updateSelectedItems();
                    loadItems();
                    setDefaultDate();
                }
            });
            */

  // Mock success
  setTimeout(() => {
    showAlert(
      "Purchase request created successfully! Request ID: #" +
        Math.floor(Math.random() * 1000),
      "success"
    );

    // Reset form
    document.getElementById("supplierSelect").value = "";
    document.getElementById("itemSearch").value = "";
    selectedItems = [];
    updateSelectedItems();
    loadItems();
  }, 500);
}

function showAlert(message, type) {
  const alert = document.getElementById("alert");
  alert.textContent = message;
  alert.className = `alert alert-${type} show`;

  setTimeout(() => {
    alert.classList.remove("show");
  }, 3000);
}
