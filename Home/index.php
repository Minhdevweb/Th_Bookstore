<?php include "config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TH Bookstore</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
 <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
  <header>
    <div class="logo">TH BOOKs</div>
    <div class="controls">
      <div class="search-wrapper">
        <input type="search" id="search" placeholder="Search books..." />
        <i class="fas fa-search"></i>
      </div>
      <button id="themeToggle" class="btn"><i class="fas fa-adjust"></i></button>
      <button id="loginBtn" class="btn">Login</button>
      <button id="registerBtn" class="btn">Register</button>
      <button id="addProductBtn" class="btn">Add Product</button>
      <a href="#" id="cartBtn" class="btn cart"><i class="fas fa-shopping-cart"></i><span id="cartCount">0</span></a>
    </div>
  </header>

  <div class="content">
    <aside class="filters">
      <h4>Filters</h4>
      <select id="category">
        <option value="">All Categories</option>
        <option>English Books</option>
        <option>Vietnamese Books</option>
        <option>Stationery</option>
      </select>
      <select id="price">
        <option value="">Price Range</option>
        <option>Under $10</option>
        <option>$10-20</option>
        <option>$20-50</option>
        <option>Over $50</option>
      </select>
      <select id="rating">
        <option value="">Min Rating</option>
        <option>4.0</option>
        <option>4.5</option>
        <option>4.7</option>
      </select>
      <button id="apply" class="btn-modal">Apply</button>
    </aside>
    
    <main>
      <section class="grid" id="productGrid"></section>
      <div id="pagination"></div>

    </main>
  </div>

  <div class="modal" id="loginModal">
    <div class="modal-content">
      <button class="close" data-close="loginModal">&times;</button>
      <h3>Login</h3>
      <label for="loginEmail">Email</label>
      <input id="loginEmail" type="email" placeholder="you@example.com" required />
      <label for="loginPwd">Password</label>
      <input id="loginPwd" type="password" required minlength="6" />
      <button id="submitLogin" class="btn-modal">Sign In</button>
    </div>
  </div>

  <div class="modal" id="regModal">
    <div class="modal-content">
      <button class="close" data-close="regModal">&times;</button>
      <h3>Register</h3>
      <label for="regEmail">Email</label>
      <input id="regEmail" type="email" placeholder="you@example.com" required />
      <label for="regPwd">Password</label>
      <input id="regPwd" type="password" required minlength="6" />
      <label for="regConfirm">Confirm Password</label>
      <input id="regConfirm" type="password" required minlength="6" />
      <button id="submitReg" class="btn-modal">Sign Up</button>
    </div>
  </div>

  <div class="modal" id="cartModal">
    <div class="modal-content">
      <button class="close" data-close="cartModal">&times;</button>
      <h3>Your Cart</h3>
      <div id="cartItems"></div>
      <p>Total: $<span id="cartTotal">0.00</span></p>
      <button id="checkout" class="btn-modal">Checkout</button>
    </div>
  </div>
  
  <div class="modal" id="addProductModal">
    <div class="modal-content">
      <button class="close" data-close="addProductModal">&times;</button>
      <h3>Add Product</h3>

      <label>Title</label>
      <input id="prodTitle" type="text" required />

      <label>Author</label>
      <input id="prodAuthor" type="text" required />

      <label>Category</label>
      <input id="prodCategory" type="text" required />

      <label>Price</label>
      <input id="prodPrice" type="number" min="0" step="0.01" required />

      <label>Rating</label>
      <input id="prodRating" type="number" min="0" max="5" step="0.1" required />

      <label>Image File</label>
      <input id="prodImage" type="file" accept="image/*" required />

      <button id="submitProduct" class="btn-modal">Add Product</button>
    </div>
  </div>

  <script src="../javascript/main.js"></script>
</body>
</html>