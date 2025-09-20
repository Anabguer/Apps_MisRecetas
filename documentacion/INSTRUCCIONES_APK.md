# 📱 INSTRUCCIONES PASO A PASO PARA GENERAR APK

## ✅ **ESTADO ACTUAL**
- 🔥 **Aplicación funcionando**: http://localhost/mis_recetas/
- 📱 **PWA optimizada**: Manifest y Service Worker listos
- 🗄️ **151 recetas migradas**: Base de datos operativa
- 🔐 **Sistema de login**: Usuario-aplicación implementado

---

## 🚀 **PASO 1: GENERAR APK CON PWA BUILDER**

### **1.1 Abrir PWA Builder:**
```
🌐 Ir a: https://www.pwabuilder.com/
```

### **1.2 Introducir URL:**
```
📝 URL: http://localhost/mis_recetas/
🔘 Clic en "Start" o "Generate"
```

### **1.3 Configurar la aplicación:**
```
📋 App Name: Mis Recetas
📦 Package Name: com.misrecetas.app
🔢 Version: 1.0.0
📱 Target: Android APK
```

### **1.4 Opciones recomendadas:**
```
✅ Enable notifications: NO (por ahora)
✅ Offline support: YES (ya implementado)
✅ Install prompt: YES
✅ Fullscreen: YES
```

---

## 🔧 **PASO 2: CONFIGURACIÓN AVANZADA (OPCIONAL)**

### **2.1 Si PWA Builder pide más configuración:**

#### **Icons (si es necesario):**
```json
"icons": [
  {
    "src": "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E🍃%3C/text%3E%3C/svg%3E",
    "sizes": "any",
    "type": "image/svg+xml"
  }
]
```

#### **Permisos APK:**
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
```

---

## 📥 **PASO 3: DESCARGAR Y PROBAR**

### **3.1 Descargar APK:**
```
📱 Clic en "Download" → Android Package
💾 Guardar archivo .apk
```

### **3.2 Instalar en dispositivo:**
```
🔧 Activar "Instalación de fuentes desconocidas"
📲 Transferir APK al móvil
📱 Instalar y probar
```

---

## 🧪 **PASO 4: TESTING EN MÓVIL**

### **4.1 Funciones a probar:**
```
✅ Login/registro funciona
✅ Crear nueva receta
✅ Subir imagen (cámara/galería)
✅ Filtros por tipo
✅ Búsqueda en tiempo real
✅ Editar receta existente
✅ Eliminar receta
✅ Funciona sin internet (PWA)
```

### **4.2 Problemas comunes:**
```
❌ "No se puede conectar": 
   → Verificar que XAMPP esté ejecutándose
   → Verificar IP local del PC

❌ "Error de certificado":
   → Normal en localhost, ignorar por ahora
```

---

## 🌐 **PASO 5: PREPARAR PARA PRODUCCIÓN**

### **5.1 Para Google Play Store:**
```
🔐 Necesitarás certificado firmado
🌍 URL de producción (Hostalia)
📋 Descripción para Store
📸 Screenshots de la app
```

### **5.2 Hostalia (cuando estés listo):**
```
🗄️ Subir base de datos: sistema_apps_db
📁 Subir archivos: public_html/mis_recetas/
🔧 Actualizar config/database.php
🌐 Nueva APK con URL real
```

---

## ⚡ **SOLUCIÓN RÁPIDA SI FALLA PWA BUILDER**

### **Alternativa: Cordova/PhoneGap Build:**
```
1. 📦 Crear package.json
2. 🔧 Configurar config.xml
3. 📱 Build online
```

### **Alternativa: Android Studio:**
```
1. 📱 Crear proyecto WebView
2. 🔗 Apuntar a localhost
3. 📦 Generar APK manual
```

---

## 🎯 **RESULTADO ESPERADO**

```
📱 APK funcional de ~5-15MB
🚀 Instala como app nativa
🔄 Funciona offline (recetas cacheadas)
🔐 Login con tu usuario actual
📊 Todas las 151 recetas disponibles
```

---

**🍃 ¡LISTO PARA GENERAR TU PRIMERA APK!**

**¿Empezamos con PWA Builder?** 
👆 Abre https://www.pwabuilder.com/ y sigue los pasos
