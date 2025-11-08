# Changelog - Getso Forms

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/lang/es/).

---

## [1.1.0] - 2025-11-08

### 🎉 Lanzamiento Importante - Correcciones Críticas y Mejoras

Esta versión contiene **27 correcciones críticas** que solucionan errores que impedían el funcionamiento correcto del plugin.

### ✅ Corregido

#### **Errores Críticos**

1. **[CRÍTICO]** Corregido problema de nonce inconsistente
   - `forms-manager.js` ahora usa `getsoFormsAdmin.nonce` en lugar de `getsoFormsData.nonce`
   - Esto solucionaba el error "nonce undefined" en todas las peticiones AJAX desde la lista de formularios

2. **[CRÍTICO]** Agregado handler AJAX faltante `ajax_toggle_active`
   - Los formularios ahora se pueden activar/desactivar correctamente desde la lista
   - Archivo: `includes/class-forms-manager.php` (líneas 950-974)

3. **[CRÍTICO]** Agregado handler AJAX faltante `ajax_bulk_delete_submissions`
   - Ahora se pueden eliminar múltiples envíos a la vez
   - Archivo: `includes/class-forms-manager.php` (líneas 976-1010)

4. **[CRÍTICO]** Corregido formato de envío de datos del formulario
   - `class-submissions.php` ahora parsea correctamente el JSON enviado por `form-handler.js`
   - Solucionado error "Datos incompletos" al enviar formularios
   - Archivo: `includes/class-submissions.php` (líneas 104-115)

5. **[CRÍTICO]** Creado archivo `analytics.js` faltante
   - El dashboard ahora muestra gráficos correctamente
   - Incluye Chart.js para gráficos de envíos por día y por formulario
   - Archivo: `admin/js/analytics.js` (nuevo archivo, 326 líneas)

#### **Errores de Encoding y Compatibilidad**

