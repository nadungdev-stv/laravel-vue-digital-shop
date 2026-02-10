const puppeteer = require('puppeteer');

(async () => {
    // Lấy URL từ tham số dòng lệnh
    const url = process.argv[2];
    if (!url) {
        console.error('Error: URL is required');
        process.exit(1);
    }

    let browser;
    try {
        // Khởi động trình duyệt
        browser = await puppeteer.launch({
            headless: "new", // Chạy ẩn
            dumpio: true,    // IN ERROR LOG CỦA CHROME RA MÀN HÌNH ĐỂ DEBUG
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                // '--single-process', // Tạm tắt để tránh lỗi treo trên một số VPS
                '--disable-gpu'
            ]
        });

        const page = await browser.newPage();

        // Giả lập User-Agent xịn để tránh bị chặn
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        // Set kích thước màn hình
        await page.setViewport({ width: 1366, height: 768 });

        // Truy cập URL với timeout 60s
        // Truy cập URL (nhanh hơn với domcontentloaded)
        await page.goto(url, {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        // Smart Wait: Chờ đến khi thấy mã 4 số hoặc text key (Max 10s)
        try {
            await page.waitForFunction(
                () => {
                    const text = document.body.innerText;
                    return /\b\d{4}\b/.test(text) ||
                        text.includes('Mã truy cập tạm thời') ||
                        text.includes('Temporary Access Code') ||
                        text.includes('Nhập mã này');
                },
                { timeout: 10000, polling: 100 }
            );
        } catch (e) {
            // Timeout -> Cứ lấy content hiện tại
        }

        // Lấy toàn bộ text trên trang
        const text = await page.evaluate(() => document.body.innerText);

        // In ra màn hình console (để PHP đọc)
        console.log(text);

    } catch (error) {
        console.error('Error:', error.message);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();
