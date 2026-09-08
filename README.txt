DIONISIO FITNESS CENTER - MEMBERSHIP + MERCH SHOP
=================================================

This version keeps the original Dionisio Fitness Center brand-guide design and adds a real PHP/MySQL member account, membership, cart, checkout, order history, and stock system.

IMPORTANT SECURITY NOTE
-----------------------
No website can honestly be guaranteed 100% impossible to hack. This project uses protections appropriate for a local XAMPP school project, including password hashing, PDO prepared statements, sessions, CSRF tokens, output escaping, ownership checks, server-side price/stock validation, and checkout transactions.

For local development, keep XAMPP on localhost. Do not expose phpMyAdmin or MySQL directly to the public internet. If deploying publicly, use HTTPS, strong database credentials, updated PHP/MariaDB, a firewall, backups, and production server configuration.

INSTALLATION
------------
1. Extract this ZIP.
2. Put the folder inside:
   C:\xampp\htdocs\
3. Open XAMPP.
4. Start Apache.
5. Start MySQL.
6. Open:
   http://localhost/phpmyadmin/
7. Import database.sql.
8. Open:
   http://localhost/Dionisio_Fitness_Website_Brand_Guide/

If your XAMPP MySQL root account has a password, edit config/database.php.

USER FLOW
---------
Visitor -> JOIN NOW / BUY -> LOGIN -> REGISTER (if needed) -> LOGIN -> continue.

Visitors who are not logged in cannot access the account, membership, cart, checkout, or order pages.

REGISTER / LOGIN
----------------
- register.php creates an account.
- Passwords are stored using password_hash().
- login.php checks passwords using password_verify().
- The session ID is regenerated after login.

MY ACCOUNT
----------
account.php shows:
- Profile
- Active membership
- Remaining membership days
- Membership history
- Number of orders
- Cart item count
- Links to shop, orders, profile, and logout

MEMBERSHIP
----------
Plans:
BASIC    PHP 999/month
PREMIUM  PHP 1,499/month
VIP      PHP 2,499/month

A membership is recorded against the logged-in user's ID. Starting a new membership expires the user's previous active membership and creates a new active record.

The expiration date is calculated from the start date. Remaining days are calculated from today's date, not hard-coded.

MERCH + STOCK
------------
The merch page reads products and stock from MySQL.
Products:
- Dionisio Performance Tee - PHP 599
- Dionisio Shaker Bottle - PHP 399
- Dionisio Hoodie - PHP 1,099
- Dionisio Cap - PHP 1,099

Stock appears on each product. Users can add products to their own cart only after logging in.

CART + CHECKOUT
---------------
cart.php lets the logged-in user update quantities or remove products.

checkout.php:
- re-reads prices from MySQL
- checks current stock
- calculates totals on the server
- uses a database transaction
- creates the order and order items
- decreases stock only after a successful order
- clears the user's cart

Stock cannot become negative. If stock changed while checking out, the transaction rolls back and the order is not completed.

ORDERS
------
orders.php shows only the logged-in user's orders.
order_details.php shows only an order owned by the logged-in user.

The order item stores the product name and price at the time of the order so old orders remain accurate even if a product's current price changes later.

SECURITY FEATURES
-----------------
- password_hash / password_verify
- PDO prepared statements
- server-side validation
- CSRF tokens on important POST actions
- HttpOnly + SameSite session cookies
- Secure session cookie automatically enabled when HTTPS is detected
- session_regenerate_id(true) after login
- authenticated-page protection
- ownership checks for profile, cart, membership, and orders
- htmlspecialchars() for displayed user/database text
- server-side price and stock verification
- MySQL transactions for checkout
- POST -> Redirect -> GET after successful checkout
- no public SQL execution page
- no ordinary-user admin privileges

PHOTO SETTINGS
--------------
The original style.css still contains easy-to-find photo size and positioning comments. You can change the image height/object-position there without changing the PHP.

ETHNOCENTRIC FONT
-----------------
The CSS expects:
fonts/Ethnocentric.ttf

If you have the actual Ethnocentric font file, place it there. The ZIP only contains an instruction placeholder because the font file was not supplied.

PRODUCT STOCK / PRICE
---------------------
Product stock and price are stored in the products table in MySQL.
You can edit them through phpMyAdmin for this school project.

Do not let normal website users edit product stock or price.

DATABASE TABLES
---------------
users
memberships
products
cart_items
orders
order_items

BACKUPS
-------
Before changing the database, export a backup from phpMyAdmin.

XAMPP SAFETY
------------
Keep PHP and MariaDB/MySQL updated. Do not expose the XAMPP development server, phpMyAdmin, or MySQL port directly to the public internet. A public production website should use HTTPS and proper server hardening.
