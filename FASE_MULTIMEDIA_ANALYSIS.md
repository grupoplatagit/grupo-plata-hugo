# ANÁLISIS: FASE MULTIMEDIA - Recepción de Audios, Stickers, Imágenes

**Fecha:** 2026-08-03  
**Estado:** ANÁLISIS (pre-implementación)  
**Backup previo:** C:\Users\Juanc\Desktop\TRABAJOS\BACKUP API CLOUD DE HUGO KELLER

---

## 1. ESTADO ACTUAL DEL WEBHOOK

### Archivo: `public/api-whatsapp.php`

**Lo que hace:**
- ✅ GET: Verifica webhook con Meta
- ✅ POST: Recibe webhook JSON de Meta
- ✅ Procesa: `value['messages'][]` para mensajes de texto
- ✅ Procesa: `value['statuses'][]` para status updates (FASE 1)
- ❌ Ignora: `value['messages'][].type` si no es `text`

**Código actual de procesamiento de mensajes (líneas 75-97):**
```php
foreach ($value['messages'] ?? [] as $msg) {
    $from = $msg['from'] ?? '';
    $body = $msg['text']['body'] ?? '';  // ← Asume siempre text.body
    $wa_msg_id = $msg['id'] ?? '';
    
    // INSERT siempre con direction='in', wa_status='received'
    // NO verifica message type
}
```

**Problema identificado:**
- Si `$msg['type'] !== 'text'`, entonces `$msg['text']` no existe
- La BD intenta guardar `body = NULL`
- No se captura `media_id`, `mime_type`, etc.

---

## 2. ESTRUCTURA DE WA_MESSAGES ACTUAL

### Esquema real (necesito verificar):

**Columnas ESPERADAS:**
- `id` — PK autoincrement
- `lead_id` — FK nullable a leads
- `from_phone` — TEXT (número del remitente)
- `wa_msg_id` — TEXT UNIQUE (Meta wamid)
- `direction` — CHECK('in'/'out')
- `body` — TEXT NOT NULL
- `leido` — INTEGER DEFAULT 0
- `wa_status` — TEXT (sent/delivered/read/failed/received)
- `created_at` — TEXT

**Columnas QUE FALTA VERIFICAR SI EXISTEN:**
- `message_type` — (text, audio, image, sticker, document, video)
- `media_id` — (para descargar desde Meta)
- `mime_type` — (audio/ogg, image/jpeg, image/webp, etc)
- `file_name` — (para documentos)
- `caption` — (texto que acompaña imagen/doc/video)

---

## 3. ESTRUCTURA DE PAYLOADS MULTIMEDIA DE META

### 3.1 Mensaje de TEXTO (actual)
```json
{
  "messages": [{
    "from": "5491234567890",
    "id": "wamid.HBg...",
    "timestamp": "1234567890",
    "type": "text",
    "text": {
      "body": "Hola!"
    }
  }]
}
```

### 3.2 Mensaje de AUDIO / NOTA DE VOZ
```json
{
  "messages": [{
    "from": "5491234567890",
    "id": "wamid.HBg...",
    "timestamp": "1234567890",
    "type": "audio",
    "audio": {
      "mime_type": "audio/ogg",
      "id": "2168297...",
      "voice": true  // ← indica si es nota de voz
    }
  }]
}
```

**Datos a guardar:**
- `message_type = 'audio'`
- `media_id = '2168297...'`
- `mime_type = 'audio/ogg'`
- `body = '[Nota de voz]'` (placeholder)
- `caption = null`

### 3.3 Mensaje de STICKER
```json
{
  "messages": [{
    "from": "5491234567890",
    "id": "wamid.HBg...",
    "timestamp": "1234567890",
    "type": "sticker",
    "sticker": {
      "mime_type": "image/webp",
      "id": "2168297...",
      "animated": false  // o true para stickers animados
    }
  }]
}
```

**Datos a guardar:**
- `message_type = 'sticker'`
- `media_id = '2168297...'`
- `mime_type = 'image/webp'`
- `body = '[Sticker]'`

