<?php
include("../config/db.php");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $password = $_POST['password'];

    /* ── Validate password length (min 5) ── */
    if (strlen($password) < 5) {
        $error = "Password must be at least 5 characters.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        /* ── Check if email already exists (uses p_id, your actual PK column) ── */
        $check = $conn->prepare("SELECT p_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            /* ── Insert new user ── */
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $address, $hashed);

            if ($stmt->execute()) {
                /* ── Set session so user is logged in immediately ── */
                $new_id = $conn->insert_id;
                $_SESSION['user_id']   = $new_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['email']     = $email;

                header("Location: ../index.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zomazon – Register</title>
    <link rel="stylesheet" href="Registration.css">
</head>
<body>

<div class="form-box">
    <h2>Register</h2>

    <?php if ($error): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        <input name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
        <textarea name="address" placeholder="Address" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        <input type="password" name="password" placeholder="Password (min 5 characters)" minlength="5" required>
        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>