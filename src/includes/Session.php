<?php

namespace App;

/*
 * session handler class
 * 
 * Creator: Mihkel Oviir
 * 03.2011
 * 
 */

class Session
{

    const SESSION_STARTED = true;
    const SESSION_NOT_STARTED = false;

    // The state of the session
    private $sessionState = self::SESSION_NOT_STARTED;

    // THE only instance of the class
    private static $instance;

    /**
     *    Returns THE instance of 'Session'.
     *    The session is automatically initialized if it wasn't.
     *
     * @return    object
     **/

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        self::$instance->startSession();
        return self::$instance;
    }


    /**
     *    (Re)starts the session.
     *
     * @return    bool    TRUE if the session has been initialized, else FALSE.
     **/

    public function startSession(): bool
    {
        if ($this->sessionState === self::SESSION_NOT_STARTED) {
            $this->sessionState = session_start();
        }
        return $this->sessionState;
    }

    /**
     * Stores datas in the session.
     * Example: $instance->foo = 'bar';
     *
     * @param $name string Name of the datas.
     * @param $value mixed Your datas.
     * @return void
     */
    public function __set(string $name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Gets datas from the session.
     * Example: echo $instance->foo;
     *
     * @param $name string Name of the datas to get.
     * @return mixed|void Datas stored in session.
     */
    public function __get(string $name)
    {
        return $_SESSION[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    public function __unset($name)
    {
        unset($_SESSION[$name]);
    }

    /**
     *    Destroys the current session.
     *
     * @return    bool    TRUE is session has been deleted, else FALSE.
     **/

    public function destroy(): bool
    {
        if ($this->sessionState === self::SESSION_STARTED) {
            $this->sessionState = !session_destroy();
            unset($_SESSION);
            return !$this->sessionState;
        }
        return false;
    }
}

/*
    Examples:

// We get the instance
$data = Session::getInstance();

// Let's store datas in the session
$data->nickname = 'Someone';
$data->age = 18;

// Let's display datas
printf( '<p>My name is %s and I\'m %d years old.</p>' , $data->nickname , $data->age );


    It will display:
   
    Array
    (
        [nickname] => Someone
        [age] => 18
    )


printf( '<pre>%s</pre>' , print_r( $_SESSION , TRUE ));

// TRUE
var_dump( isset( $data->nickname ));

// We destroy the session
$data->destroy();

// FALSE
var_dump( isset( $data->nickname ));
*/
