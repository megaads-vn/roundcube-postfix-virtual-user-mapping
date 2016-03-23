<?php

/*
 * Virtual User mapping multiple user logins for one account
 *
 * @version 1.0
 * @license GNU GPLv3+
 * @author GDR!
 * @author Pierre Arlt
 */
class postfix_virtual_user_mapping extends rcube_plugin
{
    const LOGIN_DATA_USER = 'user';
    const LOGIN_DATA_PASSWORD = 'password';
    const LOGIN_DATA_PASS = 'pass';

    /** @var  rcmail */
    private $app;

    /** @var  string */
    private $postfixVirtualPath = '/etc/postfix/virtual';

    /** @var string  */
    private $logName = 'postfix_virtual_user_log';


    function init()
    {
        $this->app = rcmail::get_instance();
        $this->add_hook('authenticate', array($this, 'login'));
    }      

    /**
     * @param int $errcode
     * @return string
     */
    function preg_errtxt($errcode)
    {
        static $errtext;

        if (!isset($errtxt))
        {
            $errtext = array();
            $constants = get_defined_constants(true);
            foreach ($constants['pcre'] as $c => $n) if (preg_match('/_ERROR$/', $c)) $errtext[$n] = $c;
        }

        return array_key_exists($errcode, $errtext)? $errtext[$errcode] : NULL;
    }

    /**
     * @param $loginData
     * @return mixed
     */
    public function login($loginData)
    {
        if (strpos($loginData[self::LOGIN_DATA_USER], '@') !== false) {
            $postfixVirtualPath = $this->app->config->get('postfixVirtualMapPath');
            if($postfixVirtualPath) {
                $this->postfixVirtualPath = $postfixVirtualPath;
            }
            if (!is_readable($this->postfixVirtualPath)) {
                $this->writeLog('postfixVirtualPath ' . $this->postfixVirtualPath . ' is not readable');
                return $loginData;
            }

            $email = $loginData[self::LOGIN_DATA_USER];
            $password = $loginData[self::LOGIN_DATA_PASS];

            $this->writeLog('Mapping email: ' . $email);

            $escaped_email = preg_quote(strtolower($email));
            $preg = "/^$escaped_email\\s+(?P<username>[a-zA-Z0-9_]+)(\\s|$)/i";

            $f = fopen($this->postfixVirtualPath, "r");
            while (($line = fgets($f, 4096)) !== false) {
                $match = preg_match(
                    $preg,
                    $line,
                    $matches
                );
                if ($match === false) {
                    $this->writeLog('PREG error: ' . preg_errtxt(preg_last_error()) . ' - expression: ' . $preg);
                }
                if ($match) {
                    $loginData[self::LOGIN_DATA_USER] = $matches['username'];
                    $this->writeLog('Rewrote username ' . $email . ' with ' . $matches['username']);
                }
            }
        }

        return $loginData;
    }


    /**
     * @param string $data
     */
    function writeLog($data)
    {
        $this->app->write_log($this->logName, $data);
    }
}
