<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Form\ExpenseType;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/expense')]
class UserExpenseController extends AbstractController
{
    #[Route('/', name: 'app_user_expense_index', methods: ['GET'])]
    public function index(ExpenseRepository $expenseRepository): Response
    {
        $user = $this->getUser();
    
        return $this->render('user_expense/index.html.twig', [
            'expenses' => $expenseRepository->findBy(['user' => $user]),
        ]);
    }
    

    #[Route('/new', name: 'app_user_expense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $expense = new Expense();

        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Користувач не залогінений');
        }

        $expense->setUser($user);

        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($expense);
            $em->flush();

            return $this->redirectToRoute('app_user_expense_index');
        }

        return $this->render('user_expense/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    

    #[Route('/{id}', name: 'app_user_expense_show', methods: ['GET'])]
    public function show(Expense $expense): Response
    {
        return $this->render('user_expense/show.html.twig', [
            'expense' => $expense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_expense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user_expense/edit.html.twig', [
            'expense' => $expense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_expense_delete', methods: ['POST'])]
    public function delete(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$expense->getId(), $request->request->get('_token'))) {
            $entityManager->remove($expense);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_expense_index', [], Response::HTTP_SEE_OTHER);
    }
}