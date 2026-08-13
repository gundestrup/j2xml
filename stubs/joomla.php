<?php
/**
 * PHPStan stub file for the Joomla CMS framework.
 *
 * This file declares the subset of Joomla\CMS\* classes, legacy J* aliases,
 * global functions and global constants that the J2XML extension references.
 * It exists solely so PHPStan can resolve symbols without a full Joomla
 * installation being present.
 *
 * Method/property signatures are kept minimal and use `mixed` where the real
 * Joomla API is complex — the goal is symbol discovery for static analysis,
 * not a faithful reproduction of the framework.
 *
 * Joomla CMS itself uses scanFiles pointing at libraries/loader.php (see
 * https://github.com/joomla/joomla-cms/blob/5.4-dev/phpstan.neon) but that
 * requires the full framework source.  For a standalone extension repo
 * without Composer, stub files are the lightweight alternative.
 *
 * @see https://magazine.joomla.org/issues/2026/january-2026/test-your-extension-part-3-phpstan
 */

// ---------------------------------------------------------------------------
// Global namespace: constants, functions, and legacy J* aliases
// ---------------------------------------------------------------------------

namespace
{
    defined('_JEXEC') or define('_JEXEC', 1);

    define('JPATH_ROOT', __DIR__);
    define('JPATH_BASE', __DIR__);
    define('JPATH_SITE', __DIR__);
    define('JPATH_ADMINISTRATOR', __DIR__);
    define('JPATH_CONFIGURATION', __DIR__);
    define('JPATH_LIBRARIES', __DIR__);
    define('JPATH_PLATFORM', __DIR__);
    define('JPATH_PLUGINS', __DIR__);
    define('JPATH_THEMES', __DIR__);
    define('JPATH_COMPONENT', __DIR__);
    define('JPATH_COMPONENT_ADMINISTRATOR', __DIR__);

    define('JDEBUG', false);
    define('JERROR_ALERTNOAUTHOR', 'JERROR_ALERTNOAUTHOR');
    define('JERROR_ALERTNOTAUTH', 'JERROR_ALERTNOTAUTH');

    /**
     * Global helper that exits the application, optionally printing a message.
     *
     * @param mixed $message
     * @return void
     */
    function jexit($message = 0): void
    {
    }

    // Legacy aliases (Joomla 3.x class names still referenced in places)
    class JLoader
    {
        public static function import(string $path, ?string $base = null): bool { return true; }
        public static function register(string $class, string $path, bool $force = false): bool { return true; }
        public static function registerAlias(string $alias, string $original, bool $force = false): bool { return true; }
        public static function registerNamespace(string $namespace, string $path, bool $reset = false, bool $prepend = false): bool { return true; }
        public static function load(string $class): bool { return true; }
    }

    class JApplicationCli extends \Joomla\CMS\Application\CliApplication
    {
        public static function getInstance(?string $name = null): self { return new self(); }
    }

    class JText extends \Joomla\CMS\Language\Text {}

    class JTable extends \Joomla\CMS\Table\Table {}

    class JModelLegacy extends \Joomla\CMS\MVC\Model\BaseModel {}

    class JPlugin extends \Joomla\CMS\Plugin\CMSPlugin {}

    class JHtml extends \Joomla\CMS\HTML\HTMLHelper {}

    class JRegistry extends \Joomla\Registry\Registry {}

    class JFactory extends \Joomla\CMS\Factory
    {
        public static $application = null;
    }

    class JResponseJson extends \Joomla\CMS\Response\JsonResponse {}

    // Additional legacy aliases and helper classes used by J2XML

    /** @deprecated Legacy alias for Joomla\CMS\Log\Log */
    class JLog extends \Joomla\CMS\Log\Log {}

    /** @deprecated Legacy alias for Joomla\CMS\Plugin\PluginHelper */
    class JPluginHelper extends \Joomla\CMS\Plugin\PluginHelper {}

    /** @deprecated Legacy alias for Joomla\CMS\Router\Route */
    class JRouter extends \Joomla\CMS\Router\Route
    {
        public static function getInstance(string $client = 'site'): self { return new self(); }
    }

    /** @deprecated Legacy error handling class */
    class JError
    {
        public static $legacy = false;
        public static function raiseError(int $code, string $msg): void {}
        public static function raiseWarning(int $code, string $msg): void {}
        public static function raiseNotice(int $code, string $msg): void {}
    }

    /** @deprecated Legacy sidebar helper */
    class JHtmlSidebar
    {
        public static function addEntry(string $name, string $link = '', bool $active = false): void {}
        public static function getEntries(): array { return []; }
        public static function addFilter(string $label, string $name, array $options, bool $noDefault = false): void {}
        public static function getFilters(): array { return []; }
        public static function render(): string { return ''; }
    }

