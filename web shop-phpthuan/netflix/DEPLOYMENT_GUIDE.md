# HƯỚNG DẪN DEPLOYMENT CHI TIẾT
## Netflix Auto-Approval System

---

## 📋 YÊU CẦU TRƯỚC KHI BẮT ĐẦU

### 1. Tài khoản & Services
- ✅ **Domain** của bạn (ví dụ: `yourdomain.com`)
- ✅ **Cloudflare Account** (free) - đã add domain vào Cloudflare
- ✅ **Gmail account** đang nhận email Netflix
- ✅ **Server/VPS** có PHP + MySQL + Node.js

### 2. Kiểm tra môi trường server
```bash
# Kiểm tra PHP
php -v
# Cần PHP >= 7.4

# Kiểm tra MySQL
mysql --version
# Cần MySQL >= 5.7

# Kiểm tra Node.js
node -v
# Cần Node.js >= 16.0

# Nếu chưa có Node.js, cài đặt:
# Ubuntu/Debian:
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# CentOS/RHEL:
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo yum install -y nodejs
```

---

## 🔧 BƯỚC 1: TẠO DATABASE TABLE

### 1.1. Truy cập MySQL
```bash
# Từ terminal
mysql -u root -p

# Hoặc qua phpMyAdmin
```

### 1.2. Chạy SQL script
```sql
-- Chọn database
USE webshop;  -- Thay 'webshop' bằng tên database của bạn

-- Chạy schema
SOURCE /path/to/netflix/schema_approvals.sql;

-- Hoặc copy-paste từ file schema_approvals.sql và execute
```

### 1.3. Verify table đã tạo
```sql
SHOW TABLES LIKE 'pending_approvals';
DESCRIBE pending_approvals;
```

---

## 📧 BƯỚC 2: SETUP CLOUDFLARE EMAIL ROUTING

### 2.1. Login Cloudflare Dashboard
1. Truy cập: https://dash.cloudflare.com
2. Chọn domain của bạn
3. Sidebar → **Email** → **Email Routing**

### 2.2. Enable Email Routing
1. Click **Get started** hoặc **Enable**
2. Cloudflare sẽ tự động add MX records
3. Verify MX records đã active (màu xanh)

**Quan trọng**: Email Routing **hoàn toàn miễn phí** với Cloudflare!

### 2.3. Create Destination Address (Webhook)
1. Tab **Destination addresses**
2. Click **Create address**
3. Type: **Webhook**
4. Webhook URL: `https://yourdomain.com/netflix/webhook.php`
   - Thay `yourdomain.com` bằng domain thật của bạn
   - **Phải HTTPS** (Cloudflare yêu cầu)
5. Name: `Netflix Auto Approval`
6. Click **Create**

### 2.4. Create Routing Rule
1. Tab **Routing rules**
2. Click **Create routing rule**
3. Custom address: `netflix@yourdomain.com`
   - (Hoặc bất kỳ email nào bạn muốn)
4. Action: **Send to a worker**
5. Destination: Chọn webhook vừa tạo ở bước 2.3
6. Click **Save**

### 2.5. Test Webhook
```bash
# Gửi test request
curl -X POST https://yourdomain.com/netflix/webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "from": "info@account.netflix.com",
    "subject": "Test: Hộ gia đình Netflix",
    "html": "Test link: https://www.netflix.com/account/update-primary-location?nftoken=test123"
  }'

# Response mong đợi:
# {"status":"success","message":"Approval request queued","id":1}
```

---

## 📨 BƯỚC 3: SETUP GMAIL AUTO-FORWARD

### 3.1. Gmail Settings
1. Login Gmail account nhận email Netflix
2. Settings (⚙️) → **See all settings**
3. Tab **Forwarding and POP/IMAP**

### 3.2. Add Forwarding Address
1. Click **Add a forwarding address**
2. Nhập: `netflix@yourdomain.com` (địa chỉ bạn setup ở Cloudflare)
3. Click **Next** → **Proceed**
4. Gmail gửi verification email → Check email và click link xác nhận
5. Quay lại Gmail settings, chọn **Forward a copy** → địa chỉ vừa add

⚠️ **Chú ý**: Bạn có thể chọn "keep Gmail's copy" để vẫn giữ email gốc!

### 3.3. Create Filter (Khuyến nghị)
Thay vì forward tất cả email, chỉ forward email Netflix:

1. Gmail Settings → **Filters and Blocked Addresses**
2. Click **Create a new filter**
3. From: `info@account.netflix.com`
4. Subject: `Hộ gia đình Netflix`
5. Click **Create filter**
6. Check ✅ **Forward it to**: `netflix@yourdomain.com`
7. Click **Create filter**

