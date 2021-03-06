<?php
/**
 * RESTfm - FileMaker RESTful Web Service
 *
 * @copyright
 *  Copyright (c) 2011-2017 Goya Pty Ltd.
 *
 * @license
 *  Licensed under The MIT License. For full copyright and license information,
 *  please see the LICENSE file distributed with this package.
 *  Redistributions of files must retain the above copyright notice.
 *
 * @link
 *  http://restfm.com
 *
 * @author
 *  Gavin Stewart
 */

/**
 * RESTfm echo handler for Tonic.
 *
 * Echo's all data recieved and parsed for diagnostic purposes.
 *
 * @uri /{database}/echo
 */
class uriDatabaseEcho extends RESTfm\Resource {

    const URI = '/{database}/echo';

    /**
     * Handle a GET request for this resource
     *
     * @param RESTfm\Request $request
     * @param string $database
     *   From URI parsing: /{database}/echo
     *
     * @return NEVER RETURNS!
     */
    function get($request, $database) {
        return $this->_echo($request, $database);
    }

    /**
     * Handle a POST request for this resource
     *
     * @param RESTfm\Request $request
     * @param string $database
     *   From URI parsing: /{database}/echo
     *
     * @return NEVER RETURNS!
     */
    function post($request, $database) {
        return $this->_echo($request, $database);
    }

    /**
     * Handle a PUT request for this resource
     *
     * @param RESTfm\Request $request
     * @param string $database
     *   From URI parsing: /{database}/echo
     *
     * @return NEVER RETURNS!
     */
    function put($request, $database) {
        return $this->_echo($request, $database);
    }

    /**
     * Handle a delete request for this resource
     *
     * @param RESTfm\Request $request
     * @param string $database
     *   From URI parsing: /{database}/echo
     *
     * @return NEVER RETURNS!
     */
    function delete($request, $database) {
        return $this->_echo($request, $database);
    }

    /**
     * Echo everything we can find about this session and exit.
     *
     * @param RESTfm\Request $request
     * @param string $database
     *  From URI parsing: /{database}/echo
     *
     * @throws RESTfm\ResponseException
     *  If authentication fails.
     *
     * @return NEVER RETURNS!
     */
    function _echo($request, $database) {
        $database = RESTfm\Url::decode($database);

        if (RESTfm\Config::getVar('settings', 'diagnostics') !== TRUE) {
            header('HTTP/1.1 200 OK');
            header('Content-Type: text/plain; charset=utf-8');
            echo "Diagnostics disabled.\n";
            exit();
        }

        // Ensure we are authenticated by making a trivial query.
        $backend = RESTfm\BackendFactory::make($request, $database);
        $opsDatabase = $backend->makeOpsDatabase($database);
        $restfmDataLayouts = $opsDatabase->readLayouts();

        // Only needed to determine response format.
        $response = new RESTfm\Response($request);

        $restfmParameters = $request->getParameters();

        // Basic text response.
        header('HTTP/1.1 200 OK');
        header('Content-Type: text/plain; charset=utf-8');

        echo '        RESTfm ' . RESTfm\Version::getVersion() . ' Echo Service' . "\n";
        echo '=========================================================' . "\n";

        echo "\n" . '------------ Parameters -------------' . "\n";
        echo $restfmParameters;

        echo "\n" . '------------ Data -------------------' . "\n";
        echo $request->getMessage();

        echo "\n" . '------------ RESTfm -----------------' . "\n";
        echo 'request method=' . $request->method .  "\n";
        echo 'response format=' . $response->format . "\n";

        // Only dump $_SERVER if explicitly requested.
        if (isset($restfmParameters->RFMechoServer)) {
            echo "\n" . '------------ $_SERVER ---------------' . "\n";
            foreach ($_SERVER as $key => $val) {
                echo $key . '="' . addslashes($val) . '"' . "\n";
            }
        }

        exit();
    }

};
