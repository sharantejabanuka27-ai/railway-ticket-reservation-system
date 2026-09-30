<?php
require_once "db.php";
require_once "config/auth.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT user_id, full_name, email, password, role
            FROM users
            WHERE email = $1";

    $result = pg_query_params($conn, $sql, [$email]);

    if ($result && pg_num_rows($result) === 1) {

        $user = pg_fetch_assoc($result);

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"] = $user["role"];

            header("Location: dashboard.php");
            exit();

        } else {
            $message = "Invalid email or password.";
        }

    } else {
        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation System Login</title>
</head>

<body>

<h1>Reservation System</h1>

<h2>Login</h2>

<?php if ($message): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="POST">

    <label>Email:</label><br>
    <input type="email" name="email" required>

    <br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required>

    <br><br>

    <button type="submit">Login</button>

</form>

</body>
</html>