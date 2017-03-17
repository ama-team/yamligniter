<?php

namespace AmaTeam\YamlIgniter\Test\Suite\Unit;

use AmaTeam\YamlIgniter\ConfigurationFileMissingException;
use AmaTeam\YamlIgniter\FilesystemInterface;
use AmaTeam\YamlIgniter\IllegalConfigurationException;
use AmaTeam\YamlIgniter\Test\Support\AllureCompatibilityTrait;
use AmaTeam\YamlIgniter\YamlIgniter;
use Symfony\Component\Yaml\Yaml;

class YamlIgniterTest extends \Codeception\Test\Unit
{
    use AllureCompatibilityTrait;

    // tests

    /**
     * @test
     *
     * @return void
     */
    public function shouldCorrectlyMergeConfigDefinitions()
    {
        $path = 'config.yml';
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem
            ->method('exists')
            ->willReturn(true);
        $filesystem
            ->method('read')
            ->willReturnCallback(function ($argument) use ($path) {
                return $argument === $path ? 'alpha: beta' : 'gamma: delta';
            });

        $expected = ['config' => ['alpha' => 'beta', 'gamma' => 'delta']];
        $igniter = new YamlIgniter($filesystem);
        $this->assertEquals($expected, $igniter->loadConfig($path, 3));
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldCorrectlyMergeDatabaseDefinitions()
    {
        $config = [
            'active_group' => 'alpha',
            'db' => [
                'alpha' => [
                    'host' => 'srv01',
                    'password' => 'charlie',
                    'failover' => [
                        [
                            'host' => 'srv03',
                            'password' => 'lisa',
                        ],
                    ],
                ],
                'beta' => [
                    'host' => 'srv02',
                    'password' => 'johnny',
                ],
            ],
        ];
        $entryContext = [
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
        ];
        $rootContext = [
            'query_builder' => true,
            'active_group' => 'default'
        ];
        $expected = [
            'query_builder' => true,
            'active_group' => 'alpha',
            'db' => [
                'alpha' => [
                    'host' => 'srv01',
                    'user' => 'root',
                    'password' => 'charlie',
                    'failover' => [
                        [
                            'host' => 'srv03',
                            'user' => 'root',
                            'password' => 'lisa',
                        ],
                    ],
                ],
                'beta' => [
                    'host' => 'srv02',
                    'user' => 'root',
                    'password' => 'johnny',
                ],
            ],
        ];

        $path = 'database.yml';

        $filesystem = $this
            ->createMock(FilesystemInterface::class);
        $filesystem
            ->method('exists')
            ->willReturn(true);
        $filesystem
            ->method('read')
            ->willReturnCallback(function ($argument) use ($path, $config, $rootContext, $entryContext) {
                if ($argument === $path) {
                    return Yaml::dump($config);
                }
                if (strpos($argument, 'database.yml') !== false) {
                    return Yaml::dump($rootContext);
                }
                return Yaml::dump($entryContext);
            });

        $igniter = new YamlIgniter($filesystem);
        $this->assertEquals($expected, $igniter->loadDatabases($path));
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldThrowOnMissingConfigurationFile()
    {
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(false);

        $igniter = new YamlIgniter($filesystem);
        $this->expectException(ConfigurationFileMissingException::class);
        $igniter->loadConfig('config.yml');
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldThrowOnUnreadableFile()
    {
        $path = 'config.yml';
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem
            ->method('read')
            ->willReturnCallback(function ($argument) use ($path) {
                if ($argument === $path) {
                    return '{alpha: true';
                }
                return '{}';
            });

        $this->expectException(IllegalConfigurationException::class);
        (new YamlIgniter($filesystem))->loadConfig($path);
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldThrowOnInvalidConfiguration()
    {
        $path = 'config.yml';
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem
            ->method('read')
            ->willReturnCallback(function ($argument) use ($path) {
                if ($argument === $path) {
                    return 'string';
                }
                return '{}';
            });

        $this->expectException(IllegalConfigurationException::class);
        (new YamlIgniter($filesystem))->loadConfig($path);
    }
}