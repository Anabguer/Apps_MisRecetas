# 🔧 SOLUCIÓN APK: Configuración WebView para Inputs de Archivo

## 🎯 PROBLEMA CONFIRMADO

- ✅ **Los inputs funcionan en navegador móvil** (via URL)
- ❌ **Los inputs NO funcionan en la APK** (WebView)
- 🔍 **Causa**: WebView de Android no está configurado para manejar `<input type="file">`

## 🛠️ SOLUCIÓN: MODIFICAR LA APK

### 1. **PERMISOS NECESARIOS** (`AndroidManifest.xml`)

```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.READ_MEDIA_IMAGES" />
<uses-permission android:name="android.permission.READ_MEDIA_VIDEO" />
```

### 2. **CONFIGURACIÓN DEL WEBVIEW** (MainActivity.java)

```java
public class MainActivity extends AppCompatActivity {
    private WebView webView;
    private ValueCallback<Uri[]> mFilePathCallback;
    private final int FILECHOOSER_RESULTCODE = 1;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        webView = findViewById(R.id.webview);
        
        // CONFIGURACIÓN CRÍTICA DEL WEBVIEW
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowFileAccessFromFileURLs(true);
        webSettings.setAllowUniversalAccessFromFileURLs(true);
        webSettings.setAllowContentAccess(true);
        webSettings.setDomStorageEnabled(true);
        
        // ESTABLECER EL WEBCHROMELIENT PERSONALIZADO
        webView.setWebChromeClient(new MyWebChromeClient());
        
        // CARGAR TU APLICACIÓN
        webView.loadUrl("https://colisan.com/sistema_apps_upload/app_recetas.html");
    }

    // WEBCHROMELIENT PERSONALIZADO PARA MANEJAR FILE INPUTS
    private class MyWebChromeClient extends WebChromeClient {
        
        // Para Android 5.0+ (API 21+)
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
            
            // Determinar tipo de archivo según accept
            String[] acceptTypes = fileChooserParams.getAcceptTypes();
            if (acceptTypes != null && acceptTypes.length > 0) {
                if (acceptTypes[0].contains("image")) {
                    intent.setType("image/*");
                } else if (acceptTypes[0].contains("video")) {
                    intent.setType("video/*");
                } else {
                    intent.setType("*/*");
                }
            } else {
                intent.setType("*/*");
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

        // Para Android 4.1-4.4 (API 16-19)
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

        // Para Android 3.0-4.0 (API 11-15)
        public void openFileChooser(ValueCallback<Uri> uploadMsg, String acceptType) {
            openFileChooser(uploadMsg, acceptType, "");
        }

        // Para Android 2.2-2.3 (API 8-10)
        public void openFileChooser(ValueCallback<Uri> uploadMsg) {
            openFileChooser(uploadMsg, "", "");
        }
    }

    // MANEJAR EL RESULTADO DE LA SELECCIÓN DE ARCHIVO
    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        
        if (requestCode == FILECHOOSER_RESULTCODE) {
            if (mFilePathCallback == null) return;
            
            Uri[] results = null;
            
            if (resultCode == Activity.RESULT_OK && data != null) {
                String dataString = data.getDataString();
                if (dataString != null) {
                    results = new Uri[]{Uri.parse(dataString)};
                }
            }
            
            mFilePathCallback.onReceiveValue(results);
            mFilePathCallback = null;
        }
    }
}
```

### 3. **SOLICITAR PERMISOS EN TIEMPO DE EJECUCIÓN** (Android 6.0+)

```java
private void requestPermissions() {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
        String[] permissions = {
            Manifest.permission.CAMERA,
            Manifest.permission.READ_EXTERNAL_STORAGE,
            Manifest.permission.WRITE_EXTERNAL_STORAGE
        };
        
        for (String permission : permissions) {
            if (ContextCompat.checkSelfPermission(this, permission) 
                != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this, permissions, 100);
                break;
            }
        }
    }
}

@Override
public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
    super.onRequestPermissionsResult(requestCode, permissions, grantResults);
    // Manejar resultado de permisos
}
```

## 🚀 IMPLEMENTACIÓN

### **OPCIÓN 1: Modificar APK Existente**
1. **Descompilar** tu APK actual
2. **Modificar** el código Java con el WebChromeClient
3. **Agregar** permisos al AndroidManifest.xml
4. **Recompilar** y firmar la APK

### **OPCIÓN 2: Crear Nueva APK**
1. **Crear proyecto** Android Studio nuevo
2. **Implementar** el código de arriba
3. **Configurar** WebView para cargar tu URL
4. **Compilar** APK desde cero

### **OPCIÓN 3: Usar Framework Híbrido**
- **Cordova/PhoneGap**: Maneja automáticamente los file inputs
- **Ionic**: Incluye plugins nativos para cámara/galería
- **React Native**: Con componentes nativos

## 📋 CÓDIGO MÍNIMO COMPLETO

Si quieres crear una APK simple que solo cargue tu web con soporte para file inputs:

```java
// MainActivity.java - CÓDIGO COMPLETO MÍNIMO
public class MainActivity extends AppCompatActivity {
    private WebView webView;
    private ValueCallback<Uri[]> mFilePathCallback;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Layout simple con solo WebView
        webView = new WebView(this);
        setContentView(webView);
        
        // Configurar WebView
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowFileAccessFromFileURLs(true);
        settings.setDomStorageEnabled(true);
        
        // WebChromeClient para file inputs
        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback, 
                                           FileChooserParams fileChooserParams) {
                mFilePathCallback = filePathCallback;
                
                Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
                intent.addCategory(Intent.CATEGORY_OPENABLE);
                intent.setType("*/*");
                
                startActivityForResult(Intent.createChooser(intent, "Seleccionar"), 1);
                return true;
            }
        });
        
        // Cargar tu aplicación
        webView.loadUrl("https://colisan.com/sistema_apps_upload/app_recetas.html");
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        
        if (requestCode == 1 && mFilePathCallback != null) {
            Uri[] results = null;
            if (resultCode == RESULT_OK && data != null) {
                results = new Uri[]{data.getData()};
            }
            mFilePathCallback.onReceiveValue(results);
            mFilePathCallback = null;
        }
    }
}
```

## 🛠️ HERRAMIENTAS NECESARIAS

- **Android Studio**: Para desarrollo nativo
- **APK Tool**: Para descompilar APK existente
- **Java JDK**: Para compilación
- **Firma digital**: Para firmar la APK

## ✅ RESULTADO ESPERADO

Con esta configuración:
- ✅ Los `<input type="file">` abrirán el selector de archivos del sistema
- ✅ Funcionará la galería de fotos
- ✅ Funcionará la selección de videos
- ✅ Compatible con todas las versiones de Android

---

**🎯 CONCLUSIÓN**: El problema no está en tu HTML/JS, está en que la APK no tiene configurado el WebChromeClient para manejar file inputs. Esta es la única solución definitiva.