    /** @deprecated Legacy base object class */
    class JObject
    {
        public function get(string $property, $default = null) { return $default; }
        public function getProperties(bool $public = true): array { return []; }
        public function getError($i = null, $toString = true) { return null; }
        public function getErrors(): array { return []; }
        public function set($property, $value = null): mixed { return $value; }
        public function setProperties($properties): bool { return true; }
        public function setError(string $error): void {}
    }

    /** Runtime configuration class (loaded from configuration.php) */
    class JConfig {}

    /** @deprecated Legacy exception class */
    class JException extends \Exception {}

    /** @deprecated Legacy database driver alias */
    class JDatabaseDriver extends \Joomla\Database\JDatabaseDriver {}

    /** @deprecated Legacy database exception */
    class JDatabaseExceptionExecuting extends \Joomla\Database\JDatabaseExceptionExecuting {}

    /** @deprecated Legacy event dispatcher */
    class JEventDispatcher
    {
        public static function getInstance(): self { return new self(); }
        public function register(string $event, $handler): void {}
        public function trigger(string $event, array $args = []): array { return []; }
    }

    /** Legacy layout file class */
    class _JLayoutFile
    {
        public static function addIncludePaths(array $paths): void {}
        public function getDefaultIncludePaths() { return []; }
    }

    class JLayoutFile extends _JLayoutFile
    {
        public function __construct(string $layoutId = '', ?string $basePath = null, array $options = []) {}
        public function render(array $displayData = []): string { return ''; }
        public function addIncludePath(string $path): self { return $this; }
        public function setIncludePaths(array $paths): self { return $this; }
        public function getIncludePaths(): array { return []; }
    }

    /**
     * Legacy wrapper for jimport().
     * @param string $path
     * @return bool
     */
    function jimport(string $path): bool { return true; }

    // xmlrpc client classes (from the bundled phpxmlrpc library)
    class xmlrpcval
    {
        public function __construct($val = -1, string $type = '') {}
        public function scalarval() { return null; }
        public function scalartyp(): string { return ''; }
        public function structmem(string $name) { return null; }
        public function structeach(): array { return []; }
        public function structreset(): void {}
        public function arraymem(int $id) { return null; }
        public function arraysize(): int { return 0; }
        public function serialize(): string { return ''; }
    }

    class xmlrpcresp
    {
        public function __construct($val = null, int $fcode = 0, string $fstr = '') {}
        public function faultCode(): int { return 0; }
        public function faultString(): string { return ''; }
        public function value(): ?xmlrpcval { return null; }
        public function serialize(): string { return ''; }
    }

    class xmlrpcmsg
    {
        public function __construct(string $method, array $params = []) {}
        public function addParam(xmlrpcval $param): void {}
        public function getParam(int $i): ?xmlrpcval { return null; }
        public function serialize(): string { return ''; }
        public function method(): string { return ''; }
        public function params(): array { return []; }
    }

    class xmlrpc_client
    {
        public function __construct(string $path, string $server, int $port = 80) {}
        public function send(xmlrpcmsg $msg, int $timeout = 0, string $method = ''): xmlrpcresp { return new xmlrpcresp(); }
        public function setCredentials(string $user, string $password, string $authType = ''): void {}
        public function setDebug(int $level): void {}
    }

    class xmlrpc_server
    {
        public function __construct(?array $dispatchMap = null, bool $serviceNow = true) {}
        public function service(): void {}
        public function addIntrospectionData(): void {}
        public function serialize(): string { return ''; }
    }
}

// ===========================================================================
// Joomla\CMS
// ===========================================================================

namespace Joomla\CMS
{
    class Version
    {
        public function getShortVersion(): string { return ''; }
        public function getLongVersion(): string { return ''; }
        public function getFullVersion(): string { return ''; }
    }

    use Joomla\CMS\Application\CMSApplication;
    use Joomla\CMS\Document\Document;
    use Joomla\CMS\Language\Language;
    use Joomla\CMS\User\User;
    use Joomla\Database\DatabaseInterface;
    use Joomla\Registry\Registry;
    use Joomla\CMS\Date\Date;

    /**
     * @deprecated since Joomla 4.3, use the DI container where possible.
     */
    class Factory
    {
        public static function getApplication(): CMSApplication { return new CMSApplication(); }
        public static function getConfig(): Registry { return new Registry(); }
        public static function getContainer(): \Joomla\DI\Container { return new \Joomla\DI\Container(); }
        public static function getDate($date = 'now', $tz = null): Date { return new Date($date); }
        public static function getDbo(): DatabaseInterface { return new class implements DatabaseInterface {}; }
        public static function getDocument(): Document { return new Document(); }
        public static function getLanguage(): Language { return new Language(); }
        public static function getUser($id = null): User { return new User(); }
        public static function getXML(string $data, bool $isFile = false): \SimpleXMLElement { return new \SimpleXMLElement('<x/>'); }
    }
}

