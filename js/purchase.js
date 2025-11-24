// Global state
let allItems = []; // This will be populated from the server
let selectedItems = [];

document.addEventListener("DOMContentLoaded", function () {
  loadInitialData();
  setupEventListeners();
  setDefaultDate();
});

function setupEventListeners() {
  document.getElementById("itemSearch").addEventListener("input", filterItems);
  document.getElementById("submitBtn").addEventListener("click", submitRequest);
  document.getElementById("clearBtn").addEventListener("click", clearAll);
   // Modal listeners
  document.getElementById('closeModal').addEventListener('click', closeReceiptModal);
  document.getElementById('printReceiptBtn').addEventListener('click', printReceipt);
}

function setDefaultDate() {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("requestDate").value = today;
}

// --- Data Fetching ---
async function loadInitialData() {
  try {
    await Promise.all([loadSuppliers(), loadAllItems()]);
    // Initial render of items after loading
    renderItemsList();
  } catch (error) {
    showAlert("Failed to load initial page data.", "error");
    console.error("Initialization failed:", error);
  }
}

async function loadSuppliers() {
  try {
    const response = await fetch("purchase.php?api=1&fetch=suppliers");
    const result = await response.json();

    if (!result.success) throw new Error(result.message);
    
    const select = document.getElementById("supplierSelect");
    select.innerHTML = '<option value="">-- Select Supplier --</option>'; // Clear existing
    result.data.forEach((supplier) => {
      const option = document.createElement("option");
      option.value = supplier.supplier_id;
      option.textContent = supplier.supplier_name;
      select.appendChild(option);
    });
  } catch (error) {
    showAlert("Could not load suppliers.", "error");
    console.error("Error loading suppliers:", error);
  }
}

async function loadAllItems() {
  try {
    const response = await fetch("purchase.php?api=1&fetch=items");
    const result = await response.json();

    if (!result.success) throw new Error(result.message);
    
    allItems = result.data; // Store all items globally
  } catch (error) {
    showAlert("Could not load items.", "error");
    console.error("Error loading items:", error);
  }
}

// --- UI Rendering ---
function renderItemsList() {
  const filter = document.getElementById("itemSearch").value;
  const itemsList = document.getElementById("itemsList");
  
  const filteredItems = allItems.filter((item) =>
    item.item_name.toLowerCase().includes(filter.toLowerCase())
  );

  if (filteredItems.length === 0) {
    itemsList.innerHTML = `<div class="empty-state"><i class="fas fa-search"></i><p>No items found</p></div>`;
    return;
  }

  itemsList.innerHTML = filteredItems.map((item) => {
      const isAdded = selectedItems.some((si) => si.item_id === item.item_id);
      return `
        <div class="item-option">
          <div class="item-info">
            <div class="item-name"><i class="fas fa-box"></i> ${item.item_name}</div>
            <div class="item-stock">Current Stock: ${item.stock || 0}</div>
          </div>
          <button class="item-add-btn" onclick="addItem(${item.item_id})" ${isAdded ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ""}>
            <i class="fas ${isAdded ? "fa-check" : "fa-plus"}"></i>
            ${isAdded ? "Added" : "Add"}
          </button>
        </div>`;
    }).join("");
}

function updateSelectedItemsUI() {
  const container = document.getElementById("selectedItemsList");
  const count = document.getElementById("selectedCount");
  const submitBtn = document.getElementById("submitBtn");

  count.textContent = selectedItems.length;

  if (selectedItems.length === 0) {
    container.innerHTML = `<div class="empty-state"><i class="fas fa-inbox"></i><p>No items selected</p></div>`;
    submitBtn.disabled = true;
  } else {
    container.innerHTML = selectedItems.map((item, index) => `
      <div class="selected-item">
        <div class="selected-item-header">
          <span class="selected-item-name">${item.name}</span>
          <button class="remove-btn" onclick="removeItem(${index})"><i class="fas fa-times"></i></button>
        </div>
        <div class="qty-input-group">
          <span class="qty-label">Quantity:</span>
          <input type="number" class="qty-input" value="${item.qty_requested}" min="1" onchange="updateQty(${index}, this.value)">
        </div>
      </div>`).join("");
    submitBtn.disabled = false;
  }
}

