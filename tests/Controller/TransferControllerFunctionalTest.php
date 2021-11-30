<?php
/**
 * File:        TransferControllerFunctionalTest.php
 * Author:      Shawn Shiers
 */

namespace App\Tests\Controller;


use App\Entity\Customer;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class TransferControllerFunctionalTest extends WebTestCase
{
    protected static $static_em;
    protected static $repository;

    protected EntityManager $em;

    protected function setUp(): void
    {
        $this->em = self::$static_em;
    }

    public static function setUpBeforeClass(): void
    {
        static::ensureKernelShutdown();
        $em = static::createClient()->getContainer()->get('doctrine')->getManager();
        self::$static_em = $em;

        parent::setUpBeforeClass();

        self::ensureKernelShutdown();
    }

    protected function getForm(Crawler $crawler): Form
    {
        return $crawler->selectButton('form_transfer[submit]')->form();
    }

    protected function getClient(): KernelBrowser|AbstractBrowser|null
    {
        return static::createClient();
    }

    protected function getCustomerWithBalance()
    {
        return $this->em->createQuery(
            "SELECT c FROM App:Customer c ORDER BY c.id ASC")
            ->setMaxResults(1)
            ->getSingleResult();
    }

    protected function getCustomerWithZeroBalance()
    {
        /** @var Customer $customer */
        $customer = $this->em->createQuery(
            "SELECT c FROM App:Customer c WHERE c.balance = 0 ORDER BY c.id ASC")
            ->setMaxResults(1)
            ->getResult();

        return $customer[0];
    }

    public function testTransferPage(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        self::assertSelectorTextContains('h2', 'Money Transfer');
    }

    public function testSuccessfulTransferAmountEntered(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = '10';

        $client->submit($form);

        self::assertSelectorTextContains('h1', 'Customers');
    }

    public function testSuccessfulSubmitNoAmountEntered(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = '';

        $client->submit($form);

        self::assertSelectorTextContains('h1', 'Customers');
    }

    public function testUnsuccessfulTransferInvalidEntry(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = 'a';

        $client->submit($form);

        $this->assertMatchesRegularExpression(
            '/Only numeric values are allowed/',
            $client->getResponse()->getContent()
        );
    }

    public function testUnsuccessfulTransferBalanceIsZero(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithZeroBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = '500';

        $client->submit($form);

        $this->assertMatchesRegularExpression(
            '/Transfers may not be made if balance is zero or in overdraft/',
            $client->getResponse()->getContent()
        );
    }

    public function testUnsuccessfulTransferAmountGreaterThanBalance(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = '500';

        $client->submit($form);

        $this->assertMatchesRegularExpression(
            '/Transfer amount must be less or equal to the balance/',
            $client->getResponse()->getContent()
        );
    }


    public function testUnsuccessfulTransferAmountOverLimit(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = '1000';

        $client->submit($form);

        $this->assertMatchesRegularExpression(
            '/may not exceed/',
            $client->getResponse()->getContent()
        );
    }

    public function testUnsuccessfulTransferNegativeValueEntered(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        /** @var Customer $customer */
        $customer = $this->getCustomerWithBalance();
        $id = $customer->getId();

        $crawler = $client->request('GET', '/transfer/'.$id);

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = '-1';

        $client->submit($form);

        $this->assertMatchesRegularExpression(
            '/Transfers may not be negative values/',
            $client->getResponse()->getContent()
        );
    }

    /********************************************************************
     * Common Asserts
     *******************************************************************/

    protected function assertSubmitButtonPresent($client): void
    {
        $this->assertMatchesRegularExpression(
            '/submit/',
            $client->getResponse()->getContent()
        );
    }

    protected function assertAmountFieldPresent($client): void
    {
        $this->assertMatchesRegularExpression(
            '/amount/',
            $client->getResponse()->getContent()
        );
    }
}
