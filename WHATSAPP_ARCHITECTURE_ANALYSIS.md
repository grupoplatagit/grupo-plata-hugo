# Análisis de Arquitectura WhatsApp - Grupo Plata

## 1. ARQUITECTURA ACTUAL DETECTADA

### 1.1 Flujo Técnico Actual
```
Cliente WhatsApp
    ↓ (SMS entra)
Meta WhatsApp Cloud API (1252695447922656)
    ↓ (POST JSON)
Webhook: /public/api-whatsapp.php
    ↓ (Verifica Meta challenge, procesa entrada)
SQLite: wa_messages + wa_contacts
    ↓ (Almacena mensaje)
Frontend AJAX: /admin/pages/inbox.php
    ↓ (Carga via /admin/api/wa-messages.php?action=conversations)
Asesor ve conversación
    ↓ (Escribe respuesta)
POST a /admin/api/wa-messages.php (send)
    ↓ (Llama sendWAMessage())
Meta WhatsApp Cloud API
    ↓ (POST al endpoint /messages)
Cliente recibe SMS
```

### 1.2 Componentes Principales

**Webhook (Entrada)**
- Archivo: `public/api-whatsapp.php`
- Método: GET (verificación) + POST (mensajes)
- Protocolo: HTTP (sin autenticación en el webhook mismo, Meta autentica con URL)
- Acceso: Público (necesario para Meta)
- Comportamiento:
  - GET: Responde con `hub.challenge` si `hub.mode === 'subscribe'`
  - POST: Recibe eventos de Meta, parsea mensajes, guarda en `wa_messages`
  - Maneja: incoming messages, status updates
  - NO verifica wa_token en webhook (correcto para que Meta pueda acceder)

**API Interno (Obtener/Enviar)**
- Archivo: `admin/api/wa-messages.php`
- Métodos: GET (conversaciones, mensajes) + POST (enviar)
- Autenticación: requireLogin() - solo usuarios autenticados
- Acciones:
  - `leads_with_phone`: lista leads con teléfono
  - `conversations`: obtiene lista de conversaciones (UNION de leads + unknowns)
  - `messages`: obtiene thread de un lead/phone, marca como leído
  - POST: envía mensaje de texto o plantilla

**Envío (Outgoing)**
- Archivo: `admin/api/wa-send.php`
- Usa función: `sendWAMessage($token, $phoneId, $phone, $message)`
- Endpoint Meta utilizado: `/messages` en `https://graph.instagram.com/v18.0/`
- Credenciales: `wa_token` y `wa_phone_id` de settings

**Configuración de Credenciales**
- Ubicación 1: `settings` tabla (wa_token, wa_phone_id, wa_waba_id, wa_app_secret, wa_webhook_token)
- Ubicación 2: `local.php` (opcional, para desarrollo/privacidad)
- Acceso: `getSetting($db, 'wa_token')`

**Interfaz (Inbox)**
- Archivo: `admin/pages/inbox.php`
- Frontend: JavaScript vanilla
- Actualización: Manual (recarga de página o click)
- No hay polling/WebSockets actualmente

---

## 2. QUÉ YA EXISTE (Funcionando)

✅ **Recepción bidireccional**
- Webhook recibe mensajes de Meta
- Mensajes se guardan en wa_messages
- Inbox muestra conversaciones

✅ **Envío funcionando**
- CRM puede enviar mensajes de texto
- Mensajes se registran en wa_messages
- Cliente recibe SMS correctamente

✅ **Gestión de contactos básica**
- Tabla `wa_contacts` con phone, lead_id, wa_name, label, notes
- Integración con leads (se linkean por teléfono)
- Labels: nuevo, potencial, calificado, agendado, cerrado, descartado

✅ **Almacenamiento de mensajes**
- Tabla `wa_messages` con estructura completa
- Campos: id, lead_id, from_phone, wa_msg_id, direction, body, leido, wa_status, created_at
- wa_status actual: solo 'received' o 'sent'
- Soporta UNIQUE wa_msg_id (previene duplicados)

