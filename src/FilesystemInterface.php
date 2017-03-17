<?php

namespace AmaTeam\YamlIgniter;

/**
 * Basic filesystem utility specification.
 *
 * @package AmaTeam\YamlIgniter
 * @author  Etki <etki@etki.name>
 */
interface FilesystemInterface
{
    /**
     * Read file located at $path.
     *
     * @param string $path File location.
     *
     * @throws ConfigurationFileMissingException
     *
     * @return string
     */
    public function read($path);

    /**
     * Tells if file exists.
     *
     * @param string $path File location.
     *
     * @return boolean
     */
    public function exists($path);
}
