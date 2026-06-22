<?php
include "../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['p_id'];       // fixed: was $user['p_id']
            $_SESSION['user_name'] = $user['name'];     // fixed: matches register.php
            $_SESSION['email']     = $user['email'];

            header("Location: ../index.php");
            exit();
        } else {
            $error = "Wrong password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zomazon – Login</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.box {
    background: white;
    padding: 36px 30px;
    border-radius: 16px;
    width: 340px;
    box-shadow: 0 16px 40px rgba(0,0,0,0.18);
}

.box h2 {
    font-size: 22px;
    font-weight: 600;
    color: #16a34a;
    margin-bottom: 6px;
}

.box p.subtitle {
    font-size: 13px;
    color: #9ca3af;
    margin-bottom: 20px;
}

.error-msg {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #dc2626;
    font-size: 13px;
    padding: 9px 12px;
    border-radius: 8px;
    margin-bottom: 14px;
}

label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 5px;
    margin-top: 14px;
}

input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1.5px solid #e5e7eb;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color .2s;
}
input:focus { border-color: #22c55e; }

.pass-wrap {
    position: relative;
}
.pass-wrap input { padding-right: 42px; }
.eye-btn {
    position: absolute;
    right: 11px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 17px;
    color: #9ca3af;
    line-height: 1;
    padding: 0;
}
.eye-btn:hover { color: #16a34a; }

.pass-warn {
    color: #f59e0b;
    font-size: 12px;
    margin-top: 5px;
    display: none;
}

button[type="submit"] {
    margin-top: 22px;
    width: 100%;
    padding: 11px;
    background: #22c55e;
    color: white;
    border: none;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background .18s, transform .12s;
}
button[type="submit"]:hover  { background: #16a34a; }
button[type="submit"]:active { transform: scale(.98); }

.links {
    margin-top: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    text-align: center;
}
.links a {
    font-size: 13px;
    color: #16a34a;
    text-decoration: none;
}
.links a:hover { text-decoration: underline; }
</style>
</head>

<body>
<div class="box">
    <h2>Welcome back</h2>
    <p class="subtitle">Sign in to your Zomazon account</p>

    <?php if ($error): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm()">

        <label for="email">Email address</label>
        <input type="email" id="email" name="email"
               placeholder="you@example.com"
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
               required>

        <label for="password">Password</label>
        <div class="pass-wrap">
            <input type="password" id="password" name="password"
                   placeholder="Min 5 characters"
                   minlength="5" required>
            <button type="button" class="eye-btn" id="eye-btn" onclick="togglePassword()" title="Show/hide password">
                👁
            </button>
        </div>
        <p class="pass-warn" id="pass-warn">⚠️ Password must be at least 5 characters</p>

        <button type="submit">Login</button>
    </form>

    <div class="links">
        <a href="forgot_password.php">Forgot password?</a>
        <a href="register.php">Don't have an account? Register</a>
    </div>
</div>

<script>
const passInput = document.getElementById('password');
const passWarn  = document.getElementById('pass-warn');
const eyeBtn    = document.getElementById('eye-btn');

function togglePassword() {
    const isHidden = passInput.type === 'password';
    passInput.type = isHidden ? 'text' : 'password';
    eyeBtn.textContent = isHidden ? '🙈' : '👁';
}

passInput.addEventListener('input', () => {
    passWarn.style.display = passInput.value.length > 0 && passInput.value.length < 5
        ? 'block' : 'none';
});

function validateForm() {
    if (passInput.value.length < 5) {
        passWarn.style.display = 'block';
        passInput.focus();
        return false;
    }
    return true;
}
</script>

</body>
</html>