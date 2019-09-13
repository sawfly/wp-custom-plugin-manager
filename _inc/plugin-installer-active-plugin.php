<?php

/**
 * Class Plugin_Installer_Manager
 */
class Plugin_Installer_Active_Plugin
{
    use Plugin_Installer_Plugin_Name_Finder_Trait;

    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $length;

    /**
     * Plugin_Installer_Active_Plugin constructor.
     *
     * @param string $name
     */
    public function __construct($name = '')
    {
        $this->setName($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        if (empty($name)) {
            return $this;
        }

        $this->name = $name;
        $this->length = strlen($name);

        return $this;
    }

    /**
     * @return string
     */
    public function format()
    {
        if (empty($this->name)) {
            throw Exception("Set $this->name before calling format method");
        }

        return sprintf('s:%d:"%s";', $this->length, $this->name);
    }

    /**
     * Check for existence of plugin and field "Plugin Name:"
     */
    public function isValid()
    {
        $pluginLocation = __DIR__ . "/../../$this->name";
        $result = false;

        if (is_file($pluginLocation)) {
            $fp = fopen($pluginLocation, 'rb');

            if ($fp === false) {
                throw new Exception("Can't open file $this->name");
            }

            $result = $this->findByFilePointer($fp);
        }

        return $result;
    }

}