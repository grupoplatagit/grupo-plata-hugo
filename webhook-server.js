const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');
const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());

// GET - Webhook verification (Meta)
app.get('/webhook', (req, res) => {
    const mode = req.query['hub.mode'];
    const token = req.query['hub.verify_token'];
    const challenge = req.query['hub.challenge'];

    console.log(`[${new Date().toISOString()}] GET verification: mode=${mode}, token=${token}`);

    const verifyToken = process.env.WEBHOOK_TOKEN || 'grupo_plata_verify_2024';

    if (mode === 'subscribe' && token === verifyToken) {
        console.log('✅ Webhook verified');
        res.status(200).send(challenge);
    } else {
        console.log('❌ Webhook verification failed');
        res.status(403).send('Forbidden');
    }
});

// POST - Incoming messages
app.post('/webhook', (req, res) => {
    const data = req.body;

    console.log(`[${new Date().toISOString()}] POST received:`, JSON.stringify(data).substring(0, 300));

    if (data.object === 'whatsapp_business_account') {
        data.entry?.forEach(entry => {
            entry.changes?.forEach(change => {
                const value = change.value || {};

                // Log messages
                value.messages?.forEach(msg => {
                    const from = msg.from;
                    const body = msg.text?.body || `[${msg.type}]`;
                    console.log(`📨 Message from ${from}: ${body}`);
                });

                // Log status updates
                value.statuses?.forEach(status => {
                    console.log(`📊 Status update: ${status.status}`);
                });
            });
        });
    }

    res.status(200).send('OK');
});

// Health check
app.get('/health', (req, res) => {
    res.status(200).json({ status: 'ok', timestamp: new Date().toISOString() });
});

// ✅ Proxy para PHP (todo lo demás va al sitio PHP)
// Redirige todas las otras peticiones al servidor PHP en Hostinger
app.use('/', createProxyMiddleware({
    target: 'https://tan-spider-523924.hostingersite.com',
    changeOrigin: true,
    pathRewrite: { '^/': '/' },
    onProxyReq: (proxyReq, req, res) => {
        // Mantén el host original para que PHP detecte grupoplatasf.com
        proxyReq.setHeader('Host', req.get('host'));
        proxyReq.setHeader('X-Forwarded-Host', req.get('host'));
        proxyReq.setHeader('X-Forwarded-Proto', 'https');
    },
    onError: (err, req, res) => {
        console.error('Proxy error:', err);
        res.status(503).send('Service Unavailable');
    }
}));

app.listen(PORT, () => {
    console.log(`🚀 Webhook server running on port ${PORT}`);
    console.log(`📍 Webhook URL: http://localhost:${PORT}/webhook`);
    console.log(`🔐 Webhook Token: ${process.env.WEBHOOK_TOKEN || 'grupo_plata_verify_2024'}`);
    console.log(`🌐 Proxy enabled for PHP app`);
});
