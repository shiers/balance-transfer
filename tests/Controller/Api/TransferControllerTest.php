<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferControllerTest extends WebTestCase
{
    private function postTransfer(array $payload): array
    {
        $client = static::createClient();
        $client->request('POST', '/api/transfers', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        return [
            'client' => $client,
            'status' => $client->getResponse()->getStatusCode(),
            'data' => json_decode($client->getResponse()->getContent(), true),
        ];
    }

    // ---------------------------------------------------------------
    // GET /api/transfers
    // ---------------------------------------------------------------

    public function testListTransfers(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/transfers');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ---------------------------------------------------------------
    // POST /api/transfers - Successful
    // ---------------------------------------------------------------

    public function testSuccessfulTransfer(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,    // Albert Apple, balance 345.00
            'recipientId' => 3, // Steve Spinach
            'amount' => 10.00,
        ]);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals('success', $result['data']['type']);
        $this->assertStringContainsString('Transferred $10.00', $result['data']['message']);
        $this->assertStringContainsString('Albert Apple', $result['data']['message']);
        $this->assertStringContainsString('Steve Spinach', $result['data']['message']);
    }

    // ---------------------------------------------------------------
    // POST /api/transfers - Validation errors
    // ---------------------------------------------------------------

    public function testTransferMissingFields(): void
    {
        $result = $this->postTransfer([]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
    }

    public function testTransferNonNumericAmount(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,
            'recipientId' => 3,
            'amount' => 'abc',
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('numeric', $result['data']['message']);
    }

    public function testTransferZeroAmount(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,
            'recipientId' => 3,
            'amount' => 0,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('without an amount', $result['data']['message']);
    }

    public function testTransferNegativeAmount(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,
            'recipientId' => 3,
            'amount' => -50,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('negative', $result['data']['message']);
    }

    public function testTransferExceedsLimit(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,
            'recipientId' => 3,
            'amount' => 501,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('exceed', $result['data']['message']);
    }

    public function testTransferSenderNotFound(): void
    {
        $result = $this->postTransfer([
            'senderId' => 9999,
            'recipientId' => 3,
            'amount' => 10,
        ]);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
    }

    public function testTransferRecipientNotFound(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,
            'recipientId' => 9999,
            'amount' => 10,
        ]);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
    }

    public function testTransferSenderZeroBalance(): void
    {
        // Minnie Mango has balance 0.00
        $result = $this->postTransfer([
            'senderId' => 2,
            'recipientId' => 3,
            'amount' => 10,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('zero or in overdraft', $result['data']['message']);
    }

    public function testTransferAmountExceedsSenderBalance(): void
    {
        // Steve Spinach has balance 87.50
        $result = $this->postTransfer([
            'senderId' => 3,
            'recipientId' => 1,
            'amount' => 100,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('less or equal to the balance', $result['data']['message']);
    }

    public function testTransferToSelf(): void
    {
        $result = $this->postTransfer([
            'senderId' => 1,
            'recipientId' => 1,
            'amount' => 10,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('error', $result['data']['type']);
        $this->assertStringContainsString('yourself', $result['data']['message']);
    }

    // ---------------------------------------------------------------
    // POST /api/transfers - Balance updates
    // ---------------------------------------------------------------

    public function testTransferUpdatesBalances(): void
    {
        $client = static::createClient();

        // Make a transfer: Albert Apple (345) -> Steve Spinach (87.50), amount 50
        $client->request('POST', '/api/transfers', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'senderId' => 1,
            'recipientId' => 3,
            'amount' => 50,
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Verify sender balance decreased
        $client->request('GET', '/api/customers/1');
        $sender = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('295.00', $sender['balance']);

        // Verify recipient balance increased
        $client->request('GET', '/api/customers/3');
        $recipient = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('137.50', $recipient['balance']);
    }

    public function testTransferAppearsInList(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/transfers', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'senderId' => 1,
            'recipientId' => 3,
            'amount' => 25,
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Verify transfer appears in the list
        $client->request('GET', '/api/transfers');
        $transfers = json_decode($client->getResponse()->getContent(), true);

        $lastTransfer = end($transfers);
        $this->assertEquals(25, $lastTransfer['amount']);
        $this->assertEquals('Albert Apple', $lastTransfer['customerFrom']);
        $this->assertEquals('Steve Spinach', $lastTransfer['customerTo']);
    }
}
