<?php

namespace App\Tests\E2E;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * End-to-End Integration Tests
 * These tests verify the complete flow from frontend to backend to database
 */
class ExpenseManagementE2ETest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        
        // Create a test user for E2E testing
        $this->user = new User();
        $this->user->setEmail('e2e-test@example.com');
        
        $passwordHasher = $this->getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->user, 'e2epassword');
        $this->user->setPassword($hashedPassword);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up all test data
        $expenses = $this->entityManager->getRepository(Expense::class)->findBy(['user' => $this->user]);
        foreach ($expenses as $expense) {
            $this->entityManager->remove($expense);
        }
        
        $this->entityManager->remove($this->user);
        $this->entityManager->flush();
        
        parent::tearDown();
    }

    public function testCompleteExpenseLifecycle(): void
    {
        // This test verifies the complete CRUD lifecycle of an expense
        
        $this->client->loginUser($this->user);
        
        // 1. Create a category for testing
        $category = new Category();
        $category->setName('E2E Test Category');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // 2. Test CREATE - Add new expense via API
        $expenseData = [
            'label' => 'E2E Test Expense',
            'amount' => 99.99,
            'date' => '2025-01-15',
            'category' => 'E2E Test Category'
        ];

        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );
        
        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Expense created successfully', $response['message']);

        // Verify expense was created in database
        $expense = $this->entityManager->getRepository(Expense::class)
            ->findOneBy(['label' => 'E2E Test Expense']);
        
        $this->assertNotNull($expense);
        $this->assertSame('E2E Test Expense', $expense->getLabel());
        $this->assertSame(99.99, $expense->getAmount());
        $this->assertSame($this->user, $expense->getUser());
        $this->assertSame($category, $expense->getCategory());

        $expenseId = $expense->getId();

        // 3. Test READ - Get expense list via API
        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseIsSuccessful();
        $expensesList = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($expensesList);
        $this->assertCount(1, $expensesList);
        $this->assertSame('E2E Test Expense', $expensesList[0]['label']);
        $this->assertSame(99.99, $expensesList[0]['amount']);

        // 4. Test READ SINGLE - Get specific expense via API
        $this->client->request('GET', "/api/expense/$expenseId");
        
        $this->assertResponseIsSuccessful();
        $singleExpense = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertSame('E2E Test Expense', $singleExpense['label']);
        $this->assertSame(99.99, $singleExpense['amount']);
        $this->assertSame('E2E Test Category', $singleExpense['category']['name']);

        // 5. Test UPDATE - Modify expense via API
        $updateData = [
            'label' => 'E2E Updated Expense',
            'amount' => 149.99,
            'date' => '2025-01-20',
            'category' => 'E2E Test Category'
        ];

        $this->client->request('PUT', "/api/expense/$expenseId", [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );
        
        $this->assertResponseIsSuccessful();
        $updateResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Expense updated successfully', $updateResponse['message']);

        // Verify update in database
        $this->entityManager->clear();
        $updatedExpense = $this->entityManager->find(Expense::class, $expenseId);
        
        $this->assertSame('E2E Updated Expense', $updatedExpense->getLabel());
        $this->assertSame(149.99, $updatedExpense->getAmount());

        // 6. Test WEB INTERFACE - Access expense via web interface
        $this->client->request('GET', '/user/expense/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Expense index');
        $this->assertSelectorTextContains('td', 'E2E Updated Expense');
        $this->assertSelectorTextContains('td', '149.99');

        // 7. Test WEB SHOW - View specific expense
        $this->client->request('GET', "/user/expense/$expenseId");
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Expense');
        $this->assertSelectorTextContains('td', 'E2E Updated Expense');

        // 8. Test DELETE - Remove expense via API
        $this->client->request('DELETE', "/api/expense/$expenseId");
        
        $this->assertResponseIsSuccessful();
        $deleteResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Expense deleted', $deleteResponse['message']);

        // Verify deletion in database
        $deletedExpense = $this->entityManager->find(Expense::class, $expenseId);
        $this->assertNull($deletedExpense);

        // 9. Verify empty list after deletion
        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseIsSuccessful();
        $emptyList = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $emptyList);

        // Clean up category
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testMultiUserIsolation(): void
    {
        // Test that users can only see their own expenses
        
        $this->client->loginUser($this->user);
        
        // Create another user
        $otherUser = new User();
        $otherUser->setEmail('other-e2e@example.com');
        $passwordHasher = $this->getContainer()->get(UserPasswordHasherInterface::class);
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        
        $category = new Category();
        $category->setName('Isolation Test Category');
        
        $this->entityManager->persist($otherUser);
        $this->entityManager->persist($category);
        
        // Create expense for first user
        $expense1 = new Expense();
        $expense1->setLabel('User 1 Expense');
        $expense1->setAmount(100.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-01'));
        $expense1->setCategory($category);
        $expense1->setUser($this->user);
        
        // Create expense for second user
        $expense2 = new Expense();
        $expense2->setLabel('User 2 Expense');
        $expense2->setAmount(200.00);
        $expense2->setDate(new \DateTimeImmutable('2025-01-02'));
        $expense2->setCategory($category);
        $expense2->setUser($otherUser);
        
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        // Test that user 1 only sees their own expenses
        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseIsSuccessful();
        $userExpenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $userExpenses);
        $this->assertSame('User 1 Expense', $userExpenses[0]['label']);

        // Test that user 1 cannot access user 2's expense
        $this->client->request('GET', '/api/expense/' . $expense2->getId());
        $this->assertResponseStatusCodeSame(403);

        // Test that user 1 cannot delete user 2's expense
        $this->client->request('DELETE', '/api/expense/' . $expense2->getId());
        $this->assertResponseStatusCodeSame(403);

        // Switch to other user
        $this->client->loginUser($otherUser);
        
        // Test that user 2 only sees their own expenses
        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseIsSuccessful();
        $otherUserExpenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $otherUserExpenses);
        $this->assertSame('User 2 Expense', $otherUserExpenses[0]['label']);

        // Clean up
        $this->entityManager->remove($expense1);
        $this->entityManager->remove($expense2);
        $this->entityManager->remove($otherUser);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testErrorHandlingFlow(): void
    {
        // Test various error scenarios in the complete flow
        
        $this->client->loginUser($this->user);

        // Test 1: Create expense with invalid data
        $invalidData = [
            'label' => '', // Empty label
            'amount' => -10, // Negative amount
            'date' => 'invalid-date',
            'category' => 'NonExistentCategory'
        ];

        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );
        
        $this->assertResponseStatusCodeSame(404); // Category not found

        // Test 2: Access non-existent expense
        $this->client->request('GET', '/api/expense/999999');
        $this->assertResponseStatusCodeSame(404);

        // Test 3: Update non-existent expense
        $this->client->request('PUT', '/api/expense/999999', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['label' => 'Updated'])
        );
        $this->assertResponseStatusCodeSame(404);

        // Test 4: Delete non-existent expense
        $this->client->request('DELETE', '/api/expense/999999');
        $this->assertResponseStatusCodeSame(404);

        // Test 5: Invalid JSON format
        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testConcurrentOperations(): void
    {
        // Test handling of concurrent operations
        
        $this->client->loginUser($this->user);
        
        $category = new Category();
        $category->setName('Concurrent Test Category');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Create multiple expenses rapidly
        $expenseIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $expenseData = [
                'label' => "Concurrent Expense $i",
                'amount' => $i * 10.0,
                'date' => '2025-01-15',
                'category' => 'Concurrent Test Category'
            ];

            $this->client->request('POST', '/api/expense', [], [], 
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($expenseData)
            );
            
            $this->assertResponseStatusCodeSame(201);
        }

        // Verify all expenses were created
        $this->client->request('GET', '/api/expenses');
        $this->assertResponseIsSuccessful();
        
        $expenses = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $expenses);

        // Test concurrent updates and deletes
        foreach ($expenses as $expense) {
            // Update each expense
            $updateData = [
                'label' => $expense['label'] . ' Updated',
                'amount' => $expense['amount'] + 5
            ];

            $this->client->request('PUT', '/api/expense/' . $expense['id'], [], [], 
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($updateData)
            );
            
            $this->assertResponseIsSuccessful();
        }

        // Verify updates
        $this->client->request('GET', '/api/expenses');
        $updatedExpenses = json_decode($this->client->getResponse()->getContent(), true);
        
        foreach ($updatedExpenses as $expense) {
            $this->assertStringContains('Updated', $expense['label']);
        }

        // Delete all expenses
        foreach ($updatedExpenses as $expense) {
            $this->client->request('DELETE', '/api/expense/' . $expense['id']);
            $this->assertResponseIsSuccessful();
        }

        // Verify all deleted
        $this->client->request('GET', '/api/expenses');
        $finalList = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $finalList);

        // Clean up category
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testDataConsistencyAcrossInterfaces(): void
    {
        // Test that data is consistent between API and web interfaces
        
        $this->client->loginUser($this->user);
        
        $category = new Category();
        $category->setName('Consistency Test Category');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Create expense via API
        $expenseData = [
            'label' => 'Consistency Test Expense',
            'amount' => 75.25,
            'date' => '2025-01-15',
            'category' => 'Consistency Test Category'
        ];

        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );
        
        $this->assertResponseStatusCodeSame(201);

        // Verify via API
        $this->client->request('GET', '/api/expenses');
        $apiExpenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $apiExpenses);
        $this->assertSame('Consistency Test Expense', $apiExpenses[0]['label']);
        $this->assertSame(75.25, $apiExpenses[0]['amount']);

        // Verify via web interface
        $this->client->request('GET', '/user/expense/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('td', 'Consistency Test Expense');
        $this->assertSelectorTextContains('td', '75.25');

        // Update via web interface and verify via API
        $expenseId = $apiExpenses[0]['id'];
        
        $crawler = $this->client->request('GET', "/user/expense/$expenseId/edit");
        $form = $crawler->selectButton('Update')->form();
        
        $form['expense[label]'] = 'Web Updated Expense';
        $form['expense[amout]'] = '85.75'; // Note: typo in field name matches your code

        $this->client->submit($form);
        $this->assertResponseRedirects('/user/expense/');

        // Verify update via API
        $this->client->request('GET', '/api/expenses');
        $updatedApiExpenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertSame('Web Updated Expense', $updatedApiExpenses[0]['label']);
        $this->assertSame(85.75, $updatedApiExpenses[0]['amount']);

        // Clean up
        $expense = $this->entityManager->find(Expense::class, $expenseId);
        $this->entityManager->remove($expense);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
