# 🛒 FreshMart - Grocery E-Commerce Website

A complete full-stack grocery e-commerce platform where users can browse products, add to cart, checkout securely, and track orders. Includes an admin dashboard for managing products, orders, and customers.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

---

## 📌 Project Overview

**FreshMart** is a fully functional online grocery store that allows customers to:

- 👤 Register and login securely
- 🛍️ Browse 5000+ products by category
- 🔍 Search and filter products
- 🛒 Add/remove items from shopping cart
- 💳 Checkout with multiple payment options
- 📦 Track order status
- ⭐ Save items to wishlist
- 📱 Shop from any device (responsive design)

**Admin Panel Features:**

- 📊 Dashboard with sales analytics
- ➕ Add/Edit/Delete products
- 📦 Manage customer orders
- 👥 View and manage customers
- 📈 Generate sales reports

---

## 🚀 Features

### 👤 User Features
| Feature | Description |
|---------|-------------|
| User Authentication | Secure registration & login with password hashing |
| Product Catalog | Browse products with images, prices, and descriptions |
| Search & Filters | Search by keyword, category, price range |
| Shopping Cart | Add, remove, update quantities in real-time |
| Wishlist | Save favorite products for later |
| Checkout | Secure checkout with shipping & payment details |
| Order Tracking | View order history and current status |
| Profile Management | Update personal information and addresses |

### 👨‍💼 Admin Features
| Feature | Description |
|---------|-------------|
| Dashboard | View total sales, orders, products, customers |
| Product Management | Add, edit, delete products with images |
| Order Management | Update order status (pending → shipped → delivered) |
| Customer Management | View all registered customers |
| Inventory Control | Track stock levels and low stock alerts |
| Sales Reports | Generate and export sales data |

### 🎨 Additional Features
| Feature | Description |
|---------|-------------|
| Responsive Design | Works on desktop, tablet, and mobile |
| Secure Payments | Multiple payment options (COD, Card, Digital Wallet) |
| Email Notifications | Order confirmation and status updates |
| Coupon System | Apply discount coupons at checkout |
| Product Reviews | Rate and review purchased products |

---

## 🛠️ Technologies Used

### Frontend
| Technology | Purpose |
|------------|---------|
| HTML5 | Structure of web pages |
| CSS3 | Styling and animations |
| JavaScript | Client-side interactivity |
| AJAX | Real-time cart updates without page refresh |
| Font Awesome | Icons and UI elements |

### Backend
| Technology | Purpose |
|------------|---------|
| PHP 8+ | Server-side logic and API endpoints |
| MySQL 8+ | Database for storing all data |
| Session Management | User login persistence |
| PDO | Secure database connections |

### Tools
| Tool | Purpose |
|------|---------|
| XAMPP/Laragon | Local development server |
| phpMyAdmin | Database management |
| VS Code | Code editor |
| Git | Version control |

📂 Project Structure
```bash
grocery-store/
│
├── frontend/
│   ├── index.html                 # Homepage
│   ├── products.html              # Product listing page
│   ├── product-detail.html        # Single product page
│   ├── cart.html                  # Shopping cart page
│   ├── checkout.html              # Checkout process
│   ├── login.html                 # User login
│   ├── register.html              # User registration
│   ├── profile.html               # User profile
│   ├── orders.html                # Order history
│   ├── wishlist.html              # Saved products
│   ├── about.html                 # About us
│   ├── contact.html               # Contact page
│   ├── faq.html                   # FAQ page
│   ├── terms.html                 # Terms of service
│   ├── privacy.html               # Privacy policy
│   │
│   └── assets/
│       ├── css/
│       │   ├── styles.css         # Main stylesheet
│       │   └── responsive.css     # Mobile responsive
│       ├── js/
│       │   ├── main.js            # Core functionality
│       │   ├── cart.js            # Cart operations
│       │   └── validation.js      # Form validation
│       └── images/                # Product & UI images
│
├── backend/
│   ├── config/
│   │   ├── database.php           # Database connection
│   │   └── constants.php          # Global settings
│   │
│   ├── api/
│   │   ├── products/
│   │   │   ├── get_all.php
│   │   │   ├── get_by_id.php
│   │   │   ├── get_by_category.php
│   │   │   └── search.php
│   │   ├── cart/
│   │   │   ├── add.php
│   │   │   ├── remove.php
│   │   │   ├── update.php
│   │   │   └── get.php
│   │   ├── orders/
│   │   │   ├── create.php
│   │   │   └── get_user_orders.php
│   │   └── auth/
│   │       ├── register.php
│   │       ├── login.php
│   │       └── logout.php
│   │
│   ├── includes/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── navigation.php
│   │
│   ├── models/
│   │   ├── Product.php
│   │   ├── User.php
│   │   ├── Cart.php
│   │   └── Order.php
│   │
│   └── admin/
│       ├── index.php              # Admin dashboard
│       ├── products.php           # Manage products
│       ├── orders.php             # Manage orders
│       └── customers.php          # Manage customers
│
├── database/
│   ├── schema.sql                 # Database structure
│   └── sample-data.sql            # Sample products data
│
├── .htaccess                      # URL rewriting & security
└── README.md                      # Project documentation
```
## 📊 Database Schema

