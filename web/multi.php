<?php
header("Content-Type: text/html; charset=utf-8");
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';

echo "<h2>🧪 multi_query() 示範 - Stacked SQL Injection 測試</h2>";

$query = "SELECT title FROM items WHERE id = $id";
echo "<p><strong>執行的 SQL：</strong><code>" . $query . "</code></p>";

if ($mysqli->multi_query($query)) {
    do {
        if ($result = $mysqli->store_result()) {
            while ($row = $result->fetch_assoc()) {
                echo "<h3>標題：{$row['title']}</h3>";
            }
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
} else {
    echo "<p style='color:red;'>❌ 執行失敗：{$mysqli->error}</p>";
}
?>

<h3>🧨 測試 Stacked Payload：</h3>
<ul>
  <li><code>1; SELECT user()--</code> ← 第二語句會執行</li>
  <li><code>1; INSERT INTO users(id,username,password) VALUES(999,'multi','multi123')--</code></li>
  <li><code>1; DROP TABLE IF EXISTS temp_table--</code></li>
</ul>

<p>✅ 這頁使用 <code>multi_query()</code>，允許一次執行多個 SQL 指令。注意這在實務上極需防範！</p>
