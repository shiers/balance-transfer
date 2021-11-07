<?php
/**
 * File:        TransferController.php
 * Author:      Shawn Shiers
 */

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Transfer;
use App\Form\TransferType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This Controller is responsible for handling the money transfers
 *
 * @package App\Controller
 */
class TransferController extends AbstractController
{
    private EntityManagerInterface $em;
    private CustomerRepository $customerRepository;

    public function __construct(EntityManagerInterface $entityManager, CustomerRepository $customerRepository)
    {
        $this->em = $entityManager;
        $this->customerRepository = $customerRepository;
    }

    #[Route('/transfer/{id}', name: 'transfer')]
    public function addTransfer(Request $request, int $id): Response
    {
        /** @var Customer $customer */
        $customer = $this->customerRepository->find($id);

        $transfer = new Transfer();

        $form = $this->createForm(TransferType::class, $transfer, [
            'action' => $this->generateUrl('transfer', ['id' => $id]),
            'method' => 'POST',
            'customerFrom' => $customer
        ])
            ->add('submit', SubmitType::class, ['label' => 'Submit transfer' ]
        );

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            // `&& $form->isValid()` was intentionally omitted from the if statement below
            // to allow TransferController::processTransfer to be thoroughly tested
            if ($form->isSubmitted()) {
                $formData = $request->get('form_transfer');
                $transferSenderId = $id;

                $response = $this->processTransfer($formData, $transferSenderId);

                if (!empty($response['message'])) {
                    $this->addFlash($response['type'], $response['message']);
                }

                if ($response['type'] === 'error') {
                    return $this->redirect($this->generateUrl('transfer', ['id' => $id]));
                }

                return $this->redirect($this->generateUrl('default'));
            }
        }

        $data = [
            'form_route' => $this->generateUrl('transfer', ['id' => $id]),
            'form' => $form->createView(),
            'customer' => $customer
        ];

        return $this->render('transfer/transfer.html.twig', $data);
    }

    #[ArrayShape(['message' => "string", 'type' => "string"])]
    public function processTransfer($formData, $transferSenderId): array
    {
        /** @var Customer $transferSender */
        $transferSender = $this->customerRepository->find($transferSenderId);

        $amountToTransfer = $formData['amount'];

        // Initialize response
        $response = $this->setResponseMessage('success','');

        // Check if $amountToTransfer is not a numeric value, excluding empty string
        if (!empty($amountToTransfer) && !is_numeric($amountToTransfer)) {
            $message = "Only numeric values are allowed!";
            $response = $this->setResponseMessage('error', $message);
        } elseif ($transferSender->getBalance() <= 0) {
            // Prevent a customer's balance from going further into overdraft
            $message = "Transfers may not be made if balance is zero or in overdraft";
            $response = $this->setResponseMessage('error', $message);
        } elseif ($amountToTransfer > $transferSender->getBalance()) {
            // Prevent a customer's balance from going below zero
            $message = "Transfer amount must be less or equal to the balance of $" .
                $transferSender->getBalance() .
                " and may not exceed $500";
            $response = $this->setResponseMessage('error', $message);
        }  elseif ($amountToTransfer > 500) {
            $message = "Transfers may not exceed $500";
            $response = $this->setResponseMessage('error', $message);
        } elseif (!empty($amountToTransfer) && $amountToTransfer < 0) {
            $message = "Transfers may not be negative values";
            $response = $this->setResponseMessage('error', $message);
        } elseif (empty($amountToTransfer)) {
            $message = "The transfer was submitted without an amount entered!";
            $response = $this->setResponseMessage('warning', $message);
        }

        if ($response['type'] === 'success') {
            $transferRecipientId = $formData['customerTo'];
            if (!empty($transferRecipientId)) {

                /** @var Customer $transferRecipient */
                $transferRecipient  = $this->customerRepository->find($transferRecipientId);
                // For some reason Customer.balance is stored as a string
                $transferSenderNewBalance = (float)$transferSender->getBalance() - (float)$amountToTransfer;

                // Update the transfer sender's balance
                $transferSender->setBalance($transferSenderNewBalance);
                $this->em->persist($transferSender);

                // Update the transfer recipient's balance
                $transferRecipientNewBalance = (float)$transferRecipient->getBalance() + (float)$amountToTransfer;
                $transferRecipient->setBalance($transferRecipientNewBalance);
                $this->em->persist($transferRecipient);

                $transfer = new Transfer();
                $transfer->setDate(date_create());
                $transfer->setAmount($amountToTransfer);
                $transfer->setCustomerFrom($transferSender->getName());
                $transfer->setCustomerTo($transferRecipient->getName());
                $this->em->persist($transfer);

                $this->em->flush();

                $message = "Transferred $" .
                    $amountToTransfer .
                    " from " .
                    $transferSender->getName() .
                    " to " .
                    $transferRecipient->getName();

                $response = $this->setResponseMessage('success', $message);

            } else {
                // If the transferRecipient (Customer) was not found then its entry may have been deleted at the same
                // time as the Transfer was being process and in that case we will inform the user to try again.
                $message = "There was a error attempting to retrieve the recipient of the transfer. Please try again.";
                $response = $this->setResponseMessage('warning', $message);
            }
        }
        return $response;
    }

    #[ArrayShape(['message' => "string", 'type' => "string"])]
    private function setResponseMessage(string $type, string $message): array
    {
        return [
            'message' => $message,
            'type' => $type
        ];
    }
}
