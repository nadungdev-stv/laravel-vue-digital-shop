<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Lỗi hệ thống</title>
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
        .error-content { text-align: center; max-width: 500px; padding: 40px 20px; }
        .error-icon {
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, #FF3B30 0%, #FF6B6B 100%);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 32px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        .error-icon i { font-size: 48px; color: #fff; }
        .error-code { font-size: 96px; font-weight: 800; color: #FF3B30; line-height: 1; margin-bottom: 12px; }
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
    </style>
</head>
<body>
    <div class="error-content">
        <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Lỗi hệ thống</h2>
        <p class="error-desc">Đã xảy ra lỗi trên máy chủ. Vui lòng thử lại sau.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/" class="btn-home"><i class="fas fa-home"></i> Về trang chủ</a>
        </div>
    </div>
</body>
</html>
