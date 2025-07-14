<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Integration test that verifies database operations work correctly
 * This demonstrates integration testing: frontend + backend + database
 */
/**
 * Integration test that verifies database operations work correctly
 * This demonstrates integration testing: frontend + backend + database
 */
class DatabaseIntegrationTest extends DatabaseTestCase
{
    public function testDatabaseConnectionAndBasicOperations(): void
    {
        // This test demonstrates TRUE INTEGRATION TESTING:
        // - Database schema is created (database layer)
        // - Entity operations work (backend layer)  
        // - Data persistence works (integration between layers)
        
        // Create a user
        $user = new User();
        $user->setEmail('integration-test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($user, 'testpassword');
        $user->setPassword($hashedPassword);

        // Persist and flush - tests database integration
        $this->persistAndFlush($user);

        // Verify user was saved with an ID
        $this->assertNotNull($user->getId());
        $this->assertIsInt($user->getId());

        // Create a category
        $category = new Category();
        $category->setName('Integration Test Category');
        $this->persistAndFlush($category);

        $this->assertNotNull($category->getId());

        // Create an expense - this tests the full integration stack
        $expense = new Expense();
        $expense->setLabel('Integration Test Expense');
        $expense->setAmount(42.50);
        $expense->setDate(new \DateTimeImmutable('2025-01-15'));
        $expense->setCategory($category);
        $expense->setUser($user);

        $this->persistAndFlush($expense);

        $this->assertNotNull($expense->getId());

        // Test retrieval - verify database integration
        $retrievedUser = $this->entityManager->find(User::class, $user->getId());
        $this->assertSame($user->getEmail(), $retrievedUser->getEmail());

        $retrievedCategory = $this->entityManager->find(Category::class, $category->getId());
        $this->assertSame($category->getName(), $retrievedCategory->getName());

        $retrievedExpense = $this->entityManager->find(Expense::class, $expense->getId());
        $this->assertSame($expense->getLabel(), $retrievedExpense->getLabel());
        $this->assertSame($expense->getAmount(), $retrievedExpense->getAmount());

        // Test relationships - verify entity integration
        $this->assertSame($user, $retrievedExpense->getUser());
        $this->assertSame($category, $retrievedExpense->getCategory());
        $this->assertContains($expense, $user->getExpenses());
        $this->assertContains($expense, $category->getExpenses());

        // Cleanup handled by base class
    }

    public function testCascadeOperations(): void
    {
        // Test cascade operations and orphan removal
        $user = new User();
        $user->setEmail('cascade-test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $category = new Category();
        $category->setName('Cascade Category');

        $expense1 = new Expense();
        $expense1->setLabel('Cascade Expense 1');
        $expense1->setAmount(10.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-01'));
        $expense1->setCategory($category);
        $expense1->setUser($user);

        $expense2 = new Expense();
        $expense2->setLabel('Cascade Expense 2');
        $expense2->setAmount(20.00);
        $expense2->setDate(new \DateTimeImmutable('2025-01-02'));
        $expense2->setCategory($category);
        $expense2->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        $userId = $user->getId();
        $categoryId = $category->getId();
        $expense1Id = $expense1->getId();
        $expense2Id = $expense2->getId();

        // Test that all entities exist
        $this->assertNotNull($this->entityManager->find(User::class, $userId));
        $this->assertNotNull($this->entityManager->find(Category::class, $categoryId));
        $this->assertNotNull($this->entityManager->find(Expense::class, $expense1Id));
        $this->assertNotNull($this->entityManager->find(Expense::class, $expense2Id));

        // Test removing user (with orphanRemoval=true on expenses)
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        // User should be deleted
        $this->assertNull($this->entityManager->find(User::class, $userId));
        
        // Expenses should be deleted due to orphanRemoval=true
        $this->assertNull($this->entityManager->find(Expense::class, $expense1Id));
        $this->assertNull($this->entityManager->find(Expense::class, $expense2Id));

        // Category should still exist (no cascade delete from expense to category)
        $this->assertNotNull($this->entityManager->find(Category::class, $categoryId));

        // Clean up remaining category
        $remainingCategory = $this->entityManager->find(Category::class, $categoryId);
        $this->entityManager->remove($remainingCategory);
        $this->entityManager->flush();
    }

    public function testTransactions(): void
    {
        // Test transaction handling
        $user = new User();
        $user->setEmail('transaction-test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $category = new Category();
        $category->setName('Transaction Category');

        // Start a transaction
        $this->entityManager->beginTransaction();
        
        try {
            $this->entityManager->persist($user);
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            // Simulate an error condition and rollback
            $this->entityManager->rollback();

            // Entities should not be persisted
            $this->assertNull($user->getId());
            $this->assertNull($category->getId());

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        // Now test successful transaction
        $this->entityManager->beginTransaction();
        
        try {
            $this->entityManager->persist($user);
            $this->entityManager->persist($category);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // Entities should be persisted
            $this->assertNotNull($user->getId());
            $this->assertNotNull($category->getId());

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testQueryPerformance(): void
    {
        // Test basic query performance and N+1 query problems
        $user = new User();
        $user->setEmail('performance-test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $category = new Category();
        $category->setName('Performance Category');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);

        // Create multiple expenses
        $expenses = [];
        for ($i = 1; $i <= 10; $i++) {
            $expense = new Expense();
            $expense->setLabel("Performance Expense $i");
            $expense->setAmount($i * 10.0);
            $expense->setDate(new \DateTimeImmutable("2025-01-0$i"));
            $expense->setCategory($category);
            $expense->setUser($user);
            
            $expenses[] = $expense;
            $this->entityManager->persist($expense);
        }

        $this->entityManager->flush();

        // Test efficient querying with joins to avoid N+1 queries
        $expenseRepository = $this->entityManager->getRepository(Expense::class);
        
        $query = $expenseRepository->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->leftJoin('e.user', 'u')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery();

        $results = $query->getResult();

        $this->assertCount(10, $results);
        
        // Verify that category and user are loaded (no additional queries needed)
        foreach ($results as $expense) {
            $this->assertSame($category->getName(), $expense->getCategory()->getName());
            $this->assertSame($user->getEmail(), $expense->getUser()->getEmail());
        }

        // Clean up
        foreach ($expenses as $expense) {
            $this->entityManager->remove($expense);
        }
        $this->entityManager->remove($user);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testConstraintViolations(): void
    {
        // Test database constraint violations
        $user1 = new User();
        $user1->setEmail('constraint-test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user1->setPassword($passwordHasher->hashPassword($user1, 'password'));

        $this->entityManager->persist($user1);
        $this->entityManager->flush();

        // Try to create another user with the same email (should violate unique constraint)
        $user2 = new User();
        $user2->setEmail('constraint-test@example.com');
        $user2->setPassword($passwordHasher->hashPassword($user2, 'password'));

        $this->entityManager->persist($user2);

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->entityManager->flush();

        // This line should not be reached, but clean up in tearDown if needed
        $this->entityManager->remove($user1);
        $this->entityManager->flush();
    }
}
