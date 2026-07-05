<?php

declare(strict_types=1);

namespace App\Controller;

use App\Crud\BookCrud;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/books')]
final class BookController extends AbstractController
{
    public function __construct(private readonly BookCrud $crud)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): Response
    {
        return $this->crud->list();
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): Response
    {
        return $this->crud->create($request);
    }

    #[Route('/{serialNumber}', methods: ['PATCH'])]
    public function updateStatus(string $serialNumber, Request $request): Response
    {
        return $this->crud->updateStatus($serialNumber, $request);
    }

    #[Route('/{serialNumber}', methods: ['DELETE'])]
    public function delete(string $serialNumber): Response
    {
        return $this->crud->delete($serialNumber);
    }
}
