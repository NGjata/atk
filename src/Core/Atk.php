<?php

namespace Sintattica\Atk\Core;

use Dotenv\Dotenv;
use Exception;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Sintattica\Atk\Core\Menu\Menu;
use Sintattica\Atk\Handlers\ActionHandler;
use Sintattica\Atk\Security\AccessLoggerServiceProvider;
use Sintattica\Atk\Security\SecurityManager;
use Sintattica\Atk\Security\SqlWhereclauseBlacklistChecker;
use Sintattica\Atk\Session\SessionManager;
use Sintattica\Atk\Ui\IndexPage;

class Atk
{
    public array $g_nodes = [];
    public array $g_nodesClasses = [];
    public array $g_nodeRepository = [];
    public array $g_modules = [];
    /** @var Module[] */
    public array $g_moduleRepository = [];
    public array $g_nodeHandlers = [];
    public array $g_nodeListeners = [];
    private string $environment;

    /** @var static $s_instance */
    public static $s_instance;

    /**
     * @throws Exception
     */
    public function __construct($environment, $basedir)
    {
        global $g_startTime;
        $g_startTime = microtime(true);

        if (static::$s_instance) {
            throw new RuntimeException('Only one Atk app can be created');
        }

        static::$s_instance = $this;

        $this->environment = $environment;

        if (!file_exists($basedir . '/.env')) {
            die("Cannot locate .env file in $basedir");
        }
        $dotenv = Dotenv::createImmutable($basedir);
        $dotenv->load();

        require_once 'adodb-time.php';

        Config::init();

        if (Config::getGlobal('debug') > 0) {
            ini_set('display_errors', 1);
        }
        if (Config::getGlobal('use_atkerrorhandler', true)) {
            set_error_handler('Sintattica\Atk\Core\Tools::atkErrorHandler');
            error_reporting(E_ALL);
            set_exception_handler('Sintattica\Atk\Core\Tools::atkExceptionHandler');
            register_shutdown_function('Sintattica\Atk\Core\Tools::atkFatalHandler');
        }

        // Filter the atkselector REQUEST variable for blacklisted SQL (like UNIONs)
        SqlWhereclauseBlacklistChecker::filter_request_where_clause(Node::PARAM_ATKSELECTOR);
        SqlWhereclauseBlacklistChecker::filter_request_where_clause('atkfilter');

        // set locale
        $locale = Tools::atktext('locale', 'atk', '', '', true);
        if ($locale) {
            setlocale(LC_TIME, $locale);
        }

        $modules = Config::getGlobal('modules');
        if (is_array($modules)) {
            foreach ($modules as $module) {
                static::$s_instance->registerModule($module);
            }
        }

        $debug = 'Created a new Atk instance.';
        $debug .= ' Environment: ' . $environment . '.';
        $debug .= ' PHP version: ' . PHP_VERSION . '.';

        if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['SERVER_ADDR'])) {
            $debug .= ' Server info: ' . $_SERVER['SERVER_NAME'] . ' (' . $_SERVER['SERVER_ADDR'] . ')';
        }

        AccessLoggerServiceProvider::register();

        Tools::atkdebug($debug);
    }

    /**
     * Get new Atk object.
     */
    public static function getInstance(): Atk|static
    {
        if (!is_object(static::$s_instance)) {
            throw new RuntimeException('Atk instance not available');
        }
        return static::$s_instance;
    }

    /**
     * @throws ReflectionException
     */
    public function run(): void
    {
        $sessionManager = SessionManager::getInstance();
        $sessionManager->start();

        if (Config::getGlobal('session_autorefresh') && array_key_exists(Config::getGlobal('session_autorefresh_key'), $_GET)) {
            die(session_id());
        }

        $securityManager = SecurityManager::getInstance();
        $securityManager->run();

        if ($securityManager->isAuthenticated()) {
            $this->bootModules();
            $indexPageClass = Config::getGlobal('indexPage');

            /** @var IndexPage $indexPage */
            $indexPage = new $indexPageClass($this);

            $default_destination = Config::getGlobal('default_destination');
            if (is_array($default_destination)) {
                $indexPage->setDefaultDestination($default_destination);
            }

            $indexPage->generate();
        }
    }

    public function runCli(): void
    {
        Config::setGlobal('authentication', 'none');
        Config::setGlobal('authorization', 'none');
        $securityManager = SecurityManager::getInstance();
        $securityManager->run();
        $this->bootModules();
    }

    public function bootModules(): void
    {
        $this->initMenu();
        foreach ($this->g_moduleRepository as $module) {
            $module->boot();
        }
    }

    /**
     * Load all the menu items
     */
    public function initMenu(): void
    {
        /** @var Menu $menuClass */
        $menuClass = Config::getGlobal('menu');
        $menuClass::getInstance()->appendMenuItems();
    }

    /**
     * Tells ATK that a node exists, and what actions are available to
     * perform on that node.  Note that registerNode() is not involved in
     * deciding which users can do what, only in establishing the full set
     * of actions that can potentially be performed on the node.
     *
     * @param string $nodeUri uri of the node
     * @param string $class class of the node
     * @param array $actions actions that can be performed on the node
     * @param array $tabs tabnames for which security should be handled.
     *              Note that tabs that every user may see need not be
     *              registered.
     * @param string $section
     */
    public function registerNode($nodeUri, $class, $actions = null, $tabs = [], $section = null): void
    {
        if (!is_array($tabs)) {
            $section = $tabs;
            $tabs = [];
        }

        $module = Tools::getNodeModule($nodeUri);
        $type = Tools::getNodeType($nodeUri);
        $this->g_nodesClasses[$nodeUri] = $class;

        if ($actions) {
            // prefix tabs with tab_
            for ($i = 0, $_i = Tools::count($tabs); $i < $_i; ++$i) {
                $tabs[$i] = 'tab_' . $tabs[$i];
            }

            if ($module == '') {
                $module = 'main';
            }
            if ($section == null) {
                $section = $module;
            }

            $this->g_nodes[$section][$module][$type] = array_merge($actions, $tabs);
        }
    }

    public function unregisterNode(string $nodeUri, ?string $section = null): void
    {
        if (isset($this->g_nodesClasses[$nodeUri])) {
            unset($this->g_nodesClasses[$nodeUri]);
        }

        $module = Tools::getNodeModule($nodeUri);
        $type = Tools::getNodeType($nodeUri);

        $section ??= $module;

        if (isset($this->g_nodes[$section][$module][$type])) {
            unset($this->g_nodes[$section][$module][$type]);
        }
    }


    /**
     * Get an instance of a node. If an instance doesn't exist, it is created.  Note that nodes
     * are cached (unless $reset is true); multiple requests for the same node will return exactly
     * the same node object.
     *
     * @param string $nodeUri The node uri
     * @param bool $init Initialize the node?
     * @param string $cacheId The cache id in the node repository
     * @param bool $reset Whether to reset the particular node in the repository
     *
     * @return Node the node
     */
    public function atkGetNode(string $nodeUri, bool $init = true, string $cacheId = 'default', bool $reset = false): Node
    {
        if (!isset($this->g_nodeRepository[$cacheId][$nodeUri]) || !is_object($this->g_nodeRepository[$cacheId][$nodeUri]) || $reset) {
            Tools::atkdebug("Constructing a new node $nodeUri ($cacheId)");
            $this->g_nodeRepository[$cacheId][$nodeUri] = $this->newAtkNode($nodeUri, $init);
        }

        return $this->g_nodeRepository[$cacheId][$nodeUri];
    }

    /**
     * Retrieve the Module with the given name.
     *
     * @param string $moduleName The name of the module
     *
     * @return Module An instance of the Module
     */
    public function atkGetModule(string $moduleName): Module
    {
        return $this->g_moduleRepository[$moduleName];
    }

    public function isModule(string $moduleName): bool
    {
        return array_key_exists($moduleName, $this->g_moduleRepository) && is_object($this->g_moduleRepository[$moduleName]);
    }

    /**
     * Construct a new node.
     *
     * @param string $nodeUri the node uri
     * @param bool $init initialize the node?
     *
     * @return Node new node object
     */
    public function newAtkNode(string $nodeUri, bool $init = true): Node
    {
        $nodeClass = $this->g_nodesClasses[$nodeUri];

        Tools::atkdebug("Creating a new node: $nodeUri class: $nodeClass");

        /** @var Node $node */
        $node = new $nodeClass($nodeUri);
        if ($init && $node != null) {
            $node->init();
        }

        return $node;
    }

    /**
     * Return the physical directory of a module.
     *
     * @param string $moduleName name of the module.
     *
     * @return string The path to the module.
     * @throws ReflectionException
     */
    public function moduleDir(string $moduleName): string
    {
        $modules = $this->g_modules;
        if (isset($modules[$moduleName])) {
            $class = $modules[$moduleName];

            $reflection = new ReflectionClass($class);
            $dir = dirname($reflection->getFileName());
            if (!str_ends_with($dir, '/')) {
                $dir .= '/';
            }
            return $dir;
        }
        return '';
    }

    /**
     * Returns a registered node action handler.
     *
     * @param string $nodeUri the uri of the node
     * @param string $action the node action
     *
     * @return ActionHandler|null full class or object instance (subclass of ActionHandler) or NULL if no handler exists for the specified action
     */
    public function atkGetNodeHandler(string $nodeUri, string $action): ?ActionHandler
    {
        if (isset($this->g_nodeHandlers[$nodeUri][$action])) {
            $handler = $this->g_nodeHandlers[$nodeUri][$action];
            if (!is_object($handler)) {
                $handler = new $handler();
                $this->g_nodeHandlers[$nodeUri][$action] = $handler;
            }
        } elseif (isset($this->g_nodeHandlers['*'][$action])) {
            $handler = $this->g_nodeHandlers['*'][$action];
            if (!is_object($handler)) {
                $handler = new $handler();
                $this->g_nodeHandlers['*'][$action] = $handler;
            }
        } else {
            return null;
        }

        return $handler;
    }

    /**
     * Registers a new node action handler.
     *
     * @param string $nodeUri the uri of the node (* matches all)
     * @param string $action the node action
     * @param string $handler handler functionname or object (is_subclass_of atkActionHandler)
     *
     * @return bool true if there is no known handler
     */
    public function atkRegisterNodeHandler($nodeUri, $action, $handler): bool
    {
        if (isset($this->g_nodeHandlers[$nodeUri][$action])) {
            return false;
        } else {
            $this->g_nodeHandlers[$nodeUri][$action] = $handler;
        }

        return true;
    }

    public function registerModule($moduleClass): void
    {
        $reflection = new ReflectionClass($moduleClass);
        $name = $reflection->getStaticPropertyValue('module');
        $this->g_modules[$name] = $moduleClass;

        if (!self::isModule($name)) {
            Tools::atkdebug("Constructing a new module - $name");
            $modClass = $this->g_modules[$name];

            /** @var Menu $menuClass */
            $menuClass = Config::getGlobal('menu');

            /* @var Module $module */
            $module = new $modClass(static::$s_instance, $menuClass::getInstance());
            $this->g_moduleRepository[$name] = $module;
            $module->register();
        }

    }

    public function getEnvironment()
    {
        return $this->environment;
    }
}
