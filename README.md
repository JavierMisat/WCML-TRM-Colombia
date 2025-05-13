# WCML TRM Colombia (Superfinanciera)

**Versión:** 1.0.8
**Autor:** Juan Pablo Misat
**URI del Autor:** https://javiermisat.com
**Requiere WordPress:** 5.0 o superior
**Probado hasta WordPress:** 6.5 (Asumido, ajustar según pruebas)
**Requiere WooCommerce:** 3.0 o superior
**Requiere WPML:** 4.0 o superior
**Requiere WooCommerce Multilingual & Multicurrency (WCML):** Sí (versión compatible con los hooks usados)
**Requiere PHP:** 7.4 o superior (recomendado para `SoapClient` moderno y `stream_context`)
**Requiere Extensión SOAP PHP:** Sí
**Text Domain:** `wcml-trm-colombia`
**Licencia:** GPL-2.0-or-later
**URI de Licencia:** http://www.gnu.org/licenses/gpl-2.0.html

Añade un servicio de tasas de cambio automático a **WooCommerce Multilingual & Multicurrency (WCML)** para obtener la Tasa Representativa del Mercado (TRM) USD/COP directamente desde el servicio web oficial de la Superintendencia Financiera de Colombia.

## Descripción

Este plugin se integra con el sistema de múltiples monedas de WooCommerce Multilingual & Multicurrency (WCML), permitiendo a los administradores de tiendas seleccionar "TRM Colombia (Superfinanciera)" como un proveedor automático de tasas de cambio. Una vez configurado, obtendrá diariamente la TRM oficial para la conversión entre Dólares Estadounidenses (USD) y Pesos Colombianos (COP).

Esto es especialmente útil para tiendas colombianas que operan con múltiples monedas y necesitan una fuente confiable y actualizada para la tasa de cambio USD/COP.

## Características Principales

* **Servicio de Tasa de Cambio para WCML:** Se registra como "TRM Colombia (Superfinanciera)" en las opciones de servicios de tasas de cambio automáticas de WCML.
* **Obtención Automática de TRM:** Consulta el servicio web de la Superintendencia Financiera de Colombia para la TRM más reciente.
* **Conversión USD/COP:** Proporciona la tasa para:
    * COP, si la moneda base de la tienda es USD.
    * USD, si la moneda base de la tienda es COP.
* **No Requiere Clave API:** Acceso directo al servicio público de la Superfinanciera.
* **Optimización con Transients:** Almacena la TRM obtenida en un transient de WordPress (por defecto durante 12 horas) para mejorar el rendimiento y minimizar las solicitudes al servicio web.
* **Registro de Errores:** Registra información y errores en el archivo `debug.log` de WordPress (si `WP_DEBUG_LOG` está activado) para facilitar la solución de problemas.
* **Fácil Configuración:** Una vez activado, se selecciona desde la configuración de WCML.

## ¿Cómo Funciona?

1.  **Activación del Plugin:** Al activar, el plugin verifica si WooCommerce, WPML y WooCommerce Multilingual & Multicurrency están activos.
2.  **Registro del Servicio:**
    * El plugin se engancha al sistema de WCML para añadir "TRM Colombia (Superfinanciera)" a la lista de servicios de tasas de cambio disponibles.
    * Esto ocurre durante la acción `init` de WordPress.
3.  **Configuración en WCML:**
    * El administrador de la tienda debe ir a `WPML` -> `WooCommerce Multilingual` -> pestaña `Multi-currency`.
    * En la sección "Tasas de cambio automáticas", se debe habilitar la opción y seleccionar "TRM Colombia (Superfinanciera)" como el servicio.
4.  **Obtención de Tasas:**
    * Cuando WCML solicita una actualización de tasas (ya sea manualmente o a través de su CRON programado) y el servicio "TRM Colombia (Superfinanciera)" está activo:
        * El plugin primero verifica si existe una TRM válida en el transient de WordPress.
        * Si no hay un transient válido, realiza una solicitud SOAP al servicio web de la Superintendencia Financiera de Colombia.
        * Si la solicitud es exitosa, el valor de la TRM (USD a COP) se almacena en el transient.
        * El plugin devuelve la tasa apropiada a WCML (TRM directa si base=USD y target=COP, o `1/TRM` si base=COP y target=USD).
    * WCML utiliza esta tasa para actualizar los precios en la moneda secundaria.

## Requisitos

