# Getso Forms - Archivos Faltantes del Plugin WordPress

## 📋 Resumen

Se han creado **11 archivos faltantes** para completar el plugin "Getso Forms" para WordPress.

**Total archivos creados:**
- 1 clase PHP (core)
- 4 vistas admin PHP
- 3 archivos JavaScript admin
- 2 archivos JavaScript frontend
- 1 archivo CSS global

---

## 📁 Estructura de Archivos

### 1. **includes/class-fields-builder.php** (21 KB)
**Propósito:** Clase core para construir y renderizar campos dinámicamente

**Funcionalidades:**
- Método `build_field()` - Construye campo completo con wrapper
- Método `render_field()` - Renderiza HTML según tipo de campo
- Método `validate_field()` - Validación server-side de campos
- Soporte para 12 tipos de campos:
  - text, email, tel, rut, textarea
  - select, radio, checkbox
  - number, date, file, hidden
- Validación de RUT chileno (algoritmo módulo 11)
- Validación de teléfono chileno (+56XXXXXXXXX)
- Validación de email RFC compliant

**Ubicación en plugin:** `/includes/class-fields-builder.php`

---

### 2. **admin/views/dashboard.php** (18 KB)
**Propósito:** Vista principal del dashboard con estadísticas y gráficos

**Funcionalidades:**
- 4 cards de estadísticas:
  - Total formularios (activos/inactivos)
  - Total envíos (con contador 30 días)
  - Tasa de éxito webhooks (%)
  - Fecha actual
- Gráficos Chart.js:
  - Envíos por día (últimos 7 días) - Line chart
  - Envíos por formulario (top 5) - Bar chart
- Top 3 formularios más usados con acciones
- Actividad reciente (últimos 10 envíos)
- Quick actions (botones rápidos)

**Dependencias:** Chart.js (ya debe estar enqueued)

**Ubicación en plugin:** `/admin/views/dashboard.php`

---

### 3. **admin/views/form-editor.php** (28 KB)
**Propósito:** Editor completo de formularios con drag & drop y tabs

**Funcionalidades:**
- Información básica del formulario (nombre, descripción, estado)
- **5 Tabs principales:**
  1. **Campos:** Drag & drop para reordenar, modal add/edit campos
  2. **CSS/IA:** Editor CodeMirror + Chat IA + Preview iframe
  3. **Configuración:** Mensajes, redirección, captcha, storage
  4. **Webhooks:** Primario/secundario con test
  5. **Chatwoot:** Account ID, Inbox ID, opciones
- Modal para agregar/editar campos
- Preview en tiempo real del formulario
- Shortcode generator con copy button
- Validación con nonces

**Ubicación en plugin:** `/admin/views/form-editor.php`

---

### 4. **admin/views/submissions.php** (19 KB)
**Propósito:** Lista de todos los envíos con filtros y exportación

**Funcionalidades:**
- Filtros avanzados:
  - Por formulario
  - Por estado webhook (success/error/pending)
  - Por rango de fechas (desde/hasta)
- Estadísticas rápidas:
  - Total envíos
  - Envíos exitosos
  - Envíos con errores
- Tabla con información:
  - ID, Formulario, Fecha/Hora
  - Estado webhooks primario/secundario
  - Estado Chatwoot (contact_id)
  - Botones: Ver detalles, Eliminar
- Paginación (20 items por página)
- Exportación a CSV con filtros aplicados
- Acciones en lote (eliminar múltiples)
- Select all checkbox

**Ubicación en plugin:** `/admin/views/submissions.php`

---

### 5. **admin/views/submission-details.php** (16 KB)
**Propósito:** Modal con detalles completos de un envío específico

**Funcionalidades:**
- **Información general:**
  - ID, Formulario, Fecha/Hora
  - IP Address, User Agent
- **Datos enviados:** Tabla con todos los campos del formulario
- **Estado webhooks:**
  - Primario: estado, respuesta HTTP, código
  - Secundario: estado, respuesta
- **Integración Chatwoot:**
  - Contact ID, Conversation ID (si aplica)
- Botón eliminar envío
- Renderizado vía AJAX con template JavaScript
- Sistema de template mustache-like

**Ubicación en plugin:** `/admin/views/submission-details.php`

---

### 6. **admin/js/ai-chat.js** (15 KB) ⭐ CRÍTICO
**Propósito:** Clase JavaScript para chat con IA en editor CSS

**Funcionalidades:**
- Clase `GetsoFormsAIChat` con métodos:
  - `sendPrompt()` - Enviar prompt a IA vía AJAX
  - `applyTemplate()` - 4 templates predefinidos (modern, minimal, colorful, dark)
  - `addMessageToChat()` - Agregar mensajes al historial
  - `updatePreview()` - Actualizar iframe de preview
- Rate limiting (20 requests/hora)
- Conversational history (últimos 10 mensajes)
- Loading states y error handling
- Integración con CodeMirror editor
- Obtiene campos del formulario para contexto