---

## 🖥️ BƯỚC 4: CÀI ĐẶT NODE.JS WORKER

### 4.1. Upload files lên server
```bash
# Từ máy local, upload files
scp webhook.php approval_worker.js package.json cleanup_approvals.php \
    user@yourserver:/path/to/netflix/

# Hoặc dùng FTP/FileZilla
```

### 4.2. Cài đặt dependencies
```bash
# SSH vào server
ssh user@yourserver

# Di chuyển đến thư mục netflix
cd /path/to/netflix/

# Cài đặt Node.js packages
npm install

# Output:
# added 2 packages (puppeteer, mysql2)
# Puppeteer sẽ tự động download Chromium (~300MB)
```

### 4.3. Cấu hình database trong worker
```bash
# Edit approval_worker.js
nano approval_worker.js

# Tìm dòng:
const dbConfig = {
  host: 'localhost',
  user: 'root',          # ← Thay bằng MySQL user của bạn
  password: '',          # ← Thay bằng MySQL password
  database: 'webshop'    # ← Thay bằng tên database
};

# Save: Ctrl+O → Enter → Ctrl+X
```

### 4.4. Test worker thủ công
```bash
# Test run worker
node approval_worker.js

# Output mong đợi:
# [2025-12-27T21:40:00.000Z] [INFO] Worker started - polling for pending approvals...

# Nếu có pending approval trong database, sẽ thấy:
# [2025-12-27T21:40:01.000Z] [INFO] Found 1 pending approval(s)
# [2025-12-27T21:40:01.000Z] [INFO] Starting approval process for ID: 1
# ...

# Nhấn Ctrl+C để stop
```

---

## 🔄 BƯỚC 5: CHẠY WORKER NHƯ DAEMON (TỰ ĐỘNG)

### Option A: Dùng PM2 (Khuyến nghị)

#### 5.1. Cài đặt PM2
```bash
# Cài PM2 globally
npm install -g pm2

# Hoặc với sudo (nếu cần)
sudo npm install -g pm2
```

#### 5.2. Start worker với PM2
```bash
cd /path/to/netflix/

# Start worker
pm2 start approval_worker.js --name netflix-approval

# Output:
# ┌─────┬──────────────────┬─────────┬─────────┬─────────┐
# │ id  │ name             │ status  │ restart │ uptime  │
# ├─────┼──────────────────┼─────────┼─────────┼─────────┤
# │ 0   │ netflix-approval │ online  │ 0       │ 0s      │
# └─────┴──────────────────┴─────────┴─────────┴─────────┘
```

#### 5.3. PM2 Commands
```bash
# Xem logs real-time
pm2 logs netflix-approval

# Xem status
pm2 status

# Stop worker
pm2 stop netflix-approval

# Restart worker
pm2 restart netflix-approval

# Xóa worker
pm2 delete netflix-approval

# Monitor (dashboard)
pm2 monit
```

#### 5.4. Auto-start khi server reboot
```bash
# Lưu PM2 config
pm2 save

# Generate startup script
pm2 startup

# Copy command output và chạy (ví dụ):
# sudo env PATH=$PATH:/usr/bin pm2 startup systemd -u username --hp /home/username
```

---

### Option B: Dùng systemd (Linux)

#### 5.1. Tạo service file
```bash
sudo nano /etc/systemd/system/netflix-approval.service
```

#### 5.2. Nội dung file:
```ini
[Unit]
Description=Netflix Auto-Approval Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/netflix
ExecStart=/usr/bin/node /path/to/netflix/approval_worker.js
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

#### 5.3. Enable & Start service
```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable auto-start
sudo systemctl enable netflix-approval

# Start service
sudo systemctl start netflix-approval

# Check status
sudo systemctl status netflix-approval

# View logs
sudo journalctl -u netflix-approval -f
```

---

## 🧹 BƯỚC 6: SETUP CRON CLEANUP

### 6.1. Edit crontab
```bash
crontab -e
```

### 6.2. Add cleanup job
```bash
# Chạy mỗi giờ lúc xx:00
0 * * * * php /path/to/netflix/cleanup_approvals.php >> /path/to/logs/cleanup.log 2>&1

