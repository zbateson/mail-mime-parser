<?php

namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(MailMimeParser::class)]
#[Group('MailMimeParser')]
#[Group('Base')]
class PluginDiscoveryTest extends TestCase
{
    private string $tmpDir;

    protected function setUp() : void
    {
        $this->tmpDir = \sys_get_temp_dir() . '/mmp-plugin-test-' . \uniqid();
        \mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown() : void
    {
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir) : void
    {
        if (!\is_dir($dir)) {
            return;
        }
        foreach (\scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            \is_dir($path) ? $this->removeDir($path) : \unlink($path);
        }
        \rmdir($dir);
    }

    private function createInstalledJson(array $packages) : string
    {
        $path = $this->tmpDir . '/installed.json';
        \file_put_contents($path, \json_encode(['packages' => $packages]));
        return $path;
    }

    private function createPackageDir(string $relativePath, ?string $configFileName = null, ?string $configContent = null) : void
    {
        $dir = $this->tmpDir . '/' . $relativePath;
        \mkdir($dir, 0755, true);
        if ($configFileName !== null) {
            \file_put_contents(
                $dir . '/' . $configFileName,
                $configContent ?? "<?php\nreturn [];\n"
            );
        }
    }

    public function testDiscoversSinglePlugin() : void
    {
        $this->createPackageDir('zbateson/mmp-crypt', 'mmp_di_config.php');

        $installedJson = $this->createInstalledJson([
            [
                'name' => 'zbateson/mmp-crypt',
                'install-path' => 'zbateson/mmp-crypt',
                'extra' => [
                    'mail-mime-parser' => [
                        'di_config' => 'mmp_di_config.php',
                    ],
                ],
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(1, $configs);
        $this->assertStringEndsWith('mmp_di_config.php', $configs[0]);
    }

    public function testSkipsPackageWithoutExtraKey() : void
    {
        $this->createPackageDir('some/package');

        $installedJson = $this->createInstalledJson([
            [
                'name' => 'some/package',
                'install-path' => 'some/package',
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(0, $configs);
    }

    public function testSkipsPackageWithExtraButNoDiConfig() : void
    {
        $installedJson = $this->createInstalledJson([
            [
                'name' => 'some/package',
                'install-path' => 'some/package',
                'extra' => [
                    'mail-mime-parser' => [
                        'other_key' => 'something',
                    ],
                ],
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(0, $configs);
    }

    public function testSkipsPackageWhenConfigFileMissing() : void
    {
        $this->createPackageDir('zbateson/mmp-crypt');  // no config file created

        $installedJson = $this->createInstalledJson([
            [
                'name' => 'zbateson/mmp-crypt',
                'install-path' => 'zbateson/mmp-crypt',
                'extra' => [
                    'mail-mime-parser' => [
                        'di_config' => 'mmp_di_config.php',
                    ],
                ],
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(0, $configs);
    }

    public function testDiscoversMultiplePlugins() : void
    {
        $this->createPackageDir('zbateson/mmp-crypt', 'mmp_di_config.php');
        $this->createPackageDir('zbateson/mmp-other', 'custom_config.php');

        $installedJson = $this->createInstalledJson([
            [
                'name' => 'zbateson/mmp-crypt',
                'install-path' => 'zbateson/mmp-crypt',
                'extra' => [
                    'mail-mime-parser' => [
                        'di_config' => 'mmp_di_config.php',
                    ],
                ],
            ],
            [
                'name' => 'zbateson/mmp-other',
                'install-path' => 'zbateson/mmp-other',
                'extra' => [
                    'mail-mime-parser' => [
                        'di_config' => 'custom_config.php',
                    ],
                ],
            ],
            [
                'name' => 'unrelated/package',
                'install-path' => 'unrelated/package',
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(2, $configs);
    }

    public function testReturnsEmptyForMissingInstalledJson() : void
    {
        $configs = MailMimeParser::parsePluginConfigs($this->tmpDir . '/nonexistent.json');

        $this->assertCount(0, $configs);
    }

    public function testReturnsEmptyForInvalidJson() : void
    {
        $path = $this->tmpDir . '/installed.json';
        \file_put_contents($path, 'not valid json{{{');

        $configs = MailMimeParser::parsePluginConfigs($path);

        $this->assertCount(0, $configs);
    }

    public function testSkipsPackageWithNoInstallPath() : void
    {
        $installedJson = $this->createInstalledJson([
            [
                'name' => 'zbateson/mmp-crypt',
                'extra' => [
                    'mail-mime-parser' => [
                        'di_config' => 'mmp_di_config.php',
                    ],
                ],
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(0, $configs);
    }

    public function testReturnsAbsolutePaths() : void
    {
        $this->createPackageDir('zbateson/mmp-crypt', 'mmp_di_config.php');

        $installedJson = $this->createInstalledJson([
            [
                'name' => 'zbateson/mmp-crypt',
                'install-path' => 'zbateson/mmp-crypt',
                'extra' => [
                    'mail-mime-parser' => [
                        'di_config' => 'mmp_di_config.php',
                    ],
                ],
            ],
        ]);

        $configs = MailMimeParser::parsePluginConfigs($installedJson);

        $this->assertCount(1, $configs);
        $this->assertEquals(\realpath($this->tmpDir . '/zbateson/mmp-crypt/mmp_di_config.php'), $configs[0]);
    }
}
