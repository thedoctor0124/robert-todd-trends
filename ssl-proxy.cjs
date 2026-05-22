const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

const options = {
    key: fs.readFileSync(path.join(__dirname, '.certs/localhost+1-key.pem')),
    cert: fs.readFileSync(path.join(__dirname, '.certs/localhost+1.pem')),
    requestCert: false,
    rejectUnauthorized: false,
};

const server = https.createServer(options, (req, res) => {
    const proxyReq = http.request({
        hostname: '127.0.0.1',
        port: 8050,
        path: req.url,
        method: req.method,
        headers: {
            ...req.headers,
            host: req.headers.host,
            'x-forwarded-proto': 'https',
            'x-forwarded-for': req.socket.remoteAddress,
            'x-forwarded-port': '8443',
        },
    }, (proxyRes) => {
        res.writeHead(proxyRes.statusCode, proxyRes.headers);
        proxyRes.pipe(res);
    });

    proxyReq.on('error', (e) => {
        console.error('Proxy error:', e.message);
        res.writeHead(502);
        res.end('Proxy error: ' + e.message);
    });

    req.pipe(proxyReq);
});

server.maxHeaderSize = 16384;
server.listen(8443, '127.0.0.1', () => {
    console.log('HTTPS proxy running on https://127.0.0.1:8443 -> http://127.0.0.1:8050');
});
