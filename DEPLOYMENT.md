# Hướng Dẫn Triển Khai (Deployment Guide) - Webshop Laravel

Tài liệu này hướng dẫn chi tiết cách triển khai dự án lên VPS, bao gồm cả việc cập nhật Database an toàn để tránh lỗi thiếu bảng/cột.

## 1. Chuẩn bị (Tại máy Local)

Đảm bảo bạn đã đẩy code và database sửa lỗi lên Git:

```bash
# 1. Thêm file database đã fix
git add database/sql/webshop_fixed_deploy.sql

# 2. Commit và Push
git commit -m "Add fixed database for deployment"
git push origin main
```

*(File `webshop_fixed_deploy.sql` là phiên bản đã được vá lỗi thiếu cột `views`, `sort_order`, `sessions`... so với file dump gốc)*

## 2. Triển khai trên VPS

### Bước 1: Lấy code mới nhất

Truy cập vào thư mục dự án trên VPS:

```bash
cd /path/to/your/project
git pull origin main
```

### Bước 2: Cài đặt & Cấu hình Docker (Nếu chưa chạy)

Nếu đây là lần đầu chạy hoặc cần khởi động lại container:

```bash
# Tắt container cũ (nếu có)
docker compose down

# Khởi động lại
docker compose up -d
```

### Bước 3: Cập nhật Database (QUAN TRỌNG)

Để đảm bảo database trên VPS giống hệt Local và không bị lỗi, hãy chạy 2 lệnh sau:

**Lệnh 1: Xóa database cũ và tạo lại (Reset):**
```bash
docker compose exec -T mysql mysql -u user -ppassword -e "DROP DATABASE IF EXISTS webshop; CREATE DATABASE webshop;"
```

**Lệnh 2: Import database đã sửa lỗi (Fixed Version):**
```bash
# LƯU Ý: Dùng file webshop_fixed_deploy.sql (Không dùng file gốc DBweb_ban_tai_khoan.sql)
docker compose exec -T mysql mysql -u user -ppassword webshop < database/sql/webshop_fixed_deploy.sql
```

### Bước 4: Build Giao diện (Frontend)

Để đảm bảo giao diện hiển thị đúng (không bị lỗi style/css):

```bash
# Chạy build assets
docker compose exec app npm run build

# Xóa cache view cũ
docker compose exec app php artisan view:clear
```

## 3. Kiểm tra

Truy cập website trên trình duyệt.
- Trang chủ: Kiểm tra hiển thị sản phẩm.
- Trang Admin: Kiểm tra đăng nhập và danh sách sản phẩm (không còn lỗi `Unknown column`).

---
**Lưu ý:**
- File `database/sql/DBweb_ban_tai_khoan.sql`: Là file gốc (thiếu cột).
- File `database/sql/webshop_fixed_deploy.sql`: Là file chuẩn để deploy (đã fix). Hãy luôn dùng file này.
