# Curler

A simple wrapper around PHP's cURL library to accomplish some of the most common use cases, minimally.

## How is Curler different from existing PHP cURL libraries?

Curler is more of a cURL handle factory than a wrapper for an individual cURL connection. There are no special data structures or chains of function calls. It just gives you what you need and gets out of your way.

## Make some requests.

Curler provides shortcuts for some of the more common tasks like fetching content via POST/GET requests and storing the result in memory or on the file system.

Need to just grab some data and move on? `Curler::get` and `Curler::post` handles all of the cURL configuring for you. All you need is the URL, and the set of POST fields if executing a POST request. Want to save the data to a file instead of the default? Just pass a file path as the optional argument.

## Create a cURL handle and configure it quickly.

Calling `Curler::create` without the optional array of configurations will return a cURL handle with the default CURLOPTs (see below). `Curler::get` and `Curler::post` both use this function with the defaults as a base. However, if you need more specific configuration you can make use of some of the curl config functions. Whatever your preference may be, there's no need to remember many of the CURLOPT constants if you use the `curl_config` functions.

The following configuration functions are supported:

* `Curler::curl_config_defaults`: Takes the URL as input, along with an optional configuration array, and returns an array of CURLOPTs setting `CURLOPT_HEADER` to false, `CURLOPT_RETURNTRANSFER` to TRUE, and `CURLOPT_URL` to the input URL string.

* `Curler::curl_config_post`: Pass the post fields, as a formatted query string or a key-value array, and an optional existing configuration array. Sets, or resets, `CURLOPT_POST` and `CURLOPT_POSTFIELDS` for POST requests and returns the configured array.

* `Curler::curl_config_authentication`: Pass a user name, password, and optional existing array to set `CURLOPT_USERPWD` in the configuration array. Returns the configuration array.

* `Curler::curl_config_save_file`: Takes a file handle, from an `fopen()` call, along with an optional array of configurations. Sets `CURLOPT_FILE` to the input file handle. Returns the config array.

* `Curler::curl_config_return_boolean`: Pass an optional array of configurations. Sets `CURLOPT_RETURNTRANSFER` to FALSE which makes the result of curl_exec return a boolean instead of a payload. Returns the configuration array.

* `Curler::curl_config_disable_certificate_verification`: Pass an optional array of configurations. Sets `CURLOPT_SSL_VERIFYPEER` and `CURLOPT_SSL_VERIFYHOST` settings to FALSE for those times that certificate verification on SSL requests isn't REALLY necessary. Returns the configuration array.

## Example Usage ##

Say you want to just fetch the weather for Boston, ID 2367105, using Yahoo's weather API and save the XML payload to a file for later processing. Just use the Curler::get function with the URL and optional file path to save the XML.

``` php
<?php
/**
* Use Curler to fetch the XML for Boston's weather from Yahoo's weather
* API.
*/

include 'curler.php';

// Download the weather in Boston from Yahoo! and save it to a file ( XML )
echo "Downloading Boston Weather from weather.yahooapis.com\n";
$url  = 'http://weather.yahooapis.com/forecastrss?w=2367105';
$file = 'boston_weather.xml'; // within current path

if( !\Curler::get( $url, $file ) ) {
    echo "Oh Snap! The request to '$url' failed :(\n";
}

echo "Request to '$url' was successful. Payload was saved in '$file'\n";

?>
```