// ===========================================================================
// Joomla\CMS\Application
// ===========================================================================

namespace Joomla\CMS\Application
{
    use Joomla\CMS\Input\Input;
    use Joomla\CMS\Language\Language;
    use Joomla\CMS\Session\Session;

    class CMSApplication
    {
        public Input $input;
        public Language $language;

        public function get(string $key, $default = null) { return $default; }
        public function getMessageQueue(): array { return []; }
        public function getSession(): Session { return new Session(); }
        public function getTemplate(bool $params = false) { return 'atum'; }
        public function getUserState(string $key, $default = null) { return $default; }
        public function getUserStateFromRequest(string $key, string $request, $default = null, string $type = 'none', bool $resetPage = true) { return $default; }
        public function enqueueMessage(string $msg, string $type = 'message') {}
        public function login(array $credentials, array $options = []): bool { return true; }
        public function logout(int $userid = 0, array $options = []): bool { return true; }
        public function redirect(string $url, int $status = 303): void {}
        public function setUserState(string $key, $value): void {}
        public function setHeader(string $name, string $value, bool $replace = true): void {}
        public function isClient(string $client): bool { return false; }
        public function isAdmin(): bool { return true; }
        public function isSite(): bool { return false; }
        public function getRouter(): \Joomla\CMS\Router\Router { return new \Joomla\CMS\Router\Router(); }
    }

    class WebApplication extends CMSApplication {}

    class CliApplication extends CMSApplication
    {
        public function out(string $text = '', bool $nl = true): self { return $this; }
        public function in(): string { return ''; }
    }
}

// ===========================================================================
// Joomla\CMS\Input
// ===========================================================================

namespace Joomla\CMS\Input
{
    class Input
    {
        public Input $get;
        public Input $post;
        public FilesInput $files;

        public function get(string $name, $default = null, string $filter = 'cmd') { return $default; }
        public function set(string $name, $value): void {}
        public function getArray(array $vars = [], ?array $datasource = null): array { return []; }
        public function __get(string $name) { return null; }
    }

    class FilesInput extends Input {}
}

// ===========================================================================
// Joomla\CMS\Session
// ===========================================================================

namespace Joomla\CMS\Session
{
    class Session
    {
        public static function checkToken(string $method = 'post'): bool { return true; }
        public static function getFormToken(bool $forceNew = false): string { return ''; }
        public function get(string $name, $default = null, string $namespace = 'default') { return $default; }
        public function set(string $name, $value, string $namespace = 'default') { return $value; }
        public function has(string $name, string $namespace = 'default'): bool { return false; }
    }
}

// ===========================================================================
// Joomla\CMS\Language
// ===========================================================================

namespace Joomla\CMS\Language
{
    class Text
    {
        public static function _(string $string, ...$args): string { return $string; }
        public static function sprintf(string $string, ...$args): string { return $string; }
        public static function script(string $string, ...$args): string { return $string; }
        public static function plural(string $string, int $count, ...$args): string { return $string; }
    }

    class Language
    {
        public function getDefault(): string { return 'en-GB'; }
        public function load(string $extension,  ?string $basePath = null, ?string $language = null, bool $reload = false, bool $default = true): bool { return true; }
        public function getTag(): string { return 'en-GB'; }
    }

    class Associations
    {
        public static function isEnabled(): bool { return false; }
    }

    class Multilang
    {
        public static function isEnabled(): bool { return false; }
    }
}

// ===========================================================================
// Joomla\CMS\Log
// ===========================================================================

namespace Joomla\CMS\Log
{
    class Log
    {
        public const ALL = 30719;
        public const EMERGENCY = 1;
        public const ALERT = 2;
        public const CRITICAL = 4;
        public const ERROR = 8;
        public const WARNING = 16;
        public const NOTICE = 32;
        public const INFO = 64;
        public const DEBUG = 128;

        public static function add(LogEntry $entry, int $priority = self::ALL, string $category = '', ?string $message = null): void {}
        public static function addLogger(array $options, int $priorities = self::ALL, array $categories = [], int $level = 0): void {}
    }

    class LogEntry
    {
        public function __construct($message, int $priority = Log::INFO, string $category = '', ?string $date = null, array $context = []) {}
    }

    abstract class Logger
    {
        protected array $options = [];
        public function __construct(array $options = []) {}
        abstract public function addEntry(LogEntry $entry): void;
    }
}

// ===========================================================================
// Joomla\CMS\User
// ===========================================================================

namespace Joomla\CMS\User
{
    class User
    {
        public int $id = 0;
        public bool $guest = true;
        public string $name = '';
        public string $username = '';
        public string $email = '';
        public array $groups = [];

