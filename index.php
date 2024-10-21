<?php
// مسار الملفات
$accountsFile = 'accounts.json'; // ملف تخزين الحسابات بصيغة JSON
$messagesFile = 'messages.txt';

// تحقق إذا كان الحساب موجود
$accounts = file_exists($accountsFile) ? json_decode(file_get_contents($accountsFile), true) : [];
$loggedIn = false;
$loggedInUser = '';

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'createAccount') {
        $username = htmlspecialchars($_POST['username']);
        $code = htmlspecialchars($_POST['code']);

        // إضافة حساب جديد
        $accounts[$code] = $username;
        file_put_contents($accountsFile, json_encode($accounts));
        $loggedIn = true;
        $loggedInUser = $username;
    } elseif ($_POST['action'] == 'login') {
        $code = htmlspecialchars($_POST['code']);

        // تحقق من وجود الحساب
        if (isset($accounts[$code])) {
            $loggedIn = true;
            $loggedInUser = $accounts[$code];
        }
    } elseif ($_POST['action'] == 'logout') {
        // مسح الحساب والرسائل عند تسجيل الخروج
        $code = htmlspecialchars($_POST['code']);
        unset($accounts[$code]);
        file_put_contents($accountsFile, json_encode($accounts));
        file_put_contents($messagesFile, ""); // حذف جميع الرسائل
        header("Refresh:0"); // إعادة تحميل الصفحة
    } elseif (isset($_POST['message']) && isset($_POST['username']) && isset($_POST['code'])) {
        $username = htmlspecialchars($_POST['username']);
        $code = htmlspecialchars($_POST['code']);
        $message = htmlspecialchars($_POST['message']);
        $time = date('h:i A');

        // إضافة اسم الكاتب والكود إلى الرسالة
        $fullMessage = "$username ($code): $message - $time";
        file_put_contents($messagesFile, $fullMessage . "\n", FILE_APPEND);
    }
}

// اقرأ جميع الرسائل
$messages = file_exists($messagesFile) ? file($messagesFile) : [];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام رسائل واتسابي</title>
    <!-- روابط Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .chat-container {
            width: 100%;
            max-width: 600px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 90vh;
            overflow: hidden;
        }
        .messages {
            flex-grow: 1;
            background-color: #e5ddd5;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .message {
            background-color: #dcf8c6;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            max-width: 80%;
            align-self: flex-start;
        }
        .message.other {
            background-color: #fff;
            align-self: flex-end;
        }
        .message-header {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
        }
        .message-time {
            font-size: 10px;
            text-align: right;
            color: #666;
        }
        form {
            display: flex;
            padding: 10px;
            background-color: #f1f1f1;
        }
        input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: none;
            border-radius: 20px;
            margin-right: 10px;
        }
        button {
            padding: 10px 20px;
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
        }
        button:hover {
            background-color: #128C7E;
        }
        .login-form, .register-form {
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        .login-form input, .register-form input {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .login-form button, .register-form button {
            padding: 10px;
            background-color: #128C7E;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-form button:hover, .register-form button:hover {
            background-color: #25D366;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .action-buttons button {
            background-color: #128C7E;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .action-buttons button:hover {
            background-color: #25D366;
        }
        .logout {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .logout button {
            background-color: transparent;
            border: none;
            cursor: pointer;
        }
        .logout button i {
            font-size: 24px;
            color: #128C7E;
        }
        .logout-menu {
            display: none;
            position: absolute;
            top: 40px;
            right: 10px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .logout-menu button {
            padding: 10px;
            background-color: #128C7E;
            color: white;
            border: none;
            border-radius: 5px;
            width: 100px;
            cursor: pointer;
        }
        .logout-menu button:hover {
            background-color: #25D366;
        }
    </style>
</head>
<body>

<?php if (!$loggedIn): ?>
    <div class="chat-container">
        <div class="action-buttons">
            <button onclick="showRegisterForm()">إنشاء حساب جديد</button>
            <button onclick="showLoginForm()">الدخول إلى حسابك</button>
        </div>

        <form id="login-form" class="login-form" method="post" style="display: none;">
            <input type="text" name="code" placeholder="أدخل كود الحساب" required>
            <button type="submit" name="action" value="login">دخول</button>
        </form>

        <form id="register-form" class="register-form" method="post" style="display: none;">
            <input type="text" name="username" placeholder="أدخل اسم المستخدم" required>
            <input type="text" name="code" placeholder="أدخل كود مميز" required>
            <button type="submit" name="action" value="createAccount">إنشاء حساب</button>
        </form>
    </div>

    <script>
        function showLoginForm() {
            document.getElementById('login-form').style.display = 'flex';
            document.getElementById('register-form').style.display = 'none';
        }
        
        function showRegisterForm() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'flex';
        }
    </script>
<?php else: ?>
    <div class="chat-container">
        <!-- زر الحساب مع أيقونة الشخص -->
        <div class="logout">
            <button onclick="toggleLogoutMenu()">
                <i class="material-icons">person</i>
            </button>
            <div id="logout-menu" class="logout-menu">
                <form method="post">
                    <input type="hidden" name="code" value="<?php echo array_search($loggedInUser, $accounts); ?>">
                    <button type="submit" name="