### 3.4 Mensaje de IMAGEN
```json
{
  "messages": [{
    "from": "5491234567890",
    "id": "wamid.HBg...",
    "timestamp": "1234567890",
    "type": "image",
    "image": {
      "mime_type": "image/jpeg",
      "id": "2168297...",
      "caption": "Foto del evento"  // opcional
    }
  }]
}
```

**Datos a guardar:**
- `message_type = 'image'`
- `media_id = '2168297...'`
- `mime_type = 'image/jpeg'`
- `caption = 'Foto del evento'`
- `body = '[Imagen]'`

### 3.5 Mensaje de DOCUMENTO
```json
{
  "messages": [{
    "from": "5491234567890",
    "id": "wamid.HBg...",
    "timestamp": "1234567890",
    "type": "document",
    "document": {
      "mime_type": "application/pdf",
      "id": "2168297...",
      "filename": "presupuesto.pdf",
      "caption": "Presupuesto Q3"  // opcional
    }
  }]
}
```

**Datos a guardar:**
- `message_type = 'document'`
- `media_id = '2168297...'`
- `mime_type = 'application/pdf'`
- `file_name = 'presupuesto.pdf'`
- `caption = 'Presupuesto Q3'`

### 3.6 Mensaje de VIDEO
```json
{
  "messages": [{
    "from": "5491234567890",
    "id": "wamid.HBg...",
    "type": "video",
    "video": {
      "mime_type": "video/mp4",
      "id": "2168297...",
      "caption": "Video de demostración"  // opcional
    }
  }]
}
```

---

## 4. MIGRACIÓN DE BD (ADITIVA, SIN ROMPER)

### 4.1 Verificar columnas existentes

**Comando SQL para inspeccionar:**
```sql
PRAGMA table_info(wa_messages);
```

### 4.2 Columnas a AGREGAR (si no existen)

```sql
ALTER TABLE wa_messages ADD COLUMN message_type TEXT DEFAULT 'text';
ALTER TABLE wa_messages ADD COLUMN media_id TEXT;
ALTER TABLE wa_messages ADD COLUMN mime_type TEXT;
ALTER TABLE wa_messages ADD COLUMN file_name TEXT;
ALTER TABLE wa_messages ADD COLUMN caption TEXT;
```

**NO modificar:**
- `id`, `lead_id`, `from_phone`, `wa_msg_id`, `direction`, `body`, `leido`, `wa_status`, `created_at`

---

## 5. MODIFICACIONES EN WEBHOOK

### 5.1 Cambios en `public/api-whatsapp.php`

**Nuevo flujo:**
```php
foreach ($value['messages'] ?? [] as $msg) {
    $msg_type = $msg['type'] ?? 'text';
    $wa_msg_id = $msg['id'] ?? '';
    $from = $msg['from'] ?? '';
    
    // Validar que tenga wamid
    if (!$wa_msg_id) continue;
    
    switch ($msg_type) {
        case 'text':
            $body = $msg['text']['body'] ?? '';
            // INSERT con message_type='text', media_id=null
            break;
            
        case 'audio':
            $media_id = $msg['audio']['id'] ?? '';
            $mime_type = $msg['audio']['mime_type'] ?? 'audio/ogg';
            $body = '[Nota de voz]';
            // INSERT con message_type='audio', media_id, mime_type
            break;
            
        case 'image':
            $media_id = $msg['image']['id'] ?? '';
            $mime_type = $msg['image']['mime_type'] ?? 'image/jpeg';
            $caption = $msg['image']['caption'] ?? null;
            $body = '[Imagen]';
            // INSERT con message_type='image', media_id, mime_type, caption
            break;
            
        case 'sticker':
            $media_id = $msg['sticker']['id'] ?? '';
            $mime_type = $msg['sticker']['mime_type'] ?? 'image/webp';
            $body = '[Sticker]';
            // INSERT con message_type='sticker', media_id, mime_type
            break;
            
        case 'document':
            $media_id = $msg['document']['id'] ?? '';
            $mime_type = $msg['document']['mime_type'] ?? 'application/octet-stream';
            $file_name = $msg['document']['filename'] ?? null;
            $caption = $msg['document']['caption'] ?? null;
            $body = '[Documento: ' . $file_name . ']';
            // INSERT con message_type='document', media_id, mime_type, file_name, caption
            break;
            
        case 'video':
            $media_id = $msg['video']['id'] ?? '';
            $mime_type = $msg['video']['mime_type'] ?? 'video/mp4';
            $caption = $msg['video']['caption'] ?? null;
            $body = '[Video]';
            // INSERT con message_type='video', media_id, mime_type, caption
            break;
            
        default:
            $body = '[Tipo de mensaje no soportado: ' . $msg_type . ']';
            $media_id = null;
            // INSERT con message_type desconocido
    }
    
    // Guardar en BD
    $db->prepare("
        INSERT INTO wa_messages
        (from_phone, wa_msg_id, direction, message_type, body, media_id, mime_type, file_name, caption, leido, wa_status, created_at)
        VALUES (?, ?, 'in', ?, ?, ?, ?, ?, ?, 0, 'received', datetime('now', 'localtime'))
    ")->execute([
        $from, $wa_msg_id, $msg_type, $body, $media_id, $mime_type, $file_name ?? null, $caption ?? null
    ]);
}
```

