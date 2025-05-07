<?php
// src/Controller/Api/ApiExpenseController.php
namespace App\Controller;

use App\Entity\Expense;
use App\Repository\ExpenseRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;


#[Route('/api')]
class ApiExpenseController extends AbstractController
{
    #[Route('/expense', name: 'api_expense_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Unauthorized'], 401);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse(['error' => 'Invalid JSON'], 400);
            }

            $expense = new Expense();
            $expense->setUser($user);
            $expense->setLabel($data['label'] ?? '');

            $categoryName = $data['category'] ?? null;
            if ($categoryName) {
                $category = $categoryRepository->findOneBy(['name' => $categoryName]);
                if (!$category) {
                    return new JsonResponse(['error' => 'Category not found'], 404);
                }
                $expense->setCategory($category);
            } else {
                return new JsonResponse(['error' => 'Category is required'], 400);
            }

            $expense->setAmount((float) ($data['amount'] ?? 0));
            $expense->setDate(!empty($data['date']) ? new \DateTimeImmutable($data['date']) : new \DateTimeImmutable());

            $em->persist($expense);
            $em->flush();

            return new JsonResponse(['message' => 'Expense created successfully'], 201);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    #[Route('/expenses', name: 'api_expense_list', methods: ['GET'])]
    public function list(ExpenseRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $expenses = $repo->findBy(['user' => $user]);
        $data = $serializer->normalize($expenses, null, ['groups' => 'expense:read']);

        return new JsonResponse($data, 200);
    }

    #[Route('/expense/{id}', name: 'api_expense_delete', methods: ['DELETE'])]
    public function delete(Expense $expense, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $expense->getUser() !== $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $em->remove($expense);
        $em->flush();

        return new JsonResponse(['message' => 'Expense deleted'], 200);
    }

    #[Route('/expense/{id}', name: 'api_expense_get', methods: ['GET'])]
    public function getExpense(Expense $expense): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $expense->getUser() !== $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }
    
        return $this->json($expense, 200, [], ['groups' => 'expense:read']);
    }    

    #[Route('/expense/{id}', name: 'api_expense_update', methods: ['PUT'])]
    public function update(Request $request, Expense $expense, CategoryRepository $categoryRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $expense->getUser() !== $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $expense->setLabel($data['label'] ?? $expense->getLabel());
        $expense->setAmount((float) ($data['amount'] ?? $expense->getAmount()));
        $expense->setDate(!empty($data['date']) ? new \DateTimeImmutable($data['date']) : $expense->getDate());

        if (!empty($data['category'])) {
            $category = $categoryRepository->findOneBy(['name' => $data['category']]);
            if ($category) {
                $expense->setCategory($category);
            }
        }

        $em->flush();

        return new JsonResponse(['message' => 'Expense updated successfully'], 200);
    }

}
