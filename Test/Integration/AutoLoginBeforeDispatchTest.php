<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Test\Integration;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Interception\DefinitionInterface;
use Magento\Framework\Interception\PluginListInterface;
use Magento\Security\Model\AdminSessionsManager;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Magento\User\Test\Fixture\User as UserFixture;
use PHPUnit\Framework\TestCase;
use Yireo\AdminAutoLogin\Config\Config;
use Yireo\AdminAutoLogin\Plugin\AutoLoginBeforeDispatch;

#[AppArea('adminhtml')]
#[DbIsolation(true)]
class AutoLoginBeforeDispatchTest extends TestCase
{
    private AuthSession $authSession;

    protected function setUp(): void
    {
        $this->authSession = Bootstrap::getObjectManager()->get(AuthSession::class);
        $this->authSession->setUser(null);
    }

    protected function tearDown(): void
    {
        $this->authSession->setUser(null);
    }

    public function testPluginRunsBeforeCoreAuthentication(): void
    {
        $pluginList = Bootstrap::getObjectManager()->get(PluginListInterface::class);
        $order = [];
        $code = '__self';
        do {
            $next = (array)$pluginList->getNext(AbstractAction::class, 'dispatch', $code);
            foreach ((array)($next[DefinitionInterface::LISTENER_BEFORE] ?? []) as $name) {
                $order[] = $name;
            }

            $code = $next[DefinitionInterface::LISTENER_AROUND] ?? null;
            if ($code) {
                $order[] = $code;
            }
        } while ($code);

        $ownPosition = array_search('Yireo_AdminAutoLogin::autoLogin', $order, true);
        $corePosition = array_search('adminAuthentication', $order, true);
        $this->assertIsInt($ownPosition);
        $this->assertIsInt($corePosition);
        $this->assertLessThan($corePosition, $ownPosition);
    }

    #[DataFixture(UserFixture::class, as: 'user')]
    public function testValidUserIsLoggedIn(): void
    {
        $user = $this->getFixtureUser();
        $this->dispatch($this->createPlugin($user->getUserName()));

        $this->assertTrue((bool)$this->authSession->isLoggedIn());
        $this->assertSame((int)$user->getId(), (int)$this->authSession->getUser()->getId());

        $adminSessionsManager = Bootstrap::getObjectManager()->get(AdminSessionsManager::class);
        $this->assertTrue((bool)$adminSessionsManager->getCurrentSession()->isLoggedInStatus());
    }

    #[DataFixture(UserFixture::class, as: 'user')]
    public function testNoLoginOutsideDeveloperMode(): void
    {
        $user = $this->getFixtureUser();
        $this->dispatch($this->createPlugin($user->getUserName(), mode: State::MODE_PRODUCTION));
        $this->assertNotLoggedIn();

        $this->dispatch($this->createPlugin($user->getUserName(), mode: State::MODE_DEFAULT));
        $this->assertNotLoggedIn();
    }

    #[DataFixture(UserFixture::class, as: 'user')]
    public function testNoLoginWithoutExplicitEnvFlag(): void
    {
        $user = $this->getFixtureUser();
        $this->dispatch($this->createPlugin($user->getUserName(), enabled: null));
        $this->assertNotLoggedIn();

        $this->dispatch($this->createPlugin($user->getUserName(), enabled: '1'));
        $this->assertNotLoggedIn();
    }

    #[DataFixture(UserFixture::class, as: 'user')]
    public function testNoLoginFromDisallowedIp(): void
    {
        $user = $this->getFixtureUser();
        $this->dispatch($this->createPlugin($user->getUserName(), ip: '8.8.8.8'));
        $this->assertNotLoggedIn();

        $this->dispatch($this->createPlugin($user->getUserName(), ip: '127.0.0.1', allowedIps: ['10.0.0.1']));
        $this->assertNotLoggedIn();
    }

    #[DataFixture(UserFixture::class, as: 'user')]
    public function testNoLoginOnAuthController(): void
    {
        $user = $this->getFixtureUser();
        $this->dispatch($this->createPlugin($user->getUserName()), 'auth', 'logout');
        $this->assertNotLoggedIn();
    }

    public function testNoLoginForUnknownUser(): void
    {
        $this->dispatch($this->createPlugin('unknown-user-' . uniqid()));
        $this->assertNotLoggedIn();
    }

    public function testNoLoginForEmptyUsername(): void
    {
        $this->dispatch($this->createPlugin(''));
        $this->assertNotLoggedIn();
    }

    #[DataFixture(UserFixture::class, ['is_active' => 0], 'user')]
    public function testNoLoginForInactiveUser(): void
    {
        $this->dispatch($this->createPlugin($this->getFixtureUser()->getUserName()));
        $this->assertNotLoggedIn();
    }

    #[DataFixture(UserFixture::class, as: 'user')]
    public function testNoLoginForLockedUser(): void
    {
        $user = $this->getFixtureUser();
        Bootstrap::getObjectManager()->get(UserResource::class)->lock([(int)$user->getId()], 0, 3600);

        $this->dispatch($this->createPlugin($user->getUserName()));
        $this->assertNotLoggedIn();
    }

    private function assertNotLoggedIn(): void
    {
        $this->assertFalse((bool)$this->authSession->isLoggedIn());
    }

    private function getFixtureUser(): User
    {
        $user = DataFixtureStorageManager::getStorage()->get('user');
        $this->assertInstanceOf(User::class, $user);
        return $user;
    }

    private function dispatch(
        AutoLoginBeforeDispatch $plugin,
        string $controllerName = 'dashboard',
        string $actionName = 'index'
    ): void {
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        $request->setRouteName('adminhtml');
        $request->setControllerName($controllerName);
        $request->setActionName($actionName);

        $plugin->beforeDispatch($this->createMock(AbstractAction::class), $request);
    }

    private function createPlugin(
        string $username,
        mixed $enabled = true,
        string $mode = State::MODE_DEVELOPER,
        string $ip = '127.0.0.1',
        ?array $allowedIps = null
    ): AutoLoginBeforeDispatch {
        $objectManager = Bootstrap::getObjectManager();

        $deploymentConfig = $this->createMock(DeploymentConfig::class);
        $values = [
            'yireo_admin_autologin/enabled' => $enabled,
            'yireo_admin_autologin/username' => $username,
            'yireo_admin_autologin/allowed_ips' => $allowedIps,
        ];
        $deploymentConfig->method('get')->willReturnCallback(
            fn ($key = null, $defaultValue = null) => $values[$key] ?? $defaultValue
        );

        $config = $objectManager->create(Config::class, [
            'deploymentConfig' => $deploymentConfig,
            'appState' => $objectManager->create(State::class, ['mode' => $mode]),
        ]);

        $remoteAddress = $this->createMock(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn($ip);

        return $objectManager->create(AutoLoginBeforeDispatch::class, [
            'config' => $config,
            'remoteAddress' => $remoteAddress,
        ]);
    }
}
