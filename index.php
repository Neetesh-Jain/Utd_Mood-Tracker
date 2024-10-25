<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/functions.php'; 

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['reset_password']) && !isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            header('Location: dashboard.php'); 
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = $_POST['reg_username'];
    $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    $personal_question = $_POST['personal_question'];
    $personal_answer = $_POST['personal_answer'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, personal_question, personal_answer) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $personal_question, $personal_answer);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! You can now log in.');</script>";
    } else {
        echo "<script>alert('Registration failed: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $username = $_POST['username'];
    $personal_question = $_POST['personal_question'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND personal_question = ?");
    $stmt->bind_param("ss", $username, $personal_question);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $new_password, $username);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Password reset successful!');</script>";
    } else {
        echo "<script>alert('Invalid username or personal question answer.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Tracker Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-image: url('loginpage.jpg');
            background-size: cover;
            background-position: center;
        }

        .container {
            width: 400px;
            padding: 40px;
            margin-top: 100px;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
        }

        .form-control, .btn {
            border-radius: 10px;
        }

        .btn-primary {
            position: relative;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <h2>Login to Mood Tracker</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" class="mt-3">
            <input type="text" id="username" name="username" class="form-control mb-3" placeholder="Username" required>
            <input type="password" id="password" name="password" class="form-control mb-3" placeholder="Password" required>
            <button type="submit" id="loginButton" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="mt-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">Forgot Password?</a><br>
            <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Register</a>
        </div>
    </div>

    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                        <input type="text" name="personal_question" class="form-control mb-2" placeholder="What is your birthplace?" required>
                        <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
                        <button type="submit" name="reset_password" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="text" name="reg_username" class="form-control mb-2" placeholder="Username" required>
                        <input type="password" name="reg_password" class="form-control mb-2" placeholder="Password" required>
                        <input type="text" name="personal_question" class="form-control mb-2" placeholder="Security Question" required>
                        <input type="text" name="personal_answer" class="form-control mb-2" placeholder="Answer" required>
                        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const loginButton = document.getElementById('loginButton');
        const loginForm = document.getElementById('loginForm');

        loginForm.addEventListener('submit', function (e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                e.preventDefault();
                moveButton();
            }
        });

        function moveButton() {
            const randomX = Math.random() * (window.innerWidth - 100);
            const randomY = Math.random() * (window.innerHeight - 100);
            loginButton.style.transform = `translate(${randomX}px, ${randomY}px)`;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
