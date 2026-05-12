let cartItems = [];

// Load cart when cart page opens
document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById("cartItems")) {
        loadCart();
    }
});

/* ================= LOAD CART ================= */
function loadCart() {
    cartItems = JSON.parse(localStorage.getItem("cart")) || [];
    renderCart();
    updateCartSummary();
}

/* ================= RENDER CART ================= */
function renderCart() {
    const container = document.getElementById("cartItems");

    if (!container) return;

    if (!cartItems.length) {
        container.innerHTML = `
            <div class="empty-cart">
                <h3>Your cart is empty</h3>
                <a href="products.html" class="btn btn-primary">Shop Now</a>
            </div>
        `;
        return;
    }

    container.innerHTML = cartItems
        .map(
            (item) => `
        <div class="cart-item">
            <img src="assets/images/products/${item.image || "default.png"}"
                 class="cart-item-image"
                 onerror="this.src='assets/images/products/default.png'">

            <div class="cart-item-info">
                <h3>${item.name}</h3>
                <p>₹${item.price}</p>

                <div class="quantity-selector">
                    <button onclick="changeQty(${item.id}, -1)">−</button>
                    <span>${item.qty}</span>
                    <button onclick="changeQty(${item.id}, 1)">+</button>
                </div>

                <button class="remove-btn" onclick="removeItem(${item.id})">
                    Remove
                </button>
            </div>

            <div class="cart-item-total">
                ₹${(item.price * item.qty).toFixed(2)}
            </div>
        </div>
    `
        )
        .join("");
}

/* ================= CHANGE QUANTITY ================= */
function changeQty(id, change) {
    const item = cartItems.find((p) => p.id === id);
    if (!item) return;

    item.qty += change;
    if (item.qty <= 0) {
        cartItems = cartItems.filter((p) => p.id !== id);
    }

    saveCart();
}

/* ================= REMOVE ITEM ================= */
function removeItem(id) {
    cartItems = cartItems.filter((p) => p.id !== id);
    saveCart();
}

/* ================= SAVE CART ================= */
function saveCart() {
    localStorage.setItem("cart", JSON.stringify(cartItems));
    renderCart();
    updateCartSummary();
    if (typeof updateCartBadge === "function") {
        updateCartBadge();
    }
}

/* ================= CART SUMMARY ================= */
function updateCartSummary() {
    const summary = document.getElementById("cartSummary");
    if (!summary) return;

    const subtotal = cartItems.reduce(
        (sum, item) => sum + item.price * item.qty,
        0
    );
    const FREE_SHIPPING_THRESHOLD = 50;
    const SHIPPING_FEE = 50;
    const shipping =
        subtotal >= FREE_SHIPPING_THRESHOLD || subtotal === 0
            ? 0
            : SHIPPING_FEE;
    const tax = subtotal * 0.05;
    const total = subtotal + shipping + tax;

    summary.innerHTML = `
        <h3>Order Summary</h3>
        <p>Subtotal: ₹${subtotal.toFixed(2)}</p>
        <p>Shipping: ${shipping === 0 ? "FREE" : "₹" + shipping}</p>
        <p>Tax: ₹${tax.toFixed(2)}</p>
        <h4>Total: ₹${total.toFixed(2)}</h4>

        <button class="btn btn-primary" onclick="checkout()">
            Proceed to Checkout
        </button>
    `;
}

/* ================= CHECKOUT ================= */
function checkout() {
    alert("Checkout flow can be implemented next");
}