### Tables Structure

| Table | Description | Key Columns |
| --- | --- | --- |
| users | Customer & admin accounts | id, name, email, password_hash, role, address, phone |
| products | Grocery items | id, name, description, price, category_id, stock, image |
| categories | Product categories | id, name, parent_id |
| cart | Shopping cart items | id, user_id, product_id, quantity, session_id |
| orders | Customer orders | id, user_id, total, status, payment_method, shipping_address |
| order_items | Individual order items | id, order_id, product_id, quantity, price |
```bash
⚙️ How to Run This Project
1️⃣ Install Requirements
Install XAMPP or Laragon

Install Git (optional)

2️⃣ Clone Repository
bash
git clone https://github.com/your-username/grocery-store.git
3️⃣ Move to Server Folder
For XAMPP:

text
C:\xampp\htdocs\grocery-store
For Laragon:

text
C:\laragon\www\grocery-store
4️⃣ Import Database
Open phpMyAdmin: http://localhost/phpmyadmin

Create database named: grocery_db

Go to Import tab

Select file: database/schema.sql

Click Go

5️⃣ Configure Database
Open backend/config/database.php and set:

php
host = localhost
username = root
password = ""
database = grocery_db
6️⃣ Run Project
Start Apache and MySQL, then open:

text
http://localhost/grocery-store/frontend/index.html
Demo Credentials:

Role	Email	Password
Admin	admin@freshmart.com	admin123
Customer	customer@example.com	customer123
```
Application Screenshots
<img width="1905" height="919" alt="image" src="https://github.com/user-attachments/assets/581b47fc-c66b-4a7c-b21d-b4f89da7b6b9" />
<img width="1897" height="916" alt="image" src="https://github.com/user-attachments/assets/24f4807e-1e73-4f72-a250-4cd4177feb5d" />
<img width="1913" height="915" alt="image" src="https://github.com/user-attachments/assets/9edee970-fdd5-4446-a34d-cb5868112c43" />
<img width="1898" height="909" alt="image" src="https://github.com/user-attachments/assets/a1f0f1f9-af96-449d-88f0-208aed888fc3" />

| 🔐 Security Features     | Implementation                          |
| ------------------------ | --------------------------------------- |
| Password Hashing         | `password_hash()` with bcrypt algorithm |
| SQL Injection Prevention | PDO prepared statements                 |
| XSS Protection           | `htmlspecialchars()` input sanitization |
| Session Security         | Session regeneration on login           |
| CSRF Protection          | Tokens on all forms                     |
| File Upload Security     | Extension & size validation             |

| 🧪 Testing          | Test Cases | Status            |
| ------------------- | ---------- | ----------------- |
| User Authentication | 15         | ✅ Passed          |
| Product Operations  | 20         | ✅ Passed          |
| Shopping Cart       | 25         | ✅ Passed          |
| Checkout & Payment  | 18         | ✅ Passed          |
| Admin Functions     | 30         | ✅ Passed          |
| Responsive Design   | 12         | ✅ Passed          |
| **Total**           | **120**    | ✅ **100% Passed** |

```bash
**📊 Future Improvements
Mobile application (React Native / Flutter)

AI-powered product recommendations

Voice search integration

Real-time order tracking with map

WhatsApp order notifications

Loyalty points and rewards system

Multi-vendor marketplace

Subscription-based delivery service

Live chat support system

International shipping options******
```
👨‍💻 Author
Name: S Mohammed Aakif

Course: BCA (Bachelor of Computer Applications)

Project Type: Full-Stack Web Development Project

Mail for Suggestion : syedmdaakif007@gmail.com

GitHub:https://github.com/Aakif009-new

🙏 Acknowledgments
W3Schools for PHP/MySQL tutorials

FontAwesome for icons

Unsplash for product images

Stack Overflow community for support

⭐ Show Your Support
If you found this project helpful, please give it a ⭐ on GitHub!

Made with ❤️ by S Mohammed Aakif
