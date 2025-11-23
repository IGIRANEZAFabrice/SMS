// Global variables
let receipts = [];

// DOM elements
const receiptsTableBody = document.getElementById("receiptsTableBody");
const searchInput = document.getElementById("searchReceipts");
const receiptModal = document.getElementById("receiptModal");

// Modal detail elements
const modalReceiptCode = document.getElementById("modalReceiptCode");
const detailReceiptCode = document.getElementById("detailReceiptCode");
const detailDate = document.getElementById("detailDate");
const detailCreatedBy = document.getElementById("detailCreatedBy");
const detailItemCount = document.getElementById("detailItemCount");
const detailItemsBody = document.getElementById("detailItemsBody");
const detailSubtotal = document.getElementById("detailSubtotal");
const detailDiscount = document.getElementById("detailDiscount");
const detailGrandTotal = document.getElementById("detailGrandTotal");

const closeModalBtn = document.querySelector(".close-btn");

// Event listeners
document.addEventListener("DOMContentLoaded", () => {
  loadReceipts();

  // Search functionality
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      const searchTerm = e.target.value.toLowerCase();
      filterReceipts(searchTerm);
    });
  }

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target === receiptModal) {
      closeModal();
    }
  });

  // Close modal button
  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeModal);
  }
});

// Load receipts from API
async function loadReceipts() {
  try {
    const response = await fetch("receipt.php?api=receipts");
    const data = await response.json();

    if (data.success) {
      receipts = data.data;
      renderReceiptsTable(receipts);
      updateStats(receipts);
    } else {
      showAlert("Error loading receipts", "error");
    }
  } catch (error) {
    console.error("Error:", error);
    showAlert("Failed to load receipts", "error");
  }
}

// Render receipts table
function renderReceiptsTable(receiptsToRender) {
  receiptsTableBody.innerHTML = "";

  if (receiptsToRender.length === 0) {
    receiptsTableBody.innerHTML = `
            <tr>
              <td colspan="8" class="text-center">No receipts found</td>
            </tr>
          `;
    return;
  }

  receiptsToRender.forEach((receipt) => {
    const row = document.createElement("tr");

    const formattedDate = new Date(receipt.created_at).toLocaleString();
    
    row.innerHTML = `
            <td>${receipt.receipt_code}</td>
            <td>${formattedDate}</td>
            <td class="text-center">...</td> <!-- Item count would require another query -->
            <td class="text-right">${formatCurrency(receipt.total_amount)}</td>
            <td class="text-right">${formatCurrency(receipt.discount)}</td>
            <td class="text-right">${formatCurrency(receipt.grand_total)}</td>
            <td>${receipt.cashier_name}</td>
            <td class="text-center">
              <button class="btn btn-sm btn-view" onclick="viewReceipt('${receipt.receipt_code}')">
                <i class="fas fa-eye"></i> View
              </button>
            </td>
          `;

    receiptsTableBody.appendChild(row);
  });
}

// Update stats cards
function updateStats(receipts) {
    const totalReceipts = receipts.length;
    const totalSales = receipts.reduce((sum, r) => sum + r.grand_total, 0);
    const avgSale = totalReceipts > 0 ? totalSales / totalReceipts : 0;
    
    const today = new Date().toISOString().slice(0, 10);
    const todaySales = receipts
        .filter(r => r.created_at.startsWith(today))
        .reduce((sum, r) => sum + r.grand_total, 0);

    document.getElementById('totalReceipts').textContent = totalReceipts;
    document.getElementById('totalSales').textContent = formatCurrency(totalSales);
    document.getElementById('avgSale').textContent = formatCurrency(avgSale);
    document.getElementById('todaySales').textContent = formatCurrency(todaySales);
}


// Filter receipts based on search term
function filterReceipts(searchTerm) {
  if (!searchTerm) {
    renderReceiptsTable(receipts);
    return;
  }

  const filtered = receipts.filter(
    (receipt) =>
      receipt.receipt_code.toLowerCase().includes(searchTerm) ||
      receipt.cashier_name.toLowerCase().includes(searchTerm) ||
      receipt.created_at.toLowerCase().includes(searchTerm)
  );

  renderReceiptsTable(filtered);
}

// View receipt details
async function viewReceipt(receiptCode) {
  try {
    const response = await fetch(
      `receipt.php?api=receipt_items&receipt_code=${receiptCode}`
    );
    const data = await response.json();

    if (data.success) {
      showReceiptModal(data.receipt, data.items);
    } else {
      showAlert("Error loading receipt details", "error");
    }
  } catch (error) {
    console.error("Error:", error);
    showAlert("Failed to load receipt details", "error");
  }
}

// Show receipt modal with details
function showReceiptModal(receipt, items) {
  modalReceiptCode.textContent = `Receipt Details`;
  detailReceiptCode.textContent = receipt.receipt_code;
  detailDate.textContent = formatDateTime(receipt.date);
  detailCreatedBy.textContent = receipt.cashier;
  detailItemCount.textContent = items.length;

  // Clear previous items
  detailItemsBody.innerHTML = "";

  // Add items to the modal
  items.forEach((item, index) => {
    const row = document.createElement("tr");
    row.innerHTML = `
            <td>${index + 1}</td>
            <td><strong>${item.item_name}</strong></td>
             <td>...</td> <!-- Unit not available in data -->
            <td class="text-right">${item.qty}</td>
            <td class="text-right">${formatCurrency(item.price)}</td>
            <td class="text-right"><strong>${formatCurrency(
              item.total
            )}</strong></td>
          `;
    detailItemsBody.appendChild(row);
  });

  // Update totals
  detailSubtotal.textContent = formatCurrency(receipt.total_amount);
  detailDiscount.textContent = formatCurrency(receipt.discount);
  detailGrandTotal.textContent = formatCurrency(receipt.grand_total);

  // Show the modal
  receiptModal.style.display = "block";
  document.body.style.overflow = "hidden";
}

// Close the modal
function closeModal() {
  if (receiptModal) {
    receiptModal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Print the receipt
function printReceipt() {
  const modalContent = document.querySelector("#receiptModal .modal-content");
  if (!modalContent) return;

  const printContents = modalContent.cloneNode(true);
  
  // Remove buttons from clone
  const footer = printContents.querySelector('.modal-footer');
  if(footer) footer.remove();

  const printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>Print Receipt</title>');
  printWindow.document.write('<link rel="stylesheet" href="../css/receipt.css">'); // Add your css file
  printWindow.document.write('<style> body { background: #fff; } .modal-content { border: none; box-shadow: none; } </style>');
  printWindow.document.write('</head><body>');
  printWindow.document.write(printContents.innerHTML);
  printWindow.document.write('</body></html>');
  
  printWindow.document.close();
  printWindow.focus(); 
  
  setTimeout(() => {
      printWindow.print();
      printWindow.close();
  }, 250);
}


// Format currency
function formatCurrency(amount) {
  if (typeof amount !== 'number') {
    amount = 0;
  }
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
}

// Format date and time
function formatDateTime(dateTimeString) {
  const options = {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  };
  return new Date(dateTimeString).toLocaleString("en-US", options);
}

// Show alert message
function showAlert(message, type = "success") {
  // You can implement a toast notification here
  alert(`${type.toUpperCase()}: ${message}`);
}
