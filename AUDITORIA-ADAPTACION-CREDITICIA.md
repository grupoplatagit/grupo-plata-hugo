# 📋 AUDITORÍA: ADAPTACIÓN DE CRM A RUBRO CREDITICIO

**Fecha:** 2026-08-11  
**Backup Realizado:** `BACKUP-ANTES-ADAPTACION-CREDITICIA-20260811_224338.bundle` (41 MB)  
**Estado:** LISTO PARA REVISAR - NO SE HA MODIFICADO NADA AÚN

---

## ✅ BACKUP CONFIRMADO

```
✓ Backup completo del proyecto (119 commits)
✓ Tamaño: 41 MB
✓ Incluye: todo el historio de git
✓ Almacenado en: BACKUP-ANTES-ADAPTACION-CREDITICIA-20260811_224338.bundle
✓ Restaurable con: git clone BACKUP-*.bundle proyecto-restaurado
```

---

## 📁 ESTRUCTURA DEL PROYECTO

### Directorios Principales
```
./admin/                          → Panel administrativo
├── pages/                        → Páginas del CRM
│   ├── leads.php                 ✏️ A MODIFICAR
│   ├── clientes.php              ✏️ A MODIFICAR
│   ├── prospectos.php            ✏️ A MODIFICAR
│   ├── pipeline.php              ✏️ A MODIFICAR
│   ├── propuestas.php            ✏️ A MODIFICAR
│   ├── nuevo-cliente.php         ✏️ A MODIFICAR
│   ├── editar-cliente.php        ✏️ A MODIFICAR
│   ├── whatsapp.php              ✓ NO TOCAR (config WhatsApp)
│   ├── inbox.php                 ⚠️ SOLO TEXTOS (Inbox WhatsApp)
│   └── area-dev.php              ✓ NO TOCAR
└── api/                          → APIs
    ├── wa-*.php                  ✓ NO TOCAR (todas las APIs de WhatsApp)
    └── prospectos.php            ✏️ A MODIFICAR

./app/                            → Backend
├── config.php                    ✏️ MODIFICAR (referencias JP MARKET)
├── db.php                        ✏️ MODIFICAR (referencias JP MARKET + schema)
├── functions.php                 ✓ REVISAR (buscar referencias)
├── bot.php                       ✏️ MODIFICAR (mensajes de bot)
└── auth.php                      ✓ REVISAR

./views/admin/                    → Vistas generales
├── header.php                    ✏️ MODIFICAR (branding JP MARKET)
└── footer.php                    ✏️ MODIFICAR (referencias JP MARKET)

./public/                         → Archivos públicos
├── webhook/                      ✓ NO TOCAR (webhook de WhatsApp)
└── assets/                       ✓ REVISAR (logo, imágenes)
```

---

## 🗄️ TABLAS DE BASE DE DATOS

### Tablas del CRM (A REVISAR/MODIFICAR)

#### 1. **leads** (Lead de conversión)
```sql
id              INTEGER PRIMARY KEY
nombre          TEXT
email           TEXT
whatsapp        TEXT
nicho           TEXT              ← RENOMBRAR a "tipo_credito"
ciudad          TEXT              ✓ REUTILIZABLE
pais            TEXT              ✓ REUTILIZABLE
presupuesto     TEXT              ← RENOMBRAR a "monto_solicitado"
objetivo        TEXT              ← RENOMBRAR a "destino_credito"
leido           INTEGER
wa_status       TEXT
wa_sent_at      TEXT
created_at      TEXT
```

#### 2. **clientes** (Clientes registrados)
```sql
id              INTEGER PRIMARY KEY
nombre          TEXT              ✓ REUTILIZABLE
empresa         TEXT              ← CAMBIAR a "tipo_credito"
email           TEXT              ✓ REUTILIZABLE
telefono        TEXT              ✓ REUTILIZABLE
ciudad          TEXT              ✓ REUTILIZABLE
servicio        TEXT              ← CAMBIAR a "destino_credito"
monto           REAL              ← RENOMBRAR a "monto_solicitado"
campana         INTEGER           ← ELIMINAR (sin usar en crédito)
notas           TEXT              ✓ REUTILIZABLE
activo          INTEGER
created_at      TEXT
updated_at      TEXT
```