✅ **Autenticación de panel**
- Inbox solo accesible con login
- API wa-messages.php requiere autenticación
- Protección contra acceso no autorizado

✅ **Auto-envío**
- Envío automático de mensajes a leads pendientes cada N minutos
- Archivo: `admin/api/wa-send.php` (modo cron)

✅ **Webhooks status updates**
- Meta envía cambios de estado (sent, delivered, read)
- Webhook recibe en POST

---

## 3. QUÉ FALTA (Brecha Técnica)

❌ **Estado de mensajes incompleto**
- wa_status almacena solo 'received' o 'sent'
- NO actualiza cuando Meta envía: 'delivered', 'read', 'failed'
- El webhook RECIBE estos eventos pero no los procesa
- Necesario: parsear value.statuses[] y actualizar wa_messages.wa_status

❌ **Actualización automática (Inbox)**
- Inbox NO recarga automáticamente
- Usuario debe recargar página para ver nuevos mensajes
- No hay polling ni WebSockets
- Se pierden actualizaciones en tiempo real

❌ **Identificación única de contacto**
- Si contacto no está en `leads`, se guarda con lead_id=NULL
- Problema: no hay tabla de "conversaciones" explícita
- Inbox une resultados de leads + wa_messages sin lead_id
- Riesgo: duplicados si mismo phone aparece en múltiples leads

❌ **Información del contacto en webhook**
- Meta envía wa_id, pero no siempre envía nombre del perfil
- Si Meta envía profile_name en metadata, no se captura
- wa_name solo se obtiene de wa_contacts (manual o Lead)

❌ **Multimedia**
- No soporta imágenes, videos, audios, documentos
- Webhook solo procesa text messages
- No hay descarga de media

❌ **Plantillas aprobadas**
- Código tiene stub para sendWATemplate() pero no validación
- No hay verificación de ventana de atención de Meta
- No advierte al asesor si está fuera de ventana

❌ **Asignación multiusuario**
- No hay assigned_user_id en wa_contacts
- Todos los asesores ven todas las conversaciones
- No se registra quién envió cada mensaje

❌ **Notificaciones/Badges**
- No hay contador no leído en badge de Inbox
- No hay sonido/alerta de nuevo mensaje
- No hay indicador visual de prioridad

❌ **Logs y debugging**
- Webhook registra en `/logs/whatsapp-webhook.log` (bien)
- Pero sin estandarización: falta message_id, errores parciales
- No hay rotación de logs

---

## 4. PROBLEMAS/RIESGOS DETECTADOS

### 🔴 CRÍTICOS

**1. Duplicados por reintentos de webhook**
- Meta reintenta webhooks 3 veces en 30 seg si no recibe 200
- Actualmente: se detectan por wa_msg_id UNIQUE
- ✅ MITIGADO: constraint UNIQUE evita inserción
- Pero: si mismo message_id viene 3 veces, solo entra 1
- Riesgo: bajo si wa_msg_id siempre está presente

**2. Contactos sin lead duplicados**
- Mismo teléfono externo puede aparecer en múltiples filas
- La query UNION intenta deduplicar pero agrupación puede fallar
- Riesgo: se muestren mismo contacto 2+ veces en lista

**3. Access Token en plain text**
- `wa_token` se almacena en SQLite sin encripción
- `local.php` tiene token visible
- Si alguien accede a BD, tiene credencial completa
- Riesgo: alto si BD se compromete

### 🟡 MODERADOS

**4. Status updates no se procesan**
- Webhook recibe value.statuses[] pero código no lo procesa
- wa_status nunca se actualiza a 'delivered' o 'read'
- Asesor no ve si mensaje llegó o fue leído
- Riesgo: experiencia pobre, confusión