---

## 6. ENDPOINTS DE SEGURIDAD PARA MULTIMEDIA

### 6.1 Nuevo archivo: `admin/api/wa-media.php`

**Propósito:** Servir archivos multimedia de forma segura (solo usuarios autenticados)

**Flujo:**
```
GET /admin/api/wa-media.php?media_id=2168297...&type=audio
↓
1. Verificar que usuario esté autenticado (requireLogin)
2. Validar media_id en la BD (EXISTS en wa_messages)
3. Construir URL de descarga con Access Token del backend
4. Descargar desde Meta Graph API
5. Servir al navegador con Content-Type correcto
6. Opcional: cachear por N tiempo si es necesario
```

**Seguridad:**
- ✅ Autenticación required
- ✅ Token en backend, nunca en frontend
- ✅ Validación de media_id
- ✅ Control de Content-Type
- ✅ Logs de acceso

### 6.2 Endpoint Meta para obtener URL de descarga

```
GET https://graph.instagram.com/v18.0/{media_id}
    ?access_token={token}

Respuesta:
{
  "url": "https://media-...",
  "mime_type": "audio/ogg",
  "id": "2168297..."
}
```

---

## 7. ACTUALIZAR API DE MENSAJES

### 7.1 Modificar `admin/api/wa-messages.php`

**En respuesta de `action=messages`:**

```php
// Cambiar de retornar solo:
SELECT * FROM wa_messages

// A retornar específicamente:
SELECT 
    id, lead_id, from_phone, wa_msg_id, direction,
    body, leido, wa_status, created_at,
    message_type, media_id, mime_type, file_name, caption
FROM wa_messages
```

**El JSON respuesta debe incluir:**
```json
{
  "messages": [{
    "id": 123,
    "from_phone": "5491234567890",
    "body": "[Nota de voz]",
    "direction": "in",
    "wa_status": "received",
    "message_type": "audio",
    "media_id": "2168297...",
    "mime_type": "audio/ogg",
    "file_name": null,
    "caption": null,
    "created_at": "2026-08-03 15:30:00"
  }]
}
```

---

## 8. ACTUALIZAR FRONTEND: INBOX

### 8.1 Modificar `admin/pages/inbox.php`

**En función `appendMessage()` (línea ~496):**