#### 3. **prospectos** (Prospectos/Contactos)
```sql
id              INTEGER PRIMARY KEY
nombre          TEXT              ✓ REUTILIZABLE
zona            TEXT              ← CAMBIAR a "ciudad"
telefono        TEXT              ✓ REUTILIZABLE
web             TEXT              ← ELIMINAR (no aplica)
instagram       TEXT              ← ELIMINAR (no aplica)
direccion       TEXT              ✓ REUTILIZABLE
categoria       TEXT              ← CAMBIAR a "tipo_credito"
estado          TEXT              ✓ REUTILIZABLE (estados nuevos)
notas           TEXT              ✓ REUTILIZABLE
secuencia_dia   INTEGER           ← REVISAR (no aplica)
ultimo_contacto TEXT              ✓ REUTILIZABLE
created_at      TEXT
updated_at      TEXT
```

#### 4. **oportunidades** (Pipeline)
```sql
id              INTEGER PRIMARY KEY
titulo          TEXT              ✓ REUTILIZABLE
cliente         TEXT              ✓ REUTILIZABLE
lead_id         INTEGER           ✓ REUTILIZABLE
etapa           TEXT DEFAULT 'nuevo'  ← CAMBIAR VALORES (estados nuevos)
valor           REAL              ← RENOMBRAR a "monto"
moneda          TEXT              ✓ REUTILIZABLE
probabilidad    INTEGER           ← REVISAR (aplica a crédito)
notas           TEXT              ✓ REUTILIZABLE
motivo_perdida  TEXT              ✓ REUTILIZABLE
created_at      TEXT
updated_at      TEXT
```

### Tablas del Inbox WhatsApp (✓ NO TOCAR)
```
✓ wa_messages          → Mensajes de WhatsApp
✓ wa_contacts          → Contactos de WhatsApp
✓ user_whatsapp_config → Config de WhatsApp
```

### Tablas de Soporte
```
✓ admins               → Usuarios del sistema
✓ settings             → Configuración general
✓ bot_sessions         → Sesiones del bot (revisar si aplica)
✓ tareas               → Tareas
✓ actividades          → Actividades
✓ login_attempts       → Seguridad
✓ propuestas           → Propuestas (revisar si aplica)
```

---

## 🔤 REFERENCIAS A "JP MARKET" (ENCONTRADAS)

### En Archivos PHP
- ✏️ `admin/login.php` - Email y título
- ✏️ `admin/pages/whatsapp.php` - Mensaje de prueba
- ✏️ `app/bot.php` - Mensajes del bot (3 referencias)
- ✏️ `app/config.php` - Rutas, nombres de archivos
- ✏️ `app/db.php` - Email default, mensajes, webhook token
- ✏️ `views/admin/header.php` - Branding, logo
- ✏️ `views/footer.php` - Email, footer

### Total de Referencias: 27 encontradas

---

## 📊 ESTADOS ACTUALES vs NUEVOS

### Estados Actuales del CRM
```
LABELS en app/config.php o frontend:
- nuevo (gris)
- potencial (ámbar)
- calificado (azul)
- agendado (púrpura)
- cerrado (verde)
- descartado (rojo)
```

### Estados Nuevos Propuestos (Pipeline Crediticio)
```
1. NUEVO                → Primer contacto
2. CONTACTADO           → Se realizó primer contacto
3. DATOS SOLICITADOS    → Se pidieron datos personales
4. DOCUMENTACIÓN        → Se solicitó documentación
5. EN ANÁLISIS          → Se está analizando solicitud
6. PREAPROBADO          → Aprobación preliminar
7. APROBADO             → Aprobación final
8. DESEMBOLSADO         → Dinero entregado
9. RECHAZADO            → Solicitud rechazada
10. DESCARTADO          → Descartado por el usuario
```

