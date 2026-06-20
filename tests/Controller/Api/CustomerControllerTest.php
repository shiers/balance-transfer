<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CustomerControllerTest extends WebTestCase
{
    public function testListCustomers(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/customers');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(10, $data);
        $this->assertEquals('Albert Apple', $data[0]['name']);
        $this->assertEquals('345.00', $data[0]['balance']);
    }

    public function testListCustomersContainsExpectedFields(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/customers');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('balance', $data[0]);
    }

    public function testShowCustomer(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/customers/1');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Albert Apple', $data['name']);
        $this->assertEquals('345.00', $data['balance']);
    }

    public function testShowCustomerNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/customers/9999');

        self::assertResponseStatusCodeSame(404);
    }
}