**5. Sin actualización automática**
- Asesor debe recargar para ver nuevos mensajes
- Es 2024 y no hay polling/WebSockets
- Riesgo: UX muy atrasada, productividad baja

**6. Ventana de atención no validada**
- Meta permite mensajes libres solo en ventana de atención
- Si se intenta fuera de ventana, falla (HTTP 400-403)
- Código no previene ni advierte
- Asesor recibe error confuso
- Riesgo: confusión, creencia de que mensajes no se enviaron

### 🟢 BAJOS

**7. Formato de phone no normalizado**
- `normalizeWAPhone()` se usa a veces pero no siempre
- from_phone en webhook puede variar (con/sin +, con/sin 54)
- Riesgo: dificultad de matching, duplicados en BD

---

## 5. MIGRACIONES DE BD REQUERIDAS

### 5.1 Tabla wa_messages (CAMBIOS)

Agregar columna (sin afectar datos existentes):
```sql
ALTER TABLE wa_messages ADD COLUMN message_type TEXT DEFAULT 'text';
-- Para soportar 'text', 'image', 'audio', 'document', 'video', etc.
```

**Opcionales (Fase Multimedia):**
```sql
ALTER TABLE wa_messages ADD COLUMN media_id TEXT;
-- ID de Meta para descargar el archivo

ALTER TABLE wa_messages ADD COLUMN media_url TEXT;
-- URL segura para servir el media

ALTER TABLE wa_messages ADD COLUMN mime_type TEXT;
-- Para renderizar correctamente (image/jpeg, audio/ogg, etc)

ALTER TABLE wa_messages ADD COLUMN file_name TEXT;
-- Nombre del archivo original si existe
```

### 5.2 Tabla wa_contacts (CAMBIOS)

Agregar columnas:
```sql
ALTER TABLE wa_contacts ADD COLUMN assigned_user_id INTEGER;
-- Referencia a admin que atiende

ALTER TABLE wa_contacts ADD COLUMN first_message_at TEXT;
-- Cuándo escribió por primera vez

ALTER TABLE wa_contacts ADD COLUMN last_message_at TEXT;
-- Cuándo fue el último intercambio

ALTER TABLE wa_contacts ADD COLUMN unread_count INTEGER DEFAULT 0;
-- Para badge

ALTER TABLE wa_contacts ADD COLUMN is_hidden INTEGER DEFAULT 0;
-- Para archivar conversaciones
```

### 5.3 Nueva tabla: wa_conversations (OPCIONAL pero RECOMENDADO)

Actualmente conversaciones se derivan de leads + wa_messages.
Agregar tabla explícita resuelve duplicados:

```sql
CREATE TABLE wa_conversations (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    from_phone          TEXT NOT NULL UNIQUE,
    lead_id             INTEGER,
    wa_name             TEXT,
    assigned_user_id    INTEGER,
    label               TEXT DEFAULT 'nuevo',
    first_message_at    TEXT,
    last_message_at     TEXT,
    unread_count        INTEGER DEFAULT 0,
    is_archived         INTEGER DEFAULT 0,
    created_at          TEXT DEFAULT (datetime('now','localtime')),
    updated_at          TEXT DEFAULT (datetime('now','localtime'))
);
```

Cambios en wa_messages: agregar `conversation_id` en lugar de `from_phone` directo.

**Impacto migratorio:** moderado, requiere migrar datos de wa_messages/leads

### 5.4 Nueva tabla: wa_message_status_log (AUDITORÍA)

Para rastrear cambios de estado:
```sql
CREATE TABLE wa_message_status_log (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    wa_message_id   INTEGER NOT NULL,
    old_status      TEXT,
    new_status      TEXT,
    updated_at      TEXT DEFAULT (datetime('now','localtime'))
);
```

**Impacto:** bajo, es append-only

---

## 6. ARCHIVOS A MODIFICAR POR FASE