**Mapeo de Colores:**
- nuevo → gris (mantener)
- contactado → azul claro
- datos_solicitados → azul
- documentacion → púrpura
- en_analisis → ámbar
- preaprobado → verde claro
- aprobado → verde
- desembolsado → verde oscuro
- rechazado → rojo
- descartado → gris (mantener)

---

## 📝 CAMPOS A MODIFICAR

### Tabla LEADS

| Campo Actual | Nuevo Campo | Tipo | Notas |
|---|---|---|---|
| `nicho` | `tipo_credito` | TEXT | Valores: Crédito personal, Préstamo, Adelanto, Refinanciación, Otro |
| `presupuesto` | `monto_solicitado` | TEXT | Ej: $500.000 |
| `objetivo` | `destino_credito` | TEXT | Valores: Consumo, Deudas, Médico, Vivienda, Vehículo, Emprendimiento, Educación, Otro |

**Campos Nuevos Necesarios:**
- `dni` - Documento de identidad
- `provincia` - Provincia (además de ciudad)
- `ingresos_mensuales` - Ingresos
- `situacion_laboral` - Empleo/Independiente/etc
- `antiguedad_laboral` - Antigüedad en el trabajo
- `cuotas_deseadas` - Cantidad de cuotas
- `origen_lead` - Cómo llegó el lead
- `asesor_asignado` - Asesor responsable
- `prioridad` - Alta/Media/Baja

**Sin Eliminar:**
- `email` - Mantener
- `whatsapp` - Mantener (crítico para Inbox)
- `ciudad` - Mantener
- `pais` - Mantener (renombrar a `provincia` si es local)

---

## 🎨 MODO DÍA/NOCHE

### Ubicación del CSS Actual
```
✓ views/admin/header.php → <style> (CSS general)
✓ public/assets/css/ → Posibles archivos CSS (revisar)
```

### Variables CSS Actuales Usadas
```css
--bg           → Fondo principal
--surface      → Superficies (cards)
--surface2     → Superficies secundarias
--text         → Texto principal
--muted        → Texto atenuado
--border       → Bordes
--accent       → Color de acento (verde #25d366)
```

### Implementación
```
1. Crear tema claro (inverso del actual)
2. Guardar preferencia en localStorage
3. Aplicar tema al cargar la página
4. Botón toggle en header
5. NO modificar funcionalidades
```

### Archivos a Modificar
```
✏️ views/admin/header.php    → Agregar CSS tema claro + botón toggle
✏️ admin/pages/*.php         → Agregar JS para cargar tema desde localStorage
```

---

## ⚠️ ARCHIVOS QUE NO SE TOCARÁN

```
✓ admin/api/wa-*.php              → APIs de WhatsApp (funcionan perfectamente)
✓ admin/pages/inbox.php            → Lógica del Inbox (solo textos si es necesario)
✓ public/webhook/                  → Webhook de Meta
✓ public/api-whatsapp.php          → Webhook receiver
✓ app/functions.php                → Funciones generales (revisar solo)
✓ app/auth.php                     → Autenticación
✓ database/                        → Archivos de BD (schema en db.php)
✓ Sistema de multimedia            → Download/upload de archivos
✓ Sistema de estados de WhatsApp   → Sent/delivered/read
```

---

## 🚀 PLAN DE IMPLEMENTACIÓN

### FASE 1: Actualizar Branding (2-3 cambios)
1. Reemplazar referencias visuales a "JP MARKET" por nombre neutro o genérico
2. Cambiar logo si corresponde
3. Actualizar email de admin@jpmarket.com a valor genérico

### FASE 2: Adaptar Tablas de BD (Sin eliminar datos)
1. Renombrar columnas en LEADS:
   - `nicho` → `tipo_credito`
   - `presupuesto` → `monto_solicitado`
   - `objetivo` → `destino_credito`
2. Agregar columnas nuevas en LEADS:
   - `dni`, `provincia`, `ingresos_mensuales`, `situacion_laboral`, `antiguedad_laboral`, `cuotas_deseadas`, `origen_lead`, `asesor_asignado`, `prioridad`
3. Actualizar vista en CLIENTES y PROSPECTOS

