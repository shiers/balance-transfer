<?php

namespace App\Controller;

use App\Entity\Transfer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Customer;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'default')]
    public function index(): Response
    {
        $customers = $this->getDoctrine()
            ->getRepository(Customer::class)
            ->findAll();

        $transfers = $this->getDoctrine()
            ->getRepository(Transfer::class)
            ->findAll();

        return $this->render('default/index.html.twig', [
            'customers' => $customers,
            'transfers' => $transfers
        ]);
    }
}
