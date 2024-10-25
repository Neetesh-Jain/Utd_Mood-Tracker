<?php
include 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (registerUser($_POST['username'], $_POST['email'], $_POST['password'])) {
        header('Location: index.php');
    } else {
        echo "Registration failed!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <h2 class="mt-5">Register</h2>
        <form method="POST" class="mt-3">
            <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
            <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
