<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class CustomerController extends AbstractController
{
    private CustomerRepository $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    #[Route('/customers', name: 'api_customers', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $customers = $this->customerRepository->findAll();

        $data = array_map(function (Customer $customer) {
            return [
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'balance' => $customer->getBalance(),
            ];
        }, $customers);

        return $this->json($data);
    }

    #[Route('/customers/{id}', name: 'api_customer_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if (!$customer) {
            return $this->json(['error' => 'Customer not found'], 404);
        }

        return $this->json([
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'balance' => $customer->getBalance(),
        ]);
    }
}
