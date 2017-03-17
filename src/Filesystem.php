<?php

namespace AmaTeam\YamlIgniter;

/**
 * Basic filesystem utility class.
 *
 * @package AmaTeam\YamlIgniter
 * @author  Etki <etki@etki.name>
 */
class Filesystem implements FilesystemInterface
{
    /**
     * @inheritdoc
     */
    public function read($path)
    {
        return file_get_contents($path);
    }

    /**
     * @inheritdoc
     */
    public function exists($path)
    {
        return file_exists($path);
    }
}
