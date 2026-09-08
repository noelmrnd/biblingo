# Add project specific ProGuard rules here.
# You can control the set of applied configuration files using the
# proguardFiles setting in build.gradle.
#
# For more details, see
#   http://developer.android.com/guide/developing/tools/proguard.html

# Uncomment this to preserve the line number information for
# debugging stack traces.
-keepattributes SourceFile,LineNumberTable

# Capacitor bridge llama plugins Java via reflection (nombre clase/metodo
# tal cual desde JS) — sin esto R8 los renombra/elimina y el bridge rompe.
-keep class com.getcapacitor.** { *; }
-keep class com.getcapacitor.plugin.** { *; }
-keep @com.getcapacitor.annotation.CapacitorPlugin class * { *; }
-keepclassmembers class * {
    @com.getcapacitor.PluginMethod public *;
}
-keep class com.google.gson.** { *; }

# Firebase / Google Play Services (Analytics, Cloud Messaging) — usan
# reflection para deserializar mensajes push y eventos, ver caso raro
# de Google Sign-In documentado en docs/SHA_FINGERPRINTS.md (mismo tipo
# de fallo silencioso si faltan estas reglas).
-keep class com.google.firebase.** { *; }
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.firebase.**
-dontwarn com.google.android.gms.**

# @capgo/capacitor-social-login (Google + Apple Sign-In) — plugin
# comunitario, no first-party: no asumir que trae sus propias reglas.
-keep class com.getcapacitor.community.** { *; }
-keep class ee.forgr.capacitor.social.login.** { *; }
-dontwarn ee.forgr.capacitor.social.login.**

# Modelos serializados via reflection (Gson/JSON) entre WebView <-> nativo.
-keepclassmembers class * implements java.io.Serializable {
    static final long serialVersionUID;
    private static final java.io.ObjectStreamField[] serialPersistentFields;
    !static !transient <fields>;
}
