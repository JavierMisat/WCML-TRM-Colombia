=== WCML TRM Colombia (Superfinanciera) ===
Contributors: jpmisat
Author: Juan Pablo Misat
Author URI: https://javiermisat.com
Tags: woocommerce, wpml, wcml, exchange rate, colombia, trm, superfinanciera, multicurrency, currency
Requires at least: 5.0
Tested up to: 6.5
WC requires at least: 3.0
WC tested up to: 8.8
WPML requires at least: 4.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wcml-trm-colombia

Añade un servicio de tasas de cambio automático a WooCommerce Multilingual & Multicurrency (WCML) para obtener la TRM USD/COP desde la Superintendencia Financiera de Colombia.

== Description ==

Este plugin integra un servicio de tasas de cambio automático con **WooCommerce Multilingual & Multicurrency (WCML)**. Permite obtener la Tasa Representativa del Mercado (TRM) oficial para el par de divisas Dólar Estadounidense (USD) / Peso Colombiano (COP) directamente desde la Superintendencia Financiera de Colombia.

**Características principales:**

*   **Integración con WCML:** Se registra como un nuevo proveedor de servicios de tasas de cambio en la configuración de Múltiples Monedas de WCML.
*   **TRM Oficial:** Utiliza el servicio web de la Superintendencia Financiera de Colombia para obtener la TRM vigente.
*   **Conversión Bidireccional:** Proporciona tasas para conversiones de USD a COP y de COP a USD.
*   **Cacheo Automático:** La tasa obtenida se guarda en un caché (transient de WordPress) durante 12 horas para optimizar el rendimiento y reducir las consultas al servicio externo.
*   **Fácil Configuración:** No requiere claves API ni configuraciones complejas. Simplemente active el plugin y selecciónelo como su proveedor de tasas en WCML.
*   **Registro de Errores:** Notifica los problemas de conexión o errores de la API en el registro de errores de PHP para facilitar el diagnóstico.

**Requisitos:**

*   WordPress 5.0 o superior.
*   PHP 7.2 o superior.
*   Extensión SOAP de PHP habilitada en su servidor.
*   WooCommerce 3.0 o superior.
*   WPML Multilingual CMS 4.0 o superior.
*   WooCommerce Multilingual & Multicurrency (WCML).

== Installation ==

1.  Sube la carpeta `wcml-trm-colombia` (o el archivo .zip completo del plugin) al directorio `/wp-content/plugins/` de tu instalación de WordPress.
2.  Activa el plugin a través del menú 'Plugins' en WordPress.
3.  Una vez activado, ve a `WooCommerce > WCML` en el panel de administración de WordPress.
4.  Navega a la pestaña `Múltiples Monedas`.
5.  En la sección "Servicios de tipos de cambio automáticos", busca el desplegable para seleccionar el proveedor de servicios.
6.  Selecciona "**TRM Colombia (Superfinanciera)**" de la lista.
7.  Guarda los cambios. Las tasas para los pares de monedas USD/COP se actualizarán automáticamente según la TRM oficial cuando WCML ejecute su tarea programada de actualización de tasas.

== Frequently Asked Questions ==

= ¿De dónde obtiene el plugin la tasa de cambio? =
El plugin consulta el servicio web oficial (`TCRMServicesWebService`) proporcionado por la Superintendencia Financiera de Colombia para obtener la TRM del día para el par USD/COP.

= ¿Con qué frecuencia se actualiza la tasa? =
El plugin obtiene la tasa y la almacena en un caché (transient de WordPress) con una duración de 12 horas. WooCommerce Multilingual & Multicurrency (WCML) tiene su propia programación para solicitar la actualización de las tasas de cambio (generalmente diaria), momento en el cual este plugin proporcionará la tasa más reciente obtenida de la Superfinanciera (o la versión cacheada si aún es válida). La Superfinanciera usualmente publica la TRM que regirá para el siguiente día hábil.

= ¿Qué pares de monedas soporta este servicio? =
Este plugin está diseñado específicamente para el par de monedas Dólar Estadounidense (USD) y Peso Colombiano (COP). Proporcionará la tasa para la conversión de USD a COP (ej. si USD es tu moneda base y COP una secundaria) y de COP a USD (ej. si COP es tu moneda base y USD una secundaria).

= ¿Necesito una clave API para usar este plugin? =
No, el servicio web de la Superintendencia Financiera de Colombia es público y no requiere una clave API para su consulta.

= ¿Qué sucede si el servicio de la Superfinanciera no está disponible o hay un error? =
Si el plugin no puede obtener la tasa (por ejemplo, debido a problemas de conexión con el servicio web externo, errores en la respuesta de la API, o si la extensión SOAP de PHP no está habilitada en el servidor), se registrará un error detallado en el log de errores de PHP de WordPress. En tal caso, el plugin no podrá proporcionar una nueva tasa a WCML, y WCML probablemente continuará usando la última tasa válida que tenga almacenada para ese par de monedas.

= ¿El plugin requiere alguna configuración adicional después de la instalación? =
No, aparte de seleccionarlo como el servicio de tasas de cambio en la configuración de WCML, no se requiere ninguna otra configuración.

== Changelog ==

= 1.0.5 =
*   Lanzamiento inicial del plugin.
*   Integración con WooCommerce Multilingual & Multicurrency (WCML) para tasas de cambio automáticas USD/COP.
*   Obtención de la TRM oficial desde el servicio web de la Superintendencia Financiera de Colombia.
*   Implementación de cacheo de la tasa (transient) por 12 horas para optimizar el rendimiento.
*   Manejo de errores y registro detallado para diagnóstico.
*   Soporte para conversión USD -> COP y COP -> USD.
*   Comprobación de dependencias (WooCommerce, WPML, WCML) y notificación si no están activas.
*   Comprobación de la extensión SOAP de PHP.

== Upgrade Notice ==

= 1.0.5 =
Este es el lanzamiento inicial del plugin "WCML TRM Colombia (Superfinanciera)". Asegúrate de tener todos los plugins requeridos (WooCommerce, WPML, WooCommerce Multilingual & Multicurrency) activos y la extensión SOAP de PHP habilitada en tu servidor.