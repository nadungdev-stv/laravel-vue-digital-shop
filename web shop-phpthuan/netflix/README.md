# Netflix Auto-Approval System
## Hệ thống tự động xác nhận Netflix Household Updates

---

## 📌 MÔ TẢ HỆ THỐNG

Hệ thống này tự động:
1. **Nhận email Netflix real-time** (< 2s) khi khách hàng trigger xác nhận từ TV
2. **Trích xuất link xác nhận** từ email
3. **Tự động click nút "Xác nhận cập nhật"** trên web Netflix
4. **Hoàn tất trong 5-8 giây** - khách hàng không phải chờ lâu!

---

## 🏗️ KIẾN TRÚC

```
Gmail (Netflix Email)
    ↓ Auto-forward
Domain Email (@yourdomain.com)
    ↓ Cloudflare Email Routing
webhook.php
    ↓ Save
Database (pending_approvals)
    ↑ Poll every 1s
approval_worker.js (Node.js + Puppeteer)
    ↓ Navigate & Click
Netflix Approval Page
    ✅ Approved!
```

---

## 📁 CẤU TRÚC FILES

```
netflix/
├── webhook.php              # Webhook nhận email từ Cloudflare
├── approval_worker.js       # Node.js worker tự động approve
├── package.json             # Dependencies (Puppeteer, MySQL)
├── config.php               # Database config
├── cleanup_approvals.php    # Cron script cleanup
├── schema_approvals.sql     # Database schema
├── DEPLOYMENT_GUIDE.md      # 📖 Hướng dẫn deployment CHI TIẾT
├── README.md                # File này
└── logs/
    ├── webhook.log          # Webhook logs
    ├── cleanup.log          # Cleanup logs
    └── (worker logs via PM2)
```

---

## ⚡ QUICK START

### 1. Tạo Database Table
```bash
mysql -u root -p webshop < schema_approvals.sql
```

### 2. Cài đặt Node.js Dependencies
```bash
cd /path/to/netflix/
npm install
```

### 3. Cấu hình Worker
Edit `approval_worker.js`:
```javascript
const dbConfig = {
  host: 'localhost',
  user: 'your_mysql_user',
  password: 'your_mysql_password',
  database: 'webshop'
};
```

### 4. Start Worker
```bash
# Option A: Test run
node approval_worker.js

# Option B: Daemon với PM2
npm install -g pm2
pm2 start approval_worker.js --name netflix-approval
pm2 save
```

### 5. Setup Cloudflare & Gmail
👉 **Xem file [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)** để hướng dẫn chi tiết!

---

## 🔧 DEPLOYMENT

**‼️ ĐỌC KỸ FILE [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)**

File này có hướng dẫn chi tiết từng bước:
- ✅ Setup Cloudflare Email Routing (FREE)
- ✅ Gmail Auto-Forward configuration
- ✅ Node.js worker setup & daemon config
- ✅ Troubleshooting & monitoring
- ✅ Security recommendations

**Thời gian deployment ước tính: 1-2 giờ**

---

## 📊 MONITORING

### Check Worker Status
```bash
# PM2
pm2 status
pm2 logs netflix-approval

# systemd
sudo systemctl status netflix-approval
```

### Check Database
```sql
-- Pending approvals
SELECT * FROM pending_approvals WHERE status = 'pending';

-- Recent approvals
SELECT * FROM pending_approvals ORDER BY created_at DESC LIMIT 10;

-- Failed approvals
SELECT * FROM pending_approvals WHERE status = 'failed';
```

### Check Logs
```bash
# Webhook
tail -f logs/webhook.log

# Worker (PM2)
pm2 logs netflix-approval

# Cleanup
tail -f logs/cleanup.log
```

---

## 🧪 TESTING

### Test Webhook
```bash
curl -X POST https://yourdomain.com/netflix/webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "from": "info@account.netflix.com",
    "subject": "Hộ gia đình Netflix",
    "html": "Link: https://www.netflix.com/account/update-primary-location?nftoken=test"
  }'
```

### Test Worker
```sql
-- Insert test approval
INSERT INTO pending_approvals (link, email_from, status) 
VALUES ('https://www.netflix.com/account/update-primary-location?nftoken=test', 'test@test.com', 'pending');

-- Worker sẽ pickup và process
```

---

## 🔍 TROUBLESHOOTING

### Issue: Worker không chạy
```bash
# Check Node.js
node -v

# Check process
ps aux | grep approval_worker

# Restart
pm2 restart netflix-approval
```

### Issue: Không nhận email
- Check Cloudflare Email Routing logs
- Check Gmail forwarding settings
- Check webhook.log

### Issue: Puppeteer error
```bash
# Cài chromium dependencies
sudo apt-get install -y chromium-browser

# Hoặc xem DEPLOYMENT_GUIDE.md phần Troubleshooting
```

---

## 📈 PERFORMANCE

**Latency breakdown**:
- Email Gmail → Cloudflare: ~1s
- Cloudflare → webhook: ~0.5s
- Webhook → database: ~0.1s
- Worker poll → detect: ~1s
- Puppeteer navigate + click: ~3-5s

**Total: 5-8 giây từ khi email đến → approved**

---

## 🔒 SECURITY

1. ✅ Validate email source (chỉ accept từ netflix.com)
2. ✅ HTTPS required cho webhook
3. ✅ Database credentials trong config file (không hardcode)
4. ✅ Logs không chứa sensitive data
5. ⚠️ **TODO**: Implement Cloudflare signature validation

---

## 📝 MAINTENANCE

### Cron Jobs
```bash
# Cleanup old records (chạy mỗi giờ)
0 * * * * php /path/to/netflix/cleanup_approvals.php >> /path/to/logs/cleanup.log 2>&1
```

### Database Backup
```bash
# Backup pending_approvals table
mysqldump -u root -p webshop pending_approvals > backup_approvals.sql
```

---

## 🆘 SUPPORT

Nếu gặp vấn đề:
1. Check logs (webhook.log, PM2 logs)
2. Check database records
3. Test từng component riêng lẻ
4. Xem DEPLOYMENT_GUIDE.md phần Troubleshooting

---

## 📚 TÀI LIỆU THAM KHẢO

- [Cloudflare Email Routing Docs](https://developers.cloudflare.com/email-routing/)
- [Puppeteer Documentation](https://pptr.dev/)
- [PM2 Documentation](https://pm2.keymetrics.io/)

---

## ✨ NEXT STEPS

Sau khi deployment:
1. Monitor logs trong 24h đầu
2. Test với 1-2 approval thật từ TV
3. Adjust timeouts nếu cần
4. Setup backup cho database
5. Thêm admin notification (email/Telegram) nếu muốn

---

**Created: 2025-12-27**  
**Version: 1.0.0**  
**Author: Netflix Auto-Approval Team**