### FASE 3: Adaptar Estados del Pipeline
1. Actualizar LABELS en config.php
2. Cambiar valores de `etapa` en tabla `oportunidades`
3. Actualizar colores en CSS
4. Actualizar filtros en frontend

### FASE 4: Adaptar Formularios
1. Cambiar etiquetas en formulario de LEADS
2. Cambiar opciones de select (tipo_credito, destino_credito, etc)
3. Agregar campos nuevos al formulario
4. Mantener funcionalidad de conversión CONVERSACIÓN → LEAD

### FASE 5: Implementar Modo Día/Noche
1. Crear tema claro en CSS
2. Agregar botón toggle en header
3. Guardar preferencia en localStorage
4. Aplicar tema dinámicamente

### FASE 6: Pruebas
1. Verificar que Inbox sigue funcionando
2. Verificar que recepción de WhatsApp funciona
3. Verificar que envío de multimedia funciona
4. Verificar que conversión CONVERSACIÓN → LEAD funciona
5. Prueba de modo día/noche

---

## 📋 RESUMEN DE CAMBIOS

| Tipo | Cantidad | Complejidad |
|---|---|---|
| Archivos a modificar | 12 | Media |
| Columnas a renombrar en DB | 3 | Baja (ALTER TABLE) |
| Columnas nuevas a agregar | 9 | Baja (ALTER TABLE) |
| Estados a actualizar | 6 | Media |
| Funcionalidades a preservar | 100% | Crítica |
| Archivos a NO tocar | 15+ | Crítica |

---

## ⚙️ RIESGOS IDENTIFICADOS

### RIESGO ALTO
- ❌ Eliminar columnas existentes (SI OCURRE: Perder datos históricos)
- ❌ Modificar wa_messages, wa_contacts, webhook (SI OCURRE: Romper Inbox)
- ❌ Cambiar autenticación o permissions (SI OCURRE: Usuarios no pueden acceder)

### RIESGO MEDIO
- ⚠️ Cambiar tipos de datos sin migración (SI OCURRE: Datos incompatibles)
- ⚠️ Reemplazar strings "JP MARKET" automáticamente (SI OCURRE: Romper funcionalidad)

### RIESGO BAJO
- ℹ️ Agregar columnas nuevas (SEGURO: ALTER TABLE solo agrega)
- ℹ️ Cambiar CSS (SEGURO: Solo afecta visualización)

### MITIGACIÓN
- ✓ Backup realizado y validado
- ✓ Cambios por fase (no todo de una vez)
- ✓ Validar después de cada fase
- ✓ Mantener datos históricos

---

## 📌 RECOMENDACIONES

1. **Cambio de Nombre de Empresa**
   - ¿Cuál es el nombre de la empresa nueva?
   - ¿Qué email de admin debe usarse?
   - ¿Hay logo nuevo?

2. **Migración de Datos**
   - Los leads existentes seguirán en la BD
   - Se mostrarán con los campos nuevos
   - Los valores de "nicho/presupuesto/objetivo" seguirán siendo usables

3. **Estados del Pipeline**
   - Los estados actuales NO coinciden con el nuevo pipeline crediticio
   - Hay que decidir cómo mapear leads existentes a nuevos estados

4. **Campos Nuevos**
   - No todos son obligatorios
   - Los marcados con ⚠️ son recomendados para crédito
   - Pueden dejarse opcionales durante la transición

5. **Modo Día/Noche**
   - Agregar después de terminar adaptación crediticia
   - Es completamente independiente
   - Bajo riesgo

---

## ✅ SIGUIENTE PASO

**REVISAR ESTE INFORME Y CONFIRMAR:**

1. ¿Está completo el análisis?
2. ¿Son correctas las referencias encontradas?
3. ¿Están todos los archivos identificados?
4. ¿Accepción el plan de implementación?
5. ¿Hay cambios adicionales necesarios?
6. ¿Cuál es el nombre/email de la empresa nueva?

**RECIÉN DESPUÉS:**
- Iniciar FASE 1 de implementación
- Cambios por fase
- Validar después de cada fase

---

**Estado:** 🟢 LISTO PARA REVISAR - Sin cambios realizados aún