**Endpoints AJAX requeridos:**
- `getso_forms_ai_generate_css` - Generar CSS con IA

**Ubicación en plugin:** `/admin/js/ai-chat.js`

---

### 7. **admin/js/css-editor.js** (7.8 KB)
**Propósito:** Inicialización y control del editor CSS con CodeMirror

**Funcionalidades:**
- Inicializa CodeMirror con:
  - Mode: CSS
  - Theme: material
  - Line numbers, wrapping, brackets
  - Autocomplete (Ctrl+Space)
- Botón "Guardar CSS" → AJAX save
- Botón "Restaurar" → Revertir cambios
- Atajos de teclado:
  - Ctrl+S: Guardar
  - Tab: Indent con 2 espacios
- Auto-update preview (debounce 500ms)
- Prevenir salir sin guardar (beforeunload)
- Conecta con `getsoAIChat` para preview

**Dependencias:** CodeMirror library

**Endpoints AJAX requeridos:**
- `getso_forms_save_css` - Guardar CSS

**Ubicación en plugin:** `/admin/js/css-editor.js`

---

### 8. **admin/js/forms-manager.js** (9.5 KB)
**Propósito:** Gestión de formularios en lista (duplicar, eliminar, toggle)

**Funcionalidades:**
- Duplicar formulario → Redirect a editor del nuevo
- Eliminar formulario (con confirmación)
- Copiar shortcode al clipboard:
  - Clipboard API moderna
  - Fallback para navegadores antiguos
  - Feedback visual "¡Copiado!"
- Toggle activo/inactivo con AJAX
- Actualización de badges en tiempo real
- Sistema de notificaciones toast

**Endpoints AJAX requeridos:**
- `getso_forms_duplicate_form`
- `getso_forms_delete_form`
- `getso_forms_toggle_active`

**Ubicación en plugin:** `/admin/js/forms-manager.js`

---

### 9. **public/js/form-validator.js** (9.8 KB)
**Propósito:** Validación frontend de formularios

**Funcionalidades:**
- Clase `GetsoFormsValidator` por formulario
- Validación en tiempo real (blur event)
- Limpiar errores en input
- Validaciones específicas:
  - **Email:** RFC compliant regex
  - **Teléfono chileno:** +56XXXXXXXXX (9 dígitos)
  - **RUT chileno:** Algoritmo módulo 11 correcto
  - **Number:** min, max, step
  - **URL:** new URL() validation
- Mostrar errores bajo cada campo
- Focus automático en primer error
- Scroll suave al error
- Previene submit si hay errores

**Auto-inicialización:** Busca `.getso-form` en DOM ready

**Ubicación en plugin:** `/public/js/form-validator.js`

---

### 10. **public/js/field-formatters.js** (5.7 KB)
**Propósito:** Formateo automático de campos en tiempo real

**Funcionalidades:**
- **Formatear RUT:** 12.345.678-9
  - Puntos cada 3 dígitos
  - Guión antes del dígito verificador
  - Maxlength 12 caracteres
- **Formatear teléfono:** +56912345678
  - Auto-agregar +56 en focus si está vacío
  - Validar 9 dígitos después de +56
  - Maxlength 12 caracteres
- **Formatear números:** Separador de miles con puntos
- Función global `getsoCleanFieldValue()` para limpiar antes de enviar

**Auto-inicialización:** Busca campos con:
- Clase `.getso-rut-field` o `data-format="rut"`
- Clase `.getso-tel-field` o `data-format="phone"`

**Ubicación en plugin:** `/public/js/field-formatters.js`

---

### 11. **admin/css/admin-global.css** (14 KB)
**Propósito:** Estilos globales para todas las vistas admin

**Funcionalidades:**
- **Variables CSS:** Colores, sombras, borders
- **Tabs navigation:** Estilo WordPress con active state
- **Grid system 3 columnas:** Editor CSS (sidebar IA + editor + preview)
- **AI Chat styles:** Messages, templates, status
- **Modales:** Overlay, content, animations
- **Form editor layout:** 2 columnas (editor + preview)
- **Fields builder:** Drag handle, badges, actions
- **Botones:** Primary, large, with icons, states
- **Status badges:** Success, error, warning, neutral
- **Notificaciones toast:** Slide-in desde derecha
- **Shortcode box:** Código con copy button
- **Responsive:** Breakpoints 1200px, 900px, 782px
- **Animations:** Spin, fadeIn, slideIn

**Ubicación en plugin:** `/admin/css/admin-global.css`

---

## 🔧 Instalación

### Paso 1: Copiar archivos al plugin