// --- Event Handlers & Actions ---
function filterItems() {
  renderItemsList();
}

function addItem(itemId) {
  const item = allItems.find((i) => i.item_id === itemId);
  if (!item) return;

  const exists = selectedItems.some((si) => si.item_id === itemId);
  if (exists) {
    showAlert("Item already added", "error");
    return;
  }

  selectedItems.push({ item_id: item.item_id, name: item.item_name, qty_requested: 1 });

  updateSelectedItemsUI();
  renderItemsList();
  showAlert(`${item.name} added`, "success");
}

function updateQty(index, value) {
  const qty = parseInt(value, 10) || 1;
  selectedItems[index].qty_requested = qty < 1 ? 1 : qty;
  updateSelectedItemsUI(); // No need to re-render, just update the model
}

function removeItem(index) {
  selectedItems.splice(index, 1);
  updateSelectedItemsUI();
  renderItemsList();
  showAlert("Item removed", "success");
}

function clearAll() {
  if (selectedItems.length === 0) return;
  if (confirm("Are you sure you want to clear all selected items?")) {
    selectedItems = [];
    updateSelectedItemsUI();
    renderItemsList();
    showAlert("All items cleared", "success");
  }
}
function resetForm() {
    document.getElementById('supplierSelect').value = '';
    document.getElementById('itemSearch').value = '';
    selectedItems = [];
    updateSelectedItemsUI();
    renderItemsList();
    setDefaultDate();
}
async function submitRequest() {
  const supplierSelect = document.getElementById("supplierSelect");
  const supplierId = supplierSelect.value;
  let supplierName = '';
  if (supplierSelect.selectedIndex > -1) {
      supplierName = supplierSelect.options[supplierSelect.selectedIndex].text;
  }
  const requestDate = document.getElementById("requestDate").value;
  const submitBtn = document.getElementById("submitBtn");

  if (!supplierId || !requestDate || selectedItems.length === 0) {
    showAlert("Please select a supplier, date, and add at least one item.", "error");
    return;
  }

  const requestData = {
    supplier_id: supplierId,
    request_date: requestDate,
    items: selectedItems,
  };

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

  try {
    const response = await fetch('purchase.php?api=1', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(requestData)
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }
    showAlert(`Request #${result.request_id} created successfully!`, 'success');
     // Show receipt
    showReceiptModal({
        requestId: result.request_id,
        supplierName: supplierName,
        requestDate: requestDate,
        items: selectedItems
    });

  } catch (error) {
    showAlert(error.message || 'An unknown error occurred during submission.', 'error');
    console.error("Submission failed:", error);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
  }
}

function showReceiptModal(data) {
    const receiptDetails = document.getElementById('receiptDetails');
    const modal = document.getElementById('receiptModal');

    const itemsHtml = data.items.map(item => `
        <tr>
            <td>${item.name}</td>
            <td class="text-right">${item.qty_requested}</td>
        </tr>
    `).join('');

    receiptDetails.innerHTML = `
        <div class="receipt-header">
            <h3>Purchase Order</h3>
        </div>
        <div class="receipt-info">
            <p><strong>Request ID:</strong> #${data.requestId}</p>
            <p><strong>Supplier:</strong> ${data.supplierName}</p>
            <p><strong>Date:</strong> ${new Date(data.requestDate).toLocaleDateString()}</p>
        </div>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th class="text-right">Quantity</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>
        <div class="receipt-footer">
            <p>Thank you for your business!</p>
        </div>
    `;

    modal.style.display = 'flex';
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.style.display = 'none';
    resetForm(); // Reset the main form after closing the modal
}

function printReceipt() {
    window.print();
}

function showAlert(message, type) {
  const alert = document.getElementById("alert");
  alert.textContent = message;
  alert.className = `alert alert-${type} show`;

  setTimeout(() => {
    alert.classList.remove("show");
  }, 3000);
}
