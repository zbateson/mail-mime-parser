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

    private function createPackageDir(string $diConfigFileName = null, string $diConfigContent = null, array $extraComposerJson = []) : string
    {
        $packageDir = $this->tmpDir . '/package-' . \uniqid();
        \mkdir($packageDir, 0755, true);

        if ($diConfigFileName !== null) {
            \file_put_contents(
                $packageDir . '/' . $diConfigFileName,
                $diConfigContent ?? "<?php\nreturn [];\n"
            );
        }

        if (!empty($extraComposerJson)) {
            \file_put_contents(
                $packageDir . '/composer.json',
                \json_encode($extraComposerJson)
            );
        }

        return $packageDir;
    }

    public function testReadsPluginConfigFromComposerJson() : void
    {
        $packageDir = $this->createPackageDir('mmp_di_config.php', null, [
            'name' => 'zbateson/mmp-crypt',
            'extra' => [
                'mail-mime-parser' => [
                    'di_config' => 'mmp_di_config.php',
                ],
            ],
        ]);

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNotNull($result);
        $this->assertStringEndsWith('mmp_di_config.php', $result);
        $this->assertEquals(\realpath($packageDir . '/mmp_di_config.php'), $result);
    }

    public function testReturnsNullWhenNoComposerJson() : void
    {
        $packageDir = $this->createPackageDir();

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNull($result);
    }

    public function testReturnsNullWhenComposerJsonHasNoExtraKey() : void
    {
        $packageDir = $this->createPackageDir(null, null, [
            'name' => 'some/package',
        ]);

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNull($result);
    }

    public function testReturnsNullWhenExtraHasNoMmpKey() : void
    {
        $packageDir = $this->createPackageDir(null, null, [
            'name' => 'some/package',
            'extra' => [
                'other-tool' => ['key' => 'value'],
            ],
        ]);

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNull($result);
    }

    public function testReturnsNullWhenMmpExtraHasNoDiConfig() : void
    {
        $packageDir = $this->createPackageDir(null, null, [
            'name' => 'some/package',
            'extra' => [
                'mail-mime-parser' => [
                    'other_key' => 'something',
                ],
            ],
        ]);

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNull($result);
    }

    public function testReturnsNullWhenDiConfigFileMissing() : void
    {
        $packageDir = $this->createPackageDir(null, null, [
            'name' => 'zbateson/mmp-crypt',
            'extra' => [
                'mail-mime-parser' => [
                    'di_config' => 'mmp_di_config.php',
                ],
            ],
        ]);

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNull($result);
    }

    public function testReturnsNullForInvalidComposerJson() : void
    {
        $packageDir = $this->tmpDir . '/bad-package';
        \mkdir($packageDir, 0755, true);
        \file_put_contents($packageDir . '/composer.json', 'not valid json{{{');

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNull($result);
    }

    public function testReturnsAbsolutePath() : void
    {
        $packageDir = $this->createPackageDir('custom_config.php', null, [
            'name' => 'zbateson/mmp-other',
            'extra' => [
                'mail-mime-parser' => [
                    'di_config' => 'custom_config.php',
                ],
            ],
        ]);

        $result = MailMimeParser::readPluginConfigPath($packageDir);

        $this->assertNotNull($result);
        $this->assertTrue($result === \realpath($result), 'Expected an absolute path');
    }

    public function testReturnsNullForNonexistentPackageDir() : void
    {
        $result = MailMimeParser::readPluginConfigPath($this->tmpDir . '/nonexistent');

        $this->assertNull($result);
    }
}
