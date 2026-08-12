// Service Worker para notificaciones en background
const CACHE_NAME = 'grupo-plata-v1';

self.addEventListener('install', (e) => {
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(clients.claim());
});

// Polling en background cada 5 segundos
setInterval(async () => {
    try {
        const response = await fetch('/admin/api/wa-messages.php?action=conversations');
        const data = await response.json();
        const conversations = data.data || [];

        // Contar mensajes sin leer
        const unreadCount = conversations.reduce((sum, c) => sum + parseInt(c.unread || 0), 0);

        // Guardar el contador anterior en storage
        const cacheData = await caches.open(CACHE_NAME);
        const prevData = await cacheData.match('unread-count');
        const prevCount = prevData ? parseInt(await prevData.text()) : 0;

        // Si hay nuevos mensajes sin leer, notificar
        if (prevCount > 0 && unreadCount > prevCount) {
            const convWithUnread = conversations.find(c => parseInt(c.unread || 0) > 0);
            if (convWithUnread) {
                self.registration.showNotification('📱 Nuevo mensaje', {
                    body: (convWithUnread.nombre || 'Nuevo contacto') + ': ' + (convWithUnread.last_msg || ''),
                    icon: '/public/assets/img/favicon.ico',
                    badge: '/public/assets/img/favicon.ico',
                    tag: 'wa-notification',
                    requireInteraction: false
                });

                // Reproducir sonido notificación
                notifyClients('newMessage', { nombre: convWithUnread.nombre, msg: convWithUnread.last_msg });
            }
        }

        // Guardar contador actual
        const response2 = new Response(unreadCount.toString());
        await cacheData.put('unread-count', response2);
    } catch (e) {
        console.log('Service Worker poll error:', e);
    }
}, 5000);

// Notificar a los clientes activos
function notifyClients(type, data) {
    clients.matchAll({ type: 'window' }).then(clientList => {
        clientList.forEach(client => {
            client.postMessage({ type, data });
        });
    });
}

// Escuchar mensajes del cliente
self.addEventListener('message', (e) => {
    if (e.data && e.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
