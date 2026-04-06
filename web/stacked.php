<?php
header("Content-Type: text/html; charset=utf-8");
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';

echo "<h2>💣 Stacked Queries SQL Injection 測試</h2>";

$query = "SELECT title FROM items WHERE id = '$id'";
echo "<p><strong>執行的 SQL：</strong><code>" . htmlspecialchars($query) . "</code></p>";

// 示範 stacked query 的限制
// mysqli_query 不允許多語句；此頁面純粹用來教學顯示，不會真正執行 stacked payload
if (strpos($id, ';') !== false) {
    echo "<p style='color: red;'>❌ Stacked queries 無法執行：MySQL + mysqli 不支援多語句執行</p>";
} else {
    $result = $mysqli->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<h3>標題：{$row['title']}</h3>";
        }
    } else {
        echo "<p style='color:red;'>❌ 找不到資料</p>";
    }
}
?>

<h3>🧪 Stacked Payload 教學：</h3>
<ul>
  <li><code>1; DROP TABLE users --</code> ← 嘗試刪除表（⚠️ 不會被執行）</li>
  <li><code>1; INSERT INTO users(id,username,password) VALUES(999,'hacker','p@ss')--</code></li>
  <li><code>1; SELECT user()--</code> ← 嘗試加一句額外查詢</li>
</ul>

<p>⚠️ 注意：此頁面僅供演示用途，PHP 的 mysqli_query() 預設不允許執行多個語句。</p>
