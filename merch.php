<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/csrf.php';
$pageTitle='Dionisio Fitness Center | Merch';
$currentPage='merch.php'; $message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!isLoggedIn()){ header('Location: login.php?redirect=merch.php'); exit; }
    if(!verifyCsrf($_POST['csrf_token']??null)){
        $error='Invalid security token. Please try again.';
    } else {
        $pid=(int)($_POST['product_id']??0);
        $qty=filter_var($_POST['quantity']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($qty===false){ $error='Please choose a valid quantity.'; }
        else {
            $q=$pdo->prepare('SELECT product_id,stock FROM products WHERE product_id=? LIMIT 1'); $q->execute([$pid]); $prod=$q->fetch();
            if(!$prod){ $error='Product not found.'; }
            elseif((int)$prod['stock']===0){ $error='This product is currently out of stock.'; }
            elseif($qty>(int)$prod['stock']){ $error='Only '.(int)$prod['stock'].' item(s) are available.'; }
            else {
                $up=$pdo->prepare('INSERT INTO cart_items(user_id,product_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=LEAST(quantity+VALUES(quantity),?), updated_at=CURRENT_TIMESTAMP');
                $up->execute([currentUserId(),$pid,$qty,(int)$prod['stock']]);
                $message='Product added to your cart.';
            }
        }
    }
}
$products=$pdo->query('SELECT product_id,product_name,price,image,stock FROM products ORDER BY product_id')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="description" content="Dionisio Fitness Center merchandise."><title><?=e($pageTitle)?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><link rel="stylesheet" href="css/style.css"></head><body>
<header class="site-header" id="home"><a href="index.php" class="brand" aria-label="Dionisio Fitness Center home"><span class="brand-mark">D</span><span class="brand-text"><strong>DIONISIO</strong><small>FITNESS CENTER</small></span></a><button class="menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="mainMenu"><span></span><span></span><span></span></button><nav class="main-nav" id="mainMenu"><a href="index.php">HOME</a><a href="about.php">ABOUT</a><a href="services.php">SERVICES</a><a href="programs.php">PROGRAMS</a><a class="active" href="merch.php">MERCH</a><a href="gallery.php">GALLERY</a><a href="contact.php">CONTACT</a></nav><?php if(isLoggedIn()):?><a class="header-signin" href="account.php">MY ACCOUNT</a><a class="header-cta" href="logout.php">LOG OUT</a><?php else:?><a class="header-signin" href="login.php">SIGN IN</a><a class="header-cta" href="join.php">JOIN NOW</a><?php endif;?></header>
<main><section class="page-banner"><div class="page-banner-content reveal"><p class="eyebrow">MERCH</p><h1>REP YOUR <span>GRIND.</span></h1><p>Dionisio gear for training days and every day.</p></div></section><?php if($message):?><div class="shop-message auth-success"><?=e($message)?> <a href="cart.php">VIEW CART</a></div><?php endif;?><?php if($error):?><div class="shop-message auth-alert"><?=e($error)?></div><?php endif;?><section class="merch section dark-section"><div class="merch-grid"><?php foreach($products as $product):?><article class="product-card reveal"><div class="product-image"><img src="assets/<?=e($product['image'])?>" alt="<?=e($product['product_name'])?>"></div><h3><?=e(strtoupper($product['product_name']))?></h3><strong>₱<?=number_format((float)$product['price'],2)?></strong><p class="stock-left <?=((int)$product['stock']===0?'out-stock':'')?>"><?=((int)$product['stock']===0?'OUT OF STOCK':'STOCK LEFT: '.number_format((int)$product['stock']))?></p><?php if(isLoggedIn()):?><form method="POST" class="add-cart-form"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="product_id" value="<?=e((string)$product['product_id'])?>"><input class="cart-quantity" type="number" name="quantity" min="1" max="<?=e((string)$product['stock'])?>" value="1" <?=((int)$product['stock']===0?'disabled':'')?>><button class="product-btn" type="submit" <?=((int)$product['stock']===0?'disabled':'')?>>ADD TO CART</button></form><?php else:?><a class="product-btn product-link" href="login.php?redirect=merch.php">SIGN IN TO BUY</a><?php endif;?></article><?php endforeach;?></div></section></main>
<footer class="footer"><div class="footer-top"><div><div class="footer-brand">DIONISIO <span>FITNESS CENTER</span></div><p>Focus. Train. Conquer. Improve.</p></div><div class="footer-links"><strong>QUICK LINKS</strong><a href="index.php">Home</a><a href="about.php">About</a><a href="services.php">Services</a><a href="programs.php">Programs</a><a href="merch.php">Merch</a><a href="gallery.php">Gallery</a><a href="contact.php">Contact</a></div><div><strong>CONTACT US</strong><p>+63 955 855 1383</p><p>dionisiobj@gmail.com</p></div></div><div class="footer-bottom"><span>© <?=date('Y')?> DIONISIO FITNESS CENTER. ALL RIGHTS RESERVED.</span><span>BUILT FOR STRENGTH.</span></div></footer><button id="topBtn" title="Back to top" aria-label="Back to top">↑</button><script src="js/script.js"></script></body></html>