```javascript
function appendMessage(m, list) {
    // ... verificaciones actuales ...
    
    // Cambiar renderizado según message_type
    let messageHTML = '';
    
    switch (m.message_type) {
        case 'text':
            messageHTML = `<div class="msg-bubble msg-${m.direction}">
                ${esc(m.body)}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;
            
        case 'audio':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <audio controls style="width:100%;max-width:280px;height:36px">
                    <source src="${ADMIN}/api/wa-media.php?media_id=${m.media_id}&type=audio" 
                            type="${m.mime_type || 'audio/ogg'}">
                    Tu navegador no soporta audio.
                </audio>
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;
            
        case 'image':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <img src="${ADMIN}/api/wa-media.php?media_id=${m.media_id}&type=image" 
                     style="max-width:280px;border-radius:8px;cursor:pointer"
                     onclick="openMediaModal('${m.media_id}', 'image', '${m.mime_type}')">
                ${m.caption ? `<div style="margin-top:6px;font-size:.85rem">${esc(m.caption)}</div>` : ''}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;
            
        case 'sticker':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media" style="background:transparent;border:none">
                <img src="${ADMIN}/api/wa-media.php?media_id=${m.media_id}&type=sticker" 
                     style="max-width:200px;height:auto">
                <div class="msg-time" style="margin-top:4px">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;
            
        case 'document':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <div style="display:flex;gap:10px;align-items:center">
                    <span style="font-size:1.5rem">📄</span>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:.85rem">${esc(m.file_name || 'Documento')}</div>
                        ${m.caption ? `<div style="font-size:.75rem;color:var(--muted)">${esc(m.caption)}</div>` : ''}
                    </div>
                    <a href="${ADMIN}/api/wa-media.php?media_id=${m.media_id}&type=document&download=1"
                       class="btn btn-sm" style="padding:4px 10px;font-size:.7rem">
                        ⬇️ Descargar
                    </a>
                </div>
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;
            
        case 'video':
            messageHTML = `<div class="msg-bubble msg-${m.direction} msg-media">
                <video controls style="width:100%;max-width:280px;border-radius:8px">
                    <source src="${ADMIN}/api/wa-media.php?media_id=${m.media_id}&type=video" 
                            type="${m.mime_type || 'video/mp4'}">
                    Tu navegador no soporta video.
                </video>
                ${m.caption ? `<div style="margin-top:6px;font-size:.85rem">${esc(m.caption)}</div>` : ''}
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
            break;
            
        default:
            messageHTML = `<div class="msg-bubble msg-${m.direction}">
                📎 Mensaje multimedia no compatible (${m.message_type})
                <div class="msg-time">${fmtTimeFull(m.created_at)}${tick}</div>
            </div>`;
    }
    
    wrap.innerHTML = messageHTML;
    list.appendChild(wrap);
}
```

---

## 9. DESCARGA DE MEDIA DESDE META

### 9.1 Implementar en `admin/api/wa-media.php`

**Pseudocódigo:**

```php
<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';

requireLogin(); // Solo usuarios autenticados

$media_id = $_GET['media_id'] ?? '';
$type = $_GET['type'] ?? '';
$download = $_GET['download'] ?? false;

if (!$media_id) die(http_response_code(400));

$db = getDB();

// Validar que el media_id existe en la BD
$msg = $db->prepare("SELECT * FROM wa_messages WHERE media_id = ?")
    ->execute([$media_id])
    ->fetch();

if (!$msg) die(http_response_code(404));

// Obtener token de la BD (NUNCA del frontend)
$token = getSetting($db, 'wa_token');

// Solicitar URL de descarga a Meta
$url = "https://graph.instagram.com/v18.0/{$media_id}";
$ch = curl_init($url . "?access_token={$token}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
$resp = curl_exec($ch);
$meta_data = json_decode($resp, true);
curl_close($ch);

if (!isset($meta_data['url'])) die(http_response_code(502));

// Descargar archivo desde Meta
$media_url = $meta_data['url'];
$ch = curl_init($media_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);
$file_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($file_content)) die(http_response_code(502));

// Servir al cliente
header('Content-Type: ' . ($msg['mime_type'] ?: 'application/octet-stream'));
if ($download) {
    header('Content-Disposition: attachment; filename="' . ($msg['file_name'] ?: 'media') . '"');
}
echo $file_content;
exit;
```

---

## 10. COMPATIBILIDAD DE FORMATOS

### 10.1 Audio

**Formatos que devuelve Meta:**
- `audio/ogg` (notas de voz, codec Opus)
- `audio/mp4` (audios)
- `audio/aac`