        public function authorise(string $action, ?string $assetname = null): bool { return true; }
        public function getError($i = null, $toString = true) { return null; }
        public function getState(): string { return ''; }
        public function save(): bool { return true; }
        public function getParam(string $key, $default = null) { return $default; }
        public function setParam(string $key, $value): void {}
    }

    class UserHelper
    {
        public static function genRandomPassword(int $length = 16): string { return ''; }
        public static function hashPassword(string $password): string { return ''; }
        public static function verifyPassword(string $password, string $hash, string $user_id = ''): bool { return true; }
        public static function getUserId(string $username): int { return 0; }
    }
}

// ===========================================================================
// Joomla\CMS\MVC\Controller
// ===========================================================================

namespace Joomla\CMS\MVC\Controller
{
    use Joomla\CMS\Input\Input;
    use Joomla\CMS\MVC\Model\BaseModel;

    class BaseController
    {
        public Input $input;
        protected array $default_view = [];
        public $redirect = null;

        public function __construct(array $config = []) {}
        public function display($cachable = false, $urlparams = false) {}
        public function getModel(string $name = '', string $prefix = '', array $config = []) { return new BaseModel(); }
        public function setRedirect(string $url, ?string $msg = null, ?string $type = null): self { return $this; }
        public static function getInstance(string $prefix, string $view, array $config = []): ?BaseController { return null; }
        public function getView(string $name = '', string $type = '', string $prefix = '', array $config = []) { return null; }
        public function addModelPath(array $path): void {}
        public function addViewPath(array $path): void {}
        public function registerTask(string $name, string $method): void {}
        public function registerDefaultTask(string $method): void {}
        public function checkToken(string $method = 'post'): bool { return true; }
    }

    class AdminController extends BaseController {}

    class FormController extends BaseController
    {
        public function save($key = null, $urlVar = null): bool { return true; }
        public function edit($key = null, $urlVar = null): bool { return true; }
        public function cancel($key = null, $urlVar = null): bool { return true; }
        public function add(): bool { return true; }
    }
}

// ===========================================================================
// Joomla\CMS\MVC\Model
// ===========================================================================

namespace Joomla\CMS\MVC\Model
{
    class BaseModel
    {
        public function getState($property = null, $default = null) { return $default; }
        public function setState($property, $value = null): self { return $this; }
        public function getTable(string $name = '', string $prefix = '', array $options = []) { return null; }
        public function getItem($pk = null) { return null; }
        public function getForm(array $data = [], bool $loadData = true) { return null; }
        public function save(array $data): bool { return true; }
        public function delete(array $cid): bool { return true; }
        public function getItems(): array { return []; }
        public function getTotal(): int { return 0; }
        public function getPagination() { return null; }
        public function getListQuery() { return null; }
        public function loadForm(string $name, ?string $source = null, array $options = [], bool $clear = false, ?string $xpath = null) { return null; }
        public function preprocessForm(\Joomla\CMS\Form\Form $form, $data, string $group = 'content'): void {}
        public function preprocessData(string $group, &$data, ?string $elementType = null): void {}
        public function setError(string $error): void {}
        public function getError($i = null, $toString = true) { return null; }
        public function getErrors(): array { return []; }
    }

    class FormModel extends BaseModel
    {
        public function __construct(array $config = []) {}
        protected function populateState() {}
    }
}

// ===========================================================================
// Joomla\CMS\MVC\View
// ===========================================================================

namespace Joomla\CMS\MVC\View
{
    class HtmlView
    {
        public $state = null;
        protected $form = null;
        public $items = null;
        public $paths = null;
        public $showMessage = false;
        protected $_basePath = null;

        public function __construct(array $config = []) {}
        public function display(?string $tpl = null) {}
        public function get(string $property, ?string $default = null) { return $default; }
        public function setModel($model, bool $default = false): void {}
        public function getModel( ?string $name = null) { return null; }
        public function setLayout(string $layout): void {}
        public function getLayout(): string { return 'default'; }
        public function assign(): void {}
        public function assignRef(string $key, &$val): bool { return true; }
        public function escape(string $value): string { return $value; }
        protected function _addPath(string $type, string $path): array { return []; }
        public function getName(): string { return ''; }
    }
}

// ===========================================================================
// Joomla\CMS\Table
// ===========================================================================

namespace Joomla\CMS\Table
{
    class Table
    {
        public int $id = 0;
        public string $title = '';
        public string $alias = '';
        public string $language = '*';
        public int $published = 1;
        public int $access = 1;
        public string $asset_id = '0';
        public ?string $created = null;
        public ?string $modified = null;
        public int $version = 1;
        public array $newTags = [];
        public int $catid = 0;
        public int $type = 0;
        public string $link = '';
        public int $user_id = 0;
        public int $group_id = 0;
        public string $rules = '';
        protected $_db;
        protected string $_tbl = '';
        protected string $_tbl_key = 'id';

