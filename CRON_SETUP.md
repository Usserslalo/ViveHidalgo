# Configuración del Cron para ExpirePromotions

## 📋 Descripción

Este documento explica cómo configurar la ejecución automática del comando `app:expire-promotions` en el servidor para desactivar automáticamente las promociones expiradas.

## 🎯 Objetivo

El comando `app:expire-promotions` debe ejecutarse periódicamente para:
- Verificar promociones con fecha de expiración pasada
- Desactivar automáticamente las promociones expiradas
- Mantener la integridad de los datos de promociones activas

## ⚙️ Configuración del Cron

### Opción 1: Cron del Sistema (Recomendado)

#### 1. Abrir el crontab del usuario
```bash
crontab -e
```

#### 2. Agregar la siguiente línea
```bash
# Ejecutar cada hora para verificar promociones expiradas
0 * * * * cd /ruta/completa/a/tu/proyecto && php artisan app:expire-promotions >> /dev/null 2>&1

# O ejecutar cada 30 minutos para mayor frecuencia
*/30 * * * * cd /ruta/completa/a/tu/proyecto && php artisan app:expire-promotions >> /dev/null 2>&1

# O ejecutar diariamente a las 2:00 AM
0 2 * * * cd /ruta/completa/a/tu/proyecto && php artisan app:expire-promotions >> /dev/null 2>&1
```

#### 3. Verificar que el cron esté activo
```bash
crontab -l
```

### Opción 2: Logs Detallados (Para Debugging)

Si necesitas logs detallados para debugging:

```bash
# Ejecutar cada hora con logs
0 * * * * cd /ruta/completa/a/tu/proyecto && php artisan app:expire-promotions >> /var/log/expire-promotions.log 2>&1
```

### Opción 3: Con Notificaciones (Opcional)

```bash
# Ejecutar cada hora y enviar notificación si hay errores
0 * * * * cd /ruta/completa/a/tu/proyecto && php artisan app:expire-promotions || echo "Error en expire-promotions: $(date)" | mail -s "Error en Cron" admin@tudominio.com
```

## 🔧 Configuración Específica por Entorno

### Desarrollo Local
```bash
# Para desarrollo, puedes ejecutar manualmente
php artisan app:expire-promotions

# O en modo simulación
php artisan app:expire-promotions --dry-run
```

### Producción
```bash
# Ejecutar cada hora en producción
0 * * * * cd /var/www/html/tu-proyecto && php artisan app:expire-promotions >> /var/log/expire-promotions.log 2>&1
```

### Staging/Testing
```bash
# Ejecutar cada 2 horas en staging
0 */2 * * * cd /var/www/html/tu-proyecto-staging && php artisan app:expire-promotions --dry-run >> /var/log/expire-promotions-staging.log 2>&1
```

## 📊 Monitoreo y Logs

### Verificar Logs de Laravel
```bash
tail -f storage/logs/laravel.log | grep "expire-promotions"
```

### Verificar Estado del Cron
```bash
# Verificar si el cron está ejecutándose
ps aux | grep cron

# Verificar logs del sistema
sudo tail -f /var/log/cron
```

### Comando de Verificación Manual
```bash
# Verificar qué promociones expirarían (modo simulación)
php artisan app:expire-promotions --dry-run

# Ejecutar manualmente
php artisan app:expire-promotions
```

## 🚨 Troubleshooting

### Problema: El cron no se ejecuta
**Solución:**
1. Verificar que el servicio cron esté activo: `sudo systemctl status cron`
2. Verificar permisos del archivo: `ls -la /ruta/a/tu/proyecto/artisan`
3. Verificar que PHP esté en el PATH: `which php`

### Problema: Permisos denegados
**Solución:**
```bash
# Dar permisos de ejecución al archivo artisan
chmod +x /ruta/a/tu/proyecto/artisan

# Verificar permisos del directorio
chmod 755 /ruta/a/tu/proyecto
```

### Problema: Variables de entorno no cargadas
**Solución:**
```bash
# Usar el comando completo con variables de entorno
0 * * * * cd /ruta/a/tu/proyecto && /usr/bin/php artisan app:expire-promotions
```

## 📈 Métricas y Monitoreo

### Logs Importantes a Monitorear
- `storage/logs/laravel.log` - Logs de Laravel
- `/var/log/cron` - Logs del sistema cron
- `/var/log/expire-promotions.log` - Logs específicos (si configurado)

### Alertas Recomendadas
- Configurar alertas si el comando falla más de 3 veces consecutivas
- Monitorear el tiempo de ejecución (no debe exceder 5 minutos)
- Alertar si no hay logs de ejecución en 24 horas

## 🔒 Seguridad

### Buenas Prácticas
1. **No ejecutar como root**: Usar un usuario específico para la aplicación
2. **Logs seguros**: No incluir información sensible en los logs
3. **Permisos mínimos**: Dar solo los permisos necesarios al usuario del cron
4. **Backup**: Mantener backup de la configuración del cron

### Ejemplo de Usuario Seguro
```bash
# Crear usuario específico para la aplicación
sudo adduser laravel-app

# Configurar cron para ese usuario
sudo crontab -u laravel-app -e
```

## 📝 Notas Importantes

1. **Ruta Absoluta**: Siempre usar rutas absolutas en el cron
2. **Variables de Entorno**: El cron no carga automáticamente las variables de entorno de Laravel
3. **Timezone**: Verificar que el timezone del servidor coincida con el de la aplicación
4. **Backup**: Hacer backup de la configuración del cron antes de modificarla

## ✅ Checklist de Configuración

- [ ] Verificar que el comando funciona manualmente
- [ ] Configurar el cron con la frecuencia deseada
- [ ] Verificar logs después de la primera ejecución
- [ ] Configurar monitoreo y alertas
- [ ] Documentar la configuración en el equipo
- [ ] Probar en entorno de staging antes de producción

## 🆘 Soporte

Si tienes problemas con la configuración:
1. Revisar los logs de Laravel: `tail -f storage/logs/laravel.log`
2. Verificar el estado del cron: `sudo systemctl status cron`
3. Probar el comando manualmente: `php artisan app:expire-promotions --dry-run`
4. Revisar permisos y rutas 