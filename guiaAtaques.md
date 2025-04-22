# Guía de Ataques y Pruebas de Seguridad

Este documento describe los ataques realizados y las verificaciones de seguridad llevadas a cabo en el proyecto mediante el script `laravel-security-tests.bash`.

## 🔒 Pruebas de Seguridad
### Verificación de que el sistema cierra la sesión automaticamente tras 1 minuto de inactividad.
1. Iniciar sesión con un usuario válido.

2. Esperar 61 segundos sin realizar ninguna petición HTTP (simular inactividad).

3. Hacer una petición a una ruta protegida (p. ej. /dashboard).

4. Comprobar que la respuesta sea redirección al login o código HTTP 401.

5. Repetir la prueba variando el tiempo (30 s, 90 s) para validar el límite.
   
### Revisión de que las cookies no presenten información sensible en texto plano.
  
1. Iniciar sesión y capturar las cookies de respuesta (usar navegador o herramienta como Postman).

2. Inspeccionar cada cookie (name y value).

3. Verificar que no incluyan datos como contraseñas, tokens JWT sin cifrar, IDs sensibles, etc.

4. Comprobar que las cookies críticas tengan las banderas HttpOnly, Secure y SameSite adecuadas.

5. Intentar decodificar o desencriptar valores para confirmar que no son legibles en texto plano.

6. Documentar cualquier hallazgo y recomendar cifrado o hash si es necesario.

### Verificación de que los usuarios no sean capaces de ingresar a las funciones del sistema a las cuales no tengan permisos según su rol asignado. 
  
1. Crear tres usuarios de prueba con roles diferentes (p. ej. admin, editor, viewer).

2. Para cada usuario:

    - Autenticar y obtener token o cookie de sesión.
    - Intentar acceder a endpoints reservados a roles superiores (p. ej. un viewer a /admin).
    - Comprobar que la respuesta sea código HTTP 403 o redirección a página de “acceso denegado”.

3. Repetir la prueba invocando acciones desde la interfaz y desde peticiones directas (API).

4. Validar que la lógica de autorización en controladores y middleware se aplique correctamente.

5. Registrar resultados y actualizar matriz de permisos si es necesario.

### Validacion de que ningún usuario pueda ingresar sin la debida autenticación por medio del logueo.

1. Sin iniciar sesión, intentar acceder a rutas protegidas (p. ej. /profile, /orders).

2. Verificar que el sistema responda con redirección al login o HTTP 401.

3. Enviar peticiones con credenciales nulas o inválidas al endpoint de login.

4. Comprobar que no se emita cookie de sesión ni token JWT.

5. Revisar los logs de autenticación para asegurar que los intentos fallidos quedan registrados.

6. Probar también con sesiones expiradas para confirmar el mismo comportamiento.

## 📂 Archivo de Ataques

Script de ataques: `laravel-security-tests.bash`

# 🔍 Ataques y Pruebas Realizadas

## 1. Cross-Site Scripting (XSS)
Inyección de scripts en formularios de creación/edición de:

- Usuarios (`<script>` en username)
- Productos (`<script>` en nombre)
- Roles (`<iframe>` malicioso en nombre)

## 2. Inyección SQL
Intentos de manipulación de base de datos mediante:

- Eliminación de tablas (`DROP TABLE`)
- Modificación masiva de precios (`UPDATE products`)
- Creación de roles no autorizados (`INSERT INTO roles`)

## 3. IDOR (Insecure Direct Object Reference)
Acceso no autorizado a:

- Edición/Eliminación de usuarios privilegiados
- Modificación de permisos de roles
- Operaciones CRUD en productos sin permisos

## 4. Bypass de Autenticación
Acceso a rutas protegidas:

- Dashboard administrativo
- Listado de usuarios/productos
- Uso de tokens JWT manipulados

## 5. Seguridad de Login
- Pruebas de inyección SQL en formulario de login
- Validación de tokens CSRF en operaciones críticas

---

# ▶️ Pasos para Ejecutar las Pruebas

Dar permisos de ejecución:

```bash
chmod +x nombre_del_script.sh
```

Antes de ejecutar las pruebas, debes modificar estas credenciales en el script para realizar pruebas con diferentes roles.
Esto permite validar accesos y restricciones específicas según el perfil del usuario (superadmin, registrador, auditor, etc.).

```bash
declare -A CREDENTIALS=(
    ["superadmin"]="admin.prueba:Adminpassword123*"
    ["registrador"]="registrador.prueba:Registradorpassword123*"
    ["auditor"]="auditor.prueba:Aurditorpassword123*"
)
```

Posteriormente, ejecutar en Git Bash:
```bash
./nombre_del_script.sh
```

Ver resultados:
- Resultados en consola con códigos de color
- Reporte detallado en: security_results/security_tests.log

## Importante:

- Requiere servidor Laravel en ejecución (php artisan serve)
- Usa datos de prueba (¡NO ejecutar en producción!)
- Limpia cookies automáticamente entre pruebas

## 🛡️ Pruebas con Kali Linux

Adicionalmente, se realizaron pruebas de hacking utilizando Kali Linux, incluyendo:

### Ataques de Fuerza Bruta:
- Utilización de Hydra contra el formulario de login para intentar descubrir credenciales válidas
- Pruebas con múltiples diccionarios de contraseñas comunes
- Verificación de bloqueo de cuenta tras intentos fallidos consecutivos
- Evaluación del límite de intentos de login permitidos

### Análisis de Vulnerabilidades SQL:
- Exploración automatizada con SQLMap sobre los formularios de la aplicación
- Intento de enumeración de bases de datos
- Pruebas de inyección en los campos de búsqueda y filtrado
- Verificación de parametrización de consultas en endpoints críticos

### Pruebas Adicionales:
- Análisis de vulnerabilidades con Nikto
- Escaneo de puertos y servicios con Nmap
- Captura y análisis de tráfico con Wireshark
- Pruebas de Man-in-the-Middle para interceptar comunicaciones

Los detalles completos de estas pruebas se encuentran en el archivo `pruebas-kali.md`.