<?php
require 'config.php';
require 'functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? 'index.php';

    $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = (int)$user['is_admin'];
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . $user['name'] . '!'];
        header('Location: ' . $redirect);
        exit;
    } else {
        $errors[] = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require 'includes/header.php';
?>

<div class="auth-box">
  <h1>Login</h1>

  <?php foreach ($errors as $e): ?>
    <div class="flash error"><?= h($e) ?></div>
  <?php endforeach; ?>

  <form method="post" class="auth-form">
    <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
    <label>Email
      <input type="email" name="email" required>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button type="submit" class="btn btn-primary">Login</button>
  </form>
  <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php require 'includes/footer.php'; ?>
