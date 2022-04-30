<?php
class router
{
    public $basePath;
    public $config;
    public $frameworkPath;
    public $configRoot;
    public $libPath;
    public $moduleRoot;
    public $wwwPath;
    public $dataPath;
    public $tmpPath;
    public $logPath;
    public $sessionID;
    public $dbh;
    public $URI;
    public $moduleName;
    public $methodName;
    public $params;

    public function __construct()
    {
        $this->basePath = realpath(dirname(__DIR__)) . DS;
        $this->setFrameworkPath();
        $this->setConfigRoot();
        $this->setModuleRoot();
        $this->setLibPath();
        $this->setTmpPath();
        $this->setLogPath();
        $this->setWwwPath();
        $this->setDataPath();

        $this->loadMainConfig();
        $this->setErrorHandler();
        $this->setTimezone();
        $this->startSession();

        $this->connectDB();
    }

    public function setFrameworkPath()
    {
        $this->frameworkPath = $this->basePath . 'framework' . DS;
    }

    public function setLibPath()
    {
        $this->libPath = $this->basePath . 'lib' . DS;
    }

    public function setConfigRoot()
    {
        $this->configRoot = $this->basePath . 'config' . DS;
    }

    public function setModuleRoot()
    {
        $this->moduleRoot = $this->basePath . 'module' . DS;
    }

    public function setWwwPath()
    {
        $this->wwwPath = $this->basePath . 'www' . DS;
    }

    public function setDataPath()
    {
        $this->wwwPath = $this->basePath . 'www' . DS . 'data' . DS;
    }

    public function setTmpPath()
    {
        $this->wwwPath = $this->basePath . 'tmp' . DS;
    }

    public function setLogPath()
    {
        $this->wwwPath = $this->basePath . 'tmp' . DS . 'log' . DS;
    }

    public function loadMainConfig()
    {
        /* 加载主配置文件。 Load the main config file. */
        $mainConfigFile = $this->configRoot . 'config.php';
        if(is_file($mainConfigFile)) include $mainConfigFile;
        $this->config = $config;
    }

    public function setTimezone()
    {
        if(isset($this->config->timezone)) date_default_timezone_set($this->config->timezone);
    }

    public function startSession()
    {
        if(!defined('SESSION_STARTED'))
        {
            $sessionName = $this->config->sessionVar;
            session_name($sessionName);
            session_start();

            $this->sessionID = session_id();
            define('SESSION_STARTED', true);
        }
    }

    public function setErrorHandler()
    {
        set_error_handler(array($this, 'saveError'));
        register_shutdown_function(array($this, 'shutdown'));
    }

    public function shutdown()
    {
        /*
         * 发现错误，保存到日志中。
         * If any error occers, save it.
         * */
        if(!function_exists('error_get_last')) return;
        $error = error_get_last();
        if($error) $this->saveError($error['type'], $error['message'], $error['file'], $error['line']);
    }

    public function triggerError($message, $file, $line, $exit = false)
    {
        /* Only show error when debug is open. */
        if(!$this->config->debug) die();

        $log = "ERROR: $message in $file on line $line";
        if(isset($_SERVER['SCRIPT_URI'])) $log .= ", request: $_SERVER[SCRIPT_URI]";;
        $trace = debug_backtrace();
        extract($trace[0]);
        extract($trace[1]);
        $log .= ", last called by $file on line $line through function $function.\n";

        /* 触发错误(Trigger the error) */
        trigger_error($log, $exit ? E_USER_ERROR : E_USER_WARNING);
    }

