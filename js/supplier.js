let suppliers = [];
const apiBase = "/SMS/pages/supplier.php";
async function fetchSuppliers() {
  const res = await fetch(`${apiBase}?api=suppliers`);
  const data = await res.json();
  suppliers = data && data.success ? data.data : [];
  loadSuppliers();
}

// Update statistics
function updateStats() {
  document.getElementById("totalSuppliers").textContent = suppliers.length;

  // Count suppliers added this month
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear();
  const newThisMonth = suppliers.filter((s) => {
    const date = new Date(s.created_at);
    return (
      date.getMonth() === currentMonth && date.getFullYear() === currentYear
    );
  }).length;
  document.getElementById("newThisMonth").textContent = newThisMonth;

  // For demo, active suppliers = total suppliers
  document.getElementById("activeSuppliers").textContent = suppliers.length;
}

// Load suppliers into table
function loadSuppliers() {
  const tbody = document.getElementById("suppliersTableBody");
  tbody.innerHTML = "";

  if (suppliers.length === 0) {
    tbody.innerHTML = `
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="fas fa-truck"></i>
                  <p>No suppliers found. Add your first supplier!</p>
                </div>
              </td>
            </tr>
          `;
    updateStats();
    return;
  }

  suppliers.forEach((supplier) => {
    const row = `
            <tr>
              <td>${supplier.supplier_id}</td>
              <td><strong>${supplier.supplier_name}</strong></td>
              <td>${
                supplier.phone ||
                '<span style="color: var(--gray-mid)">N/A</span>'
              }</td>
              <td>${
                supplier.address ||
                '<span style="color: var(--gray-mid)">N/A</span>'
              }</td>
              <td>${formatDate(supplier.created_at)}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-primary" onclick="editSupplier(${
                    supplier.supplier_id
                  })">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteSupplier(${
                    supplier.supplier_id
                  })">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </div>
              </td>
            </tr>
          `;
    tbody.innerHTML += row;
  });

  updateStats();
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Add Supplier
document.getElementById("addSupplierForm").addEventListener("submit", (e) => {
  e.preventDefault();

  const supplierName = document.getElementById("supplierName").value.trim();
  const phone = document.getElementById("supplierPhone").value.trim();
  const address = document.getElementById("supplierAddress").value.trim();

  if (!supplierName) return;
  fetch(`${apiBase}?api=suppliers`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "add",
      supplier_name: supplierName,
      phone,
      address,
    }),
  })
    .then((r) => r.json())
    .then((resp) => {
      if (resp && resp.success) {
        document.getElementById("addSupplierForm").reset();
        fetchSuppliers();
        Swal.fire({
          icon: "success",
          title: "Supplier Added!",
          text: `${supplierName} has been added successfully`,
          timer: 2000,
          showConfirmButton: false,
        });
      }
    });
});

// Edit Supplier
function editSupplier(supplierId) {
  const supplier = suppliers.find((s) => s.supplier_id === supplierId);
  if (!supplier) return;

  Swal.fire({
    title: "Edit Supplier",
    html: `
            <input id="swal-name" class="swal2-input" value="${
              supplier.supplier_name
            }" placeholder="Supplier Name">
            <input id="swal-phone" class="swal2-input" value="${
              supplier.phone || ""
            }" placeholder="Phone">
            <textarea id="swal-address" class="swal2-textarea" placeholder="Address">${
              supplier.address || ""
            }</textarea>
          `,
    showCancelButton: true,
    confirmButtonText: "Update",
    confirmButtonColor: "#3b82f6",
    preConfirm: () => {
      const name = document.getElementById("swal-name").value.trim();
      if (!name) {
        Swal.showValidationMessage("Supplier name is required");
        return false;
      }
      return {
        supplier_name: name,
        phone: document.getElementById("swal-phone").value.trim(),
        address: document.getElementById("swal-address").value.trim(),
      };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`${apiBase}?api=suppliers`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "update",
          supplier_id: supplierId,
          supplier_name: result.value.supplier_name,
          phone: result.value.phone,
          address: result.value.address,
        }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchSuppliers();
            Swal.fire({
              icon: "success",
              title: "Updated!",
              text: "Supplier has been updated",
              timer: 2000,
              showConfirmButton: false,
            });
          }
        });
    }
  });
}

// Delete Supplier
function deleteSupplier(supplierId) {
  const supplier = suppliers.find((s) => s.supplier_id === supplierId);
  if (!supplier) return;

  Swal.fire({
    title: "Delete Supplier?",
    text: `Are you sure you want to delete ${supplier.supplier_name}? This action cannot be undone.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    confirmButtonText: "Yes, delete it!",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`${apiBase}?api=suppliers`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", supplier_id: supplierId }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            fetchSuppliers();
            Swal.fire({
              icon: "success",
              title: "Deleted!",
              text: "Supplier has been deleted",
              timer: 2000,
              showConfirmButton: false,
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Cannot Delete",
              text: resp && resp.message ? resp.message : "Error",
            });
          }
        });
    }
  });
}

// Search Table
function searchTable(searchText) {
  const table = document.getElementById("suppliersTable");
  const rows = table.getElementsByTagName("tr");

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const text = row.textContent.toLowerCase();
    if (text.includes(searchText.toLowerCase())) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  }
}

// Export Suppliers
function exportSuppliers() {
  if (suppliers.length === 0) {
    Swal.fire({
      icon: "info",
      title: "No Data",
      text: "There are no suppliers to export",
    });
    return;
  }

  // Create CSV content
  let csv = "ID,Supplier Name,Phone,Address,Created At\n";
  suppliers.forEach((s) => {
    csv += `${s.supplier_id},"${s.supplier_name}","${s.phone || ""}","${
      s.address || ""
    }","${s.created_at}"\n`;
  });

  // Create download link
  const blob = new Blob([csv], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `suppliers_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);

  Swal.fire({
    icon: "success",
    title: "Exported!",
    text: "Suppliers data has been exported",
    timer: 2000,
    showConfirmButton: false,
  });
}

fetchSuppliers();
