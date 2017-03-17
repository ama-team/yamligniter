<?php

namespace AmaTeam\YamlIgniter\Test\Suite\Unit;

use AmaTeam\YamlIgniter\ConfigurationFileMissingException;
use AmaTeam\YamlIgniter\DefaultsProvider;
use AmaTeam\YamlIgniter\FilesystemInterface;
use AmaTeam\YamlIgniter\IllegalVersionException;
use AmaTeam\YamlIgniter\Test\Support\AllureCompatibilityTrait;
use PHPUnit_Framework_MockObject_MockObject;

class DefaultsProviderTest extends \Codeception\Test\Unit
{
    use AllureCompatibilityTrait;

    /**
     * @param {string} $content
     *
     * @return PHPUnit_Framework_MockObject_MockObject|FilesystemInterface
     */
    private function mockFilesystem($content)
    {
        $mock = $this
            ->getMockBuilder(FilesystemInterface::class)
            ->getMock();
        $mock
            ->method('read')
            ->willReturn($content);
        $mock
            ->method('exists')
            ->willReturn(true);
        return $mock;
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    /**
     * @test
     *
     * @return void
     */
    public function shouldCorrectlyProcessReturnedYaml()
    {
        $provider = new DefaultsProvider($this->mockFilesystem('alpha: beta'));
        self::assertEquals(['alpha' => 'beta'], $provider->get('config', 3));
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldCacheResults()
    {
        /**
         * @var FilesystemInterface|PHPUnit_Framework_MockObject_MockObject $filesystem
         */
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem
            ->expects(self::once())
            ->method('read')
            ->willReturn('alpha: beta', 'gamma: delta');
        $provider = new DefaultsProvider($filesystem);
        $expected = ['alpha' => 'beta'];
        self::assertEquals($expected, $provider->get('config', 3));
        self::assertEquals($expected, $provider->get('config', 3));
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldFlushCache()
    {
        /**
         * @var FilesystemInterface|PHPUnit_Framework_MockObject_MockObject $filesystem
         */
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem
            ->expects(self::exactly(2))
            ->method('read')
            ->willReturn('alpha: beta', 'gamma: delta');
        $provider = new DefaultsProvider($filesystem);
        self::assertEquals(['alpha' => 'beta'], $provider->get('config', 3));
        $provider->reset();
        self::assertEquals(['gamma' => 'delta'], $provider->get('config', 3));
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldThrowOnInvalidFrameworkVersion()
    {
        /**
         * @var FilesystemInterface|PHPUnit_Framework_MockObject_MockObject $filesystem
         */
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(false);
        $filesystem
            ->method('read')
            ->willReturn('alpha: beta');

        $provider = new DefaultsProvider($filesystem);

        $this->expectException(IllegalVersionException::class);
        $provider->get('config', 4);
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldThrowOnMissingConfig()
    {
        /**
         * @var FilesystemInterface|PHPUnit_Framework_MockObject_MockObject $filesystem
         */
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('exists')->willReturn(false);
        $filesystem
            ->method('read')
            ->willReturn('alpha: beta');

        $provider = new DefaultsProvider($filesystem);

        $this->expectException(ConfigurationFileMissingException::class);
        $provider->get('missing', 3);
    }
}