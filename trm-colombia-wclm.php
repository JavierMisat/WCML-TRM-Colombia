<?php
/**
 * Plugin Name: WCML TRM Colombia (Superfinanciera)
 * Description: Añade un servicio de tasas de cambio automático a WooCommerce Multilingual & Multicurrency (WCML) para obtener la TRM USD/COP desde la Superintendencia Financiera de Colombia.
 * Version: 1.0.8
 * Author: Juan Pablo Misat
 * Author URI: https://javiermisat.com
 * Text Domain: wcml-trm-colombia
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 3.0
 * WC tested up to: 8.8
 * WPML requires at least: 4.0
 *
 * Requires Plugins: woocommerce, sitepress-multilingual-cms, woocommerce-multilingual
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

define( 'WCML_TRM_COLOMBIA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCML_TRM_COLOMBIA_SERVICE_ID', 'trm_superfinanciera_co' );
define( 'WCML_TRM_COLOMBIA_TRANSIENT_KEY', 'wcml_trm_colombia_rate' );
define( 'WCML_TRM_COLOMBIA_TRANSIENT_EXPIRATION', 12 * HOUR_IN_SECONDS );

// Cargar la clase del servicio solo si WCML está activo y cargado.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'woocommerce_wpml' ) && class_exists( 'WCML_Multi_Currency' ) ) {
        WCML_TRM_Colombia_Plugin::get_instance();
    } else {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e( 'El plugin "WCML TRM Colombia" requiere que WooCommerce, WPML y WooCommerce Multilingual & Multicurrency estén activos.', 'wcml-trm-colombia' ); ?></p>
            </div>
            <?php
        });
    }
}, 20 );


/**
 * Clase principal del plugin WCML TRM Colombia.
 */
final class WCML_TRM_Colombia_Plugin {

    /**
     * Instancia única de la clase.
     * @var WCML_TRM_Colombia_Plugin
     */
    private static $instance;