* WordPress 5.0 o superior.
* WooCommerce 3.0 o superior.
* WPML (Sitepress Multilingual CMS) 4.0 o superior.
* **WooCommerce Multilingual & Multicurrency (WCML)** instalado y activado (versión que incluya el sistema de servicios de tasas de cambio).
* PHP 7.4 o superior (recomendado).
* **Extensión SOAP de PHP habilitada** en tu servidor. Contacta a tu proveedor de hosting si no estás seguro.
* La moneda base de tu tienda en WooCommerce debe ser **USD** o **COP** para que este plugin funcione directamente.
* Tanto USD como COP deben estar configuradas como monedas activas en WCML.

## Instalación

1.  Descarga el archivo `.zip` del plugin.
2.  Ve a tu panel de administración de WordPress -> `Plugins` -> `Añadir nuevo`.
3.  Haz clic en `Subir plugin` y selecciona el archivo `.zip` que descargaste.
4.  Activa el plugin "WCML TRM Colombia (Superfinanciera)" a través del menú 'Plugins' en WordPress.

## Configuración Post-Instalación

1.  Ve a `WPML` en el menú de administración de WordPress.
2.  Haz clic en `WooCommerce Multilingual`.
3.  Selecciona la pestaña `Multi-currency`.
4.  Desplázate a la sección **"Tasas de cambio automáticas"**.
5.  Marca la casilla **"Habilitar tasas de cambio automáticas"**.
6.  En el menú desplegable **"Servicio de tasas de cambio"**, selecciona **"TRM Colombia (Superfinanciera)"**.
    * No se requiere ninguna clave API para este servicio.
7.  Elige la **"Frecuencia de actualización"** (por ejemplo, "Diariamente").
8.  Haz clic en el botón **"Guardar cambios"** en la parte inferior de la página de configuración de WCML.
9.  Opcionalmente, puedes hacer clic en el botón **"Actualizar tasas manualmente ahora"** (puede tener un texto ligeramente diferente según la versión de WCML) para probar la conexión y obtener la primera tasa.

## Solución de Problemas

* **El servicio no aparece en WCML:**
    * Asegúrate de que todos los plugins requeridos (WooCommerce, WPML, WooCommerce Multilingual) estén activos.
    * Verifica que no haya errores fatales de PHP que impidan la carga del plugin (revisa `wp-content/debug.log` si `WP_DEBUG` y `WP_DEBUG_LOG` están habilitados en `wp-config.php`).
* **Error "IP Forbidden" o Fallo en la Conexión SOAP:**
    * Este error significa que el servidor de la Superintendencia Financiera está bloqueando las solicitudes desde la dirección IP de tu servidor.
    * **Verifica tu `debug.log`:** El plugin registra detalles de la conexión SOAP. Busca mensajes como "Ip Forbidden" o fallos de conexión.
    * **Prueba con Postman:** Utiliza una herramienta como Postman para realizar una solicitud SOAP directa al endpoint `https://www.superfinanciera.gov.co/SuperfinancieraWebServiceTRM/TCRMServicesWebService/TCRMServicesWebService` desde tu máquina local y desde el servidor (si es posible) para aislar el problema.
    * **Contacta a tu proveedor de hosting:** Pregunta si hay restricciones de firewall salientes o si la IP de tu servidor podría tener problemas de reputación.
    * **Contacta a la Superfinanciera:** Aunque es una vía más lenta, podrías consultar si tienen requisitos de IP en lista blanca.
* **Error "Extensión SOAP de PHP no está habilitada":**
    * Contacta a tu proveedor de hosting para que habiliten la extensión `php-soap` en tu servidor.
* **Tasas no se actualizan o son incorrectas:**
    * Asegúrate de que "TRM Colombia (Superfinanciera)" esté seleccionado como el servicio activo en WCML.
    * Verifica que la moneda base de tu tienda sea USD o COP. Si es otra, WCML necesitará tasas de otros servicios para convertir tu moneda base a USD antes de poder usar la TRM.
    * Revisa el `debug.log` para cualquier error reportado por el plugin durante la obtención de la TRM o el cálculo de tasas.
    * El valor de la TRM se almacena en un transient (`wcml_trm_colombia_rate`). Puedes intentar eliminar este transient de la tabla `wp_options` de tu base de datos para forzar una nueva obtención.

## Licencia

Este plugin es distribuido bajo la licencia GPL-2.0-or-later.

## Descargo de Responsabilidad

Este plugin es una herramienta independiente y no está afiliado, asociado, autorizado, respaldado por, ni de ninguna manera conectado oficialmente con la Superintendencia Financiera de Colombia, ni con OnTheGoSystems (los desarrolladores de WPML y WCML).