```bash
# Copiar clase core
cp class-fields-builder.php [plugin-root]/includes/

# Copiar vistas admin
cp dashboard.php [plugin-root]/admin/views/
cp form-editor.php [plugin-root]/admin/views/
cp submissions.php [plugin-root]/admin/views/
cp submission-details.php [plugin-root]/admin/views/

# Copiar JavaScript admin
cp ai-chat.js [plugin-root]/admin/js/
cp css-editor.js [plugin-root]/admin/js/
cp forms-manager.js [plugin-root]/admin/js/

# Copiar CSS admin
cp admin-global.css [plugin-root]/admin/css/

# Copiar JavaScript frontend
cp form-validator.js [plugin-root]/public/js/
cp field-formatters.js [plugin-root]/public/js/
```

### Paso 2: Enqueue de assets

Asegúrate de que estos archivos estén enqueued en tu plugin principal:

**En admin:**
```php
// admin/css/admin-global.css
wp_enqueue_style('getso-forms-admin-global');

// admin/js/ai-chat.js
wp_enqueue_script('getso-forms-ai-chat');

// admin/js/css-editor.js (requiere CodeMirror)
wp_enqueue_script('codemirror');
wp_enqueue_script('getso-forms-css-editor');

// admin/js/forms-manager.js
wp_enqueue_script('getso-forms-manager');
```

**En frontend:**
```php
// public/js/form-validator.js
wp_enqueue_script('getso-forms-validator');

// public/js/field-formatters.js
wp_enqueue_script('getso-forms-formatters');
```

### Paso 3: Registrar AJAX endpoints

Debes crear estos AJAX handlers en tu plugin principal:

```php
// AI CSS Generator
add_action('wp_ajax_getso_forms_ai_generate_css', 'getso_forms_ai_generate_css_handler');

// Save CSS
add_action('wp_ajax_getso_forms_save_css', 'getso_forms_save_css_handler');

// Forms management
add_action('wp_ajax_getso_forms_duplicate_form', 'getso_forms_duplicate_form_handler');
add_action('wp_ajax_getso_forms_delete_form', 'getso_forms_delete_form_handler');
add_action('wp_ajax_getso_forms_toggle_active', 'getso_forms_toggle_active_handler');

// Submissions
add_action('wp_ajax_getso_forms_get_submission_details', 'getso_forms_get_submission_details_handler');
add_action('wp_ajax_getso_forms_delete_submission', 'getso_forms_delete_submission_handler');
add_action('wp_ajax_getso_forms_bulk_delete_submissions', 'getso_forms_bulk_delete_submissions_handler');
add_action('wp_ajax_getso_forms_export_csv', 'getso_forms_export_csv_handler');
```

### Paso 4: Instalar dependencias externas

El plugin requiere estas librerías externas:

1. **CodeMirror** (para editor CSS)
   - Incluir: codemirror.js, codemirror.css
   - Mode: css.js
   - Theme: material.css
   
2. **Chart.js** (para gráficos dashboard)
   - Incluir: chart.min.js

Puedes usar CDN o instalarlas localmente.

---

## ✅ Checklist de Integración

- [ ] Todos los 11 archivos copiados en ubicaciones correctas
- [ ] Assets enqueued correctamente (CSS/JS)
- [ ] CodeMirror instalado y cargado
- [ ] Chart.js instalado y cargado
- [ ] 8 AJAX endpoints registrados
- [ ] Nonces configurados correctamente
- [ ] Permisos verificados (manage_options)
- [ ] Autoloader configurado para `class-fields-builder.php`
- [ ] Variables JavaScript globales definidas:
  - `getsoFormsEditor` (form-editor.php)
  - `getsoFormsData` (forms-manager.js)
  - `ajaxurl` (WordPress global)

---

## 🎨 Características Destacadas

### Editor CSS con IA ⭐
- Chat conversacional con historial
- 4 templates predefinidos
- Preview en tiempo real
- Rate limiting incorporado

### Validación RUT Chilena ✅
- Algoritmo módulo 11 correcto
- Formateo automático: 12.345.678-9
- Validación frontend + backend

### Sistema de Modales Avanzado
- Overlay con backdrop
- Animaciones suaves
- Template engine simple
- Responsive

### Gestión Completa de Envíos
- Filtros múltiples
- Exportación CSV
- Acciones en lote
- Detalles completos

---

## 📞 Soporte

Si encuentras algún problema durante la integración:

1. Verifica que todos los archivos estén en las ubicaciones correctas
2. Revisa la consola del navegador para errores JavaScript
3. Verifica que los AJAX endpoints estén registrados
4. Asegúrate de que CodeMirror y Chart.js estén cargados

---

## 📝 Notas Adicionales

- **Seguridad:** Todos los archivos usan `check_admin_referer()` y verifican permisos
- **Compatibilidad:** Compatible con WordPress 5.0+
- **PHP:** Requiere PHP 7.4+
- **Navegadores:** Chrome, Firefox, Safari, Edge (últimas versiones)

---

**Creado por:** Claude (Anthropic)  
**Fecha:** 7 de noviembre de 2025  
**Para:** Kai Getso - Getso Digital Marketing & Automation

---

¡Listo para usar! 🚀
