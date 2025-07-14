<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ExpenseRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ExpenseRepository $repository;
    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->repository = $this->entityManager->getRepository(Expense::class);
        
        // Create test user
        $this->user = new User();
        $this->user->setEmail('repo-test@example.com');
        $passwordHasher = $kernel->getContainer()->get('security.user_password_hasher');
        $this->user->setPassword($passwordHasher->hashPassword($this->user, 'password'));
        
        // Create test category
        $this->category = new Category();
        $this->category->setName('Test Category');
        
        $this->entityManager->persist($this->user);
        $this->entityManager->persist($this->category);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up all test expenses
        $expenses = $this->repository->findBy(['user' => $this->user]);
        foreach ($expenses as $expense) {
            $this->entityManager->remove($expense);
        }
        
        $this->entityManager->remove($this->user);
        $this->entityManager->remove($this->category);
        $this->entityManager->flush();
        
        parent::tearDown();
    }

    public function testFindByUser(): void
    {
        // Create test expenses for the user
        $expense1 = new Expense();
        $expense1->setLabel('User Expense 1');
        $expense1->setAmount(10.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-01'));
        $expense1->setCategory($this->category);
        $expense1->setUser($this->user);
        
        $expense2 = new Expense();
        $expense2->setLabel('User Expense 2');
        $expense2->setAmount(20.00);
        $expense2->setDate(new \DateTimeImmutable('2025-01-02'));
        $expense2->setCategory($this->category);
        $expense2->setUser($this->user);
        
        // Create expense for another user
        $otherUser = new User();
        $otherUser->setEmail('other-repo@example.com');
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        
        $otherExpense = new Expense();
        $otherExpense->setLabel('Other User Expense');
        $otherExpense->setAmount(30.00);
        $otherExpense->setDate(new \DateTimeImmutable('2025-01-03'));
        $otherExpense->setCategory($this->category);
        $otherExpense->setUser($otherUser);
        
        $this->entityManager->persist($otherUser);
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->persist($otherExpense);
        $this->entityManager->flush();

        // Test finding expenses by user
        $userExpenses = $this->repository->findBy(['user' => $this->user]);
        
        $this->assertCount(2, $userExpenses);
        $this->assertContains($expense1, $userExpenses);
        $this->assertContains($expense2, $userExpenses);
        $this->assertNotContains($otherExpense, $userExpenses);
        
        // Clean up
        $this->entityManager->remove($otherExpense);
        $this->entityManager->remove($otherUser);
        $this->entityManager->flush();
    }

    public function testFindByCategory(): void
    {
        // Create another category
        $anotherCategory = new Category();
        $anotherCategory->setName('Another Category');
        $this->entityManager->persist($anotherCategory);
        
        // Create expenses with different categories
        $expense1 = new Expense();
        $expense1->setLabel('Category 1 Expense');
        $expense1->setAmount(15.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-05'));
        $expense1->setCategory($this->category);
        $expense1->setUser($this->user);
        
        $expense2 = new Expense();
        $expense2->setLabel('Category 2 Expense');
        $expense2->setAmount(25.00);
        $expense2->setDate(new \DateTimeImmutable('2025-01-06'));
        $expense2->setCategory($anotherCategory);
        $expense2->setUser($this->user);
        
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        // Test finding expenses by category
        $categoryExpenses = $this->repository->findBy(['category' => $this->category]);
        
        $this->assertCount(1, $categoryExpenses);
        $this->assertContains($expense1, $categoryExpenses);
        $this->assertNotContains($expense2, $categoryExpenses);
        
        // Clean up
        $this->entityManager->remove($anotherCategory);
        $this->entityManager->flush();
    }

    public function testFindAll(): void
    {
        // Create test expenses
        $expense1 = new Expense();
        $expense1->setLabel('All Test 1');
        $expense1->setAmount(5.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-10'));
        $expense1->setCategory($this->category);
        $expense1->setUser($this->user);
        
        $expense2 = new Expense();
        $expense2->setLabel('All Test 2');
        $expense2->setAmount(7.50);
        $expense2->setDate(new \DateTimeImmutable('2025-01-11'));
        $expense2->setCategory($this->category);
        $expense2->setUser($this->user);
        
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        $allExpenses = $this->repository->findAll();
        
        // Should include at least our test expenses
        $this->assertGreaterThanOrEqual(2, count($allExpenses));
        $this->assertContains($expense1, $allExpenses);
        $this->assertContains($expense2, $allExpenses);
    }

    public function testFindOneBy(): void
    {
        $expense = new Expense();
        $expense->setLabel('Unique Label Test');
        $expense->setAmount(12.34);
        $expense->setDate(new \DateTimeImmutable('2025-01-15'));
        $expense->setCategory($this->category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Test finding by unique label
        $foundExpense = $this->repository->findOneBy(['label' => 'Unique Label Test']);
        
        $this->assertNotNull($foundExpense);
        $this->assertSame($expense, $foundExpense);
        $this->assertSame('Unique Label Test', $foundExpense->getLabel());
        $this->assertSame(12.34, $foundExpense->getAmount());
        
        // Test finding non-existent expense
        $notFound = $this->repository->findOneBy(['label' => 'Non Existent']);
        $this->assertNull($notFound);
    }

    public function testFind(): void
    {
        $expense = new Expense();
        $expense->setLabel('Find By ID Test');
        $expense->setAmount(99.99);
        $expense->setDate(new \DateTimeImmutable('2025-01-20'));
        $expense->setCategory($this->category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
        $expenseId = $expense->getId();

        // Test finding by ID
        $foundExpense = $this->repository->find($expenseId);
        
        $this->assertNotNull($foundExpense);
        $this->assertSame($expense, $foundExpense);
        $this->assertSame($expenseId, $foundExpense->getId());
        
        // Test finding non-existent ID
        $notFound = $this->repository->find(999999);
        $this->assertNull($notFound);
    }

    public function testCustomQueryMethods(): void
    {
        // If you have custom query methods in your repository, test them here
        // For example, if you add a method to find expenses by date range:
        
        $expense1 = new Expense();
        $expense1->setLabel('January Expense');
        $expense1->setAmount(100.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-15'));
        $expense1->setCategory($this->category);
        $expense1->setUser($this->user);
        
        $expense2 = new Expense();
        $expense2->setLabel('February Expense');
        $expense2->setAmount(200.00);
        $expense2->setDate(new \DateTimeImmutable('2025-02-15'));
        $expense2->setCategory($this->category);
        $expense2->setUser($this->user);
        
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        // Test basic query building capabilities
        $queryBuilder = $this->repository->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.amount > :minAmount')
            ->setParameter('user', $this->user)
            ->setParameter('minAmount', 150.00)
            ->orderBy('e.date', 'ASC');
        
        $results = $queryBuilder->getQuery()->getResult();
        
        $this->assertCount(1, $results);
        $this->assertSame($expense2, $results[0]);
    }
}
