<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverRequestHelper
{
    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed
     */
    public function getQueryData($key = null, $default = null)
    {
        $result = $_GET;
        if ($key) {
            $result = isset($_GET[$key]) ? $_GET[$key] : $default;
        }

        return $result;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed
     */
    public function getPost($key = null, $default = null)
    {
        $result = $_POST;
        if ($key) {
            $result = isset($_POST[$key]) ? $_POST[$key] : $default;
        }

        return $result;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed
     */
    public function getRequest($key = null, $default = null)
    {
        $result = $_REQUEST;
        if ($key) {
            $result = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        }

        return $result;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed
     */
    public function getServer($key = null, $default = null)
    {
        $result = $_SERVER;
        if ($key) {
            $result = isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
        }

        return $result;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function &getSessionData() //phpcs:ignore
    {
        return $_SESSION;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed
     * @codeCoverageIgnore
     */
    public function getSession($key = null, $default = null)
    {
        $result = $_SESSION;
        if ($key) {
            $result = isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
        }

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @codeCoverageIgnore
     */
    public function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
        if ($value === null) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @return false|string
     */
    public function getRequestContent()
    {
        return file_get_contents('php://input');
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getStringParam($key, $default = null)
    {
        $result = $this->getQueryData($key);

        return !empty($result) && is_string($result) ? $result : $default;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getHeader($key)
    {
        $value = null;
        $headers = $this->getRequestHeaders();
        foreach ($headers as $headerName => $headerValue) {
            if (strtolower($key) === strtolower($headerName)) {
                $value = $headerValue;
                break;
            }
        }

        return $value;
    }

    /**
     * @return array
     */
    protected function getRequestHeaders()
    {
        $headers = [];
        foreach ($this->getServer() as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }

        return $headers;
    }
}
