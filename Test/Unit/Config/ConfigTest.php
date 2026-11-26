<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Yireo\AdminAutoLogin\Config\Config;

class ConfigTest extends TestCase
{
    public function testIsEnabledInDeveloperModeWithUsername(): void
    {
        $this->assertTrue($this->createConfig('admin')->isEnabled());
    }

    public function testIsDisabledOutsideDeveloperMode(): void
    {
        $this->assertFalse($this->createConfig('admin', [], State::MODE_PRODUCTION)->isEnabled());
        $this->assertFalse($this->createConfig('admin', [], State::MODE_DEFAULT)->isEnabled());
    }

    public function testIsDisabledWithoutUsername(): void
    {
        $this->assertFalse($this->createConfig(null)->isEnabled());
        $this->assertFalse($this->createConfig('')->isEnabled());
        $this->assertFalse($this->createConfig("  \n")->isEnabled());
    }

    public function testGetUsernameIsTrimmed(): void
    {
        $this->assertSame('admin', $this->createConfig(" admin\n")->getUsername());
    }

    public function testGetAllowedIpsDefaultsToLocalAndPrivateRanges(): void
    {
        $this->assertSame(Config::DEFAULT_ALLOWED_IPS, $this->createConfig('admin')->getAllowedIps());
    }

    public function testGetAllowedIpsFromArray(): void
    {
        $config = $this->createConfig('admin', ['allowed_ips' => [' 127.0.0.1 ', '', '10.0.0.0/8']]);
        $this->assertSame(['127.0.0.1', '10.0.0.0/8'], $config->getAllowedIps());
    }

    public function testGetAllowedIpsFromCommaSeparatedString(): void
    {
        $config = $this->createConfig('admin', ['allowed_ips' => '127.0.0.1, ::1,']);
        $this->assertSame(['127.0.0.1', '::1'], $config->getAllowedIps());
    }

    public function testEmptyAllowedIpsBlocksEverything(): void
    {
        $this->assertSame([], $this->createConfig('admin', ['allowed_ips' => []])->getAllowedIps());
    }

    private function createConfig(
        ?string $username,
        array $envValues = [],
        string $mode = State::MODE_DEVELOPER
    ): Config {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path) use ($username) {
                return $path === 'system/admin/autologin_username' ? $username : null;
            }
        );

        $deploymentConfig = $this->createMock(DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturnCallback(
            function ($key = null, $defaultValue = null) use ($envValues) {
                $name = substr((string)$key, strlen('yireo_admin_autologin/'));
                return $envValues[$name] ?? $defaultValue;
            }
        );

        $appState = $this->createMock(State::class);
        $appState->method('getMode')->willReturn($mode);

        return new Config($scopeConfig, $deploymentConfig, $appState);
    }
}
