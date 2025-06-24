#!/bin/bash

# Script de configuración del Cron para ExpirePromotions
# Vive Hidalgo Backend

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Configuración del Cron${NC}"
    echo -e "${BLUE}  Vive Hidalgo Backend${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Función para obtener la ruta del proyecto
get_project_path() {
    # Obtener la ruta absoluta del directorio actual
    PROJECT_PATH=$(pwd)
    echo "$PROJECT_PATH"
}

# Función para verificar que estamos en el directorio correcto
verify_project_structure() {
    if [ ! -f "artisan" ]; then
        print_error "No se encontró el archivo artisan. Asegúrate de estar en el directorio raíz del proyecto Laravel."
        exit 1
    fi
    
    if [ ! -f "app/Console/Commands/ExpirePromotions.php" ]; then
        print_error "No se encontró el comando ExpirePromotions. Asegúrate de que esté instalado."
        exit 1
    fi
    
    print_message "Estructura del proyecto verificada correctamente."
}

# Función para probar el comando
test_command() {
    print_message "Probando el comando expire-promotions..."
    
    if php artisan app:expire-promotions --dry-run > /dev/null 2>&1; then
        print_message "Comando probado exitosamente."
    else
        print_error "Error al ejecutar el comando. Verifica que Laravel esté configurado correctamente."
        exit 1
    fi
}

# Función para mostrar opciones de frecuencia
show_frequency_options() {
    echo -e "${BLUE}Opciones de frecuencia disponibles:${NC}"
    echo "1) Cada hora (recomendado)"
    echo "2) Cada 30 minutos"
    echo "3) Cada 2 horas"
    echo "4) Diariamente a las 2:00 AM"
    echo "5) Personalizado"
    echo "6) Solo mostrar el comando (no instalar)"
}

# Función para obtener la frecuencia del usuario
get_frequency() {
    while true; do
        read -p "Selecciona una opción (1-6): " choice
        case $choice in
            1)
                CRON_FREQUENCY="0 * * * *"
                FREQUENCY_DESC="cada hora"
                break
                ;;
            2)
                CRON_FREQUENCY="*/30 * * * *"
                FREQUENCY_DESC="cada 30 minutos"
                break
                ;;
            3)
                CRON_FREQUENCY="0 */2 * * *"
                FREQUENCY_DESC="cada 2 horas"
                break
                ;;
            4)
                CRON_FREQUENCY="0 2 * * *"
                FREQUENCY_DESC="diariamente a las 2:00 AM"
                break
                ;;
            5)
                read -p "Ingresa la expresión cron personalizada (ej: '0 */3 * * *' para cada 3 horas): " CRON_FREQUENCY
                FREQUENCY_DESC="personalizado: $CRON_FREQUENCY"
                break
                ;;
            6)
                SHOW_ONLY=true
                break
                ;;
            *)
                print_error "Opción inválida. Por favor selecciona 1-6."
                ;;
        esac
    done
}

# Función para generar el comando cron
generate_cron_command() {
    PROJECT_PATH=$(get_project_path)
    CRON_COMMAND="$CRON_FREQUENCY cd $PROJECT_PATH && php artisan app:expire-promotions >> /dev/null 2>&1"
}

# Función para mostrar el comando
show_cron_command() {
    echo -e "${BLUE}Comando cron generado:${NC}"
    echo "$CRON_COMMAND"
    echo ""
    echo -e "${BLUE}Descripción:${NC} Ejecutar $FREQUENCY_DESC"
    echo -e "${BLUE}Ruta del proyecto:${NC} $PROJECT_PATH"
}

# Función para instalar el cron
install_cron() {
    if [ "$SHOW_ONLY" = true ]; then
        show_cron_command
        return
    fi
    
    print_message "Instalando el cron..."
    
    # Crear un archivo temporal con el cron
    TEMP_CRON=$(mktemp)
    
    # Obtener el crontab actual
    crontab -l 2>/dev/null > "$TEMP_CRON" || true
    
    # Agregar comentario y comando
    echo "" >> "$TEMP_CRON"
    echo "# Vive Hidalgo - ExpirePromotions ($FREQUENCY_DESC)" >> "$TEMP_CRON"
    echo "$CRON_COMMAND" >> "$TEMP_CRON"
    
    # Instalar el nuevo crontab
    if crontab "$TEMP_CRON"; then
        print_message "Cron instalado exitosamente."
        rm "$TEMP_CRON"
    else
        print_error "Error al instalar el cron."
        rm "$TEMP_CRON"
        exit 1
    fi
}

# Función para verificar la instalación
verify_installation() {
    if [ "$SHOW_ONLY" = true ]; then
        return
    fi
    
    print_message "Verificando la instalación..."
    
    if crontab -l | grep -q "app:expire-promotions"; then
        print_message "Cron verificado correctamente."
        echo ""
        echo -e "${BLUE}Cron actual:${NC}"
        crontab -l | grep -A 1 -B 1 "app:expire-promotions"
    else
        print_error "El cron no se instaló correctamente."
        exit 1
    fi
}

# Función para mostrar instrucciones adicionales
show_additional_instructions() {
    echo ""
    echo -e "${BLUE}Instrucciones adicionales:${NC}"
    echo "1. Para verificar logs: tail -f storage/logs/laravel.log | grep 'expire-promotions'"
    echo "2. Para probar manualmente: php artisan app:expire-promotions --dry-run"
    echo "3. Para ver el cron actual: crontab -l"
    echo "4. Para editar el cron: crontab -e"
    echo ""
    echo -e "${YELLOW}Nota:${NC} El cron se ejecutará por primera vez según la frecuencia configurada."
}

# Función principal
main() {
    print_header
    
    # Verificar que estamos en el directorio correcto
    verify_project_structure
    
    # Probar el comando
    test_command
    
    # Mostrar opciones de frecuencia
    show_frequency_options
    
    # Obtener la frecuencia del usuario
    get_frequency
    
    # Generar el comando cron
    generate_cron_command
    
    # Mostrar el comando
    show_cron_command
    
    # Preguntar si instalar
    if [ "$SHOW_ONLY" != true ]; then
        read -p "¿Deseas instalar este cron? (y/N): " install_choice
        if [[ $install_choice =~ ^[Yy]$ ]]; then
            install_cron
            verify_installation
            show_additional_instructions
        else
            print_message "Instalación cancelada. El comando cron está listo para instalar manualmente."
        fi
    fi
    
    echo ""
    print_message "Configuración completada."
}

# Ejecutar función principal
main "$@" 