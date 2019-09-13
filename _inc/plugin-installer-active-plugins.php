<?php

/**
 * Class Plugin_Installer_Manager
 */
class Plugin_Installer_Active_Plugins
{
    /**
     * @var string
     */
    private $optionValues;
    /**
     * @var array
     */
    protected $plugins = [];

    /**
     * Plugin_Installer_Manager constructor.
     *
     * @param $optionValues
     */
    public function __construct($optionValues)
    {
        $this->optionValues = $optionValues;
        $this->parse();
    }

    /**
     * @return string
     */
    public function getOptionValues()
    {
        $this->format();

        return $this->optionValues;
    }

    /**
     * @param $pluginName
     *
     * @throws Exception
     */
    public function addPlugin($pluginName)
    {
        $plugin = new Plugin_Installer_Active_Plugin($pluginName);

        if (!$plugin->isValid()) {
            return;
        }

        $this->plugins[$pluginName] = $plugin;
    }

    /**
     * @param string $pluginName
     */
    public function removePlugin($pluginName)
    {
        unset($this->plugins[$pluginName]);
    }

    /**
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Parse plugins
     */
    protected function parse()
    {
        $m = explode(';', $this->optionValues);

        for ($i = 1; $i < count($m); $i += 2) {
            $pluginName = explode('"', $m[$i])[1];
            $this->plugins[$pluginName] = new Plugin_Installer_Active_Plugin($pluginName);
        }
    }

    /**
     * Makes formatted string to insert into table wp_options as a value for active_plugins
     */
    private function format()
    {
        ksort($this->plugins);
        $pluginsCount = count($this->plugins);
        $optionValue = sprintf('a:%d:{', $pluginsCount);
        $i = 0;

        foreach ($this->plugins as $key => $plugin) {
            $optionValue .= sprintf('i:%d;%s', $i++, $plugin->format());
        }

        $optionValue .= '}';
        $this->optionValues = $optionValue;
    }

    /**
     * @param $pluginName
     *
     * @return bool
     */
    public function isPluginActive($pluginName){
        return !empty($this->plugins[$pluginName]);
    }
}