    /**
     * Obtiene la instancia única de la clase.
     * @return WCML_TRM_Colombia_Plugin
     */
    public static function get_instance(): WCML_TRM_Colombia_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado.
     */
    private function __construct() {
        load_plugin_textdomain( 'wcml-trm-colombia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        add_action( 'init', [ $this, 'initialize_service_registration' ], 99 );
    }

    /**
     * Intenta registrar el servicio de tasas de cambio con WCML.
     */
    public function initialize_service_registration() {
        global $woocommerce_wpml;

        if ( has_filter('wcml_exchange_rates_services') || class_exists('\WCML\MultiCurrency\ExchangeRateServices\Factory') ) {
             add_filter( 'wcml_exchange_rates_services', function( $services ) {
                if (!class_exists('WCML_TRM_Exchange_Rate_Service')) {
                     error_log('WCML TRM Colombia Error: La clase WCML_TRM_Exchange_Rate_Service no fue encontrada al intentar registrar el servicio vía filtro.');
                    return $services;
                }
                $trm_service = new WCML_TRM_Exchange_Rate_Service( $this );
                $services[$trm_service->getId()] = $trm_service;
                error_log('WCML TRM Colombia: Servicio registrado vía filtro wcml_exchange_rates_services.');
                return $services;
            } );
        } elseif ( isset( $woocommerce_wpml, $woocommerce_wpml->multi_currency, $woocommerce_wpml->multi_currency->exchange_rate_services ) &&
             method_exists($woocommerce_wpml->multi_currency->exchange_rate_services, 'add_service') ) {

            if (!class_exists('WCML_TRM_Exchange_Rate_Service')) {
                error_log('WCML TRM Colombia Error: La clase WCML_TRM_Exchange_Rate_Service no fue encontrada al intentar registrar el servicio directamente.');
                return;
            }
            
            $trm_service = new WCML_TRM_Exchange_Rate_Service( $this );
            $woocommerce_wpml->multi_currency->exchange_rate_services->add_service( $trm_service->getId(), $trm_service );
            error_log('WCML TRM Colombia: Servicio registrado vía método directo add_service.');

        } else {
            error_log('WCML TRM Colombia Advertencia: No se pudo encontrar un método adecuado para registrar el servicio de tasas de cambio con WCML.');
        }
    }


    /**
     * Obtiene la TRM desde el servicio web de la Superintendencia Financiera de Colombia.
     * Utiliza un transient para cachear el resultado.
     *
     * @return float|false La TRM como float en caso de éxito, o false en caso de fallo.
     */
    public function fetch_trm_from_superfinanciera() {
        $cached_trm = get_transient( WCML_TRM_COLOMBIA_TRANSIENT_KEY );
        if ( false !== $cached_trm && is_numeric($cached_trm) ) { 
            return floatval( $cached_trm );
        }

        if ( ! class_exists( 'SoapClient' ) ) {
            error_log( 'WCML TRM Colombia Error: La extensión SOAP de PHP no está habilitada.' );
            return false;
        }

        $wsdl_url       = 'https://www.superfinanciera.gov.co/SuperfinancieraWebServiceTRM/TCRMServicesWebService/TCRMServicesWebService?WSDL';
        // Usar la misma URL base para 'location' que para el WSDL, por si Postman tiene éxito con esta.
        $location_url   = 'https://www.superfinanciera.gov.co/SuperfinancieraWebServiceTRM/TCRMServicesWebService/TCRMServicesWebService';
        $query_date     = date( 'Y-m-d' );
        $plugin_version = $this->get_plugin_version();

        $stream_context_options = [
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                // 'allow_self_signed' => true, // Solo para pruebas extremas si hay problemas de certificado, NO para producción.
                // 'cafile'            => '/path/to/your/cacert.pem', // Si necesitas un CA bundle específico.
                // 'ciphers'           => 'HIGH:!SSLv3:!TLSv1.0:!TLSv1.1', // Forzar ciphers más nuevos si es necesario.
            ],
        ];
        // Para depuración extrema de SSL (NO USAR EN PRODUCCIÓN):
        // $stream_context_options['ssl']['verify_peer'] = false;
        // $stream_context_options['ssl']['verify_peer_name'] = false;


        $soap_options = [
            'trace'              => true,
            'exceptions'         => true,
            'connection_timeout' => 25, 
            'cache_wsdl'         => WSDL_CACHE_NONE, // Deshabilitar caché de WSDL para depuración
            'user_agent'         => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36', // User agent genérico
            'keep_alive'         => false, 
            'stream_context'     => stream_context_create($stream_context_options),
            'location'           => $location_url, // Forzar el endpoint de la solicitud
            'soap_version'       => SOAP_1_1, // Ser explícito con la versión SOAP
        ];

        try {
            error_log("WCML TRM Colombia: Intentando conexión SOAP. WSDL: " . $wsdl_url . ", Location: " . $location_url);
            $soap_client = new SoapClient( $wsdl_url, $soap_options );
            $call_params = [ 'tcrmQueryAssociatedDate' => $query_date ];
            $response    = $soap_client->__soapCall( 'queryTCRM', [ $call_params ] );
            error_log("WCML TRM Colombia: Respuesta SOAP recibida.");

            $trm_value_candidate = null;
            if ( isset( $response->return, $response->return->value ) ) {
                $trm_value_candidate = $response->return->value;
            } elseif ( isset( $response->queryTCRMReturn, $response->queryTCRMReturn->value ) ) {
                $trm_value_candidate = $response->queryTCRMReturn->value;
            }

            if ( null !== $trm_value_candidate && is_numeric( $trm_value_candidate ) ) {
                $trm_value = floatval( $trm_value_candidate );
                if ( $trm_value > 0 ) {
                    set_transient( WCML_TRM_COLOMBIA_TRANSIENT_KEY, $trm_value, WCML_TRM_COLOMBIA_TRANSIENT_EXPIRATION );
                    error_log("WCML TRM Colombia: TRM obtenida y guardada en transient: " . $trm_value);
                    return $trm_value;
                } else {
                    error_log( sprintf( 'WCML TRM Colombia Error: Valor TRM no positivo recibido: %s', esc_html( strval( $trm_value_candidate ) ) ) );
                    return false;
                }
            } elseif ( isset( $response->return, $response->return->message ) && ! empty( $response->return->message ) ) {
                 error_log( sprintf( 'WCML TRM Colombia Error: API Superfinanciera devolvió mensaje: %s', esc_html( $response->return->message ) ) );
                return false;
            } else {
                $response_dump = print_r( $response, true );
                error_log( 'WCML TRM Colombia Debug: Respuesta inesperada de Superfinanciera: ' . $response_dump );
                return false;
            }
        } catch ( SoapFault $e ) {
            error_log( sprintf( 'WCML TRM Colombia SoapFault: %s - %s. WSDL: %s. Location: %s.', $e->faultcode, $e->getMessage(), $wsdl_url, $location_url ) );
            error_log( 'WCML TRM Colombia SoapFault Trace: ' . $e->getTraceAsString() );
            if (isset($soap_client)) {
                error_log( 'WCML TRM Colombia Last SOAP Request Headers: ' . print_r($soap_client->__getLastRequestHeaders(), true) );
                error_log( 'WCML TRM Colombia Last SOAP Request: ' . print_r($soap_client->__getLastRequest(), true) );
                error_log( 'WCML TRM Colombia Last SOAP Response Headers: ' . print_r($soap_client->__getLastResponseHeaders(), true) );
                error_log( 'WCML TRM Colombia Last SOAP Response: ' . print_r($soap_client->__getLastResponse(), true) );
            }
            if (strpos($e->getMessage(), 'Ip Forbidden') !== false || (isset($e->faultcode) && strpos($e->faultcode, 'Client') !== false && strpos(strtolower($e->getMessage()), 'forbidden') !== false) ) {
                 error_log('WCML TRM Colombia CRITICAL: La IP del servidor parece estar bloqueada por la Superfinanciera. Por favor, contacte a su proveedor de hosting o a la Superfinanciera.');
            }
            return false;
        } catch ( Exception $e ) {
            error_log( sprintf( 'WCML TRM Colombia Exception: %s. WSDL: %s. Location: %s. Trace: %s', $e->getMessage(), $wsdl_url, $location_url, $e->getTraceAsString() ) );
            return false;
        }
    }