    public function saveError($level, $message, $file, $line)
    {
        if(empty($this->config->debug))  return true;
        if(!is_dir($this->logPath))      return true;
        if(!is_writable($this->logPath)) return true;

        if(strpos($message, 'Redefining') !== false) return true;

        $errorLog  = "\n" . date('H:i:s') . " $message in <strong>$file</strong> on line <strong>$line</strong> ";
        $errorLog .= "when visiting <strong>" . $_SERVER['REQUEST_URI'] . "</strong>\n";

        /* 保存到日志文件(Save to log file) */
        $errorFile = $this->logRoot . 'php.' . date('Ymd') . '.log.php';
        if(!is_file($errorFile)) file_put_contents($errorFile, "<?php\n die();\n?" . ">\n");
        file_put_contents($errorFile, strip_tags($errorLog), FILE_APPEND);

        if($level == E_ERROR or $level == E_PARSE or $level == E_CORE_ERROR or $level == E_COMPILE_ERROR or $level == E_USER_ERROR)
        {
            if(PHP_SAPI == 'cli') die($errorLog);

            $htmlError  = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head>";
            $htmlError .= "<body>" . nl2br($errorLog) . "</body></html>";
            die($htmlError);
        }
    }

    public function connectDB()
    {
        $dbParams = $this->config->db;
        if(!isset($dbParams->driver)) self::triggerError('no pdo driver defined, it should be mysql or sqlite', __FILE__, __LINE__, $exit = true);
        if(empty($dbParams->user) or empty($dbParams->name)) return false;

        $dsn = '';
        if($dbParams->driver == 'mysql') $dsn = "mysql:host={$dbParams->host}; port={$dbParams->port}; dbname={$dbParams->name}";
        if(empty($dsn)) return false;

        try
        {
            $dbh = new PDO($dsn, $dbParams->user, $dbParams->password, array(PDO::ATTR_PERSISTENT => $dbParams->persistant));
            $dbh->exec("SET NAMES {$dbParams->encoding}");

            /*
             * 如果系统是Linux，开启仿真预处理和缓冲查询。
             * If run on linux, set emulatePrepare and bufferQuery to true.
             **/
            if(!isset($dbParams->emulatePrepare) and PHP_OS == 'Linux') $dbParams->emulatePrepare = true;
            if(!isset($dbParams->bufferQuery) and PHP_OS == 'Linux')    $dbParams->bufferQuery = true;

            $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if(isset($dbParams->strictMode) and $dbParams->strictMode == false) $dbh->exec("SET @@sql_mode= ''");
            if(isset($dbParams->emulatePrepare)) $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, $dbParams->emulatePrepare);
            if(isset($dbParams->bufferQuery))    $dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $dbParams->bufferQuery);

            $this->dbh = $dbh;
            return $dbh;
        }
        catch (PDOException $exception)
        {
            $message = $exception->getMessage();
            self::triggerError($message, __FILE__, __LINE__, $exit = true);
        }
    }

    public function loadCommon()
    {
    }

    public function parseRequest()
    {
        $this->URI = $_SERVER['REQUEST_URI'];
        if(preg_match('/\.html$/', $this->URI)) $this->URI = preg_replace('/\.html$/', '', $this->URI);
        if(strpos($this->URI, '/') !== false) $this->URI = substr($this->URI, strrpos($this->URI, '/') + 1);

        $this->params     = explode('-', $this->URI);
        $this->moduleName = array_shift($this->params);
        $this->methodName = array_shift($this->params);
    }

    public function loadModule()
    {
        $moduleName = $this->moduleName;
        $methodName = $this->methodName;

        $controlPath = $this->moduleRoot . $moduleName . DS;
        $controlFile = $controlPath . 'control.php';
        if(!is_dir($controlPath)) $this->triggerError("the control $moduleName not found", __FILE__, __LINE__, $exit = true);

        chdir($controlPath);
        if(is_file($controlFile)) include $controlFile;

        $className = $moduleName;
        if(!class_exists($className)) $this->triggerError("the control $className not found", __FILE__, __LINE__, $exit = true);

        $module = new $className();
        if(!method_exists($module, $methodName)) $this->triggerError("the module $moduleName has no $methodName method", __FILE__, __LINE__, $exit = true);
        $this->control = $module;

        call_user_func_array(array($module, $methodName), $this->params);
        return $module;
    }
}
