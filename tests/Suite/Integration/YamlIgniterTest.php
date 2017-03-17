<?php

namespace AmaTeam\YamlIgniter\Test\Suite\Integration;

use AmaTeam\YamlIgniter\ConfigurationFileMissingException;
use AmaTeam\YamlIgniter\Test\Support\AllureCompatibilityTrait;
use AmaTeam\YamlIgniter\YamlIgniter;
use Codeception\Configuration;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Yaml\Yaml;
use Yandex\Allure\Adapter\Support\AttachmentSupport;

class YamlIgniterTest extends \Codeception\Test\Unit
{
    use AllureCompatibilityTrait;
    use AttachmentSupport;

    /**
     * Returns location to particular file with test data.
     *
     * @param string $file
     * @param int $framework Framework version
     *
     * @return string
     */
    private static function locate($file, $framework)
    {
        $suffix = sprintf('/Integration/v%d/%s.yml', $framework, $file);
        return Configuration::dataDir() . $suffix;
    }

    /**
     * Utility method for data providers.
     *
     * @param string $file File name.
     * @param string $classifier File classifier.
     * @param int $framework Framework version.
     *
     * @return string[] Source and expectation file locations.
     */
    public function combinator($file, $classifier, $framework) {
        return [
            self::locate($file . '.' . $classifier, $framework),
            self::locate($file . '.' . $classifier . '.expected', $framework),
        ];
    }
    
    public function version2DatabaseProvider()
    {
        $classifiers = ['alpha'];
        return array_map(function ($classifier) {
            return $this->combinator('database', $classifier, 2);
        }, $classifiers);
    }
    
    public function version2ConfigurationProvider()
    {
        $classifiers = ['alpha'];
        return array_map(function ($classifier) {
            return $this->combinator('config', $classifier, 2);
        }, $classifiers);
    }
    
    public function version3DatabaseProvider()
    {
        $classifiers = ['alpha'];
        return array_map(function ($classifier) {
            return $this->combinator('database', $classifier, 3);
        }, $classifiers);
    }
    
    public function version3ConfigurationProvider()
    {
        $classifiers = ['alpha'];
        return array_map(function ($classifier) {
            return $this->combinator('config', $classifier, 3);
        }, $classifiers);
    }

    private function dumpData($data, $name)
    {
        $this->addAttachment(Yaml::dump($data, 16, 2), $name . '.yml');
        return $data;
    }

    private function dumpResult($data)
    {
        return $this->dumpData($data, 'result');
    }

    private function loadExpectation($path)
    {
        return $this->dumpData(
            Yaml::parse(file_get_contents($path)),
            'expectation'
        );
    }

    private function loadSource($path)
    {
        return $this->dumpData(Yaml::parse(file_get_contents($path)), 'source');
    }

    /**
     * @test
     *
     * @dataProvider version2ConfigurationProvider
     *
     * @param string $sourceFile
     * @param string $expectationFile
     *
     * @return void
     */
    public function shouldCorrectlyMergeVersion2Configuration(
        $sourceFile,
        $expectationFile
    ) {
        $this->loadSource($sourceFile);
        $this->assertEquals(
            $this->loadExpectation($expectationFile),
            $this->dumpResult(YamlIgniter::config($sourceFile, 2))
        );
    }

    /**
     * @test
     *
     * @dataProvider version2DatabaseProvider
     *
     * @param string $sourceFile
     * @param string $expectationFile
     *
     * @return void
     */
    public function shouldCorrectlyMergeVersion2Databases(
        $sourceFile,
        $expectationFile
    ) {
        $this->loadSource($sourceFile);
        $this->assertEquals(
            $this->loadExpectation($expectationFile),
            $this->dumpResult(YamlIgniter::databases($sourceFile, 2))
        );
    }

    /**
     * @test
     *
     * @dataProvider version3ConfigurationProvider
     *
     * @param string $sourceFile
     * @param string $expectationFile
     *
     * @return void
     */
    public function shouldCorrectlyMergeVersion3Configuration(
        $sourceFile,
        $expectationFile
    ) {
        $this->loadSource($sourceFile);
        $this->assertEquals(
            $this->loadExpectation($expectationFile),
            $this->dumpResult(YamlIgniter::config($sourceFile, 3))
        );
    }

    /**
     * @test
     *
     * @dataProvider version3DatabaseProvider
     *
     * @param string $sourceFile
     * @param string $expectationFile
     *
     * @return void
     */
    public function shouldCorrectlyMergeVersion3Databases(
        $sourceFile,
        $expectationFile
    ) {
        $this->loadSource($sourceFile);
        $this->assertEquals(
            $this->loadExpectation($expectationFile),
            $this->dumpResult(YamlIgniter::databases($sourceFile, 3))
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function shouldThrowOnInvalidConfigurationLocation()
    {
        $this->expectException(ConfigurationFileMissingException::class);
        $path = '/' . Uuid::uuid4()->getHex() . '/databases.yml';
        YamlIgniter::databases($path);
    }
}