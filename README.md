# Laravel Vue Digital Shop - E-Commerce Platform for Digital Assets

A modern, full-stack e-commerce solution designed for selling digital products (Premium Accounts like Netflix, YouTube, Spotify). Built with the latest **Laravel 12** and **Vue 3**, focusing on performance, SEO, and seamless user experience.

![Project Banner](public/images/banner-placeholder.png) 
*(Add a screenshot of your homepage here)*

🟢 **Live Demo**: [https://www.veyrix.pro/](https://www.veyrix.pro/)

[English](#laravel-vue-digital-shop---e-commerce-platform-for-digital-assets) | [Tiếng Việt](#laravel-vue-digital-shop---nền-tảng-thương-mại-điện-tử-sản-phẩm-số)

---

## 🚀 Tech Stack

### Backend
- **Framework**: [Laravel 12](https://laravel.com) (Latest stable release)
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0
- **Caching**: Redis (for session & cache driver)
- **API**: RESTful API endpoints for external integrations

### Frontend
- **Framework**: [Vue.js 3](https://vuejs.org) (Composition API, Script Setup)
- **SPA Bridge**: [Inertia.js v2](https://inertiajs.com) (Server-Side Rendering feel with SPA speed)
- **Styling**: Bootstrap 5 + Custom CSS (Scoped Styles)
- **Build Tool**: [Vite](https://vitejs.dev) (HMR & Asset bundling)

---

## ✨ Key Features

### 🛒 E-Commerce Functionality
- **Product Management**: Variants, Pricing, Stock Control.
- **Cart System**: AJAX-based cart updates, Coupon/Voucher application.
- **Checkout**: Guest & Registered User checkout flows.
- **Automatic Delivery**: System automatically sends account credentials (email/pass) to customer immediately after successful payment.

### 💳 Payment Gateway Integration (SePay)
- **QR Code Generation**: Dynamic QR codes for bank transfer.
- **Real-time Webhook**: Automatically confirms order status upon receiving bank transaction via SePay Webhook.

### 📊 Advanced Analytics & SEO
- **Visitor Tracking**: Middleware-based tracking of online users, daily visits, and traffic sources.
- **SEO Optimization**:
    - **Inertia Head**: Dynamic meta tags (Title, Description, Graph Images) for every page.
    - **Schema.org**: JSON-LD structured data for Google Rich Results (Products, Organization).
    - **Sitemap**: Auto-generated XML sitemap.

### 🛡️ Admin Dashboard
- **Role-based Access Control**.
- **Order Management**: View details, manual approval, re-send accounts.
- **Stock Management**: Import accounts in bulk (.txt/csv), manage inventory.
- **Content Management**: Banners, Sliders, Articles, Pages.

---

## 🛠️ Usage & Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/nadungdev-stv/laravel-vue-digital-shop.git
   cd laravel-vue-digital-shop
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Configure your database credentials in .env
   ```

4. **Database Migration**
   ```bash
   php artisan migrate --seed
   ```

5. **Run Development Server**
   ```bash
   # Terminal 1: Laravel Server
   php artisan serve

   # Terminal 2: Vite Dev Server
   npm run dev
   ```

---

## 📂 Project Structure

- `app/Http/Controllers`: Backend logic (API & Inertia Responses).
- `app/Http/Middleware/LogVisitor.php`: Custom middleware for traffic analytics.
- `resources/js/Pages`: Vue.js Page Components (Inertia views).
- `resources/js/Components/SeoHead.vue`: Reusable SEO component.
- `routes/web.php`: Application routes definition.

---

## 📬 Contact
- **Author**: Ngo Anh Dung
- **GitHub**: [github.com/nadungdev-stv](https://github.com/nadungdev-stv)
- **Email**: [nadungdev@gmail.com](mailto:nadungdev@gmail.com)

---

# Laravel Vue Digital Shop - Nền tảng Thương mại điện tử Sản phẩm số

Một giải pháp thương mại điện tử full-stack hiện đại, được thiết kế chuyên biệt để bán các sản phẩm kỹ thuật số (Tài khoản Premium như Netflix, YouTube, Spotify). Xây dựng trên nền tảng **Laravel 12** và **Vue 3** mới nhất, tập trung tối đa vào hiệu năng, chuẩn SEO và trải nghiệm người dùng mượt mà.

🟢 **Xem Demo Trực Tiếp**: [https://www.veyrix.pro/](https://www.veyrix.pro/)

---

## 🚀 Công Nghệ Sử Dụng (Tech Stack)

### Backend
- **Framework**: [Laravel 12](https://laravel.com) (Phiên bản ổn định mới nhất)
- **Ngôn ngữ**: PHP 8.2+
- **Cơ sở dữ liệu**: MySQL 8.0
- **Caching**: Redis (quản lý session & cache driver)
- **API**: RESTful API endpoints để tích hợp hệ thống bên ngoài

### Frontend
- **Framework**: [Vue.js 3](https://vuejs.org) (Composition API, Script Setup)
- **Giao diện**: Bootstrap 5 + Custom CSS (Scoped Styles)
- **Build Tool**: [Vite](https://vitejs.dev) (HMR & đóng gói tài nguyên siêu tốc)

---

## ✨ Tính Năng Nổi Bật

### 🛒 Chức Năng Thương Mại Điện Tử
- **Quản lý sản phẩm**: Biến thể (gói tháng/năm), giá cả, quản lý tồn kho.
- **Giỏ hàng thông minh**: Cập nhật giỏ hàng tức thì (AJAX), áp dụng mã giảm giá/voucher.
- **Thanh toán**: Hỗ trợ quy trình thanh toán cho cả khách vãng lai (Guest) và thành viên.
- **Giao hàng tự động (Auto-delivery)**: Hệ thống tự động gửi thông tin tài khoản (email/pass) cho khách ngay lập tức sau khi thanh toán thành công.

### 💳 Tích Hợp Cổng Thanh Toán (SePay)
- **Tạo mã QR động**: Tự động tạo mã QR chuyển khoản ngân hàng theo đúng số tiền đơn hàng.
- **Webhook thời gian thực**: Tự động xác nhận đơn hàng ngay khi nhận được biến động số dư từ ngân hàng qua SePay Webhook.

### 📊 Phân Tích & SEO Nâng Cao
- **Theo dõi người dùng (Visitor Tracking)**: Middleware tự xây dựng để thống kê người dùng online, lượt truy cập hàng ngày và nguồn truy cập.
- **Tối ưu hóa SEO**:
    - **Inertia Head**: Tự động tạo thẻ meta động (Title, Description, ảnh Open Graph) cho từng trang chi tiết sản phẩm.
    - **Schema.org**: Tích hợp dữ liệu có cấu trúc JSON-LD giúp Google hiển thị kết quả tìm kiếm phong phú (Rich Results).
    - **Sitemap**: Tự động tạo XML sitemap.

### 🛡️ Trang Quản Trị (Admin Dashboard)
- **Phân quyền truy cập**.
- **Quản lý đơn hàng**: Xem chi tiết, duyệt thủ công, gửi lại thông tin tài khoản.
- **Quản lý nội dung**: Banner, Slider, Bài viết, Trang tĩnh.

---

## 🛠️ Cài Đặt & Sử Dụng

### Yêu cầu hệ thống
- PHP 8.2 trở lên
- Composer
- Node.js & NPM
- MySQL

## 📂 Cấu Trúc Dự Án

- `app/Http/Controllers`: Xử lý logic Backend (API & Inertia Responses).
- `app/Http/Middleware/LogVisitor.php`: Middleware tùy chỉnh để theo dõi lưu lượng truy cập.
- `resources/js/Pages`: Các Component trang Vue.js (Inertia views).
- `resources/js/Components/SeoHead.vue`: Component tái sử dụng để xử lý SEO.
- `routes/web.php`: Định nghĩa các tuyến đường (routes) của ứng dụng.

---

## 📬 Liên Hệ
- **Tác giả**: Ngô Anh Dũng
- **GitHub**: [github.com/nadungdev-stv](https://github.com/nadungdev-stv)
- **Email**: [nadungdev@gmail.com](mailto:nadungdev@gmail.com)
