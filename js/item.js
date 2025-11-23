let categories = [];
let suppliers = [];
let items = [];
let stock = [];
let units = [];
let itemsForStock = [];
const apiBase = "/SMS/pages/additem.php";

// Tab switching
function switchTab(tab) {
  const tabs = document.querySelectorAll(".tab-btn");
  const contents = document.querySelectorAll(".tab-content");

  tabs.forEach((t) => t.classList.remove("active"));
  contents.forEach((c) => c.classList.remove("active"));

  // Ensure proper activation without relying on event
  const btns = Array.from(document.querySelectorAll(".tab-btn"));
  if (tab === "categories") btns[0]?.classList.add("active");
  else if (tab === "units") btns[1]?.classList.add("active");
  else if (tab === "items") btns[2]?.classList.add("active");
  else btns[3]?.classList.add("active");
  document.getElementById(tab + "Tab").classList.add("active");
}

// Function to load dropdowns
function loadDropdowns() {
  // Load categories dropdown
  const categorySelect = document.getElementById('itemCategory');
  if (categorySelect) {
    categorySelect.innerHTML = '<option value="">Select category</option>';
    categories.forEach(category => {
      const option = document.createElement('option');
      option.value = category.cat_id;
      option.textContent = category.cat_name;
      categorySelect.appendChild(option);
    });
  }
  
  // Load suppliers dropdown
  const supplierSelect = document.getElementById('itemSupplier');
  if (supplierSelect) {
    supplierSelect.innerHTML = '<option value="">Select supplier</option>';
    suppliers.forEach(supplier => {
      const option = document.createElement('option');
      option.value = supplier.supplier_id;
      option.textContent = supplier.supplier_name;
      supplierSelect.appendChild(option);
    });
  }
  
  // Load units dropdown
  const unitSelect = document.getElementById('itemUnit');
  if (unitSelect) {
    unitSelect.innerHTML = '<option value="">Select unit</option>';
    units.filter(unit => unit.status === 1).forEach(unit => {
      const option = document.createElement('option');
      option.value = unit.unit_id;
      option.textContent = unit.unit_name;
      unitSelect.appendChild(option);
    });
  }
  
  // Load items dropdown for stock
  const itemSelect = document.getElementById('stockItem');
  if (itemSelect) {
    itemSelect.innerHTML = '<option value="">Select item</option>';
    itemsForStock.forEach(item => {
      if (item.item_status === 1) { // Only show active items
        const option = document.createElement('option');
        option.value = item.item_id;
        option.textContent = `${item.item_name} (${item.cat_name})`;
        option.setAttribute('data-category', item.cat_name);
        option.setAttribute('data-unit', item.unit_name || 'N/A');
        itemSelect.appendChild(option);
      }
    });
  }
}

// Load categories table
function loadCategories() {
  const tbody = document.getElementById("categoriesTableBody");
  tbody.innerHTML = "";

  if (categories.length === 0) {
    return;
  }

  categories.forEach((cat) => {
    tbody.innerHTML += `
            <tr>
              <td>${cat.cat_id}</td>
              <td><strong>${cat.cat_name}</strong></td>
              <td>${
                cat.description ||
                '<span style="color: var(--gray-mid)">N/A</span>'
              }</td>
              <td>${cat.created_at}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editCategory(${
                    cat.cat_id
                  })">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteCategory(${
                    cat.cat_id
                  })">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
  });
}

// Load items table
function loadItems() {
  const tbody = document.getElementById("itemsTableBody");
  tbody.innerHTML = "";

  if (items.length === 0) {
    return;
  }

  items.forEach((item) => {
    const cat = categories.find((c) => c.cat_id === item.cat_id);
    const status =
      item.item_status === 1
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-danger">Inactive</span>';

    tbody.innerHTML += `
            <tr>
              <td>${item.item_id}</td>
              <td><strong>${item.item_name}</strong></td>
              <td>${cat ? cat.cat_name : "N/A"}</td>
              <td>${item.unit_name}</td>
              <td>$${item.price.toFixed(2)}</td>
              <td>${status}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editItem(${
                    item.item_id
                  })">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteItem(${
                    item.item_id
                  })">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
  });
}