        public function __construct($db = null) {}
        public function bind($src, array $ignore = []) { return true; }
        public function check(): bool { return true; }
        public function store(bool $updateNulls = false): bool { return true; }
        public function load($keys = null, bool $reset = true) { return true; }
        public function delete($pk = null): bool { return true; }
        public function getError($i = null, $toString = true) { return null; }
        public function getErrors(): array { return []; }
        public function save(array $src, string $orderingFilter = '', array $ignore = []): bool { return true; }
        public function rebuildPath(int $pk): bool { return true; }
        public function setLocation(int $referenceId, string $position = 'after'): void {}
        public static function export($id, &$xml, $options) {}
        public static function import($xml, &$params) {}
        public static function prepareData($record, &$data, $params) {}
        public function toXML($mapKeysToText = false) { return new \SimpleXMLElement('<x/>'); }
        public static function getCategoryId(string $category, string $extension, int $defaultCategoryId = 0) { return 0; }
        public function setRules($rules): void {}

        public static function addIncludePath(?string $path = null): array { return []; }
        public static function getInstance(string $type, string $prefix = 'JTable', array $config = []): ?Table { return null; }
    }

    class Category extends Table {}
    class Content extends Table {}
    class Usergroup extends Table {}
}

// ===========================================================================
// Joomla\CMS\Plugin
// ===========================================================================

namespace Joomla\CMS\Plugin
{
    use Joomla\Event\DispatcherInterface;

    class PluginHelper
    {
        public static function getPlugin(string $type, ?string $plugin = null) { return []; }
        public static function importPlugin(?string $type = null, ?string $plugin = null, bool $autocreate = true, ?DispatcherInterface $dispatcher = null, bool $app = true): bool { return true; }
        public static function isEnabled(string $type, ?string $plugin = null): bool { return true; }
    }

    abstract class CMSPlugin
    {
        public $params = null;
        public function __construct($subject = null, array $config = []) {}
        public function onBeforeRespond(): void {}
    }
}

// ===========================================================================
// Joomla\CMS\Component
// ===========================================================================

namespace Joomla\CMS\Component
{
    use Joomla\Registry\Registry;

    class ComponentHelper
    {
        public static function getParams(string $option = ''): Registry { return new Registry(); }
        public static function getComponent(string $option, bool $strict = false) { return null; }
        public static function isEnabled(string $option): bool { return true; }
    }
}

// ===========================================================================
// Joomla\CMS\Date
// ===========================================================================

namespace Joomla\CMS\Date
{
    class Date extends \DateTime
    {
        public function __construct($date = 'now', $tz = null) { parent::__construct($date); }
        public function toSql(bool $local = false, $dbo = null): string { return ''; }
        public function toISO8601(bool $local = false): string { return ''; }
        public function toRFC822(bool $local = false): string { return ''; }
        public function toUnix(bool $local = false): int { return 0; }
        public function iso(string $format, bool $local = false): string { return ''; }
        public function format(string $format, bool $local = false, bool $translate = true): string { return ''; }
    }
}

// ===========================================================================
// Joomla\CMS\Filesystem
// ===========================================================================

namespace Joomla\CMS\Filesystem
{
    class File
    {
        public static function delete($file): bool { return true; }
        public static function exists(string $file): bool { return true; }
        public static function upload(string $src, string $dest, bool $use_streams = false, bool $allow_unsafe = false, array $safeFileOptions = []): bool { return true; }
        public static function write(string $file, string $buffer, bool $use_streams = false): bool { return true; }
        public static function copy(string $src, string $dest, ?string $path = null, bool $use_streams = false): bool { return true; }
        public static function move(string $src, string $dest, ?string $path = null, bool $use_streams = false): bool { return true; }
        public static function makeSafe(string $file): string { return $file; }
    }

    class Folder
    {
        public static function create(string $path): bool { return true; }
        public static function delete(string $path): bool { return true; }
        public static function exists(string $path): bool { return true; }
        public static function copy(string $src, string $dest, ?string $path = null, bool $force = false, bool $use_streams = false): bool { return true; }
        public static function move(string $src, string $dest, ?string $path = null, bool $use_streams = false): bool { return true; }
        public static function files(string $path, string $filter = '.', bool $recurse = false, array $exclude = [], array $excludefilter = [], bool $naturalSort = false): array { return []; }
    }

    class Path
    {
        public static function clean(string $path, string $ds = DIRECTORY_SEPARATOR): string { return $path; }
        public static function check(string $path): string { return $path; }
    }

    class FilesystemHelper
    {
        public static function fileUploadMaxSize(): int { return 0; }
    }
}

// ===========================================================================
// Joomla\CMS\Filter
// ===========================================================================

