<?php
// login.php - existing user sign in.
include "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // password_verify checks the typed password against the stored hash
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        // send each role to the page that matters most to them
        if ($user['role'] === 'organizer') {
            header("Location: organizer.php");
        } elseif ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Incorrect email or password.";
    }
}

include "header.php";
?>

<h2>Login</h2>

<?php if ($error) { ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form method="POST" class="form-box">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="register.php">Register here</a></p>

<?php include "footer.php"; ?>
