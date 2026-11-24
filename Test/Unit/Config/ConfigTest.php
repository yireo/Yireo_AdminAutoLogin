<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Test\Unit\Config;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Yireo\AdminAutoLogin\Config\Config;

class ConfigTest extends TestCase
{
    public function testIsEnabledInDeveloperModeWithFlagAndUsername(): void
    {
        $this->assertTrue($this->createConfig(['enabled' => true, 'username' => 'admin'])->isEnabled());
    }

    public function testIsDisabledOutsideDeveloperMode(): void
    {
        $values = ['enabled' => true, 'username' => 'admin'];
        $this->assertFalse($this->createConfig($values, State::MODE_PRODUCTION)->isEnabled());
        $this->assertFalse($this->createConfig($values, State::MODE_DEFAULT)->isEnabled());
    }

    public function testIsDisabledWithoutStrictTrueFlag(): void
    {
        $this->assertFalse($this->createConfig(['username' => 'admin'])->isEnabled());
        $this->assertFalse($this->createConfig(['enabled' => '1', 'username' => 'admin'])->isEnabled());
        $this->assertFalse($this->createConfig(['enabled' => 1, 'username' => 'admin'])->isEnabled());
    }

    public function testIsDisabledWithoutUsername(): void
    {
        $this->assertFalse($this->createConfig(['enabled' => true])->isEnabled());
        $this->assertFalse($this->createConfig(['enabled' => true, 'username' => "  \n"])->isEnabled());
        $this->assertFalse($this->createConfig(['enabled' => true, 'username' => ['admin']])->isEnabled());
    }

    public function testGetUsernameIsTrimmed(): void
    {
        $this->assertSame('admin', $this->createConfig(['username' => " admin\n"])->getUsername());
    }

    public function testGetAllowedIpsDefaultsToLocalAndPrivateRanges(): void
    {
        $this->assertSame(Config::DEFAULT_ALLOWED_IPS, $this->createConfig([])->getAllowedIps());
    }

    public function testGetAllowedIpsFromArray(): void
    {
        $config = $this->createConfig(['allowed_ips' => [' 127.0.0.1 ', '', '10.0.0.0/8']]);
        $this->assertSame(['127.0.0.1', '10.0.0.0/8'], $config->getAllowedIps());
    }

    public function testGetAllowedIpsFromCommaSeparatedString(): void
    {
        $config = $this->createConfig(['allowed_ips' => '127.0.0.1, ::1,']);
        $this->assertSame(['127.0.0.1', '::1'], $config->getAllowedIps());
    }

    public function testEmptyAllowedIpsBlocksEverything(): void
    {
        $this->assertSame([], $this->createConfig(['allowed_ips' => []])->getAllowedIps());
    }

    private function createConfig(array $values, string $mode = State::MODE_DEVELOPER): Config
    {
        $deploymentConfig = $this->createMock(DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturnCallback(
            function ($key = null, $defaultValue = null) use ($values) {
                $name = substr((string)$key, strlen('yireo_admin_autologin/'));
                return $values[$name] ?? $defaultValue;
            }
        );

        $appState = $this->createMock(State::class);
        $appState->method('getMode')->willReturn($mode);

        return new Config($deploymentConfig, $appState);
    }
}
