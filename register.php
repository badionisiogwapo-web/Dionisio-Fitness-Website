<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
if (isLoggedIn()) { header('Location: account.php'); exit; }
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $errors[]='Your session expired. Please try again.';
    $name=trim($_POST['full_name']??''); $email=strtolower(trim($_POST['email']??'')); $phone=trim($_POST['phone']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
    if ($name==='' || mb_strlen($name)>120) $errors[]='Please enter your full name.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Please enter a valid email address.';
    if ($password!==$confirm) $errors[]='Passwords do not match.';
    if (strlen($password)<8) $errors[]='Password must be at least 8 characters.';
    if (!$errors) {
        $check=$pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1'); $check->execute([$email]);
        if ($check->fetch()) $errors[]='That email address is already registered.';
    }
    if (!$errors) {
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $stmt=$pdo->prepare('INSERT INTO users (full_name,email,password_hash,phone) VALUES (?,?,?,?)');
        $stmt->execute([$name,$email,$hash,$phone?:null]);
        session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['user_name']=$name; $_SESSION['user_email']=$email;
        redirectAfterLogin();
    }
}
$pageTitle='Create Account | Dionisio Fitness Center';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?=e($pageTitle)?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><link rel="stylesheet" href="css/style.css"></head><body class="auth-page"><div class="auth-shell"><a href="index.php" class="auth-brand"><span class="brand-mark">D</span><span class="brand-text"><strong>DIONISIO</strong><small>FITNESS CENTER</small></span></a><div class="auth-card"><div class="auth-copy"><p class="eyebrow">NEW MEMBER</p><h1>CREATE <span>ACCOUNT.</span></h1><p>Register first to join Dionisio or shop our official merch.</p></div><?php if($errors):?><div class="auth-alert"><?=e(implode(' ',$errors))?></div><?php endif;?><form method="POST" class="auth-form"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><div class="auth-form-grid"><div class="form-group"><label>FULL NAME</label><input name="full_name" value="<?=e($_POST['full_name']??'')?>" required maxlength="120"></div><div class="form-group"><label>PHONE NUMBER</label><input name="phone" value="<?=e($_POST['phone']??'')?>" maxlength="30" autocomplete="tel"></div></div><div class="form-group"><label>EMAIL ADDRESS</label><input type="email" name="email" value="<?=e($_POST['email']??'')?>" required autocomplete="email"></div><div class="auth-form-grid"><div class="form-group"><label>PASSWORD</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div><div class="form-group"><label>CONFIRM PASSWORD</label><input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></div></div><button class="btn btn-red auth-submit" type="submit">CREATE ACCOUNT</button></form><div class="auth-switch">Already registered? <a href="login.php">SIGN IN</a></div><a class="auth-back" href="index.php">← BACK TO WEBSITE</a></div></div></body></html>
