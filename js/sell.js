// Mock data - Replace with PHP/MySQL fetch later
let items = [
  { item_id: 1, name: "Laptop Dell XPS", price: 1200.0, stock: 15 },
  { item_id: 2, name: "iPhone 14 Pro", price: 999.0, stock: 8 },
  { item_id: 3, name: "Samsung Galaxy S23", price: 899.0, stock: 12 },
  { item_id: 4, name: "iPad Air", price: 599.0, stock: 20 },
  { item_id: 5, name: "AirPods Pro", price: 249.0, stock: 3 },
  { item_id: 6, name: "Magic Mouse", price: 79.0, stock: 25 },
  { item_id: 7, name: "Mechanical Keyboard", price: 159.0, stock: 10 },
  { item_id: 8, name: "USB-C Hub", price: 49.0, stock: 30 },
  { item_id: 9, name: "Webcam HD", price: 89.0, stock: 18 },
  { item_id: 10, name: "Wireless Charger", price: 39.0, stock: 40 },
];

let cart = [];

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  loadItems();
  setupEventListeners();
});

function setupEventListeners() {
  document.getElementById("searchInput").addEventListener("input", filterItems);
  document
    .getElementById("discountInput")
    .addEventListener("input", updateTotals);
  document.getElementById("checkoutBtn").addEventListener("click", checkout);
  document.getElementById("clearCartBtn").addEventListener("click", clearCart);
}

function loadItems(filter = "") {
  const grid = document.getElementById("itemsGrid");
  const filteredItems = items.filter((item) =>
    item.name.toLowerCase().includes(filter.toLowerCase())
  );

  grid.innerHTML = filteredItems
    .map(
      (item) => `
                <div class="item-card" onclick="addToCart(${item.item_id})">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">$${item.price.toFixed(2)}</div>
                    <div class="item-stock ${
                      item.stock < 5 ? "stock-low" : ""
                    }">
                        Stock: ${item.stock}
                    </div>
                </div>
            `
    )
    .join("");
}

function filterItems() {
  const searchTerm = document.getElementById("searchInput").value;
  loadItems(searchTerm);
}

function addToCart(itemId) {
  const item = items.find((i) => i.item_id === itemId);
  if (!item) return;

  const existingItem = cart.find((c) => c.item_id === itemId);

  if (existingItem) {
    if (existingItem.qty < item.stock) {
      existingItem.qty++;
    } else {
      showAlert("Not enough stock available", "error");
      return;
    }
  } else {
    if (item.stock > 0) {
      cart.push({
        item_id: item.item_id,
        name: item.name,
        price: item.price,
        qty: 1,
        maxStock: item.stock,
      });
    } else {
      showAlert("Item out of stock", "error");
      return;
    }
  }

  updateCart();
  showAlert(`${item.name} added to cart`, "success");
}

function updateCart() {
  const cartContainer = document.getElementById("cartItems");
  const cartCount = document.getElementById("cartCount");

  if (cart.length === 0) {
    cartContainer.innerHTML = `
                    <div class="empty-cart">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                        </svg>
                        <p>Cart is empty</p>
                    </div>
                `;
  } else {
    cartContainer.innerHTML = cart
      .map(
        (item, index) => `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <span class="cart-item-name">${item.name}</span>
                            <button class="remove-btn" onclick="removeFromCart(${index})">✕</button>
                        </div>
                        <div class="cart-item-controls">
                            <div class="qty-control">
                                <button class="qty-btn" onclick="updateQty(${index}, -1)">−</button>
                                <input type="number" class="qty-input" value="${
                                  item.qty
                                }" 
                                       onchange="setQty(${index}, this.value)" min="1" max="${
          item.maxStock
        }">
                                <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                            </div>
                            <span class="item-total">$${(
                              item.price * item.qty
                            ).toFixed(2)}</span>
                        </div>
                    </div>
                `
      )
      .join("");
  }

  cartCount.textContent = cart.reduce((sum, item) => sum + item.qty, 0);
  updateTotals();
}

function updateQty(index, change) {
  const item = cart[index];
  const newQty = item.qty + change;

  if (newQty < 1) {
    removeFromCart(index);
    return;
  }

  if (newQty > item.maxStock) {
    showAlert("Exceeds available stock", "error");
    return;
  }

  item.qty = newQty;
  updateCart();
}

function setQty(index, value) {
  const qty = parseInt(value) || 1;
  const item = cart[index];

  if (qty < 1) {
    removeFromCart(index);
    return;
  }

  if (qty > item.maxStock) {
    showAlert("Exceeds available stock", "error");
    item.qty = item.maxStock;
  } else {
    item.qty = qty;
  }

  updateCart();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  updateCart();
  showAlert("Item removed from cart", "success");
}

function clearCart() {
  if (cart.length === 0) return;

  if (confirm("Clear all items from cart?")) {
    cart = [];
    updateCart();
    document.getElementById("discountInput").value = "";
    showAlert("Cart cleared", "success");
  }
}

function updateTotals() {
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const discount =
    parseFloat(document.getElementById("discountInput").value) || 0;
  const grandTotal = Math.max(0, subtotal - discount);

  document.getElementById("subtotal").textContent = `$${subtotal.toFixed(2)}`;
  document.getElementById("grandTotal").textContent = `$${grandTotal.toFixed(
    2
  )}`;

  document.getElementById("checkoutBtn").disabled = cart.length === 0;
}

function checkout() {
  if (cart.length === 0) return;

  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const discount =
    parseFloat(document.getElementById("discountInput").value) || 0;
  const grandTotal = Math.max(0, subtotal - discount);

  // Generate receipt code
  const receiptCode = "RCP-" + Date.now();

  // Prepare data for PHP/MySQL (this is mock - you'll send via AJAX to PHP)
  const receiptData = {
    receipt_code: receiptCode,
    total_amount: subtotal,
    discount: discount,
    grand_total: grandTotal,
    created_by: 1, // Replace with actual user ID from session
    items: cart.map((item) => ({
      item_id: item.item_id,
      qty: item.qty,
      price: item.price,
      total: item.price * item.qty,
    })),
  };

  console.log("Receipt Data to be sent to PHP:", receiptData);

  /* 
            TODO: Send to PHP via fetch/AJAX
            fetch('process_sale.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(receiptData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showAlert('Sale completed successfully! Receipt: ' + receiptCode, 'success');
                    // Update local stock (in PHP this will be done server-side)
                    cart.forEach(cartItem => {
                        const stockItem = items.find(i => i.item_id === cartItem.item_id);
                        if(stockItem) stockItem.stock -= cartItem.qty;
                    });
                    cart = [];
                    updateCart();
                    loadItems();
                    document.getElementById('discountInput').value = '';
                }
            });
            */

  // Mock success (remove this when implementing PHP)
  setTimeout(() => {
    showAlert(`Sale completed! Receipt: ${receiptCode}`, "success");

    // Update mock stock
    cart.forEach((cartItem) => {
      const stockItem = items.find((i) => i.item_id === cartItem.item_id);
      if (stockItem) stockItem.stock -= cartItem.qty;
    });

    cart = [];
    updateCart();
    loadItems();
    document.getElementById("discountInput").value = "";
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
