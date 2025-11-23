let items = [];

let cart = [];

// Initialize
document.addEventListener("DOMContentLoaded", async function () {
  await fetchItems();
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

async function fetchItems() {
  try {
    const res = await fetch("../api/items.php");
    const data = await res.json();
    if (res.ok && data.success) {
      items = Array.isArray(data.items) ? data.items : [];
    } else {
      throw new Error(data.message || "Failed to load items");
    }
  } catch (err) {
    showAlert(err.message || "Error loading items", "error");
    items = [];
  }
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

async function checkout() {
  if (cart.length === 0) return;

  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const discount =
    parseFloat(document.getElementById("discountInput").value) || 0;
  const grandTotal = Math.max(0, subtotal - discount);
  try {
    const res = await fetch("../api/checkout.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        discount,
        items: cart.map((item) => ({ item_id: item.item_id, qty: item.qty }))
      })
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || "Checkout failed");
    }

    showAlert(`Sale completed! Receipt: ${data.receipt_code}`, "success");
    cart = [];
    updateCart();
    await fetchItems();
    loadItems();
    document.getElementById("discountInput").value = "";
  } catch (err) {
    showAlert(err.message || "Checkout error", "error");
  }
}

function showAlert(message, type) {
  const alert = document.getElementById("alert");
  alert.textContent = message;
  alert.className = `alert alert-${type} show`;

  setTimeout(() => {
    alert.classList.remove("show");
  }, 3000);
}