**Navegadores compatibles:**
- Chrome, Firefox, Safari: soportan `audio/ogg`
- HTML5 `<audio>` maneja la mayoría

### 10.2 Stickers

**Formato:**
- `image/webp` (estático y animado)

**Navegadores:**
- Chrome, Edge, Firefox: soportan WebP nativo
- Safari 16+: soportan WebP

**Si falla WebP:**
- Mostrar ícono de fallback
- No romper conversación

### 10.3 Imágenes

**Formatos esperados:**
- `image/jpeg`
- `image/png`
- `image/webp`

**Todos soportados en HTML5 `<img>`**

### 10.4 Video

**Formatos:**
- `video/mp4` (H.264)
- `video/quicktime`

**Navegadores:**
- HTML5 `<video>` soporta mp4 nativamente

---

## 11. ARCHIVOS A MODIFICAR

| Archivo | Cambio | Complejidad |
|---------|--------|-------------|
| `public/api-whatsapp.php` | Agregar procesamiento de tipos multimedia | MEDIA |
| `admin/api/wa-messages.php` | Incluir campos multimedia en respuesta | BAJA |
| `admin/api/wa-media.php` | **CREAR** — Servir archivos con seguridad | ALTA |
| `admin/pages/inbox.php` | Renderizar según `message_type` | MEDIA |
| `app/db.php` | Agregar columnas (migración aditiva) | BAJA |

---

## 12. RESUMEN DE COLUMNAS A AGREGAR

```sql
ALTER TABLE wa_messages ADD COLUMN message_type TEXT DEFAULT 'text';
ALTER TABLE wa_messages ADD COLUMN media_id TEXT;
ALTER TABLE wa_messages ADD COLUMN mime_type TEXT;
ALTER TABLE wa_messages ADD COLUMN file_name TEXT;
ALTER TABLE wa_messages ADD COLUMN caption TEXT;
```

**Total: 5 columnas nuevas**
**Tipo: Aditivas, no destructivas**

---

## 13. PLAN DE PRUEBAS

### 13.1 Prueba 1: Mensajes de texto (regresión)
- WhatsApp → Texto
- Esperado: Sigue funcionando, sin cambios

### 13.2 Prueba 2: Notas de voz
- WhatsApp → Nota de voz
- Esperado: Aparece reproductor HTML5, se escucha

### 13.3 Prueba 3: Sticker
- WhatsApp → Sticker
- Esperado: Se ve la imagen WebP

### 13.4 Prueba 4: Imagen
- WhatsApp → Foto
- Esperado: Thumbnail, clickeable para agrandar

### 13.5 Prueba 5: Documento
- WhatsApp → PDF/ZIP
- Esperado: Tarjeta de archivo con botón descargar

### 13.6 Prueba 6: Video
- WhatsApp → Video corto
- Esperado: Reproductor HTML5

### 13.7 Prueba 7: Status updates
- Enviar texto desde CRM
- Esperado: Status ✓ → ✓✓ → ✓✓ azul (Fase 1 sigue funcionando)

---

## 14. RIESGOS IDENTIFICADOS

| Riesgo | Severidad | Mitigación |
|--------|-----------|-----------|
| Formato WebP no soportado en Safari <16 | BAJA | Mostrar fallback con ícono |
| Meta devuelve URL que expira | MEDIA | Usar media_id, no URL temporal |
| Media grande consume ancho de banda | MEDIA | Caché simple en servidor |
| Timeout descargando de Meta | MEDIA | Timeout 30s, reintentos |
| Access Token expuesto accidentalmente | CRÍTICA | Validación: nunca en HTML/JS |

---

## 15. CONFIRMACIÓN NECESARIA

Antes de implementar, confirma:

- [ ] Estructura de payloads multimedia entendida
- [ ] Plan de 5 columnas nuevas aprobado
- [ ] Seguridad de Access Token está clara
- [ ] Renderizado según message_type es correcto
- [ ] Compatibilidad de formatos es aceptable
- [ ] Plan de pruebas es realista

**NO IMPLEMENTAR** hasta que confirmes.
