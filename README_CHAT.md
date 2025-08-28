# Chat Flotante para Plugin Datacurso - Moodle 4.5

## Descripción

Se ha agregado un chat flotante al plugin `local_datacurso` que permite a los usuarios interactuar con una inteligencia artificial mientras navegan por los cursos en Moodle.

## Características

### ✅ Funcionalidades Implementadas

- **Chat flotante responsive**: Se posiciona en la esquina inferior derecha de la pantalla
- **Contexto de curso únicamente**: Solo aparece cuando el usuario está dentro de un curso o actividad
- **Detección automática de rol**: Muestra "Profesor" o "Estudiante" en el header según el rol del usuario
- **Interfaz moderna**: Diseño profesional con animaciones suaves
- **Minimizable**: El usuario puede minimizar/maximizar el chat
- **Responsive**: Se adapta a dispositivos móviles
- **Integración con Moodle**: Utiliza el sistema AMD de JavaScript y hooks modernos de Moodle 4.5

### 🎨 Componentes del Chat

1. **Header**: 
   - Título "Asistente IA"
   - Indicador de rol (Profesor/Estudiante)
   - Botón para minimizar/maximizar

2. **Área de mensajes**:
   - Historial de conversación
   - Indicador de escritura
   - Scroll automático

3. **Área de entrada**:
   - Caja de texto expandible
   - Botón de envío
   - Soporte para Enter y Shift+Enter

4. **Footer**:
   - Branding "Powered by Datacurso IA"

## Archivos Modificados/Agregados

### Nuevos Archivos

1. **`amd/src/chat.js`** - Lógica principal del chat en JavaScript
2. **`amd/build/chat.min.js`** - Versión compilada del JavaScript
3. **`styles/chat.css`** - Estilos CSS del chat flotante
4. **`classes/hook/chat_hook.php`** - Hook moderno para cargar el chat

### Archivos Modificados

1. **`lib.php`** - Agregadas funciones de utilidad:
   - `local_datacurso_is_course_context()` - Verifica contexto de curso
   - `local_datacurso_get_user_role_in_course()` - Detecta rol del usuario

2. **`db/hooks.php`** - Agregados hooks modernos para:
   - `before_footer_html_generation` - Carga el chat
   - `before_standard_head_html_generation` - Agrega metadatos

## ⚠️ Correcciones Importantes

**Versión 1.1 - Corrección de Hooks Legacy**

Se han corregido los errores de deprecación de Moodle 4.5:
- ✅ Eliminadas funciones callback legacy (`before_footer`, `before_standard_html_head`)
- ✅ Migrado a hooks modernos (`before_footer_html_generation`, `before_standard_head_html_generation`)
- ✅ Compatible con el sistema de hooks de Moodle 4.5

## Instalación

### Opción 1: Reemplazar Plugin Completo

1. Hacer backup del plugin actual
2. Reemplazar la carpeta `local/datacurso` con la versión modificada
3. Ir a `Administración del sitio > Notificaciones` para actualizar la base de datos

### Opción 2: Actualización Manual

Si prefieres mantener tu versión actual y solo agregar el chat:

1. **Copiar archivos nuevos**:
   ```
   amd/src/chat.js
   amd/build/chat.min.js
   styles/chat.css
   classes/hook/chat_hook.php
   ```

2. **Actualizar `lib.php`**: Agregar las funciones de utilidad (líneas 327-383)

3. **Actualizar `db/hooks.php`**: Agregar los nuevos hooks (líneas 48-57)

## Configuración

### Detección de Contexto de Curso

El chat aparece automáticamente cuando:
- El usuario está en una página de curso (`course/view.php`)
- El usuario está en una actividad del curso (`mod/*/view.php`)
- El contexto de la página es CONTEXT_COURSE o CONTEXT_MODULE

### Detección de Rol de Usuario

El sistema detecta automáticamente si el usuario es:
- **Profesor**: Si tiene permisos de `moodle/course:update` o `moodle/course:manageactivities`
- **Estudiante**: Por defecto si no tiene permisos de profesor

## Personalización

### Modificar Estilos

Edita el archivo `styles/chat.css` para cambiar:
- Colores del tema
- Tamaño del chat
- Posición en pantalla
- Animaciones

### Integrar IA

Para conectar con tu sistema de IA, modifica la función `simulateAIResponse()` en `amd/src/chat.js`:

```javascript
// Reemplazar esta función con tu lógica de IA
simulateAIResponse() {
    // Aquí va tu código para conectar con la IA
    // Ejemplo: hacer llamada AJAX a tu endpoint
}
```

### Configuración Avanzada

Puedes modificar el comportamiento del chat editando las variables en la clase `DatacursoChat`:

```javascript
// En amd/src/chat.js
constructor() {
    this.chatWidget = null;
    this.isMinimized = false;
    this.userRole = 'Estudiante';
    this.courseId = null;
    this.isInCourseContext = false;
}
```

## Compatibilidad

- **Moodle**: 4.5+
- **Navegadores**: Chrome, Firefox, Safari, Edge (versiones modernas)
- **Dispositivos**: Desktop y móvil
- **Temas**: Compatible con temas estándar de Moodle

## Solución de Problemas

### El chat no aparece

1. Verificar que estás en una página de curso
2. Comprobar la consola del navegador por errores JavaScript
3. Verificar que los archivos CSS y JS se cargan correctamente
4. Purgar cachés de Moodle (`Administración > Desarrollo > Purgar cachés`)

### Rol incorrecto en el header

1. Verificar permisos del usuario en el curso
2. Comprobar la función `local_datacurso_get_user_role_in_course()` en `lib.php`

### Problemas de estilo

1. Verificar que `styles/chat.css` se carga
2. Comprobar conflictos con otros CSS del tema
3. Usar herramientas de desarrollador para debuggear

### Errores de hooks legacy (CORREGIDO)

Si aparecen errores sobre callbacks legacy:
- ✅ **Ya corregido en esta versión**
- Los hooks ahora usan el sistema moderno de Moodle 4.5
- No más warnings de deprecación

## Próximos Pasos

Para completar la integración con IA:

1. **Crear endpoint de IA**: Desarrollar un servicio web que procese los mensajes
2. **Modificar JavaScript**: Reemplazar `simulateAIResponse()` con llamadas reales a la IA
3. **Agregar autenticación**: Incluir tokens de usuario para personalizar respuestas
4. **Implementar historial**: Guardar conversaciones en la base de datos
5. **Agregar configuraciones**: Panel de administración para configurar la IA

## Changelog

### Versión 1.2 (Actual)
- ✅ Corregida ruta del archivo CSS
- ✅ Movido CSS a ubicación estándar `/styles/chat.css`
- ✅ Eliminados errores de carga de recursos

### Versión 1.1
- ✅ Corregidos errores de hooks legacy
- ✅ Migrado a hooks modernos de Moodle 4.5
- ✅ Eliminados warnings de deprecación

### Versión 1.0
- ✅ Implementación inicial del chat flotante
- ✅ Detección de contexto de curso
- ✅ Detección de rol de usuario

## Soporte

Para soporte técnico o preguntas sobre la implementación, contactar al equipo de desarrollo de Datacurso.

---

**Versión**: 1.2  
**Fecha**: Agosto 2025  
**Compatibilidad**: Moodle 4.5+

