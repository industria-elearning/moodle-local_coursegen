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
- **Integración con Moodle**: Utiliza el sistema AMD de JavaScript y hooks de Moodle

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
4. **`classes/hook/chat_hook.php`** - Hook para cargar el chat (no utilizado en versión final)

### Archivos Modificados

1. **`lib.php`** - Agregadas funciones para:
   - `local_datacurso_before_footer()` - Carga el chat en páginas de curso
   - `local_datacurso_is_course_context()` - Verifica contexto de curso
   - `local_datacurso_get_user_role_in_course()` - Detecta rol del usuario
   - `local_datacurso_before_standard_html_head()` - Agrega metadatos

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
   ```

2. **Actualizar `lib.php`**: Agregar las funciones al final del archivo:
   ```php
   // Copiar las funciones desde la línea 327 en adelante
   ```

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

### Rol incorrecto en el header

1. Verificar permisos del usuario en el curso
2. Comprobar la función `local_datacurso_get_user_role_in_course()` en `lib.php`

### Problemas de estilo

1. Verificar que `styles/chat.css` se carga
2. Comprobar conflictos con otros CSS del tema
3. Usar herramientas de desarrollador para debuggear

## Próximos Pasos

Para completar la integración con IA:

1. **Crear endpoint de IA**: Desarrollar un servicio web que procese los mensajes
2. **Modificar JavaScript**: Reemplazar `simulateAIResponse()` con llamadas reales a la IA
3. **Agregar autenticación**: Incluir tokens de usuario para personalizar respuestas
4. **Implementar historial**: Guardar conversaciones en la base de datos
5. **Agregar configuraciones**: Panel de administración para configurar la IA

## Soporte

Para soporte técnico o preguntas sobre la implementación, contactar al equipo de desarrollo de Datacurso.

---

**Versión**: 1.0  
**Fecha**: Agosto 2025  
**Compatibilidad**: Moodle 4.5+