# Hoặc chạy mỗi 30 phút
*/30 * * * * php /path/to/netflix/cleanup_approvals.php >> /path/to/logs/cleanup.log 2>&1
```

### 6.3. Verify cron
```bash
# List cron jobs
crontab -l
```

---

## ✅ BƯỚC 7: KIỂM TRA END-TO-END

### 7.1. Test flow hoàn chỉnh

**Cách 1: Test thực tế**
1. Mở TV Netflix
2. Yêu cầu xác nhận household (trigger từ TV)
3. Netflix gửi email → Gmail
4. Gmail auto-forward → Cloudflare
5. Cloudflare → webhook.php
6. Webhook save vào database
7. Worker pickup và approve
8. **Kết quả**: TV tự động approved trong 5-10 giây

**Cách 2: Test với link giả**
```bash
# Insert test record vào database
mysql -u root -p webshop -e "
INSERT INTO pending_approvals (link, email_from, email_subject, status) 
VALUES (
  'https://www.netflix.com/account/update-primary-location?nftoken=FAKE_TOKEN',
  'test@test.com',
  'Test approval',
  'pending'
);
"

# Worker sẽ pickup và cố gắng approve (sẽ fail vì link fake, nhưng verify được logic)
```

### 7.2. Monitor logs
```bash
# Webhook logs
tail -f /path/to/netflix/logs/webhook.log

# Worker logs (nếu dùng PM2)
pm2 logs netflix-approval

# Cleanup logs
tail -f /path/to/logs/cleanup.log

# Database
mysql -u root -p -e "SELECT * FROM webshop.pending_approvals ORDER BY created_at DESC LIMIT 5;"
```

---

## 📊 MONITORING & TROUBLESHOOTING

### Kiểm tra từng component

#### 1. Email Routing
```bash
# Gửi test email đến netflix@yourdomain.com
# Check Cloudflare dashboard → Email Routing → Logs

# Hoặc check webhook log:
tail -f /path/to/netflix/logs/webhook.log
```

#### 2. Database
```sql
-- Check pending approvals
SELECT * FROM pending_approvals WHERE status = 'pending';

-- Check recent approvals
SELECT * FROM pending_approvals ORDER BY created_at DESC LIMIT 10;

-- Check failed approvals
SELECT * FROM pending_approvals WHERE status = 'failed';
```

#### 3. Worker Status
```bash
# PM2
pm2 status
pm2 logs netflix-approval --lines 50

# systemd
sudo systemctl status netflix-approval
sudo journalctl -u netflix-approval -n 50
```

### Common Issues

**Issue 1: Worker không pick up pending approvals**
- Check: Worker có đang chạy? `pm2 status`
- Check: Database credentials đúng chưa?
- Check: Có pending records không? `SELECT * FROM pending_approvals WHERE status='pending'`

**Issue 2: Webhook không nhận được email**
- Check: Cloudflare Email Routing đã enable?
- Check: MX records đã active?
- Check: Webhook URL đúng và HTTPS?

**Issue 3: Puppeteer lỗi**
```bash
# Cài chromium dependencies (nếu thiếu)
# Ubuntu/Debian:
sudo apt-get install -y chromium-browser

# hoặc dependencies:
sudo apt-get install -y \
  ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 \
  libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 \
  libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 \
  libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 \
  libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 \
  libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 \
  libxtst6 lsb-release wget xdg-utils
```

---

## 🎯 PERFORMANCE TUNING

### Tối ưu worker
```javascript
// Trong approval_worker.js, điều chỉnh:

// Poll interval (giảm từ 1s → 500ms nếu cần nhanh hơn)
await new Promise(resolve => setTimeout(resolve, 500));

// Số records xử lý mỗi lần (tăng từ 5 → 10)
LIMIT 10

// Timeout navigation (giảm nếu connection nhanh)
timeout: 20000  // từ 30s → 20s
```

---

## 🔒 SECURITY RECOMMENDATIONS

1. **Validate webhook source** (thêm vào webhook.php):
```php
// Verify Cloudflare signature (optional)
$signature = $_SERVER['HTTP_X_CLOUDFLARE_SIGNATURE'] ?? '';
// Validate...
```

2. **Rate limiting**:
```php
// Limit requests per IP
// Implement IP-based throttling
```

3. **Database security**:
```sql
-- Create dedicated MySQL user
CREATE USER 'netflix_worker'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE ON webshop.pending_approvals TO 'netflix_worker'@'localhost';
FLUSH PRIVILEGES;
```

---

## 📝 NEXT STEPS

Sau khi deployment xong:

1. ✅ Monitor logs trong 24h đầu
2. ✅ Test với 1-2 approval thật
3. ✅ Adjust timeouts nếu cần
4. ✅ Setup backup cho database
5. ✅ Document credentials và configs

---

## 🆘 SUPPORT

Nếu gặp vấn đề:
1. Check logs: `webhook.log`, PM2 logs, cleanup logs
2. Check database records
3. Test từng component riêng lẻ
4. Liên hệ support với logs cụ thể

**Thời gian triển khai ước tính: 1-2 giờ**
