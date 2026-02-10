const path = require('path');

/**
 * @type {import("puppeteer").Configuration}
 */
module.exports = {
    // Config lưu Chrome ngay tại thư mục hiện tại thay vì thư mục user
    cacheDirectory: path.join(__dirname, '.cache'),
};