    /**
     * Obtiene la versión del plugin desde la cabecera del archivo.
     * @return string Versión del plugin.
     */
    public function get_plugin_version(): string {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data( __FILE__ );
        return $plugin_data['Version'] ?? '1.0.0';
    }
}

/**
 * Clase que representa el servicio de tasas de cambio para TRM Colombia.
 */
if ( ! class_exists( 'WCML_TRM_Exchange_Rate_Service' ) ) {
    
    class WCML_TRM_Exchange_Rate_Service {

        private $id = WCML_TRM_COLOMBIA_SERVICE_ID;
        private $url = 'https://www.superfinanciera.gov.co/jsp/index.jsf';
        private $last_error = null;
        private $main_plugin_instance;

        public function __construct( WCML_TRM_Colombia_Plugin $main_plugin_instance ) {
            $this->main_plugin_instance = $main_plugin_instance;
        }

        public function getId(): string {
            return $this->id;
        }

        public function getName(): string {
            return __( 'TRM Colombia (Superfinanciera)', 'wcml-trm-colombia' );
        }

        public function getUrl(): string {
            return $this->url;
        }

        public function isKeyRequired(): bool {
            return false;
        }
        
        public function getApiKey() {
            return '';
        }

        public function setApiKey( $api_key ) {
            // No hace nada.
        }
        
        public function getLastError() {
            return $this->last_error;
        }

        public function clearLastError() {
            $this->last_error = null;
        }
        
        public function getSettingsForm(): string {
             return '<p>' . esc_html__( 'Este servicio obtiene la TRM oficial de Colombia y no requiere configuración adicional.', 'wcml-trm-colombia' ) . '</p>';
        }

        public function getSetting() {
            return null;
        }

        /**
         * Obtiene las tasas de cambio.
         * Este método es llamado por WCML.
         *
         * @param string $base_currency_code La moneda base de la tienda.
         * @param array  $target_currencies  Array de códigos de moneda para los que se necesitan tasas.
         * @return array Array asociativo [CURRENCY_CODE => RATE_VS_BASE_CURRENCY] o array vacío si falla.
         */
        public function getRates( string $base_currency_code, array $target_currencies ): array {
            error_log("WCML TRM Colombia Service (getRates): Llamado con base_currency_code = " . esc_html($base_currency_code) . ", target_currencies = " . print_r($target_currencies, true));

            $this->clearLastError();
            $trm_usd_to_cop = $this->main_plugin_instance->fetch_trm_from_superfinanciera();
            error_log("WCML TRM Colombia Service (getRates): fetch_trm_from_superfinanciera() devolvió: " . print_r($trm_usd_to_cop, true));

            if ( false === $trm_usd_to_cop || ! is_numeric( $trm_usd_to_cop ) || $trm_usd_to_cop <= 0 ) {
                $error_message = __( 'No se pudo obtener una TRM válida desde la Superfinanciera de Colombia. Verifique los logs del servidor para más detalles (posible bloqueo de IP o error de conexión).', 'wcml-trm-colombia' );
                $this->last_error = $error_message;
                error_log("WCML TRM Colombia Service Error (getRates): TRM inválida. " . $error_message);
                return []; 
            }

            $rates = [];

            foreach ( $target_currencies as $target_currency ) {
                if ( $target_currency === 'COP' && $base_currency_code === 'USD' ) {
                    $rates['COP'] = $trm_usd_to_cop;
                } elseif ( $target_currency === 'USD' && $base_currency_code === 'COP' ) {
                    if ($trm_usd_to_cop == 0) { 
                        $this->last_error = __('La TRM obtenida es cero, no se puede calcular la tasa inversa.', 'wcml-trm-colombia');
                        error_log("WCML TRM Colombia Service Error (getRates): TRM es cero, no se puede calcular la inversa para USD.");
                        continue; 
                    }
                    $rates['USD'] = 1 / $trm_usd_to_cop;
                }
            }
            
            error_log("WCML TRM Colombia Service (getRates): Tasas calculadas: " . print_r($rates, true));

            if (empty($rates) && $trm_usd_to_cop && !empty($target_currencies)) {
                 error_log("WCML TRM Colombia Service Info (getRates): No se calcularon tasas para la combinación de monedas dada. Base: " . esc_html($base_currency_code) . ", Objetivos: " . esc_html(implode(',', $target_currencies)) . ", TRM: " . esc_html($trm_usd_to_cop) . ". Esto podría ser normal si las monedas solicitadas no son USD o COP.");
            }

            return $rates;
        }
    }
}
