<?php
$pageTitle = 'Không tìm thấy trang';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Custom Style for 404 Page (Isolated) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Reset/Defaults for this page specifics */
    :root {
        --error-bg: #ffffff;
        --error-card-bg: #ffffff;
        --error-text: #1d1d1f;
        --error-text-secondary: #86868b;
        --error-primary: #0071e3;
        --error-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
    }

    /* Apply background only to the main container area to avoid affecting header/footer */
    body {
        background-color: var(--error-bg);
    }
    
    .error-container {
        font-family: var(--error-font);
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    
    
    .error-card {
        /* Removed card styles */
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
    }

    .error-code {
        font-size: 8rem;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1;
        margin-bottom: 10px;
        letter-spacing: -4px;
    }

    .error-title {
        font-size: 2rem;
        font-weight: 600;
        color: var(--error-text);
        margin-bottom: 20px;
        letter-spacing: -0.5px;
    }

    .error-desc {
        color: var(--error-text-secondary);
        font-size: 1.1rem;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .action-buttons .btn {
        height: 54px;
        padding: 0 35px; /* Adjust padding for fixed height */
        border-radius: 50px;
        font-weight: 500;
        font-size: 1.05rem; /* Match input font size */
        margin: 0 8px; /* Slightly more space */
        transition: all 0.3s ease;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 180px; /* Ensure buttons have substance */
    }

    .btn-home {
        background: var(--error-primary);
        color: white;
        box-shadow: 0 4px 10px rgba(0, 113, 227, 0.2); /* Subtle shadow always */
    }

    .btn-home:hover {
        background: #0077ed;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
        transform: none !important;
    }

    .btn-contact {
        background: white;
        border-color: #d2d2d7 !important;
        color: var(--error-text);
    }

    .btn-contact:hover {
        background: #f5f5f7;
        border-color: var(--error-primary) !important;
        color: var(--error-primary);
        transform: none !important;
    }
    
    /* Animation */
    .fade-in-up {
        animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    /* Search Bar Styling */
    .search-input-group {
        border-radius: 50px; 
        overflow: hidden; 
        border: 1px solid #e5e5ea; /* Apple border color */
        background: white;
        transition: all 0.3s ease;
        height: 54px; /* Fixed height for consistency */
        display: flex;
        align-items: center;
    }
    
    .search-input-group:focus-within {
        border-color: var(--error-primary);
        box-shadow: 0 4px 12px rgba(0, 113, 227, 0.15);
    }
    
    .search-icon-wrapper {
        padding-left: 20px;
        padding-right: 15px;
        color: var(--error-text-secondary);
        display: flex;
        align-items: center;
        height: 100%;
    }
    
    .search-input {
        border: none;
        outline: none;
        height: 100%;
        padding: 0;
        font-size: 1.05rem;
        color: var(--error-text);
        flex-grow: 1;
        background: transparent;
    }
    
    .search-input::placeholder {
        color: #86868b;
        font-weight: 400;
    }
    
    .search-btn {
        height: 46px; /* Slightly smaller than container for margin */
        width: 46px;
        border-radius: 50% !important;
        margin-right: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--error-primary);
        border: none;
        color: white;
        transition: all 0.2s ease;
    }
    
    .search-btn:hover {
        background: #0077ed;
        transform: scale(1.05);
    }
</style>

<div class="container error-container">
    <div class="text-center w-100">
        <div class="mb-4">
            <div class="error-code">404</div>
        </div>
        
        <h2 class="error-title">Oops! Không tìm thấy trang</h2>
        <p class="error-desc">
            Có vẻ như trang bạn đang tìm kiếm không tồn tại, đã bị xóa hoặc đường dẫn không chính xác.
        </p>
        
        <div class="action-buttons mb-5">
            <a href="/" class="btn btn-home">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
            <a href="/contact.php" class="btn btn-contact">
                <i class="fas fa-headset me-2"></i>Hỗ trợ
            </a>
        </div>
        
        <div class="d-flex justify-content-center">
            <form action="/products" method="GET" class="d-flex w-100" style="max-width: 480px;">
                <div class="search-input-group shadow-sm w-100">
                    <span class="search-icon-wrapper">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="q" class="search-input" placeholder="Tìm kiếm sản phẩm, dịch vụ...">
                    <button class="search-btn" type="submit">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
