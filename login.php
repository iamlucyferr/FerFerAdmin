<?php
session_start();
require_once 'db.php';

$error = "";

if (isset($_POST['login'])) {
    $username = $_POST['user'];
    $password = $_POST['pass'];

    try {
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Verifies the BCrypt hash for admin123
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Admin user not found.";
        }
    } catch (mysqli_sql_exception $e) {
        $error = "Database Error: Table 'admins' is missing. Please run the SQL setup in phpMyAdmin.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FerFer | Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 40px; width: 100%; max-width: 380px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body>
    <div class="glass text-white">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-500 mb-1">FerFer</h1>
            <p class="text-slate-500 text-[10px] uppercase tracking-widest font-bold">Admin Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-xs p-3 rounded-lg mb-6 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-5">
                <label class="block text-[10px] text-slate-400 font-bold uppercase mb-2 ml-1">Username</label>
                <input type="text" name="user" class="w-full bg-slate-800/50 border border-slate-700 p-3 rounded-xl outline-none focus:border-blue-500 transition text-white" placeholder="admin" required>
            </div>

            <div class="mb-8">
                <label class="block text-[10px] text-slate-400 font-bold uppercase mb-2 ml-1">Password</label>
                <input type="password" name="pass" class="w-full bg-slate-800/50 border border-slate-700 p-3 rounded-xl outline-none focus:border-blue-500 transition text-white" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-xl font-bold transition-all transform active:scale-95 shadow-lg shadow-blue-600/20">
                SIGN IN
            </button>
        </form>
    </div>
</body>
</html>