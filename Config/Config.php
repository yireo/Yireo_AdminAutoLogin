<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Config;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;

class Config
{
    public const DEFAULT_ALLOWED_IPS = [
        '127.0.0.0/8',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        'fc00::/7',
    ];

    private const PATH_ENABLED = 'yireo_admin_autologin/enabled';
    private const PATH_USERNAME = 'yireo_admin_autologin/username';
    private const PATH_ALLOWED_IPS = 'yireo_admin_autologin/allowed_ips';

    public function __construct(
        private readonly DeploymentConfig $deploymentConfig,
        private readonly State $appState
    ) {
    }

    public function isEnabled(): bool
    {
        if ($this->appState->getMode() !== State::MODE_DEVELOPER) {
            return false;
        }

        return $this->deploymentConfig->get(self::PATH_ENABLED) === true
            && $this->getUsername() !== '';
    }

    public function getUsername(): string
    {
        $username = $this->deploymentConfig->get(self::PATH_USERNAME);
        if (!is_string($username)) {
            return '';
        }

        return trim($username, " \f\n\r\t\v\x00");
    }

    /**
     * @return string[]
     */
    public function getAllowedIps(): array
    {
        $allowedIps = $this->deploymentConfig->get(self::PATH_ALLOWED_IPS);
        if (is_string($allowedIps)) {
            $allowedIps = explode(',', $allowedIps);
        }

        if (!is_array($allowedIps)) {
            return self::DEFAULT_ALLOWED_IPS;
        }

        $allowedIps = array_map(
            fn ($allowedIp) => trim((string)$allowedIp, " \f\n\r\t\v\x00"),
            $allowedIps
        );

        return array_values(array_filter($allowedIps, fn (string $allowedIp) => $allowedIp !== ''));
    }
}
