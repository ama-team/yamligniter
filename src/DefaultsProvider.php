<?php

namespace AmaTeam\YamlIgniter;

use Symfony\Component\Yaml\Yaml;

/**
 * This class fetches and return framework configuration defaults.
 *
 * @package AmaTeam\YamlIgniter
 * @author  Etki <etki@etki.name>
 */
class DefaultsProvider
{
    /**
     * Configuration cache.
     *
     * @var array
     */
    private $cache;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * Initializer.
     *
     * @param FilesystemInterface $filesystem Filesystem access handle.
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->reset();
    }

    /**
     * Retrieves configuration of particular type and for paticular framework
     * version.
     *
     * @param string $type Configuration type (`config` / `database` /
     *                     `database.entry`).
     * @param int $version Framework version.
     *
     * @return array Fetched configuration
     */
    public function get($type, $version = 3)
    {
        if (!isset($this->cache[$version])) {
            $message = 'Unknown framework version ' . $version;
            throw new IllegalVersionException($message);
        }
        if (!isset($this->cache[$version][$type])) {
            $this->cache[$version][$type] = $this->load($type, $version);
        }
        return $this->cache[$version][$type];
    }

    /**
     * Loads configuration as specified by version and type.
     *
     * @param string $type Configuration type, either `database` or `config`.
     * @param int $version Framework version.
     *
     * @return array Loaded configuration.
     */
    private function load($type, $version)
    {
        $root = dirname(__DIR__);
        $path = sprintf('%s/resources/data/v%d/%s.yml', $root, $version, $type);
        if (!$this->filesystem->exists($path)) {
            $message = 'Unexpectedly couldn\'t find file ' . $path;
            throw new ConfigurationFileMissingException($message);
        }
        return Yaml::parse($this->filesystem->read($path));
    }

    /**
     * Resets current configuration cache in rare case someone needs a reload.
     *
     * @return void
     */
    public function reset()
    {
        $this->cache = [2 => [], 3 => []];
    }
}
