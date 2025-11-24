let purchaseRequests = [];
let currentFilter = "all";

// API Base URL
const API_URL = "api/requests.php";

// Initialize
document.addEventListener("DOMContentLoaded", initRequestsPage);

async function initRequestsPage() {
  try {
    const response = await fetch(`${API_URL}?action=getRequests`);
    const result = await response.json();

    if (result.success) {
      purchaseRequests = result.purchaseRequests;
      loadRequests();
      setupFilters();
      updateStats();
    } else {
      console.error("Failed to fetch purchase requests:", result.message);
      // Optionally display an error message to the user
      showAlert("Failed to load requests. Please try again.", "danger");
    }
  } catch (error) {
    console.error("Error initializing requests page:", error);
    showAlert("Network error. Could not load requests.", "danger");
  }
}

function setupFilters() {
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      document
        .querySelectorAll(".filter-btn")
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      currentFilter = this.dataset.filter;
      loadRequests();
    });
  });
}

function loadRequests() {
  const tbody = document.getElementById("requestsTableBody");
  if (!tbody) return;

  const filtered =
    currentFilter === "all"
      ? purchaseRequests
      : purchaseRequests.filter((r) => r.status === currentFilter);

  if (filtered.length === 0) {
    tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No ${
                                  currentFilter === "all" ? "" : currentFilter
                                } requests found</p>
                            </div>
                        </td>
                    </tr>
                `;
    return;
  }

  tbody.innerHTML = filtered
    .map(
      (req) => `
                <tr>
                    <td><strong>#${req.request_id}</strong></td>
                    <td><i class="fas fa-truck"></i> ${req.supplier_name}</td>
                    <td>${formatDate(req.request_date)}</td>
                    <td><i class="fas fa-user-circle"></i> ${
                      req.created_by_name
                    }</td>
                    <td><span style="color: var(--blue); font-weight: 600;">${
                      req.items.length
                    } item(s)</span></td>
                    <td>${getStatusBadge(req.status)}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-icon btn-view" onclick="viewDetails(${
                              req.request_id
                            })">
                                <i class="fas fa-eye"></i> View
                            </button>
                            ${
                              req.status === "pending"
                                ? `
                                <button class="btn-icon btn-approve" onclick="showConfirmModal('approve', ${req.request_id})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            `
                                : ""
                            }
                            ${
                              req.status === "approved"
                                ? `
                                <button class="btn-icon btn-approve" onclick="showConfirmModal('received', ${req.request_id})">
                                    <i class="fas fa-box-open"></i> Received
                                </button>
                            `
                                : ""
                            }
                        </div>
                    </td>
                </tr>
            `
    )
    .join("");
}

function getStatusBadge(status) {
  const icons = {
    pending: "fa-clock",
    approved: "fa-check-circle",
    received: "fa-box-open",
  };
  return `<span class="status-badge ${status}">
                <i class="fas ${icons[status]}"></i>
                ${status.charAt(0).toUpperCase() + status.slice(1)}
            </span>`;
}

function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function viewDetails(requestId) {
  const request = purchaseRequests.find((r) => r.request_id === requestId);
  if (!request) return;

  const modal = document.getElementById("detailsModal");
  const modalBody = document.getElementById("modalBody");
  const modalFooter = document.getElementById("modalFooter");

  modalBody.innerHTML = `
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-hashtag"></i> Request ID:</span>
                    <span class="info-value">#${request.request_id}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-truck"></i> Supplier:</span>
                    <span class="info-value">${request.supplier_name}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar"></i> Request Date:</span>
                    <span class="info-value">${formatDate(
                      request.request_date
                    )}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user"></i> Created By:</span>
                    <span class="info-value">${request.created_by_name}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-info-circle"></i> Status:</span>
                    <span class="info-value">${getStatusBadge(
                      request.status
                    )}</span>
                </div>

                <div class="items-table">
                    <h4><i class="fas fa-boxes"></i> Requested Items</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${request.items
                              .map(
                                (item) => `
                                <tr>
                                    <td><i class="fas fa-box"></i> ${item.item_name}</td>
                                    <td><strong>${item.qty_requested}</strong></td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
            `;

  modalFooter.innerHTML = `
                ${
                  request.status === "pending"
                    ? `
                    <button class="btn btn-success" onclick="showConfirmModal('approve', ${request.request_id})">
                        <i class="fas fa-check"></i> Approve Request
                    </button>
                `
                    : ""
                }
                ${
                  request.status === "approved"
                    ? `
                    <button class="btn btn-success" onclick="showConfirmModal('received', ${request.request_id})">
                        <i class="fas fa-box-open"></i> Mark as Received
                    </button>
                `
                    : ""
                }
                <button class="btn btn-secondary" onclick="closeModal('detailsModal')">
                    <i class="fas fa-times"></i> Close
                </button>
            `;

  modal.classList.add("show");
}

function closeModal() {
  document.getElementById("detailsModal").classList.remove("show");
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("show");
}

function showConfirmModal(action, requestId) {
  const modal = document.getElementById("confirmModal");
  const icon = document.getElementById("confirmIcon");
  const title = document.getElementById("confirmTitle");
  const message = document.getElementById("confirmMessage");
  const confirmBtn = document.getElementById("confirmBtn");

  if (action === "approve") {
    icon.innerHTML = '<i class="fas fa-check-circle"></i>';
    icon.className = "confirm-icon success";
    title.textContent = "Approve Purchase Request?";
    message.textContent =
      "Are you sure you want to approve this purchase request? This action will move it to approved status.";
    confirmBtn.innerHTML = '<i class="fas fa-check"></i> Yes, Approve';
    confirmBtn.onclick = function () {
      approveRequest(requestId);
      closeModal("confirmModal");
      closeModal("detailsModal");
    };
  } else if (action === "received") {
    icon.innerHTML = '<i class="fas fa-box-open"></i>';
    icon.className = "confirm-icon success";
    title.textContent = "Mark as Received?";
    message.textContent =
      "Are you sure you want to mark this request as received? This will update the stock quantities.";
    confirmBtn.innerHTML = '<i class="fas fa-check"></i> Yes, Mark Received';
    confirmBtn.onclick = function () {
      markReceived(requestId);
      closeModal("confirmModal");
      closeModal("detailsModal");
    };
  }

  modal.classList.add("show");
}

async function approveRequest(requestId) {
  try {
    const response = await fetch(`${API_URL}?action=approveRequest`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ request_id: requestId }),
    });
    const result = await response.json();

    if (result.success) {
      showAlert("Request #" + requestId + " approved successfully!", "success");
      // Update the local purchaseRequests array and re-render
      const index = purchaseRequests.findIndex(r => r.request_id === requestId);
      if (index !== -1) {
          purchaseRequests[index].status = 'approved';
      }
      loadRequests();
      updateStats();
    } else {
      showAlert("Failed to approve request: " + result.message, "danger");
    }
  } catch (error) {
    console.error("Error approving request:", error);
    showAlert("Network error. Could not approve request.", "danger");
  }
}

