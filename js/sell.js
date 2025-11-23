let items = [];
let cart = [];

// Initialize
document.addEventListener("DOMContentLoaded", async () => {
  await fetchItems();
  renderItemGrid();
  setupEventListeners();
});

function setupEventListeners() {
  document.getElementById("searchInput").addEventListener("input", handleSearch);
  document.getElementById("discountInput").addEventListener("input", updateTotals);
  document.getElementById("checkoutBtn").addEventListener("click", handleCheckout);
  document.getElementById("clearCartBtn").addEventListener("click", clearCart);

  // Modal listeners
  document.getElementById("receiptModal").addEventListener("click", (e) => {
    if (e.target.id === "receiptModal" || e.target.id === "closeModal" || e.target.classList.contains('close-modal-btn')) {
      closeReceiptModal();
    }
  });
  document.getElementById("printReceiptBtn").addEventListener("click", () => window.print());
}

async function fetchItems() {
  try {
    const response = await fetch("../api/items.php");
    if (!response.ok) throw new Error("Network response was not ok");
    
    const data = await response.json();
    if (data.success && Array.isArray(data.items)) {
      items = data.items;
    } else {
      throw new Error(data.message || "Failed to parse items");
    }
  } catch (error) {
    showAlert(error.message || "Error fetching items.", "error");
    items = [];
  }
}

function renderItemGrid(filter = "") {
  const grid = document.getElementById("itemsGrid");
  const searchTerm = filter.toLowerCase();
  
  const filteredItems = items.filter(item => 
    item.name.toLowerCase().includes(searchTerm)
  );

  if (filteredItems.length === 0) {
    grid.innerHTML = '<p class="no-items">No items found.</p>';
    return;
  }
  
  grid.innerHTML = filteredItems.map(item => `
    <div class="item-card" onclick="addToCart(${item.item_id})">
      <div class="item-name">${item.name}</div>
      <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
      <div class="item-stock ${item.stock < 5 ? 'stock-low' : ''}">
        Stock: ${item.stock}
      </div>
    </div>
  `).join("");
}

function handleSearch(event) {
  renderItemGrid(event.target.value);
}

function addToCart(itemId) {
  const item = items.find(i => i.item_id === itemId);
  if (!item) return;

  const cartItem = cart.find(ci => ci.item_id === itemId);

  if (cartItem) {
    if (cartItem.qty < item.stock) {
      cartItem.qty++;
    } else {
      showAlert("Not enough stock available.", "warning");
    }
  } else {
    if (item.stock > 0) {
      cart.push({ ...item, qty: 1 });
    } else {
      showAlert("Item is out of stock.", "warning");
    }
  }
  renderCart();
}

function renderCart() {
  const cartContainer = document.getElementById("cartItems");
  const cartCount = document.getElementById("cartCount");

  if (cart.length === 0) {
    cartContainer.innerHTML = `
      <div class="empty-cart">
        <i class="fas fa-shopping-cart"></i>
        <p>Cart is empty</p>
      </div>`;
  } else {
    cartContainer.innerHTML = cart.map((item, index) => `
      <div class="cart-item">
        <div class="cart-item-header">
          <span class="cart-item-name">${item.name}</span>
          <button class="remove-btn" onclick="removeFromCart(${index})">×</button>
        </div>
        <div class="cart-item-controls">
          <div class="qty-control">
            <button class="qty-btn" onclick="updateQty(${index}, -1)">−</button>
            <input type="number" class="qty-input" value="${item.qty}" 
                   onchange="setQty(${index}, this.value)" 
                   min="1" max="${item.stock}">
            <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
          </div>
          <span class="item-total">$${(item.price * item.qty).toFixed(2)}</span>
        </div>
      </div>
    `).join("");
  }
  
  cartCount.textContent = cart.reduce((sum, item) => sum + item.qty, 0);
  updateTotals();
}

function updateQty(index, change) {
  const item = cart[index];
  const newQty = item.qty + change;

  if (newQty < 1) {
    removeFromCart(index);
  } else if (newQty > item.stock) {
    showAlert("Exceeds available stock.", "warning");
  } else {
    item.qty = newQty;
    renderCart();
  }
}