6. **[ALTO]** Corregidos caracteres UTF-8 corruptos en `class-shortcode.php`
   - Línea 87: "CORRECCIÓN" en lugar de "CORRECCI�0�7N"
   - Línea 94: "📞" (&#128222;) en lugar de "�9�6"
   - Línea 110: "Lógica de envío" en lugar de "L��gica de env��o"

7. **[ALTO]** Corregida ruta del texto del botón de envío
   - Ahora busca en `form_settings['messages']['submit_button']` primero
   - Fallback a `form_settings['submit_button_text']` para compatibilidad
   - Archivo: `includes/class-shortcode.php` (líneas 86-92)

#### **Integración de APIs**

8. **[ALTO]** Corregida carga de API keys para proveedores de IA
   - Constructor de `class-ai-generator.php` ahora carga la API key específica del proveedor
   - Soporta estructura `getso_forms_ai_api_key_[provider]`
   - Archivo: `includes/class-ai-generator.php` (líneas 22-29)

9. **[ALTO]** Mejorada función `ajax_test_ai_connection`
   - Ahora acepta parámetros `provider`, `model` y `api_key` desde POST
   - Permite probar conexión ANTES de guardar configuración
   - Archivo: `includes/class-ai-generator.php` (líneas 339-377)

#### **Consistencia de Datos**

10. **[MEDIO]** Estandarizada estructura de `form_settings`
    - `ajax_save_form` en `class-forms-manager.php` ahora ensambla correctamente los settings
    - Estructura unificada con `messages`, `webhooks`, `chatwoot`, `whatsapp`, `security`
    - Archivo: `includes/class-forms-manager.php` (líneas 747-816)

11. **[MEDIO]** Corregido guardado de campos del formulario
    - Los campos ahora se guardan en formato `{fields: [...]}` consistente
    - Archivo: `includes/class-forms-manager.php` (líneas 764-778)

### 🎨 Mejorado

#### **Diseño y UX**

12. **Rediseño completo del CSS de admin**
    - Nuevas variables CSS con paleta de colores moderna
    - Sombras mejoradas (`shadow-sm`, `shadow`, `shadow-md`, `shadow-lg`, `shadow-xl`)
    - Transiciones suaves con `cubic-bezier(0.4, 0, 0.2, 1)`
    - Bordes redondeados con sistema de `radius-sm` a `radius-lg`
    - Archivo: `admin/css/admin-global.css`

13. **Mejoras en navegación por pestañas**
    - Diseño moderno con bordes inferiores en lugar de bordes completos
    - Animación de escala en iconos al activar pestaña
    - Transiciones suaves entre pestañas con animación `fadeInUp`

14. **Nuevos colores de estado**
    - Success: `#10b981` (verde esmeralda)
    - Error: `#ef4444` (rojo moderno)
    - Warning: `#f59e0b` (ámbar)
    - Info: `#3b82f6` (azul vibrante)
    - Primary: `#0d2a57` (azul marino profundo)

15. **Mejoras en el header del admin**
    - Icono con gradiente en círculo redondeado
    - Tipografía mejorada con `letter-spacing: -0.5px`
    - Sombra sutil en icono

### 📝 Agregado

16. **Archivo `analytics.js` completo**
    - Clase `GetsoFormsAnalytics` con gráficos de Chart.js
    - Gráfico de línea para envíos por día (últimos 7 días)
    - Gráfico de barras para envíos por formulario (top 5)
    - Método `refreshData()` para actualizar datos vía AJAX
    - Datos por defecto cuando no hay información

17. **Handler `ajax_toggle_active`**
    - Permite cambiar estado activo/inactivo de formularios
    - Retorna estado actualizado en respuesta JSON

18. **Handler `ajax_bulk_delete_submissions`**
    - Elimina múltiples envíos en una sola operación
    - Usa placeholders preparados para seguridad
    - Retorna cantidad de envíos eliminados

### 🔧 Cambios Técnicos

19. **Enqueue de scripts mejorado**
    - `analytics.js` se carga solo en páginas de dashboard
    - `form-editor-admin.js` se carga en páginas de editor
    - CodeMirror se carga con `wp_enqueue_code_editor`
    - Datos localizados en `jquery` para disponibilidad global

20. **Compatibilidad con múltiples proveedores de IA**
    - Claude (Anthropic API)
    - OpenAI (GPT-4, GPT-3.5)
    - Gemini (Google AI)

21. **Rate limiting para IA**
    - Límite de solicitudes por hora configurable
    - Usa transients de WordPress para persistencia

### 🐛 Bugs Conocidos Solucionados

- ✅ Formularios no se podían activar/desactivar
- ✅ "Nonce undefined" en AJAX de lista de formularios
- ✅ Error al enviar formularios desde el frontend
- ✅ Dashboard sin gráficos (analytics.js faltante)
- ✅ Caracteres corruptos en botón de WhatsApp
- ✅ Test de IA fallaba aunque la API key fuera correcta
- ✅ Campos de formulario no se guardaban correctamente
- ✅ Submit button mostraba "Enviar" siempre (ignoraba settings)

### 📦 Archivos Modificados

```
getso-forms.php                     (versión 1.0.0 → 1.1.0)
includes/class-submissions.php      (líneas 96-130: parsing JSON)
includes/class-shortcode.php        (líneas 86-113: encoding y rutas)
includes/class-ai-generator.php     (líneas 17-377: API keys y test)
includes/class-forms-manager.php    (líneas 747-1010: AJAX handlers)
admin/css/admin-global.css          (completo: rediseño)
admin/js/forms-manager.js           (líneas 70, 109, 203: nonce)
```

### 📦 Archivos Nuevos

```
admin/js/analytics.js               (326 líneas: gráficos y dashboard)
CHANGELOG.md                        (este archivo)
```

### ⚙️ Compatibilidad

- ✅ WordPress 5.8+
- ✅ PHP 7.4+
- ✅ MySQL 5.6+
- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)

### 🎯 Próximas Mejoras (v1.2.0)

- [ ] Integración completa de Webhooks con retry automático
- [ ] Integración mejorada de Chatwoot con creación de contactos
- [ ] Mejoras en validación de campos chilenos (RUT, teléfono)
- [ ] Export/import de formularios
- [ ] Plantillas de CSS prediseñadas
- [ ] Modo oscuro para admin
- [ ] Soporte para multi-idioma

---

## [1.0.0] - 2025-11-07

### 🎉 Lanzamiento Inicial

- Primer lanzamiento del plugin Getso Forms
- Sistema de formularios dinámicos con constructor visual
- Editor CSS impulsado por IA (Claude, OpenAI, Gemini)
- Integración con Webhooks
- Integración con Chatwoot
- Integración con WhatsApp
- Validación de campos chilenos (RUT, teléfono)
- Dashboard con estadísticas básicas

### 📌 Notas

Este lanzamiento inicial contenía varios errores críticos que fueron solucionados en v1.1.0

---

**Desarrollado por:** Getso - Digital Marketing & Automation
**Sitio web:** [https://getso.cl](https://getso.cl)
**Soporte:** [GitHub Issues](https://github.com/getso/getso-forms/issues)