namespace Joomla\CMS\Filter
{
    class OutputFilter
    {
        public static function stringURLSafe(string $string, string $language = ''): string { return $string; }
        public static function stringURLUnicodeSlug(string $string): string { return $string; }
    }
}

// ===========================================================================
// Joomla\CMS\Form
// ===========================================================================

namespace Joomla\CMS\Form
{
    class Form
    {
        public function bind($data): bool { return true; }
        public function loadFile(string $file, bool $reset = true, bool $xpath = false): bool { return true; }
        public function setFieldAttribute(string $name, string $attribute, string $value, ?string $group = null): bool { return true; }
        public function getFieldAttribute(string $name, string $attribute, $default = null, ?string $group = null) { return $default; }
        public function getFormControl(): string { return 'jform'; }
        public function getData(): \Joomla\Registry\Registry { return new \Joomla\Registry\Registry(); }
    }

    class FormHelper
    {
        public static function parseShowOnConditions(string $showOn, ?string $formControl = null,  ?string $name = null): array { return []; }
        public static function loadFieldType(string $name, bool $new = true) { return null; }
    }
}

// ===========================================================================
// Joomla\CMS\HTML
// ===========================================================================

namespace Joomla\CMS\HTML
{
    class HTMLHelper
    {
        public static function _(string $key, ...$args) { return ''; }
        public static function stylesheet(string $file, array $attribs = [], bool $relative = false, ?string $path = null, bool $browserVersion = false): void {}
        public static function script(string $file, array $attribs = [], bool $relative = false, ?string $path = null, bool $browserVersion = false): void {}
        public static function image(string $file, string $alt, ?array $attribs = null, bool $relative = false, ?string $path = null): string { return ''; }
    }
}

// ===========================================================================
// Joomla\CMS\Helper
// ===========================================================================

namespace Joomla\CMS\Helper
{
    class ContentHelper
    {
        public static function getActions(string $component = '', string $section = '', int $id = 0): \Joomla\Registry\Registry { return new \Joomla\Registry\Registry(); }
    }

    class TagsHelper
    {
        public function getTagIds(string $id, string $prefix = ''): array { return []; }
        public function searchTags(array $filters = []): array { return []; }
    }

    class LibraryHelper
    {
        public static function isEnabled(string $library, int $default = 0): bool { return true; }
        public static function saveLibrary(string $library, array $config = [], int $default = 0): bool { return true; }
    }
}

// ===========================================================================
// Joomla\CMS\Installer
// ===========================================================================

namespace Joomla\CMS\Installer
{
    class InstallerHelper
    {
        public static function cleanupInstall(string $package, string $resultdir): void {}
        public static function downloadPackage(string $url, ?string $target = null): bool|string { return false; }
        public static function unpack(string $p_filename): bool|array { return false; }
    }
}

// ===========================================================================
// Joomla\CMS\Router
// ===========================================================================

namespace Joomla\CMS\Router
{
    class Route
    {
        public static function _(string $url, bool $xhtml = true, ?int $ssl = null): string { return $url; }
        public static function getArticleRoute(int $id, int $catid = 0, ?string $language = null): string { return ''; }
        public static function getCategoryRoute(int $id, ?string $language = null): string { return ''; }
    }

    class Router
    {
        public static function getInstance(string $client = 'site'): self { return new self(); }
        public function build(string $url): string { return $url; }
        public function parse(string $url): array { return []; }
    }
}

// ===========================================================================
// Joomla\CMS\Toolbar
// ===========================================================================

namespace Joomla\CMS\Toolbar
{
    class Toolbar
    {
        public static function getInstance(string $name = 'toolbar'): self { return new self(); }
        public function appendButton(): void {}
        public function prependButton(): void {}
        public function render(): string { return ''; }
    }

    class ToolbarHelper
    {
        public static function title(string $title, string $icon = ''): void {}
        public static function divider(): void {}
        public static function preferences(string $component, int $height = 550, int $width = 875, string $help = '', string $path = '', string $onClose = ''): void {}
        public static function addNew(string $task = 'add', string $alt = 'JTOOLBAR_NEW', bool $check = false): void {}
        public static function editList(string $task = 'edit', string $alt = 'JTOOLBAR_EDIT', bool $check = false): void {}
        public static function publish(string $task = 'publish', string $alt = 'JTOOLBAR_PUBLISH', bool $check = false): void {}
        public static function unpublish(string $task = 'unpublish', string $alt = 'JTOOLBAR_UNPUBLISH', bool $check = false): void {}
        public static function deleteList(string $msg = '', string $task = 'remove', string $alt = 'JTOOLBAR_DELETE', bool $check = false): void {}
        public static function save(string $task = 'save', string $alt = 'JTOOLBAR_SAVE'): void {}
        public static function cancel(string $task = 'cancel', string $alt = 'JTOOLBAR_CANCEL'): void {}
        public static function back(string $alt = 'JTOOLBAR_BACK', string $href = ''): void {}
        public static function custom(string $task = '', string $icon = '', string $iconOver = '', string $alt = '', bool $listSelect = true): void {}
    }
}

