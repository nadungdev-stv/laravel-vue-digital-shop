<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Không tìm thấy trang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-content { text-align: center; max-width: 520px; padding: 40px 20px; }
        .error-icon {
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 32px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        .error-icon i { font-size: 48px; color: #fff; }
        .error-code { font-size: 96px; font-weight: 800; color: #007AFF; line-height: 1; margin-bottom: 12px; }
        .error-title { font-size: 28px; font-weight: 700; color: #1a1a2e; margin-bottom: 12px; }
        .error-desc { font-size: 16px; color: #6c757d; margin-bottom: 32px; }
        .btn-home {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; background: linear-gradient(135deg, #007AFF, #5856D6);
            color: #fff; border: none; border-radius: 50px; font-size: 15px;
            font-weight: 600; text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,122,255,0.3); transition: all 0.3s;
        }
        .btn-home:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,122,255,0.4); color: #fff; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; background: #fff; color: #333;
            border: 2px solid #e0e0e0; border-radius: 50px; font-size: 15px;
            font-weight: 600; text-decoration: none; transition: all 0.3s;
        }
        .btn-back:hover { border-color: #007AFF; color: #007AFF; transform: translateY(-2px); }
        .search-box {
            background: #fff; border-radius: 16px; padding: 24px; margin-top: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e8e8e8;
        }
        .search-box p { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 14px; }
        .search-wrap {
            display: flex; align-items: center; background: #f5f7fa;
            border-radius: 12px; border: 2px solid transparent;
            transition: all 0.3s; overflow: hidden;
        }
        .search-wrap:focus-within { border-color: #007AFF; background: #fff; box-shadow: 0 0 0 4px rgba(0,122,255,0.1); }
        .search-wrap i { padding: 0 0 0 16px; color: #999; }
        .search-wrap:focus-within i { color: #007AFF; }
        .search-wrap input {
            flex: 1; border: none; background: transparent; padding: 14px 12px;
            font-size: 15px; color: #333; outline: none;
        }
        .search-wrap button {
            padding: 10px 20px; margin: 4px; background: linear-gradient(135deg, #007AFF, #5856D6);
            color: #fff; border: none; border-radius: 10px; font-size: 14px;
            font-weight: 600; cursor: pointer; white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="error-content">
        <div class="error-icon"><i class="fas fa-compass"></i></div>
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Không tìm thấy trang</h2>
        <p class="error-desc">Trang bạn đang tìm không tồn tại hoặc đã bị di chuyển.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/" class="btn-home"><i class="fas fa-home"></i> Về trang chủ</a>
            <a href="javascript:history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
        <div class="search-box">
            <p>Tìm sản phẩm bạn muốn</p>
            <form action="/products" method="GET">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Nhập tên sản phẩm, ví dụ: Netflix, Spotify, VPN..." autofocus>
                    <button type="submit">Tìm kiếm</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