### FASE 1: Estabilizar infraestructura actual
- `public/api-whatsapp.php` — agregar procesamiento de value.statuses[]
- `admin/api/wa-messages.php` — agregar soporte de status update

### FASE 2: Conversaciones y contactos
- `admin/api/wa-messages.php` — reescribir query para evitar duplicados
- `app/db.php` — agregar columnas a wa_contacts
- Considerar: crear wa_conversations tabla

### FASE 3: Mensajes mejorados
- `admin/pages/inbox.php` — mostrar ✓, ✓✓, ✓✓ en UI
- `admin/api/wa-messages.php` — retornar wa_status en messages[]

### FASE 4: Actualización automática
- `admin/pages/inbox.php` — agregar polling AJAX cada 3-5 seg
- `admin/api/wa-messages.php` — agregar ?since=ID para cambios incremental

### FASE 5: Multimedia
- `public/api-whatsapp.php` — parsear value.messages[].image/audio/document
- `app/functions.php` — agregar sendWAMedia()
- `admin/pages/inbox.php` — renderizar media en chat

### FASE 6: Envío desde CRM
- `admin/api/wa-messages.php` — agregar endpoint de upload de media

### FASE 7: Gestión comercial
- `admin/pages/inbox.php` — agregar botón "Convertir a Lead"
- `admin/api/wa-messages.php` — crear Lead desde contacto

### FASE 8: Multiusuario
- `app/db.php` — agregar assigned_user_id
- `admin/pages/inbox.php` — filtros "Mis conversaciones"
- `admin/api/wa-messages.php` — registrar user_id en outgoing

### FASE 9: Notificaciones
- `admin/pages/inbox.php` — agregar badge contador
- `admin/api/wa-messages.php` — retornar unread_count
- Considerar: notificación de sonido en JS

### FASE 10: Seguridad y robustez
- `app/functions.php` — encriptar wa_token en reposo (opcional)
- `public/api-whatsapp.php` — agregar logging estandarizado
- `admin/api/wa-messages.php` — auditar todos los inputs

### FASE 11: Logs
- `public/api-whatsapp.php` — mejorar formato de logs
- Crear rotación de logs automática

---

## 7. PLAN DE IMPLEMENTACIÓN POR ETAPAS

### FASE 1: Status Updates (Prioridad ALTA - 2-3 horas)
**Objetivo:** Actualizar estado de mensajes enviados

**Cambios:**
1. Modificar `public/api-whatsapp.php` para procesar `value.statuses[]`
   - Actualizar `wa_messages.wa_status` cuando Meta envía delivered/read
   - Usar `wa_msg_id` para encontrar el mensaje correcto
   - Usar `ON CONFLICT` para evitar duplicados

2. Verificar que Inbox muestre el estado correcto
   - UI debe mostrar ✓ (sent), ✓✓ (delivered), ✓✓ (read)

**Riesgo:** bajo
**Testing:** Enviar mensaje real y ver si estado se actualiza

---

### FASE 2: Auto-refresh del Inbox (Prioridad ALTA - 3-4 horas)
**Objetivo:** Nuevos mensajes sin recargar página

**Cambios:**
1. Agregar polling en `admin/pages/inbox.php`
   - AJAX cada 3 segundos a `wa-messages.php?action=messages&since=ID`
   - Agregar solo nuevos mensajes al DOM (no recargar todo)
   - Mantener scroll position

2. Actualizar `admin/api/wa-messages.php`
   - Parámetro ?since=ID para traer solo cambios incremental
   - Retornar también: unread_count, last_ts

**Riesgo:** bajo-moderado (puede aumentar carga de BD si polling es muy frecuente)
**Testing:** Enviar SMS desde cliente real, ver si aparece en <3 segundos sin recargar

---

### FASE 3: Contactos mejorados (Prioridad MEDIA - 2-3 horas)
**Objetivo:** Mejor gestión de identidad de contacto

