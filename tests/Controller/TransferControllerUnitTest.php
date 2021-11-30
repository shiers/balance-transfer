<?php
/**
 * File:        TransferControllerUnitTest.php
 * Author:      Shawn Shiers
 */

namespace App\Tests\Controller;


use App\Controller\TransferController;
use App\Entity\Customer;
use App\Entity\Transfer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferControllerUnitTest extends WebTestCase
{
    protected static $static_em;

    protected EntityManager $em;

    protected function setUp(): void
    {
        $this->em = self::$static_em;
    }

    public static function setUpBeforeClass(): void
    {
        $em = static::createClient()->getContainer()->get('doctrine')->getManager();
        self::$static_em = $em;
    }

    public function tearDown(): void
    {
        $this->em->createQuery("DELETE FROM App:customer c WHERE c.name = 'sender'")->execute();
        $this->em->createQuery("DELETE FROM App:customer c WHERE c.name = 'recipient'")->execute();
    }

    public static function tearDownAfterClass(): void
    {
        self::$static_em->close();
    }

    /**
     * Creates a stub CustomerRepository to use as a constructor argument for the TransferController
     *
     * @return Stub|CustomerRepository
     */
    private function getCustomerRepository(): Stub|CustomerRepository
    {
        return $this->createStub(CustomerRepository::class);
    }

    private function getSender(): Customer
    {
        $sender = new Customer();
        $sender->setName('sender');

        return $sender;
    }

    private function getRecipient(): Customer
    {
        $recipient = new Customer();
        $recipient->setName('recipient');

        return $recipient;
    }

    private function getUpdatedSender($name)
    {
        $query = "SELECT c FROM App:customer c WHERE c.name = :customer";

        return $this->em->createQuery($query)
            ->setParameters(['customer' => $name])
            ->getSingleResult();
    }

    private function getUpdatedRecipient($name)
    {
        $query = "SELECT c FROM App:customer c WHERE c.name = :customer";

        return $this->em->createQuery($query)
            ->setParameters(['customer' => $name])
            ->getSingleResult();
    }

    public function testSuccessfulTransferAmountEntered(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(500);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(0);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = 100.50;
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        $transferQuery = "SELECT trans FROM App:Transfer trans WHERE trans.customerFrom = :customer";
        /** @var Transfer $transfer */
        $transfer = $this->em->createQuery($transferQuery)
            ->setParameters(['customer' => $sender->getName()])
            ->getSingleResult();

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertUpdatedBalances(
            $data,
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Transfer asserts
        $this->assertEquals(date_create()->format('Y-m-d'), $transfer->getDate()->format('Y-m-d'));
        $this->assertEquals('sender', $transfer->getCustomerFrom());
        $this->assertEquals('recipient', $transfer->getCustomerTo());
        $this->assertEquals($data['amount'], $transfer->getAmount());

        // Response message asserts
        $this->assertEquals('Transferred $'.$data['amount'].' from sender to recipient', $responseMessage['message']);
        $this->assertEquals('success', $responseMessage['type']);
    }

    public function testSuccessfulSubmitNoAmountEntered(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(500);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(0);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = '';
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertNoChangeBalances(
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Response message asserts
        $this->assertEquals('The transfer was submitted without an amount entered!', $responseMessage['message']);
        $this->assertEquals('warning', $responseMessage['type']);
    }

    public function testUnsuccessfulTransferInvalidEntry(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(500);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(0);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = 'a';
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertUpdatedBalances(
            $data,
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Response message asserts
        $this->assertEquals('Only numeric values are allowed!', $responseMessage['message']);
        $this->assertEquals('error', $responseMessage['type']);
    }

    public function testUnsuccessfulTransferBalanceIsZero(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(0);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(100);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = 1000;
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertNoChangeBalances(
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Response message asserts
        $this->assertEquals(
            'Transfers may not be made if balance is zero or in overdraft',
            $responseMessage['message']);
        $this->assertEquals('error', $responseMessage['type']);
    }

    public function testUnsuccessfulTransferAmountGreaterThanBalance(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(50);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(100);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = 51;
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertNoChangeBalances(
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Response message asserts
        $this->assertEquals(
            'Transfer amount must be less or equal to the balance of $50 and may not exceed $500',
            $responseMessage['message']);
        $this->assertEquals('error', $responseMessage['type']);
    }

    public function testUnsuccessfulTransferAmountOverLimit(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(1000);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(100);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = 501;
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertNoChangeBalances(
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Response message asserts
        $this->assertEquals(
            'Transfers may not exceed $500',
            $responseMessage['message']);
        $this->assertEquals('error', $responseMessage['type']);
    }

    public function testUnsuccessfulTransferNegativeValueEntered(): void
    {
        $sender = $this->getSender();
        $sender->setBalance(1000);
        $this->em->persist($sender);

        $recipient = $this->getRecipient();
        $recipient->setBalance(100);
        $this->em->persist($recipient);

        $this->em->flush();
        $id = $recipient->getId();

        $senderOriginalBalance = $sender->getBalance();

        $recipientOriginalBalance = $recipient->getBalance();

        $data = [];
        $data['amount'] = -1;
        // Passing in the recipient's id here is irrelevant because the Customer Repository stub
        // is told to return $recipient from
        $data['customerTo'] = $id;

        $customerRepository = $this->getCustomerRepository();
        $customerRepository->method('find')->willReturnOnConsecutiveCalls($sender, $recipient);

        $transferController = new TransferController($this->em, $customerRepository);

        $responseMessage = $transferController->processTransfer($data, $id);

        /** @var Customer $updatedSender */
        $updatedSender = $this->getUpdatedSender($sender->getName());

        /** @var Customer $updatedRecipient */
        $updatedRecipient = $this->getUpdatedRecipient($recipient->getName());

        // Customer asserts
        $this->assertNoChangeBalances(
            $senderOriginalBalance,
            $updatedSender,
            $recipientOriginalBalance,
            $updatedRecipient
        );

        // Response message asserts
        $this->assertEquals(
            'Transfers may not be negative values',
            $responseMessage['message']);
        $this->assertEquals('error', $responseMessage['type']);
    }

    /********************************************************************
     * Common Asserts
     *******************************************************************/

    private function assertUpdatedBalances($data, $senderOriginalBalance, $updatedSender, $recipientOriginalBalance, $updatedRecipient)
    {
        // Sender balance assert
        $this->assertEquals($senderOriginalBalance - (float)$data['amount'], $updatedSender->getBalance());

        // Recipient balance assert
        $this->assertEquals($recipientOriginalBalance + (float)$data['amount'], $updatedRecipient->getBalance());
    }

    private function assertNoChangeBalances($senderOriginalBalance, $updatedSender, $recipientOriginalBalance, $updatedRecipient)
    {
        // Sender balance assert
        $this->assertEquals($senderOriginalBalance , $updatedSender->getBalance());

        // Recipient balance assert
        $this->assertEquals($recipientOriginalBalance , $updatedRecipient->getBalance());
    }
}