// ===========================================================================
// Joomla\CMS\Uri
// ===========================================================================

namespace Joomla\CMS\Uri
{
    class Uri
    {
        public static function base(bool $pathonly = false): string { return ''; }
        public static function root(bool $pathonly = false, ?string $path = null): string { return ''; }
        public static function current(): string { return ''; }
        public static function isInternal(string $url): bool { return false; }
        public function setVar(string $name, string $value): void {}
        public function getVar(string $name, ?string $default = null) { return $default; }
    }
}

// ===========================================================================
// Joomla\CMS\Client
// ===========================================================================

namespace Joomla\CMS\Client
{
    class ClientHelper
    {
        public static function setCredentialsFromRequest(string $client): bool { return true; }
        public static function getCredentials(string $client, bool $force = false): array { return []; }
    }
}

// ===========================================================================
// Joomla\CMS\Document
// ===========================================================================

namespace Joomla\CMS\Document
{
    class Document
    {
        public function addScript(string $url, string $type = 'text/javascript', bool $defer = false, bool $async = false): self { return $this; }
        public function addStyleDeclaration(string $content, string $type = 'text/css'): self { return $this; }
        public function addStyleSheet(string $url, array $attribs = []): self { return $this; }
        public function createDocumentFragment(): \DOMDocumentFragment { return new \DOMDocumentFragment(); }
        public \DOMElement $documentElement;
        public function setMimeEncoding(string $type): self { return $this; }
        public function setTitle(string $title): self { return $this; }
        public function getType(): string { return 'html'; }
    }
}

// ===========================================================================
// Joomla\CMS\Service\Provider
// ===========================================================================

namespace Joomla\CMS\Service\Provider
{
    class Database
    {
        public function register($container): void {}
    }
}

// ===========================================================================
// Joomla\CMS\Response
// ===========================================================================

namespace Joomla\CMS\Response
{
    class JsonResponse
    {
        public function __construct($response, ?string $message = null, bool $error = false) {}
    }
}

// ===========================================================================
// Joomla\Registry  (Joomla Framework)
// ===========================================================================

namespace Joomla\Registry
{
    class Registry implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
    {
        public function __construct($data = []) {}
        public function get(string $path, $default = null) { return $default; }
        public function set(string $path, $value): self { return $this; }
        public function def(string $path, $default = null) { return $default; }
        public function exists(string $path): bool { return false; }
        public function toArray(): array { return []; }
        public function toObject(): \stdClass { return new \stdClass(); }
        public function toString(bool $asXml = false): string { return ''; }
        public function loadString(string $string, string $format = 'JSON'): self { return $this; }
        public function loadArray(array $array): self { return $this; }
        public function loadObject(object $object): self { return $this; }
        public function merge($source): self { return $this; }
        public function offsetExists($offset): bool { return false; }
        public function offsetGet($offset) { return null; }
        public function offsetSet($offset, $value): void {}
        public function offsetUnset($offset): void {}
        public function count(): int { return 0; }
        public function getIterator(): \Traversable { return new \ArrayIterator(); }
        public function jsonSerialize(): mixed { return []; }
    }
}

// ===========================================================================
// Joomla\Database  (framework interface used by Factory::getDbo)
// ===========================================================================

namespace Joomla\Database
{
    interface DatabaseInterface
    {
        public function getQuery(bool $new = false): \Joomla\Database\QueryInterface;
        public function setQuery($query): self;
        public function execute(): bool;
        public function loadResult(): mixed;
        public function loadColumn(): array;
        public function loadAssoc(): ?array;
        public function loadObject(): ?object;
        public function loadAssocList(): array;
        public function loadObjectList(): array;
        public function quote($text, bool $escape = true): string;
        public function quoteName(string $name): string;
        public function q($text, bool $escape = true): string;
        public function qn(string $name): string;
        public function getNullDate(): string;
        public function getServerType(): string;
        public function insertObject(string $table, object $object,  ?string $key = null): bool;
        public function updateObject(string $table, object $object, array $key, bool $nulls = false): bool;
        public function truncateTable(string $table): void;
    }

    interface QueryInterface {}
}

// ===========================================================================
// Joomla\DI  (framework container used by Factory::getContainer)
// ===========================================================================

namespace Joomla\DI
{
    class Container
    {
        public function get(string $key) { return null; }
        public function has(string $key): bool { return false; }
        public function set(string $key, $value): self { return $this; }
    }
}

