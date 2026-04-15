<?php
header("Content-Type: text/html; charset=utf-8");
?>

<h2>🧪 SQL Injection / XSS / Upload 練習平台總覽</h2>
<ul>
  <li><a href="union.php?id=1" target="_blank">🔗 union.php - UNION-based SQL Injection</a></li>
  <li><a href="login.php" target="_blank">🔐 login.php - 登入繞過、Error-Based、Boolean-Based</a></li>
  <li><a href="title.php?id=1" target="_blank">🐞 title.php - Error-Based SQL Injection</a></li>
  <li><a href="stacked.php?id=1;SELECT user()" target="_blank">📛 stacked.php - 模擬 Stacked Queries (mysqli 不支援)</a></li>
  <li><a href="multi.php?id=1;SELECT user()" target="_blank">📛 multi.php - 可執行 Stacked Queries</a></li>
  <li><a href="upload.php" target="_blank">📤 upload.php - 檔案上傳測試</a></li>
  <li><a href="items.php?id=1" target="_blank">🔍 items.php - Error-Based SQL Injection</a></li>
  <li><a href="outfile.php?id=1" target="_blank">📂 outfile.php - INTO OUTFILE WebShell 寫入</a></li>
  <li><a href="xxe.php" target="_blank">🌐 xxe.php - XXE Injection → SSRF 攻擊</a></li>
</ul>
<p>💡 請搭配 <code>sqlmap</code>、瀏覽器或 Burp Suite 等工具進行實測。</p>
