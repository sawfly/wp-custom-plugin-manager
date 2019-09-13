<?php

/**
 * Class Plugin_Manager_Manager
 */
class Plugin_Manager_Manager
{
    use Plugin_Manager_Plugin_Name_Finder_Trait;

    /**
     * @var ZipArchive
     */
    private $zipArchive;
    /**
     * @var string filename
     */
    private $uploadedFileTmpName;
    /**
     * @var string path to WP plugins directory
     */
    private $pluginDir;
    /**
     * @var string plugin name that is entry point to plugin
     * @example 'new-plugin/plugin-name.php'
     */
    protected $pluginName;
    /**
     * @var Plugin_Installer_Active_Plugins
     */
    private $pluginInstallerActivePlugins;
    /**
     * @var Reference/wpdb
     */
    private $wpdb;

    /**
     * Codes and description of errors that can be returned by method ZipArchive::open()
     */
    const ERRORS_MESSAGES = [
        ZipArchive::ER_EXISTS => 'File already exists.',
        ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
        ZipArchive::ER_INVAL  => 'Invalid argument.',
        ZipArchive::ER_MEMORY => 'Malloc failure.',
        ZipArchive::ER_NOZIP  => 'Not a zip archive.',
        ZipArchive::ER_OPEN   => 'Can\'t open file.',
        ZipArchive::ER_READ   => 'Read error.',
        ZipArchive::ER_SEEK   => 'Seek error.',
    ];

    /**
     * Plugin_Installer_Manager constructor.
     *
     * @param $uploadedFile
     *
     * @throws Exception
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->zipArchive = new ZipArchive();
    }

    public function setUploadedFile($uploadedFile)
    {
        $this->uploadedFileTmpName = $uploadedFile['tmp_name'];

    }

    /**
     * @param $pluginDir
     */
    public function setPluginDir($pluginDir)
    {
        if (empty($plaginDir) || is_dir($pluginDir) === false) {
            $pluginDir = __DIR__ . '/../../';
        }
        $this->pluginDir = $pluginDir;
    }

    /**
     * Extract plugin
     *
     * @throws Exception
     */
    public function install()
    {
        if (!$this->validateArchive()) {
            die('invalid');
        }

        if (!$this->validatePlugin()) {
            throw new Exception("The plugin is not valid");
        }

        $this->zipArchive->extractTo("{$this->pluginDir}");
        $this->zipArchive->close();
    }

    /**
     * Makes plugin active
     * @throws Exception
     */
    public function activate()
    {
        if (empty($this->pluginName)) {
            throw new Exception("There is no plugin name. Set it directly before activation");
        }

        $this->initPluginInstallerActivePlugins();
        $this->pluginInstallerActivePlugins->addPlugin($this->pluginName);
        $this->saveActivePluginsIntoDb();
    }

    /**
     * Sets plugin name
     *
     * @param string $pluginName
     */
    public function setPluginName($pluginName)
    {
        $this->pluginName = $pluginName;
    }

    /**
     * Makes plugin inactive
     */
    public function deactivate()
    {
        $this->initPluginInstallerActivePlugins();
        $this->pluginInstallerActivePlugins->removePlugin($this->pluginName);
        $this->saveActivePluginsIntoDb();
    }

    /**
     * Removes plugin from the filesystem, previously makes him inactive
     */
    public function uninstall()
    {
        if ($this->isPluginActive()) {
            $this->deactivate();
        }

        $path = __DIR__ . "/../../" . explode('/', $this->pluginName)[0];
        $this->removeDirectory($path);
    }

    /**
     * Helper method that removes directory and all its content
     *
     * @param $directoryPath
     */
    private function removeDirectory($directoryPath)
    {
        if (is_file($directoryPath)) {
            unlink($directoryPath);

            return;
        }

        $content = scandir($directoryPath);
        unset($content[0]);
        unset($content[1]);

        foreach ($content as $fileDir) {
            $this->removeDirectory($directoryPath . "/$fileDir");
        }

        rmdir($directoryPath);

        return;
    }

    /**
     * Checks if-archive can be opened
     * @return bool
     * @throws Exception
     */
    private function validateArchive()
    {
        $status = $this->zipArchive->open($this->uploadedFileTmpName);

        if ($status === true) {
            return true;
        }

        throw new Exception(static::ERRORS_MESSAGES[$status]);
    }

    /**
     * Check if Plugin dirName contains script dirName.php
     * @return bool
     * @throws Exception
     */
    private function validatePlugin()
    {
        $filesInPluginRootDirectory = [];
        $pluginNameFound = false;
        $this->fillFilesInPluginRootDirectory($filesInPluginRootDirectory);
        $this->lookForPluginName($filesInPluginRootDirectory, $pluginNameFound);

        return $pluginNameFound;
    }

    /**
     * Fills array with files in zip archive
     *
     * @param $filesInPluginRootDirectory
     */
    private function fillFilesInPluginRootDirectory(&$filesInPluginRootDirectory)
    {
        $zp = zip_open($this->uploadedFileTmpName);

        while ($file = zip_read($zp)) {
            $entryName = zip_entry_name($file);

            if (substr_count($entryName, '/') === 1 && preg_match('/\.php$/i', $entryName) === 1) {
                $filesInPluginRootDirectory[] = $entryName;
            }
        }

        zip_close($zp);
    }

    /**
     * Looks for string Plugin Name: in comment in php file, which should be in valid plugin
     *
     * @param $filesInPluginRootDirectory
     * @param $pluginNameFound
     */
    private function lookForPluginName(&$filesInPluginRootDirectory, &$pluginNameFound)
    {
        foreach ($filesInPluginRootDirectory as $fname) {
            if ($pluginNameFound) {
                break;
            }

            $fp = fopen('zip://' . $this->uploadedFileTmpName . "#$fname", 'rb');
            if (!$fp) {
                exit("Can't open the file $fname");
            }

            $result = $this->findByFilePointer($fp);
            if ($result) {
                $this->pluginName = $fname;
                $pluginNameFound = true;
            }
            fclose($fp);
        }
    }

    /**
     * Initializes $pluginInstallerActivePlugins
     */
    private function initPluginInstallerActivePlugins()
    {
        if (!empty($this->pluginInstallerActivePlugins)) {
            return;
        }

        $res = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->options} WHERE option_name = 'active_plugins'");
        $optionValue = $res->option_value;
        $this->pluginInstallerActivePlugins = new Plugin_Installer_Active_Plugins($optionValue);
    }

    /**
     * Saves information about active plugins in db
     */
    private function saveActivePluginsIntoDb()
    {
        $this->wpdb->update(
            $this->wpdb->options,
            ['option_value' => $this->pluginInstallerActivePlugins->getOptionValues()],
            ['option_name' => 'active_plugins'],
            ['%s'],
            ['%s']
        );
    }

    /**
     * Checks if a plugin is active
     * @return mixed
     */
    private function isPluginActive()
    {
        $this->initPluginInstallerActivePlugins();

        return $this->pluginInstallerActivePlugins->isPluginActive($this->pluginName);
    }
}