function setQty(index, value) {
  const item = cart[index];
  let newQty = parseInt(value, 10);

  if (isNaN(newQty) || newQty < 1) {
    newQty = 1;
  }

  if (newQty > item.stock) {
    showAlert("Exceeds available stock.", "warning");
    newQty = item.stock;
  }
  
  item.qty = newQty;
  renderCart();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  renderCart();
}

function clearCart() {
    Swal.fire({
      title: 'Are you sure?',
      text: "This will clear all items from the cart.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, clear it!'
    }).then((result) => {
      if (result.isConfirmed) {
        cart = [];
        document.getElementById("discountInput").value = "";
        renderCart();
        showAlert("Cart has been cleared.", "success");
      }
    });
}

function updateTotals() {
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const discount = parseFloat(document.getElementById("discountInput").value) || 0;
  const grandTotal = Math.max(0, subtotal - discount);

  document.getElementById("subtotal").textContent = `$${subtotal.toFixed(2)}`;
  document.getElementById("grandTotal").textContent = `$${grandTotal.toFixed(2)}`;
  document.getElementById("checkoutBtn").disabled = cart.length === 0;
}

async function handleCheckout() {
  if (cart.length === 0) return;

  const checkoutBtn = document.getElementById("checkoutBtn");
  const originalBtnText = checkoutBtn.innerHTML;
  
  checkoutBtn.disabled = true;
  checkoutBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing...`;

  try {
    const discount = parseFloat(document.getElementById("discountInput").value) || 0;
    const res = await fetch("../api/checkout.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        discount,
        items: cart.map(item => ({ item_id: item.item_id, qty: item.qty })),
      }),
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || "Checkout failed");
    }
    
    showReceiptModal(data.receipt);

  } catch (err) {
    showAlert(err.message || "An error occurred during checkout.", "error");
  } finally {
    checkoutBtn.disabled = cart.length === 0;
    checkoutBtn.innerHTML = originalBtnText;
  }
}

function showReceiptModal(receipt) {
  const receiptDetails = document.getElementById("receiptDetails");
  receiptDetails.innerHTML = `
    <div class="receipt-header">
      <h3>LOGO</h3>
      <div class="receipt-info">
        <p>Email: fabrdaa@gmail.com</p>
        <p>Phone: 0784444314</p>
        <p>Momo Pay: 687103</p>
        <p>Bank: 123456789</p>
        <p><strong>Receipt:</strong> ${receipt.code}</p>
        <p><strong>Date:</strong> ${new Date(receipt.date).toLocaleString()}</p>
      </div>
    </div>
    <table class="receipt-table">
      <thead>
        <tr>
          <th>Item</th>
          <th class="text-right">Qty</th>
          <th class="text-right">Price</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        ${receipt.items.map(item => `
          <tr>
            <td>${item.name}</td>
            <td class="text-right">${item.qty}</td>
            <td class="text-right">$${parseFloat(item.price).toFixed(2)}</td>
            <td class="text-right">$${parseFloat(item.total).toFixed(2)}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
    <div class="receipt-totals">
      <div class="total-row">
        <span>Subtotal</span>
        <span>$${parseFloat(receipt.subtotal).toFixed(2)}</span>
      </div>
      <div class="total-row">
        <span>Discount</span>
        <span>$${parseFloat(receipt.discount).toFixed(2)}</span>
      </div>
      <div class="total-row grand-total">
        <span>Grand Total</span>
        <span>$${parseFloat(receipt.grand_total).toFixed(2)}</span>
      </div>
    </div>
    <div class="receipt-footer">
      <p>Served by: ${receipt.user}</p>
      <p>Thank you for your business!</p>
    </div>
  `;
  document.getElementById("receiptModal").style.display = "flex";
}

function closeReceiptModal() {
  document.getElementById("receiptModal").style.display = "none";
  
  // Reset state after sale
  cart = [];
  document.getElementById("discountInput").value = "";
  renderCart();
  
  // Refresh stock levels
  fetchItems().then(() => renderItemGrid()); 
  
  showAlert("Sale completed successfully!", "success");
}

function showAlert(message, type) {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: type,
    title: message,
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
  });
}