**Cambios:**
1. Agregar columnas a `wa_contacts` en `app/db.php`
   - first_message_at, last_message_at, unread_count
   
2. Actualizar webhook para llenar wa_name desde Meta cuando esté disponible
   
3. Reescribir query de conversaciones en `wa-messages.php` para evitar duplicados

**Riesgo:** bajo
**Testing:** Múltiples mensajes desde mismo número, verificar no aparece duplicado

---

### FASE 4: Multiusuario (Prioridad MEDIA - 4-5 horas)
**Objetivo:** Asignación de conversaciones

**Cambios:**
1. Agregar `assigned_user_id` a `wa_contacts`
2. UI: dropdown de "Asignar a..."
3. Registrar en wa_messages quién envió cada outgoing
4. Filtro "Mis conversaciones" en Inbox

**Riesgo:** moderado (requiere cambios en UI + BD)
**Testing:** 2 usuarios, asignar conversación, verificar filtrado

---

### FASE 5: Multimedia (Prioridad BAJA - 6-8 horas)
**Objetivo:** Recibir y enviar imágenes/documentos

**Cambios:**
1. Modificar webhook para detectar tipo de media
2. Agregar `message_type` y `media_id` a wa_messages
3. Crear endpoint de descarga de media
4. UI: mostrar thumbnails/íconos

**Riesgo:** moderado-alto (Meta API, descarga de archivos, seguridad)
**Testing:** Enviar imagen desde WhatsApp, verificar se guarda y se muestra

---

### FASE 6 onwards: Seguir según prioridad

---

## 8. RIESGOS MIGRATORIOS

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|--------|-----------|
| Duplicados en conversaciones | MEDIA | ALTO | Crear tabla explícita wa_conversations |
| Corrupción de wa_msg_id | BAJA | ALTO | Validar UNIQUE constraint antes |
| Loss de status updates históricos | MEDIA | BAJO | Solo futuros son tracked correctamente |
| Aumento de carga de polling | MEDIA | MODERADO | Empezar con 5-10 seg, monitorear |
| Acceso no autorizado a wa_token | BAJA | CRÍTICO | Considerar encriptación en BD |

---

## 9. MATRIZ DE DECISIONES PENDIENTES

| Decisión | Opción A | Opción B | Recomendación |
|----------|----------|----------|----------------|
| Actualización en tiempo real | Polling AJAX | WebSockets | **Polling (simple, stab stable)** |
| Almacenamiento de media | Blob en BD | URL a Meta | **URL a Meta (mejor rendimiento)** |
| Tabla wa_conversations | Crearla | Mantener UNION | **Crearla (previene duplicados)** |
| Encriptación de wa_token | Sí | No | **Sí (crítico para seguridad)** |
| Multiusuario | Implementar ya | Después | **Después (no bloqueante)** |

---

## 10. CHECKLIST PRE-IMPLEMENTACIÓN

- [ ] Backup de BD actual (`grupo-plata.db`)
- [ ] Revisar y aprobar plan de fases
- [ ] Crear feature branch `whatsapp/enhancement`
- [ ] Testing manual de flujo actual (baseline)
- [ ] Revisar códigos de error de Meta que pueden llegar

---

## RESUMEN EJECUTIVO

**Estado actual:** Infraestructura básica funciona (bidireccional).
**Brechas principales:** Status updates no se procesan, sin auto-refresh, sin multiusuario.
**Recomendación:** Implementar en orden:
1. FASE 1 (Status updates) — 2-3h, impacto ALTO
2. FASE 2 (Auto-refresh) — 3-4h, impacto ALTO  
3. FASE 3 (Contactos) — 2-3h, impacto MEDIO
4. FASE 4 (Multiusuario) — 4-5h, impacto MEDIO
5. Resto según prioridad comercial

**Tiempo estimado total:** 15-25 horas en las primeras 4 fases.
**Riesgo global:** BAJO-MODERADO (cambios principalmente aditivos, datos existentes se preservan).
