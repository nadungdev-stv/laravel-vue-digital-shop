/**
 * Netflix Auto-Approval Worker
 * 
 * Daemon worker chạy liên tục để poll database và tự động approve
 * Netflix household confirmation requests
 */

const puppeteer = require('puppeteer');
const mysql = require('mysql2/promise');

// Database configuration
const dbConfig = {
    host: 'localhost',
    user: 'root',      // Thay bằng MySQL user của bạn
    password: '',      // Thay bằng MySQL password của bạn
    database: 'webshop' // Thay bằng tên database của bạn
};

// Logging function
function log(message, level = 'INFO') {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] [${level}] ${message}`);
}

// Main approval function
async function approveNetflixHousehold(link, approvalId) {
    let browser = null;

    try {
        log(`Starting approval process for ID: ${approvalId}`);

        // Launch Puppeteer browser
        browser = await puppeteer.launch({
            headless: true, // Chạy ẩn (không hiển thị browser)
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ]
        });

        const page = await browser.newPage();

        // Set viewport và user agent
        await page.setViewport({ width: 1920, height: 1080 });
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        log(`Navigating to: ${link}`);

        // Navigate đến approval URL
        await page.goto(link, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        // Đợi button xuất hiện
        log('Waiting for approval button...');
        await page.waitForSelector('button[data-uia="set-primary-location-action"]', {
            timeout: 15000
        });

        // Click button "Xác nhận cập nhật"
        log('Clicking approval button...');
        await page.click('button[data-uia="set-primary-location-action"]');

        // Đợi navigation hoặc success message
        await Promise.race([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }),
            page.waitForSelector('.success-message', { timeout: 10000 }).catch(() => { })
        ]);

        // Take screenshot để verify (optional)
        // await page.screenshot({ path: `logs/approval-${approvalId}.png` });

        log(`Approval SUCCESS for ID: ${approvalId}`, 'SUCCESS');
        return { success: true };

    } catch (error) {
        log(`Approval FAILED for ID: ${approvalId} - ${error.message}`, 'ERROR');
        return { success: false, error: error.message };

    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

// Worker loop - poll database và xử lý
async function workerLoop() {
    let connection = null;

    try {
        // Kết nối MySQL
        connection = await mysql.createConnection(dbConfig);

        log('Worker started - polling for pending approvals...');

        while (true) {
            try {
                // Query pending approvals
                const [rows] = await connection.execute(
                    `SELECT id, link, attempts 
           FROM pending_approvals 
           WHERE status = 'pending' 
           AND attempts < 3 
           ORDER BY created_at ASC 
           LIMIT 5`
                );

                if (rows.length > 0) {
                    log(`Found ${rows.length} pending approval(s)`);

                    // Process từng approval
                    for (const row of rows) {
                        // Update attempts
                        await connection.execute(
                            'UPDATE pending_approvals SET attempts = attempts + 1 WHERE id = ?',
                            [row.id]
                        );

                        // Approve
                        const result = await approveNetflixHousehold(row.link, row.id);

                        if (result.success) {
                            // Update status = approved
                            await connection.execute(
                                'UPDATE pending_approvals SET status = ?, approved_at = NOW() WHERE id = ?',
                                ['approved', row.id]
                            );
                            log(`Approval completed for ID: ${row.id}`, 'SUCCESS');

                        } else {
                            // Check if max attempts reached
                            if (row.attempts + 1 >= 3) {
                                await connection.execute(
                                    'UPDATE pending_approvals SET status = ?, error_message = ? WHERE id = ?',
                                    ['failed', result.error, row.id]
                                );
                                log(`Max attempts reached for ID: ${row.id} - marking as failed`, 'WARNING');
                            } else {
                                await connection.execute(
                                    'UPDATE pending_approvals SET error_message = ? WHERE id = ?',
                                    [result.error, row.id]
                                );
                            }
                        }
                    }
                }

                // Sleep 1 giây trước khi poll tiếp
                await new Promise(resolve => setTimeout(resolve, 1000));

            } catch (error) {
                log(`Error in worker loop: ${error.message}`, 'ERROR');
                await new Promise(resolve => setTimeout(resolve, 5000)); // Sleep 5s nếu có lỗi
            }
        }

    } catch (error) {
        log(`Fatal error: ${error.message}`, 'ERROR');
        process.exit(1);

    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

// Graceful shutdown
process.on('SIGINT', () => {
    log('Received SIGINT - shutting down gracefully...', 'INFO');
    process.exit(0);
});

process.on('SIGTERM', () => {
    log('Received SIGTERM - shutting down gracefully...', 'INFO');
    process.exit(0);
});

// Start worker
workerLoop().catch(error => {
    log(`Worker crashed: ${error.message}`, 'ERROR');
    process.exit(1);
});
