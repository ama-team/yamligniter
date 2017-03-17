<?php

namespace AmaTeam\YamlIgniter;

use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * Package entry point.
 *
 * @package AmaTeam\YamlIgniter
 * @author  Etki <etki@etki.name>
 */
class YamlIgniter
{
    /**
     * Singleton-alike instance for those who like static approach more.
     *
     * @var YamlIgniter
     */
    private static $instance;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var DefaultsProvider
     */
    private $defaultsProvider;

    /**
     * Initializer.
     *
     * @param FilesystemInterface|null $filesystem
     */
    public function __construct(FilesystemInterface $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->defaultsProvider = new DefaultsProvider($this->filesystem);
    }

    /**
     * Loads databases config.
     *
     * This method will return array that duplicates CI database configuration
     * schema - it will contain `db` and `active_group` keys, as well as
     * `active_record` or `query_builder` depending on framework version.
     *
     * It is implied that `extract()` call on such array would be sufficient to
     * provide framework with everything it needs.
     *
     * @param string $path Path to .yml file.
     * @param int $version CodeIgniter version (2 or 3).
     *
     * @return array
     */
    public function loadDatabases($path, $version = 3)
    {
        $rootContext = $this->defaultsProvider->get('database', $version);
        $entryContext
            = $this->defaultsProvider->get('database.entry', $version);
        $config = $this->loadFile($path);
        $result = $this->merge($config, $rootContext);
        $result = $this->mergeDatabaseDefinitions($result, 'db', $entryContext);
        foreach ($result['db'] as &$value) {
            $value = $this->mergeDatabaseDefinitions(
                $value,
                'failover',
                $entryContext
            );
        }
        return $result;
    }

    /**
     * Utility method that fills gaps in database config structures, which may
     * appear under `$key` in `$config` array, with `$defaults`. Example of such
     * case would be `failover` key of every database structure in CodeIgniter
     * v3 database configuraiton.
     *
     * @param array $config Source configuration.
     * @param string $key Key under which array of database config structures is
     *                    expected
     * @param array $defaults Default database config structure.
     *
     * @return array Modified config.
     */
    private function mergeDatabaseDefinitions(
        array $config,
        $key,
        array $defaults
    ) {
        if (isset($config[$key]) && is_array($config[$key])) {
            foreach ($config[$key] as $k => $v) {
                $config[$key][$k] = $this->merge($v, $defaults);
            }
        }
        return $config;
    }

    /**
     * Loads application configuration.
     *
     * This method will return array that duplicates CI application
     * configuration schema, i.e. it will contain `base_url`, `uri_protocol` and
     * other keys you would normally set in config. This array consists of
     * default values which would be overriden with user-provided values found
     * in YAML file denoted by `$path`. Any custom config options will be
     * preserved.
     *
     * It is implied that `extract()` call on such array would be sufficient to
     * provide framework with everything it needs.
     *
     * @param string $path Path to application configuration.
     * @param int $version Framework version (2 or 3).
     *
     * @return array Configuration.
     */
    public function loadConfig($path, $version = 3)
    {
        $defaults = $this->defaultsProvider->get('config', $version);
        $data = $this->loadFile($path);
        return $this->merge($data, $defaults);
    }

    /**
     * Loads configuration file from filesystem.
     *
     * @param string $path
     *
     * @return array
     */
    private function loadFile($path)
    {
        if (!$this->filesystem->exists($path)) {
            $message = 'Failed to find provided configuration file %s. ' .
                'Are you sure it is there?';
            $message = sprintf($message, $path);
            throw new ConfigurationFileMissingException($message);
        }
        try {
            $config = Yaml::parse($this->filesystem->read($path));
        } catch (Throwable $e) {
            $message = 'Failed to parse YAML in file ' . $path;
            throw new IllegalConfigurationException($message, 0, $e);
        }
        if (!is_array($config)) {
            $message = 'Configuration provided in file ' . $path .
                ' is not an array';
            throw new IllegalConfigurationException($message);
        }
        return $config;
    }

    /**
     * Flushes internal cache. You probably won't need this, i'm just a fan to
     * not to keep non-resettable things.
     *
     * @return void
     */
    public function flushCache()
    {
        $this->defaultsProvider->reset();
    }

    /**
     * Merges config into defaults. Or backfills defaults with config.
     * Whatever you like more.
     *
     * @param array $config
     * @param array $defaults
     *
     * @return array
     */
    private function merge(array $config, array $defaults)
    {
        return array_replace_recursive($defaults, $config);
    }

    /**
     * Retrieves singleton instance.
     *
     * @return YamlIgniter
     */
    private static function getInstance()
    {
        return self::$instance ?: self::$instance = new YamlIgniter();
    }

    /**
     * Static wrapper for {@link YamlIgniter::loadDatabases()}.
     *
     * @see YamlIgniter::loadDatabases()
     *
     * @param string $path
     * @param int $version
     *
     * @return array
     */
    public static function databases($path, $version = 3)
    {
        return self::getInstance()->loadDatabases($path, $version);
    }

    /**
     * Static wrapper for {@link YamlIgniter::loadConfig()}.
     *
     * @see YamlIgniter::loadConfig()
     *
     * @param string $path
     * @param int $version
     *
     * @return array
     */
    public static function config($path, $version = 3)
    {
        return self::getInstance()->loadConfig($path, $version);
    }

    /**
     * @see YamlIgniter::flushCache()
     *
     * @return void
     */
    public static function flush()
    {
        self::getInstance()->flushCache();
    }
}