// Load opening stock table
function loadOpeningStock() {
  const tbody = document.getElementById("openingStockTableBody");
  tbody.innerHTML = "";

  if (stock.length === 0) {
    return;
  }

  stock.forEach((s) => {
    const item = items.find((i) => i.item_id === s.item_id);
    if (item) {
      tbody.innerHTML += `
              <tr>
                <td>${item.item_id}</td>
                <td><strong>${item.item_name}</strong></td>
                <td>${s.qty}</td>
                <td>${item.unit_name}</td>
                <td>${s.last_update}</td>
              </tr>
            `;
    }
  });
}

// Check if item has transactions
function checkItemTransactions() {
  const itemId = parseInt(document.getElementById("stockItem").value);
  const warning = document.getElementById("stockWarning");
  const warningText = document.getElementById("stockWarningText");
  const submitBtn = document.getElementById("submitStockBtn");

  if (!itemId) {
    warning.style.display = "none";
    submitBtn.disabled = false;
    return;
  }

  warning.style.display = "none";
  submitBtn.disabled = false;
}

// Add Category
document.getElementById("addCategoryForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const name = document.getElementById("catName").value.trim();
  const desc = document.getElementById("catDescription").value.trim();

  if (!name) return;
  fetch(`${apiBase}?api=categories`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "add", cat_name: name, description: desc }),
  })
    .then((r) => r.json())
    .then((resp) => {
      if (resp && resp.success) {
        document.getElementById("addCategoryForm").reset();
        fetchCategories();
        loadDropdowns();
        Swal.fire({
          icon: "success",
          title: "Category Added!",
          timer: 2000,
          showConfirmButton: false,
        });
      }
    });
});

// Function to show alerts using SweetAlert2
function showAlert(icon, title, text) {
  Swal.fire({
    icon: icon,
    title: title,
    text: text,
    timer: 2000,
    showConfirmButton: false,
  });
}

// Add Item
document.getElementById("addItemForm").addEventListener("submit", addItemForm);

// Function to handle add item form submission
async function addItemForm(e) {
  e.preventDefault();

  const form = e.target;
  const itemName = document.getElementById("itemName").value.trim();
  const catId = document.getElementById("itemCategory").value;
  const supplierId = document.getElementById("itemSupplier").value;
  const unitId = document.getElementById("itemUnit").value;
  const price = document.getElementById("itemPrice").value;
  const itemStatus = document.getElementById("itemStatus").value;

  // Basic validation
  if (!itemName || !catId || !supplierId || !unitId || !price) {
    showAlert("error", "Error", "Please fill in all required fields");
    return;
  }

  // Convert price to number and validate
  const priceValue = parseFloat(price);
  if (isNaN(priceValue) || priceValue <= 0) {
    showAlert("error", "Error", "Please enter a valid price");
    return;
  }

  // Check if the selected unit is active
  const selectedUnit = units.find(
    (unit) => unit.unit_id === parseInt(unitId)
  );
  if (!selectedUnit || selectedUnit.status !== 1) {
    showAlert(
      "error",
      "Error",
      "The selected unit is not active. Please select an active unit."
    );
    return;
  }

  try {
    // Prepare the data to send
    const postData = {
      action: "add",
      item_name: itemName,
      cat_id: catId,
      supplier_id: supplierId,
      unit_id: unitId,
      price: priceValue,
      item_status: itemStatus || 1,
    };

    const response = await fetch("additem.php?api=items", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams(postData).toString(),
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Success", "Item added successfully");
      form.reset();
      await fetchItems();

      // Switch to items tab if not already there
      switchTab("items");
    } else {
      throw new Error(data.message || "Failed to add item");
    }
  } catch (error) {
    console.error("Error adding item:", error);
    showAlert(
      "error",
      "Error",
      error.message || "Failed to add item. Please try again."
    );
  }
}

// Add Opening Stock
document
  .getElementById("addOpeningStockForm")
  .addEventListener("submit", addOpeningStock);

