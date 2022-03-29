<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Plugin;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Backend\Model\Auth\StorageInterface as AuthStorage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\User\Model\User;

class SpoofLogin
{
    private AuthSession $authSession;
    private User $user;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        AuthSession          $authSession,
        User                 $user,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->authSession = $authSession;
        $this->user = $user;
        $this->scopeConfig = $scopeConfig;
    }

    public function aroundIsLoggedIn(AuthStorage $authStorage, callable $proceed)
    {
        $username = $this->getUsername();
        if (!$username) {
            return $proceed();
        }

        $isLoggedIn = $proceed();
        if ($isLoggedIn) {
            return true;
        }

        $this->user->loadByUsername($username);
        $this->authSession->setUser($this->user);
        $this->authSession->refreshAcl($this->user);
        return true;
    }

    private function getUsername(): string
    {
        return trim((string)$this->scopeConfig->getValue('system/admin/autologin_username'));
    }
}
