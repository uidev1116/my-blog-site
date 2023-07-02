<?php

class ACMS_Session
{
    protected static $instance = array();

    protected $session = null;

    public static function singleton($config = array())
    {
        if (!self::$instance) {
            $obj = new self;
            self::$instance = $obj;
        }
        return self::$instance;
    }

    /**
     * ACMS_Session constructor.
     */
    private function __construct()
    {
        $this->session = Session::handle();
    }

    /**
     * Save
     *
     * @return void
     */
    public function save()
    {
        $this->session->save();
    }

    /**
     * Get
     *
     * @param string $key
     *
     * @return bool
     */
    public function get($key)
    {
        return $this->session->get($key);
    }

    /**
     * Set
     *
     * @param string $key
     * @param mixed $val
     *
     * @return void
     */
    public function set($key, $val)
    {
        $this->session->set($key, $val);
    }

    /**
     * Delete
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->session->delete($key);
    }

    /**
     * Clear
     *
     * @return void
     */
    public function clear()
    {
        $this->session->clear();
    }
}
