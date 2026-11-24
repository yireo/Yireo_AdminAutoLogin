<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Plugin;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Yireo\AdminAutoLogin\Config\Config;
use Yireo\AdminAutoLogin\Model\AutoLogin;
use Yireo\AdminAutoLogin\Util\IpMatcher;

class AutoLoginBeforeDispatch
{
    public function __construct(
        private readonly Config $config,
        private readonly IpMatcher $ipMatcher,
        private readonly RemoteAddress $remoteAddress,
        private readonly AuthSession $authSession,
        private readonly AutoLogin $autoLogin
    ) {
    }

    /**
     * @param AbstractAction $subject
     * @param RequestInterface $request
     * @return null
     */
    public function beforeDispatch(AbstractAction $subject, RequestInterface $request)
    {
        if ($this->authSession->isLoggedIn()) {
            return null;
        }

        if (!$request instanceof HttpRequest) {
            return null;
        }

        if ($request->getRouteName() === 'adminhtml' && $request->getControllerName() === 'auth') {
            return null;
        }

        if (!$this->config->isEnabled()) {
            return null;
        }

        $ip = (string)$this->remoteAddress->getRemoteAddress();
        if (!$this->ipMatcher->matches($ip, $this->config->getAllowedIps())) {
            return null;
        }

        $this->autoLogin->login($this->config->getUsername(), $ip);
        return null;
    }
}