// ===========================================================================
// Joomla\Event  (framework, used by CMSPlugin)
// ===========================================================================

namespace Joomla\Event
{
    interface DispatcherInterface
    {
        public function dispatch(string $eventName, ?Event $event = null): Event;
    }
    class Event {}
}

// ===========================================================================
// Joomla\Utilities  (Joomla Framework)
// ===========================================================================

namespace Joomla\Utilities
{
    class ArrayHelper
    {
        public static function toObject(array &$array, string $class = 'stdClass'): object { return new \stdClass(); }
        public static function toString(?array $array = null): string { return ''; }
        public static function fromObject($p_obj, bool $recurse = true, ?string $regex = null): array { return []; }
        public static function getColumn(array $array, string $index): array { return []; }
        public static function getValue(array $array, string $name, $default = null, string $filter = 'cmd') { return $default; }
        public static function invert(array $array): array { return []; }
        public static function pivot(array $source, ?string $key = null): array { return []; }
        public static function sortObjects(array $a, array $k, array $dir = [], array $type = []): array { return []; }
        public static function arrayUnique(array $array): array { return []; }
        public static function toInteger(array &$array, mixed $default = null): void {}
    }
}

// ===========================================================================
// Joomla\CMS\Layout
// ===========================================================================

namespace Joomla\CMS\Layout
{
    class _FileLayout
    {
        public static function addIncludePaths(array $paths): void {}
        public function getDefaultIncludePaths() { return []; }
    }

    class FileLayout extends _FileLayout
    {
        public function __construct(string $layoutId = '',  ?string $basePath = null, array $options = []) {}
        public function render(array $displayData = []): string { return ''; }
        public function addIncludePath(string $path): self { return $this; }
        public function setIncludePaths(array $paths): self { return $this; }
        public function getIncludePaths(): array { return []; }
        public function getLayoutId(): string { return ''; }
    }
}

// ===========================================================================
// Joomla\CMS\Router  (additional legacy alias)
// ===========================================================================

// Already declared: Joomla\CMS\Router\Route

// ===========================================================================
// Joomla\Database  (additional legacy classes)
// ===========================================================================

namespace Joomla\Database
{
    class JDatabaseDriver implements DatabaseInterface
    {
        public function getQuery(bool $new = false): QueryInterface { return new class implements QueryInterface {}; }
        public function setQuery($query): self { return $this; }
        public function execute(): bool { return true; }
        public function loadResult(): mixed { return null; }
        public function loadColumn(): array { return []; }
        public function loadAssoc(): ?array { return null; }
        public function loadObject(): ?object { return null; }
        public function loadAssocList(): array { return []; }
        public function loadObjectList(): array { return []; }
        public function quote($text, bool $escape = true): string { return ''; }
        public function quoteName(string $name): string { return ''; }
        public function q($text, bool $escape = true): string { return ''; }
        public function qn(string $name): string { return ''; }
        public function getNullDate(): string { return ''; }
        public function getServerType(): string { return 'mysql'; }
        public function insertObject(string $table, object $object,  ?string $key = null): bool { return true; }
        public function updateObject(string $table, object $object, array $key, bool $nulls = false): bool { return true; }
        public function truncateTable(string $table): void {}
    }

    class JDatabaseExceptionExecuting extends \RuntimeException {}
}

// ===========================================================================
// Joomla\Component\Content\Site\Helper  (content component helpers)
// ===========================================================================

namespace Joomla\Component\Content\Site\Helper
{
    class RouteHelper
    {
        public static function getArticleRoute(int $id, int $catid = 0, ?string $language = null): string { return ''; }
        public static function getCategoryRoute(int $id, ?string $language = null): string { return ''; }
    }
}

// Legacy content helper route class
namespace
{
    class ContentHelperRoute
    {
        public static function getArticleRoute(int $id, int $catid = 0, ?string $language = null): string { return ''; }
        public static function getCategoryRoute(int $id, ?string $language = null): string { return ''; }
    }

    /** Legacy Users model (com_users) */
    class UsersModelUser
    {
        public function getItem($pk = null) { return null; }
        public function getTable(string $type = 'User', string $prefix = 'UsersTable', array $config = []) { return null; }
        public function getForm(array $data = [], bool $loadData = true) { return null; }
        public function save(array $data): bool { return true; }
        public function delete(array $cid): bool { return true; }
    }
}

// ===========================================================================
// eshiol\J2xml\Table  (local stubs for classes referenced but not autoloaded)
// ===========================================================================

namespace eshiol\J2xml\Table
{
    class Rules
    {
        public function __construct($input = '') {}
        public function mergeCollection(array $input, bool $recursive = false): bool { return true; }
        public function merge($input): bool { return true; }
        public function toString(): string { return ''; }
    }

    class RuntimeException extends \RuntimeException {}
}
