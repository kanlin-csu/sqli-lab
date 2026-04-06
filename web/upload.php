<?php
header("Content-Type: text/html; charset=utf-8");

$target_dir = "uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filename = basename($_FILES["file"]["name"]);
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        echo "<p>✅ 上傳成功！<a href='{$target_file}' target='_blank'>點我開啟檔案</a></p>";
    } else {
        echo "<p style='color:red;'>❌ 上傳失敗</p>";
    }
}
?>

<h2>📤 檔案上傳練習</h2>
<p>可測試副檔名繞過、WebShell 上傳、XSS 圖片上傳等。</p>
<form method="post" enctype="multipart/form-data">
    <label>選擇檔案：</label>
    <input type="file" name="file"><br><br>
    <input type="submit" value="上傳">
</form>
