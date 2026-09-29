<?php
// register.php - new user sign up as either an attendee or an organizer.
include "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role']; // 'attendee' or 'organizer' - admin is never self-registered

    if ($role !== 'attendee' && $role !== 'organizer') {
        $role = 'attendee'; // safety fallback in case the form is tampered with
    }

    // Check if the email is already registered
    $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    $exists = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($exists) > 0) {
        $error = "An account with this email already exists.";
    } else {
        // password_hash scrambles the password - we never store plain text passwords
        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed, $role);
        mysqli_stmt_execute($stmt);

        // log the new user in immediately
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['name']    = $name;
        $_SESSION['role']    = $role;

        header("Location: index.php");
        exit;
    }
}

include "header.php";
?>

<h2>Create an Account</h2>

<?php if ($error) { ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form method="POST" class="form-box">
    <label>Full Name</label>
    <input type="text" name="name" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required minlength="6">

    <label>I am registering as a...</label>
    <select name="role">
        <option value="attendee">Attendee (I want to book tickets)</option>
        <option value="organizer">Organizer (I want to list events)</option>
    </select>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

<?php include "footer.php"; ?>
