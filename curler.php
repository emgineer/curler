<?php
/**
 * Curler
 *
 * Dynamically create a cURL object with the functions and properties you want
 * and nothing else. This class aims to simplify the creation and configuration
 * of generic cURL handles.
 *
 * @author  Emmanuel Gasquez <webgeniero@gmail.com>
 * @license MIT
 */

class Curler {

    /*** Low Level Actions *********************************************************/

    /**
     * Create a cURL handle using the input configuration array.
     *
     * @param  String $url             The URL to send the request to
     * @param  Array  $configurations  Set of standard cURL configuration options
     * @return cURL                    cURL Handle
     */
    public static function create( $url, $configurations = null ) {
        $curl = curl_init();

        // no configurations passed? default 'em
        if( !$configurations ) {
            $configurations = self::curl_config_defaults( $url );
        }

        // We don't assume the defaults were set since the user should only really
        // worry about setting the configurations they need.
        else {
            // + operator = append without overwrite :)
            $configurations += self::curl_config_defaults( $url ); // our defaults
        }

        // This should never fail if using the predefined subclasses, but
        // it's better to be safe than sorry.
        if( !curl_setopt_array( $curl, $configurations ) ) {
            throw new InvalidArgumentException( "Unsupported cURL configuration options" );
        }

        return $curl;
    }

    /**
     * Execute the cURL request for a set number of retries
     *
     * @param  cURL    $curl_handle  The active cURL handle to execute
     * @param  Integer $max_retries  The maximum number of times to retry the request
     * @return mixed                 Depends on the CURLOPT_RETURNTRANSFER setting
     */
    protected function execute( $curl_handle, $max_retries = 0 ) {
        do {
            $result = curl_exec( $curl_handle );

            // retry until we either succeed or exhaust our retries
            if( FALSE !== $result || 0 >= --$max_retries ) {
                break;
            }
        } while( TRUE );

        return $result;
    }

    /*** High Level Actions ********************************************************/

    /**
     * Submit a GET request
     *
     * @param  String $url       The URL to send the request to
     * @param  String $file_path The file name with full path to save the data
     * @return mixed             The result of the Curler::execute() function
     */
    public static function get( $url, $file_path = NULL ) {
        // if a file was provided we're downloading otherwise execute the request
        if( NULL !== $file_path ) {
            return self::download( $url, $file_path );
        }

        return self::request( $url );
    }

    /**
     * Submit a POST request
     *
     * @param  String $url         The URL to send the request to
     * @param  Mixed  $post_fields HTTP formatted query string or an array of fields
     * @param  String $file_path   The file name with full path to save the data
     * @return mixed               The result of the Curler::execute() function
     */
    public static function post( $url, $post_fields, $file_path = NULL ) {
        // default all the things and set the configurations for POST requests
        $configurations = self::curl_config_post(
            self::curl_config_defaults( $url ), // defaults
            $post_fields                        // fields to POST to
        );

        // if a file was provided we're downloading otherwise execute the request
        // as is
        if( NULL !== $file_path ) {
            return self::download( $url, $file_path, $configurations );
        }

        return self::request( $url, $configurations );
    }

    /**
     * Download a file from either a GET or POST request
     *
     * @param  String $url            The URL to send the request to
     * @param  String $file_path      The file name with full path to download the data
     * @param  Array  $configurations Array of configurations that have been set
     * @return mixed                  The result of the Curler::execute() function
     */
    protected function download( $url, $file_path, $configurations = NULL ) {
        // create the file handler and throw an exception if that fails
        $file_handle = fopen( $file_path, 'w+' );
        if( FALSE === $file_handle ) {
            throw new Exception( "Could not open the file '$file_path'" );
        }

        // if configs are null we have to create them before we can add the
        // file handle to it.
        if( NULL == $configurations ) {
            $configurations = self::configure_defaults( $url );
            self::set_return_mode_file(
                $configurations, // defaults configuration
                $file_handle     // the file handle
            );
        } else {
            self::set_return_mode_file( $configurations, $file_handle );
        }

        return self::request( $url, $configurations );
    }

    /**
     * Perform the request
     *
     * @param  Array $configurations Array of configurations that have been set
     * @return mixed                 The result of the Curler::execute() function
     */
    protected function request( $url, $configurations = NULL ) {
        // create the handle and execute the request
        $curl_handle = self::create( $url, $configurations );

        return self::execute( $curl_handle );
    }

    /*** Configuration Options *****************************************************/

    /**
     * Set some default configurations
     *
     * @param  String $url The URL for the cURL request
     * @return Array       The default array of CURLOPTs
     */
    public static function curl_config_defaults( $url, &$configurations = array() ) {
        // sets or resets the configuration array
        $configurations = array(
            CURLOPT_HEADER         => FALSE, // dont send headers
            CURLOPT_RETURNTRANSFER => TRUE,  // curl_exec returns data from request
            CURLOPT_URL            => $url   // the URL for the cURL handle
        );

        return $configurations;
    }

    /**
     * Set up the curl handle for POST requests
     *
     * @param  cURL  $handle      The cURL handle to modify
     * @param  Mixed $post_fields HTTP formatted query string or an array of fields
     * @return Array              The array of CURLOPTs
     */
    public static function curl_config_post( $post_fields, &$configurations = array() ) {
        // if we received an array, we do the query string formatting
        if( is_array( $post_fields ) ) {
            $post_fields = http_build_query( $post_fields );
        }

        // set the POST options
        $configurations[ CURLOPT_POST ]       = TRUE;
        $configurations[ CURLOPT_POSTFIELDS ] = $post_fields;

        return $configurations;
    }

    /**
     * Add credentials to the array of CURLOPTs
     *
     * @param  Array  $configurations The configurations to append to
     * @param  String $username       The Username
     * @param  String $password       The Password
     * @return Array                  The array of CURLOPTs
     */
    public static function curl_config_authentication( $username, $password, &$configurations = array() ) {
        $configurations[ CURLOPT_USERPWD ] = "{$username}:{$password}";

        return $configurations;
    }

    /**
     * Make the request write to a file
     *
     * @param  Array  $configurations The configurations to append to
     * @param  Handle $file_handle    The file handle pointing to the download location
     * @return Array                  The array of CURLOPTs
     */
    public static function curl_config_save_file( $file_handle, &$configurations = array() ) {
        // make curl_exec return TRUE/FALSE since we're storing the payload into the
        // file pointed to by the file handler provided
        self::set_return_mode_boolean( $configurations );

        // set the handle
        $configurations[ CURLOPT_FILE ] = $file_handle;

        return $configurations;
    }

    /**
     * Disable the fetching of data making a request return either TRUE or FALSE
     *
     * @param  Array $configurations The configurations to append to
     * @return Array                 The array of CURLOPTs
     */
    public static function curl_config_return_boolean( &$configurations = array() ) {
        $configurations[ CURLOPT_RETURNTRANSFER ] = FALSE;

        return $configurations;
    }

    /**
     * Disables the checking of certification authenticity when using HTTPS
     *
     * @param  Array  $configurations The configurations to append to
     * @return Array                  The array of CURLOPTs
     */
    public static function curl_config_disable_certificate_verification( &$configurations = array()) {
        $configurations[ CURLOPT_SSL_VERIFYPEER ] = FALSE;
        $configurations[ CURLOPT_SSL_VERIFYHOST ] = FALSE;

        return $configurations;
    }
}

?>