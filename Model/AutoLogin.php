<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;

class AutoLogin
{
    public function __construct(
        private readonly AuthSession $authSession,
        private readonly UserFactory $userFactory,
        private readonly AdminSessionsManager $adminSessionsManager,
        private readonly EventManager $eventManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function login(string $username, string $ip): bool
    {
        $user = $this->userFactory->create();
        $user->loadByUsername($username);

        if (!$this->isValidUser($user)) {
            $this->logger->warning('Admin auto-login skipped: user is missing, inactive or locked', [
                'username' => $username,
                'ip' => $ip,
            ]);
            return false;
        }

        $this->authSession->setUser($user);
        $this->authSession->processLogin();
        $this->adminSessionsManager->processLogin();
        $this->eventManager->dispatch('backend_auth_user_login_success', ['user' => $user]);

        $this->logger->warning('Admin auto-login performed', [
            'username' => $username,
            'ip' => $ip,
        ]);

        return (bool)$this->authSession->isLoggedIn();
    }

    private function isValidUser(User $user): bool
    {
        if (!$user->getId() || !$user->getIsActive()) {
            return false;
        }

        $lockExpires = (string)$user->getLockExpires();
        if ($lockExpires === '') {
            return true;
        }

        $lockExpiresTime = strtotime($lockExpires);
        return $lockExpiresTime !== false && $lockExpiresTime <= time();
    }
}