// Function to handle add opening stock form submission
async function addOpeningStock(e) {
  e.preventDefault();

  const form = e.target;
  const itemId = document.getElementById("stockItem").value;
  const quantity = document.getElementById("stockQty").value;

  // Basic validation
  if (!itemId || !quantity) {
    showAlert("error", "Error", "Please select an item and enter quantity");
    return;
  }

  // Convert quantity to number
  const quantityValue = parseInt(quantity);
  if (isNaN(quantityValue) || quantityValue <= 0) {
    showAlert("error", "Error", "Please enter a valid quantity");
    return;
  }

  // Check if the selected item exists and is active
  const selectedItem = itemsForStock.find((item) => item.item_id === parseInt(itemId));
  if (!selectedItem) {
    showAlert("error", "Error", "Selected item not found");
    return;
  }

  if (selectedItem.item_status !== 1) {
    showAlert("error", "Error", "Cannot add stock for an inactive item");
    return;
  }

  // Check if the item's unit is active
  const itemUnit = units.find(
    (unit) => unit.unit_id === selectedItem.unit_id
  );
  if (!itemUnit || itemUnit.status !== 1) {
    showAlert("error", "Error", "The unit for the selected item is not active");
    return;
  }

  try {
    const response = await fetch("additem.php?api=stock", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=add&item_id=${itemId}&qty=${quantityValue}`,
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Success", "Opening stock added successfully");
      form.reset();
      await fetchStock();
      await fetchItemsForStock();
      loadDropdowns();
    } else {
      throw new Error(data.message || "Failed to add opening stock");
    }
  } catch (error) {
    console.error("Error adding opening stock:", error);
    showAlert(
      "error",
      "Error",
      error.message || "Failed to add opening stock. Please try again."
    );
  }
}

// Edit/Delete functions (simplified)
function editCategory(id) {
  Swal.fire({
    icon: "info",
    title: "Edit Category",
    text: "Edit functionality - connect to your API",
  });
}

function deleteCategory(id) {
  Swal.fire({
    icon: "warning",
    title: "Delete Category",
    text: "Delete functionality - connect to your API",
  });
}

function editItem(id) {
  Swal.fire({
    icon: "info",
    title: "Edit Item",
    text: "Edit functionality - connect to your API",
  });
}

function deleteItem(id) {
  Swal.fire({
    icon: "warning",
    title: "Delete Item",
    text: "Delete functionality - connect to your API",
  });
}

async function fetchCategories() {
  const r = await fetch(`${apiBase}?api=categories`);
  const d = await r.json();
  categories = d && d.success ? d.data : [];
  loadCategories();
}

async function fetchItems() {
  const r = await fetch(`${apiBase}?api=items`);
  const d = await r.json();
  items = d && d.success ? d.data : [];
  loadItems();
}

async function fetchItemsForStock() {
  const r = await fetch(`${apiBase}?api=items-for-stock`);
  const d = await r.json();
  itemsForStock = d && d.success ? d.data : [];
}

async function fetchStock() {
  const r = await fetch(`${apiBase}?api=stock`);
  const d = await r.json();
  stock = d && d.success ? d.data : [];
  loadOpeningStock();
}

// Function to fetch units
async function fetchUnits() {
  try {
    const response = await fetch('additem.php?api=units');
    const data = await response.json();
    
    if (data.success) {
      units = data.data;
      if (document.getElementById('unitsTableBody')) {
        loadUnits();
      }
    } else {
      throw new Error(data.message || 'Failed to load units');
    }
  } catch (error) {
    console.error('Error fetching units:', error);
    showAlert('error', 'Error', 'Failed to load units. Please try again.');
  }
}

// Function to load units table
function loadUnits() {
  const tbody = document.getElementById('unitsTableBody');
  if (!tbody) return;
  
  if (units.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No units found</td></tr>';
    return;
  }
  
  tbody.innerHTML = '';
  
  units.forEach(unit => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${unit.unit_id}</td>
      <td>${unit.unit_name}</td>
      <td>
        <span class="badge ${unit.status === 1 ? 'badge-success' : 'badge-danger'}">
          ${unit.status === 1 ? 'Active' : 'Inactive'}
        </span>
      </td>
      <td>${new Date(unit.created_at).toLocaleDateString()}</td>
      <td class="action-buttons">
        <button class="btn btn-sm btn-primary" onclick="editUnit(${unit.unit_id})">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-danger" onclick="deleteUnit(${unit.unit_id}, '${unit.unit_name}')">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// Function to handle add unit form submission
async function handleAddUnit(e) {
  e.preventDefault();
  
  const unitName = document.getElementById('unitName').value.trim();
  const unitStatus = parseInt(document.getElementById('unitStatus').value);
  
  if (!unitName) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Please enter a unit name'
    });
    return;
  }

  const addButton = document.querySelector('#addUnitForm button[type="submit"]');
  const originalButtonContent = addButton.innerHTML;
  addButton.disabled = true;
  addButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
  
  try {
    const response = await fetch('additem.php?api=units', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `action=add&unit_name=${encodeURIComponent(unitName)}&status=${unitStatus}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Unit Added!',
        text: 'The new unit has been added successfully.',
        timer: 2000,
        showConfirmButton: false
      });
      document.getElementById('addUnitForm').reset();
      await fetchUnits();
      loadDropdowns(); // Refresh the units dropdown
    } else {
      throw new Error(data.message || 'Failed to add unit');
    }
  } catch (error) {
    console.error('Error adding unit:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message || 'Failed to add unit. Please try again.'
    });
  } finally {
    addButton.disabled = false;
    addButton.innerHTML = originalButtonContent;
  }
}

