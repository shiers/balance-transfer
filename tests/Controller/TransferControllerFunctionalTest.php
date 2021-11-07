<?php
/**
 * File:        TransferControllerFunctionalTest.php
 * Author:      Shawn Shiers
 */

namespace App\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class TransferControllerFunctionalTest extends WebTestCase
{
    protected function getForm(Crawler $crawler): Form
    {
        return $crawler->selectButton('form_transfer[submit]')->form();
    }

    protected function getClient(): KernelBrowser|AbstractBrowser|null
    {
        return static::createClient();
    }

    public function testTransferPage(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        $client->request('GET', '/transfer/1');

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

        $crawler = $client->request('GET', '/transfer/1');

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = 10;

        $client->submit($form);

        self::assertSelectorTextContains('h1', 'Customers');
    }

    public function testSuccessfulSubmitNoAmountEntered(): void
    {
        $client = $this->getClient();

        // Enable followRedirects to handle various redirects
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/transfer/1');

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

        $crawler = $client->request('GET', '/transfer/1');

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

        $crawler = $client->request('GET', '/transfer/7');

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = 500;

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

        $crawler = $client->request('GET', '/transfer/1');

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = 500;

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

        $crawler = $client->request('GET', '/transfer/1');

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = 1000;

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

        $crawler = $client->request('GET', '/transfer/1');

        // Assert that the Submit transfer button is on the page
        $this->assertSubmitButtonPresent($client);

        // Assert that the amount field is present
        $this->assertAmountFieldPresent($client);

        $form = $this->getForm($crawler);

        $form['form_transfer[amount]'] = -1;

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
