<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Entity\Transfer;
use App\Repository\CustomerRepository;
use App\Repository\TransferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class TransferController extends AbstractController
{
    private EntityManagerInterface $em;
    private CustomerRepository $customerRepository;
    private TransferRepository $transferRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        TransferRepository $transferRepository
    ) {
        $this->em = $entityManager;
        $this->customerRepository = $customerRepository;
        $this->transferRepository = $transferRepository;
    }

    #[Route('/transfers', name: 'api_transfers', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $transfers = $this->transferRepository->findAll();

        $data = array_map(function (Transfer $transfer) {
            return [
                'id' => $transfer->getId(),
                'amount' => $transfer->getAmount(),
                'date' => $transfer->getDate()->format('Y-m-d H:i:s'),
                'customerFrom' => $transfer->getCustomerFrom(),
                'customerTo' => $transfer->getCustomerTo(),
            ];
        }, $transfers);

        return $this->json($data);
    }

    #[Route('/transfers', name: 'api_transfer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $senderId = $data['senderId'] ?? null;
        $recipientId = $data['recipientId'] ?? null;
        $amount = $data['amount'] ?? null;

        // Validate required fields
        if (!$senderId || !$recipientId || $amount === null) {
            return $this->json([
                'type' => 'error',
                'message' => 'Missing required fields: senderId, recipientId, amount',
            ], 400);
        }

        // Validate amount is numeric
        if (!is_numeric($amount)) {
            return $this->json([
                'type' => 'error',
                'message' => 'Only numeric values are allowed!',
            ], 422);
        }

        $amount = (float) $amount;

        // Validate amount is not empty/zero
        if ($amount == 0) {
            return $this->json([
                'type' => 'error',
                'message' => 'The transfer was submitted without an amount entered!',
            ], 422);
        }

        // Validate amount is not negative
        if ($amount < 0) {
            return $this->json([
                'type' => 'error',
                'message' => 'Transfers may not be negative values',
            ], 422);
        }

        // Validate amount does not exceed $500
        if ($amount > 500) {
            return $this->json([
                'type' => 'error',
                'message' => 'Transfers may not exceed $500',
            ], 422);
        }

        /** @var Customer|null $sender */
        $sender = $this->customerRepository->find($senderId);
        if (!$sender) {
            return $this->json([
                'type' => 'error',
                'message' => 'Sender not found',
            ], 404);
        }

        /** @var Customer|null $recipient */
        $recipient = $this->customerRepository->find($recipientId);
        if (!$recipient) {
            return $this->json([
                'type' => 'error',
                'message' => 'There was an error attempting to retrieve the recipient of the transfer. Please try again.',
            ], 404);
        }

        // Validate sender has sufficient balance
        if ((float) $sender->getBalance() <= 0) {
            return $this->json([
                'type' => 'error',
                'message' => 'Transfers may not be made if balance is zero or in overdraft',
            ], 422);
        }

        if ($amount > (float) $sender->getBalance()) {
            return $this->json([
                'type' => 'error',
                'message' => 'Transfer amount must be less or equal to the balance of $' .
                    $sender->getBalance() . ' and may not exceed $500',
            ], 422);
        }

        // Prevent self-transfer
        if ($senderId === $recipientId) {
            return $this->json([
                'type' => 'error',
                'message' => 'Cannot transfer to yourself',
            ], 422);
        }

        // Process the transfer
        $senderNewBalance = (float) $sender->getBalance() - $amount;
        $sender->setBalance((string) $senderNewBalance);
        $this->em->persist($sender);

        $recipientNewBalance = (float) $recipient->getBalance() + $amount;
        $recipient->setBalance((string) $recipientNewBalance);
        $this->em->persist($recipient);

        $transfer = new Transfer();
        $transfer->setDate(new \DateTime());
        $transfer->setAmount($amount);
        $transfer->setCustomerFrom($sender->getName());
        $transfer->setCustomerTo($recipient->getName());
        $this->em->persist($transfer);

        $this->em->flush();

        return $this->json([
            'type' => 'success',
            'message' => 'Transferred $' . number_format($amount, 2) .
                ' from ' . $sender->getName() .
                ' to ' . $recipient->getName(),
        ], 201);
    }
}
