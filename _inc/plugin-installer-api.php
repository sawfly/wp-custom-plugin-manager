<?php

/**
 * Class Matomo_Configurator_API
 */
class Plugin_Installer_API extends WP_REST_Controller
{
    /**
     * @var Plugin_Installer_Manager
     */
    protected $pluginInstallerManager;

    /**
     * @var int
     */
    private $statusCode = 200;
    /**
     * @var string
     */
    private $status = 'Success';
    /**
     * @var array
     */
    private $errors = [];
    /**
     * @var array
     */
    private $payload = [];

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $version = '1';
        $namespace = 'plugin-installer/v' . $version;
        register_rest_route(
            $namespace,
            '/install',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'install'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => [
                        'key' => 'value',
                    ],
                ],
            ]
        );
        register_rest_route(
            $namespace,
            '/activate',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'activate'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => [
                        'key' => 'value',
                    ],
                ],
            ]
        );
        register_rest_route(
            $namespace,
            '/deactivate',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'deactivate'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => [
                        'key' => 'value',
                    ],
                ],
            ]
        );
        register_rest_route(
            $namespace,
            '/install-activate',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'installActivate'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => [
                        'key' => 'value',
                    ],
                ],
            ]
        );
        register_rest_route(
            $namespace,
            '/uninstall',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'uninstall'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => [
                        'key' => 'value',
                    ],
                ],
            ]
        );
    }

    /**
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function install($request)
    {
        if(empty($_FILES)){
            $this->errors[] = "There is no uploaded file";
        }

        $uploadedFile = array_shift($_FILES);

        try {
            $this->initPluginInstallerManager($uploadedFile, __DIR__ . '../');
            $this->pluginInstallerManager->install();
        } catch (Exception $e) {
            $this->statusCode = 400;
            $this->status = 'Error';
            $this->errors[] = $e->getMessage();
        }

        return new WP_REST_Response(
            ['status' => $this->status, 'errors' => $this->errors, 'payload' => $this->payload],
            $this->statusCode
        );
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function activate($request)
    {
        $bodyParams = $request->get_body_params();
        try {
            $this->initPluginInstallerManager('', '', $bodyParams['pluginName']);
            $this->pluginInstallerManager->activate();
        } catch (Exception $e) {
            $this->statusCode = 400;
            $this->status = 'Error';
            $this->errors[] = $e->getMessage();
        }

        return new WP_REST_Response(
            ['status' => $this->status, 'errors' => $this->errors, 'payload' => $this->payload],
            $this->statusCode
        );
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function installActivate($request)
    {
        $bodyParams = $request->get_body_params();
        try {
            $this->initPluginInstallerManager('', '', $bodyParams['pluginName']);
            $this->pluginInstallerManager->install();
            $this->pluginInstallerManager->activate();
        } catch (Exception $e) {
            $this->statusCode = 400;
            $this->status = 'Error';
            $this->errors[] = $e->getMessage();
        }

        return new WP_REST_Response(
            ['status' => $this->status, 'errors' => $this->errors, 'payload' =>
                $this->payload], $this->statusCode
        );
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function deactivate($request)
    {
        $bodyParams = $request->get_body_params();
        try {
            $this->initPluginInstallerManager('', '', $bodyParams['pluginName']);
            $this->pluginInstallerManager->deactivate();
        } catch (Exception $e) {
            $this->statusCode = 400;
            $this->status = 'Error';
            $this->errors[] = $e->getMessage();
        }

        return new WP_REST_Response(
            ['status' => $this->status, 'errors' => $this->errors, 'payload' =>
                $this->payload], $this->statusCode
        );
    }

    /**
     * @param $request
     *
     * @return WP_REST_Response
     */
    public function uninstall($request)
    {
        $bodyParams = $request->get_body_params();
        try {
            $this->initPluginInstallerManager('', '', $bodyParams['pluginName']);
            $this->pluginInstallerManager->uninstall();
        } catch (Exception $e) {
            $this->statusCode = 400;
            $this->status = 'Error';
            $this->errors[] = $e->getMessage();
        }

        return new WP_REST_Response(
            ['status' => $this->status, 'errors' => $this->errors, 'payload' =>
                $this->payload], $this->statusCode
        );
    }

    /**
     * Validate user's permissions to add a plugin
     * @param $request
     *
     * @return bool
     */
    public function get_items_permissions_check($request)
    {
        if(!class_exists('Jwt_Auth_Public')){
            return false;
        }

        return user_can( (new Jwt_Auth_Public('plugin-installeraaaaaa', 8))->determine_current_user(false), 'manage_options' );
    }

    /**
     * Initializes $this->pluginInstallerManager
     *
     * @param        $uploadedFile
     * @param        $pluginDir
     * @param string $pluginName
     *
     * @throws Exception
     */
    private function initPluginInstallerManager($uploadedFile, $pluginDir, $pluginName = '')
    {
        $this->pluginInstallerManager = new Plugin_Installer_Manager();

        if (empty($pluginName) && !empty($uploadedFile)) {
            $this->pluginInstallerManager->setPluginDir($pluginDir);
            $this->pluginInstallerManager->setUploadedFile($uploadedFile);
        } elseif (!empty($pluginName)) {
            $this->pluginInstallerManager->setPluginName($pluginName);
        }
    }
}