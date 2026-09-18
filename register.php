<?php
require 'config.php';
require 'functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $email, $hash);
        if ($stmt->execute()) {
            $_SESSION['user_id']   = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin']  = 0;
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome to StationHub, ' . $name . '!'];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>

<div class="auth-box">
  <h1>Create an Account</h1>

  <?php foreach ($errors as $e): ?>
    <div class="flash error"><?= h($e) ?></div>
  <?php endforeach; ?>

  <form method="post" class="auth-form">
    <label>Full Name
      <input type="text" name="name" value="<?= h($_POST['name'] ?? '') ?>" required>
    </label>
    <label>Email
      <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <label>Confirm Password
      <input type="password" name="confirm_password" required>
    </label>
    <button type="submit" class="btn btn-primary">Register</button>
  </form>
  <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php require 'includes/footer.php'; ?>
