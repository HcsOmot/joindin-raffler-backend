<?php

declare(strict_types=1);

use App\DataFixtures\ORM\UserFixtures;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Webmozart\Assert\Assert;

class FOSWebContext extends MinkContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    /**
     * FOSWebContext constructor.
     *
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     *
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->entityManager = $this->getService('doctrine.orm.default_entity_manager');
    }

    /**
     * @BeforeScenario
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function loadUserDataFixtures(BeforeScenarioScope $scope)
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->createSchema($metadata);
        $userDataFixtures = new UserFixtures();

        $fixturesLoader = new Loader();
        $fixturesLoader->addFixture($userDataFixtures);

        $ORMPurger = new ORMPurger();
        $ORMPurger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);

        $executor = new ORMExecutor($this->entityManager, $ORMPurger);
        $executor->execute($fixturesLoader->getFixtures());
    }

    /**
     * @When I visit :url
     */
    public function iVisit(string $url)
    {
        $this->visit($url);
    }

    /**
     * @When I click :link
     */
    public function iClick(string $link)
    {
        $this->clickLink($link);
    }

    /**
     * @Then I should see :rowText in the :rowIdentifier row
     */
    public function IShouldSeeIn(string $rowText, string $rowIdentifier)
    {
        $row = $this->findRowByText($rowIdentifier);
        $text = $row->getText();
        Assert::contains($text, $rowText);
    }

    /**
     * @param $rowText
     * @return \Behat\Mink\Element\NodeElement
     */
    private function findRowByText($rowText)
    {
        $row = $this->getSession()->getPage()->find('css', sprintf('table tr:contains("%s")', $rowText));
        Assert::notNull($row, 'Cannot find a table row with this text!');
        return $row;
    }

    /**
     * @Given there is a regular user with username :username
     */
    public function thereIsRegularUserWithUsername(string $username)
    {
        $userManager = $this->getService('fos_user.user_manager');
        $user        = $userManager->findUserByUsername($username);

        Assert::notNull($user);
    }

    /**
     * @Given there is no user with username :username
     */
    public function thereIsNoUserWithUsername($username)
    {
        $userManager = $this->getService('fos_user.user_manager');
        $user        = $userManager->findUserByUsername($username);
        if (null !== $user) {
            $userManager->deleteUser($user);
        }
    }

    protected function getService(string $name)
    {
        return $this->kernel->getContainer()->get($name);
    }

    /**
     * @Given I am not logged in
     */
    public function iAmNotLoggedIn()
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        /** @var \Symfony\Bundle\FrameworkBundle\Client $client */
        $client = $driver->getClient();

        $client->getCookieJar()->set(new Cookie(session_name(), 'anon_user_not_logged_in'));

        /** @var Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->getService('session');

        $providerKey = $this->kernel->getContainer()->getParameter('fos_user.firewall_name');

        $token = new AnonymousToken('$3cr37', 'anon', []);
        $session->set('_security_'.$providerKey, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    /**
     * @Given I am authorized with :accessRole
     */
    public function iAmAuthorizedWith($accessRole)
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        /** @var \Symfony\Bundle\FrameworkBundle\Client $client */
        $client = $driver->getClient();

        /** @var Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->getService('session');

//        clear residual session data from any previous scenarios
        $session->clear();

        $providerKey = $this->kernel->getContainer()->getParameter('fos_user.firewall_name');

        /** @var \FOS\UserBundle\Doctrine\UserManager $fosUserManager */
        $fosUserManager = $this->getService('fos_user.user_manager');

        $user = $fosUserManager->findUserByUsername('admin');

        $token = new UsernamePasswordToken($user, $user->getPassword(), $providerKey, [$accessRole]);

        $session->set('_security_'.$providerKey, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}
