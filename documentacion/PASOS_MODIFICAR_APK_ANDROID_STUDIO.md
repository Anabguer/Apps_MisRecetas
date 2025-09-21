# 🔧 PASOS: Modificar APK en Android Studio

## 📱 MODIFICACIONES NECESARIAS

### 1. **PERMISOS** (`app/src/main/AndroidManifest.xml`)

Agrega estos permisos ANTES de `<application>`:

```xml
<!-- Permisos para acceso a archivos -->
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.READ_MEDIA_IMAGES" />
<uses-permission android:name="android.permission.READ_MEDIA_VIDEO" />

<!-- Para Android 13+ -->
<uses-permission android:name="android.permission.READ_MEDIA_IMAGES" />
<uses-permission android:name="android.permission.READ_MEDIA_VIDEO" />
```

### 2. **MODIFICAR MainActivity.java**

Busca tu `MainActivity.java` y reemplaza todo el contenido por:

```java
package tu.paquete.nombre; // MANTÉN TU PACKAGE NAME ORIGINAL

import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {
    
    private WebView webView;
    private ValueCallback<Uri[]> mFilePathCallback;
    private final int FILECHOOSER_RESULTCODE = 1;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Si tienes un layout XML, úsalo. Si no, crea WebView programáticamente
        // setContentView(R.layout.activity_main); // Si tienes layout
        
        // Buscar WebView existente o crear uno nuevo
        webView = findViewById(R.id.webview); // Si está en tu layout
        // Si no tienes layout XML, descomenta las siguientes líneas:
        // webView = new WebView(this);
        // setContentView(webView);

        // CONFIGURACIÓN CRÍTICA DEL WEBVIEW
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowFileAccessFromFileURLs(true);
        webSettings.setAllowUniversalAccessFromFileURLs(true);
        webSettings.setAllowContentAccess(true);
        webSettings.setDomStorageEnabled(true);
        webSettings.setLoadWithOverviewMode(true);
        webSettings.setUseWideViewPort(true);
        webSettings.setBuiltInZoomControls(false);
        webSettings.setDisplayZoomControls(false);

        // WebViewClient para navegación
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                view.loadUrl(url);
                return true;
            }
        });

        // WEBCHROMELIENT PERSONALIZADO - ESTO ES LO MÁS IMPORTANTE
        webView.setWebChromeClient(new WebChromeClient() {
            
            // Para Android 5.0+ (API 21+) - MÉTODO PRINCIPAL
            @Override
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback, 
                                           FileChooserParams fileChooserParams) {
                
                // Cerrar callback anterior si existe
                if (mFilePathCallback != null) {
                    mFilePathCallback.onReceiveValue(null);
                }
                mFilePathCallback = filePathCallback;

                Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
                intent.addCategory(Intent.CATEGORY_OPENABLE);
                
                // Determinar tipo de archivo
                String[] acceptTypes = fileChooserParams.getAcceptTypes();
                if (acceptTypes != null && acceptTypes.length > 0) {
                    String acceptType = acceptTypes[0];
                    if (acceptType.contains("image")) {
                        intent.setType("image/*");
                    } else if (acceptType.contains("video")) {
                        intent.setType("video/*");
                    } else {
                        intent.setType("*/*");
                    }
                } else {
                    intent.setType("*/*"); // Por defecto cualquier archivo
                }

                try {
                    startActivityForResult(Intent.createChooser(intent, "Seleccionar archivo"), 
                                         FILECHOOSER_RESULTCODE);
                    return true;
                } catch (ActivityNotFoundException e) {
                    mFilePathCallback = null;
                    return false;
                }
            }

            // Para versiones anteriores de Android - COMPATIBILIDAD
            public void openFileChooser(ValueCallback<Uri> uploadMsg, String acceptType, String capture) {
                Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
                intent.addCategory(Intent.CATEGORY_OPENABLE);
                intent.setType(acceptType.isEmpty() ? "*/*" : acceptType);
                
                try {
                    startActivityForResult(Intent.createChooser(intent, "Seleccionar archivo"), 
                                         FILECHOOSER_RESULTCODE);
                } catch (ActivityNotFoundException e) {
                    if (uploadMsg != null) {
                        uploadMsg.onReceiveValue(null);
                    }
                }
            }

            public void openFileChooser(ValueCallback<Uri> uploadMsg, String acceptType) {
                openFileChooser(uploadMsg, acceptType, "");
            }

            public void openFileChooser(ValueCallback<Uri> uploadMsg) {
                openFileChooser(uploadMsg, "", "");
            }
        });

        // CARGAR TU APLICACIÓN WEB
        webView.loadUrl("https://colisan.com/sistema_apps_upload/app_recetas.html");
    }

    // MANEJAR RESULTADO DE SELECCIÓN DE ARCHIVO - CRÍTICO
    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        
        if (requestCode == FILECHOOSER_RESULTCODE) {
            if (mFilePathCallback == null) return;
            
            Uri[] results = null;
            
            // Si el usuario seleccionó un archivo
            if (resultCode == Activity.RESULT_OK && data != null) {
                String dataString = data.getDataString();
                if (dataString != null) {
                    results = new Uri[]{Uri.parse(dataString)};
                }
            }
            
            // Enviar resultado al WebView
            mFilePathCallback.onReceiveValue(results);
            mFilePathCallback = null;
        }
    }

    // Manejar botón atrás
    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
```

### 3. **VERIFICAR/CREAR LAYOUT** (`app/src/main/res/layout/activity_main.xml`)

Si no tienes layout o quieres simplificarlo:

```xml
<?xml version="1.0" encoding="utf-8"?>
<LinearLayout xmlns:android="http://schemas.android.com/apk/res/android"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    android:orientation="vertical">

    <WebView
        android:id="@+id/webview"
        android:layout_width="match_parent"
        android:layout_height="match_parent" />

</LinearLayout>
```

### 4. **COMPILAR Y PROBAR**

1. **Sync Project** (botón de sincronizar en Android Studio)
2. **Build → Clean Project**
3. **Build → Rebuild Project**
4. **Run** en tu dispositivo de prueba

## 🚨 **PUNTOS CRÍTICOS**

1. **NO cambies** tu package name original
2. **Mantén** el mismo `applicationId` en `build.gradle`
3. **El método `onShowFileChooser`** es el más importante - ahí se abre la galería
4. **El método `onActivityResult`** devuelve el archivo seleccionado al WebView

## 🔧 **SI HAY ERRORES**

### Error: "Cannot resolve symbol WebView"
```java
// Agregar imports al inicio:
import android.webkit.WebView;
import android.webkit.WebSettings;
import android.webkit.WebChromeClient;
import android.webkit.ValueCallback;
```

### Error: "Cannot resolve symbol AppCompatActivity"
```java
// Cambiar por:
import androidx.appcompat.app.AppCompatActivity;
// O si usas versión antigua:
import android.support.v7.app.AppCompatActivity;
```

## ✅ **RESULTADO ESPERADO**

Después de estas modificaciones:
- ✅ Los inputs de archivo abrirán la galería del sistema
- ✅ Podrás seleccionar imágenes y videos
- ✅ Los archivos se subirán correctamente a tu aplicación web

## 🎯 **PRUEBA RÁPIDA**

1. Instala la APK modificada
2. Ve a "Nueva Receta"
3. Toca "Seleccionar imagen"
4. **Debería abrirse la galería** del sistema

---

**💡 TIP**: Si algo no funciona, revisa el **Logcat** en Android Studio para ver errores específicos.