async function markReceived(requestId) {
  try {
    const response = await fetch(`${API_URL}?action=markReceived`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ request_id: requestId }),
    });
    const result = await response.json();

    if (result.success) {
      showAlert("Request #" + requestId + " marked as received!", "success");
       // Update the local purchaseRequests array and re-render
       const index = purchaseRequests.findIndex(r => r.request_id === requestId);
       if (index !== -1) {
           purchaseRequests[index].status = 'received';
       }
      loadRequests();
      updateStats();
    } else {
      showAlert("Failed to mark request as received: " + result.message, "danger");
    }
  } catch (error) {
    console.error("Error marking request as received:", error);
    showAlert("Network error. Could not mark request as received.", "danger");
  }
}

function updateStats() {
  const pending = purchaseRequests.filter((r) => r.status === "pending").length;
  const approved = purchaseRequests.filter(
    (r) => r.status === "approved"
  ).length;
  const received = purchaseRequests.filter(
    (r) => r.status === "received"
  ).length;

  document.getElementById("pendingCount").textContent = pending;
  document.getElementById("approvedCount").textContent = approved;
  document.getElementById("receivedCount").textContent = received;
}

function showAlert(message, type) {
  const alert = document.getElementById("alert");
  if (!alert) return;
  alert.textContent = message;
  alert.className = `alert alert-${type} show`;

  setTimeout(() => {
    alert.classList.remove("show");
  }, 3000);
}

// Close modals when clicking outside
document.getElementById("detailsModal")?.addEventListener("click", function (e) {
  if (e.target === this) {
    closeModal("detailsModal");
  }
});

document.getElementById("confirmModal")?.addEventListener("click", function (e) {
  if (e.target === this) {
    closeModal("confirmModal");
  }
});