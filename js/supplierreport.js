let reportData = {
  requests: [],
  stats: {},
  topSuppliers: [],
  suppliers: [],
};

document.addEventListener('DOMContentLoaded', () => {
    loadReportData();
});

async function loadReportData() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const supplierId = document.getElementById('supplierFilter').value;

    let url = 'supplierreport.php?api=supplier-report';
    if (dateFrom) url += `&from=${dateFrom}`;
    if (dateTo) url += `&to=${dateTo}`;
    if (supplierId) url += `&supplier=${supplierId}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            reportData = data.data;
            renderAll();
        } else {
            console.error('Failed to load report data:', data.message);
        }
    } catch (error) {
        console.error('Error fetching report data:', error);
    }
}

function renderAll() {
    updateStatistics();
    loadSupplierFilter();
    loadSupplierCards();
    loadPurchaseRequests();
    // The other tabs are not yet implemented in the backend, so we don't call their render functions
}

function updateStatistics() {
    const { stats } = reportData;
    document.getElementById('totalSuppliers').textContent = stats.totalSuppliers || 0;
    document.getElementById('totalRequests').textContent = stats.totalRequests || 0;
    document.getElementById('totalItems').textContent = stats.totalItems || 0;
    document.getElementById('totalValue').textContent = formatCurrency(stats.totalValue);
}

function loadSupplierFilter() {
    const select = document.getElementById('supplierFilter');
    // Preserve the "All Suppliers" option
    const currentVal = select.value;
    select.innerHTML = '<option value="">All Suppliers</option>';
    reportData.suppliers.forEach(sup => {
        select.innerHTML += `<option value="${sup.supplier_id}">${sup.supplier_name}</option>`;
    });
    select.value = currentVal;
}

function loadSupplierCards() {
    const grid = document.getElementById('suppliersGrid');
    grid.innerHTML = '';

    const { topSuppliers } = reportData;
    document.getElementById('suppliersCount').textContent = `Showing ${topSuppliers.length} top supplier(s)`;

    topSuppliers.forEach((supplier, index) => {
        const card = document.createElement('div');
        card.className = 'supplier-card';
        card.innerHTML = `
            <div class="supplier-header">
                <div>
                    <div class="supplier-name">${supplier.supplier_name}</div>
                    <div class="supplier-contact">
                        <i class="fas fa-phone"></i> ${supplier.phone || 'N/A'}<br>
                    </div>
                </div>
                <span class="supplier-badge badge-top"><i class="fas fa-star"></i> Top Supplier</span>
            </div>
            <div class="supplier-stats">
                <div class="mini-stat">
                    <div class="mini-stat-value">${supplier.total_requests}</div>
                    <div class="mini-stat-label">Requests</div>
                </div>
                <div class="mini-stat" style="grid-column: 1 / -1;">
                    <div class="mini-stat-value">${formatCurrency(supplier.total_value)}</div>
                    <div class="mini-stat-label">Total Supply Value</div>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

function loadPurchaseRequests() {
    const tbody = document.getElementById('purchaseRequestsBody');
    tbody.innerHTML = '';
    const { purchaseRequests } = reportData;

    if (!purchaseRequests || purchaseRequests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="empty-state"><i class="fas fa-file-alt"></i><h3>No Purchase Requests</h3><p>No purchase requests found for the selected filters</p></div></td></tr>';
        return;
    }

    purchaseRequests.forEach(req => {
        const statusBadge = getStatusBadge(req.status);
        tbody.innerHTML += `
            <tr>
              <td><strong>#PR${String(req.request_id).padStart(4, "0")}</strong></td>
              <td>${req.supplier_name}</td>
              <td>${new Date(req.request_date).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" })}</td>
              <td>${req.items_count} item(s)</td>
              <td class="text-right"><strong>${req.total_quantity}</strong></td>
              <td class="text-right"><strong>${formatCurrency(req.estimated_value)}</strong></td>
              <td>${statusBadge}</td>
              <td>${req.created_by}</td>
            </tr>
        `;
    });
}

function getStatusBadge(status) {
    switch (status) {
        case 'received':
            return '<span class="badge badge-received"><i class="fas fa-check-circle"></i> Received</span>';
        case 'approved':
            return '<span class="badge badge-approved"><i class="fas fa-thumbs-up"></i> Approved</span>';
        case 'pending':
            return '<span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>';
        default:
            return `<span class="badge">${status}</span>`;
    }
}


function switchTab(evt, tabName) {
  const tabs = document.querySelectorAll(".tab-btn");
  const contents = document.querySelectorAll(".tab-content");

  tabs.forEach(t => t.classList.remove("active"));
  contents.forEach(c => c.classList.remove("active"));

  evt.currentTarget.classList.add("active");
  document.getElementById(tabName + "Tab").classList.add("active");
}

function applyFilters() {
    loadReportData();
}

function resetFilters() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('supplierFilter').value = '';
    loadReportData();
}

function formatCurrency(amount) {
    if (typeof amount !== 'number') {
        amount = parseFloat(amount) || 0;
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function exportReport() {
  // This function would need to be re-implemented to use the live data from reportData
  alert('Export functionality is not yet implemented with live data.');
}