// ================= GLOBAL =================
const API_BASE = "backend/api";
let currentUser = JSON.parse(sessionStorage.getItem("user")) || null;

// ================= INIT =================
document.addEventListener("DOMContentLoaded", () => {
    updateAuthUI();
    updateCartBadge();
    initEventListeners();
});

// ================= AUTH UI =================
function updateAuthUI() {
    const loginLink = document.getElementById("loginLink");
    const userMenu = document.getElementById("userMenu");
    const userName = document.getElementById("userName");

    if (!loginLink || !userMenu) return;

    if (currentUser) {
        loginLink.style.display = "none";
        userMenu.style.display = "flex";
        if (userName) userName.textContent = currentUser.name || "User";
    } else {
        loginLink.style.display = "block";
        userMenu.style.display = "none";
    }
}

// ================= CART BADGE =================
function updateCartBadge() {
    const badge = document.getElementById("cartBadge");
    if (!badge) return;

    const cart = JSON.parse(localStorage.getItem("cart")) || [];
    const count = cart.reduce((sum, item) => sum + item.qty, 0);

    badge.textContent = count;
    badge.style.display = count > 0 ? "inline-block" : "none";
}

// ================= EVENT LISTENERS =================
function initEventListeners() {
    // Search
    const searchForm = document.getElementById("searchForm");
    if (searchForm) {
        searchForm.addEventListener("submit", handleSearch);
    }

    // Add to cart & wishlist (event delegation)
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("add-to-cart")) {
            handleAddToCart(e);
        }

        if (e.target.classList.contains("add-to-wishlist")) {
            handleAddToWishlist(e);
        }

        if (e.target.id === "logoutBtn") {
            handleLogout();
        }
    });
}

// ================= SEARCH =================
function handleSearch(e) {
    e.preventDefault();
    const query = document.getElementById("searchInput").value.trim();
    if (query) {
        window.location.href = `products.html?search=${encodeURIComponent(query)}`;
    }
}

// ================= ADD TO CART =================
function handleAddToCart(e) {
    e.preventDefault();

    const btn = e.target;
    const id = parseInt(btn.dataset.productId);
    const name = btn.dataset.productName;
    const price = parseFloat(btn.dataset.productPrice);
    const image = btn.dataset.productImage;

    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, image, qty: 1 });
    }

    localStorage.setItem("cart", JSON.stringify(cart));
    updateCartBadge();
    showNotification("Added to cart", "success");
}

// ================= WISHLIST (FRONTEND ONLY) =================
function handleAddToWishlist(e) {
    e.preventDefault();

    if (!currentUser) {
        showNotification("Please login first", "warning");
        setTimeout(() => window.location.href = "login.html", 1200);
        return;
    }

    showNotification("Added to wishlist (demo)", "success");
}

// ================= LOGOUT =================
function handleLogout() {
    sessionStorage.removeItem("user");
    currentUser = null;
    updateAuthUI();
    updateCartBadge();
    showNotification("Logged out", "success");
    setTimeout(() => window.location.href = "index.html", 1000);
}

// ================= NOTIFICATION =================
function showNotification(message, type = "info") {
    const div = document.createElement("div");
    div.className = `alert alert-${type}`;
    div.style.position = "fixed";
    div.style.top = "20px";
    div.style.right = "20px";
    div.style.zIndex = "9999";
    div.textContent = message;

    document.body.appendChild(div);

    setTimeout(() => {
        div.remove();
    }, 2500);
}

// ================= CURRENCY =================
function formatCurrency(amount) {
    return "₹" + amount.toFixed(2);
}