// Function to edit a unit
async function editUnit(unitId) {
  const unit = units.find(u => u.unit_id === unitId);
  if (!unit) return;
  
  Swal.fire({
    title: 'Edit Unit',
    html: `
      <div class="form-group">
        <label>Unit Name</label>
        <input id="editUnitName" class="swal2-input" value="${unit.unit_name}" required>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select id="editUnitStatus" class="swal2-select">
          <option value="1" ${unit.status === 1 ? 'selected' : ''}>Active</option>
          <option value="0" ${unit.status === 0 ? 'selected' : ''}>Inactive</option>
        </select>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Update',
    showLoaderOnConfirm: true,
    preConfirm: async () => {
      const unitName = document.getElementById('editUnitName').value.trim();
      const status = parseInt(document.getElementById('editUnitStatus').value);
      
      if (!unitName) {
        Swal.showValidationMessage('Please enter a unit name');
        return false;
      }
      
      try {
        const response = await fetch('additem.php?api=units', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=update&unit_id=${unitId}&unit_name=${encodeURIComponent(unitName)}&status=${status}`
        });
        
        const data = await response.json();
        
        if (!data.success) {
          throw new Error(data.message || 'Failed to update unit');
        }
        
        return data;
      } catch (error) {
        Swal.showValidationMessage(error.message || 'Failed to update unit');
        return false;
      }
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then((result) => {
    if (result.isConfirmed) {
      showAlert('success', 'Success', 'Unit updated successfully');
      fetchUnits();
      loadDropdowns(); // Refresh the units dropdown
    }
  });
}

// Function to delete a unit
async function deleteUnit(unitId, unitName) {
  // Check if the unit is used in any items
  const isUsed = items.some(item => item.unit_id === unitId);
  
  if (isUsed) {
    showAlert('error', 'Cannot Delete', `The unit "${unitName}" is being used by one or more items and cannot be deleted.`);
    return;
  }
  
  const result = await Swal.fire({
    title: 'Delete Unit',
    text: `Are you sure you want to delete the unit "${unitName}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
  });
  
  if (result.isConfirmed) {
    try {
      const response = await fetch('additem.php?api=units', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&unit_id=${unitId}`
      });
      
      const data = await response.json();
      
      if (data.success) {
        showAlert('success', 'Deleted!', 'The unit has been deleted.');
        fetchUnits();
        loadDropdowns(); // Refresh the units dropdown
      } else {
        throw new Error(data.message || 'Failed to delete unit');
      }
    } catch (error) {
      console.error('Error deleting unit:', error);
      showAlert('error', 'Error', error.message || 'Failed to delete unit. Please try again.');
    }
  }
}

async function fetchSuppliers() {
  const r = await fetch("/SMS/pages/supplier.php?api=suppliers");
  const d = await r.json();
  suppliers = d && d.success ? d.data : [];
  loadDropdowns();
}

(async function init() {
  document.getElementById('addUnitForm').addEventListener('submit', handleAddUnit);
  await Promise.all([
    fetchCategories(),
    fetchSuppliers(),
    fetchUnits(),
    fetchItems(),
    fetchItemsForStock(),
    fetchStock()
  ]);
  loadDropdowns();
})();