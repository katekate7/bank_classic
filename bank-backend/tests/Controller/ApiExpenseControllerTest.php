<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiExpenseControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Create test database schema
        $this->createSchema();
        
        // Create a test user
        $this->user = new User();
        $this->user->setEmail('api-test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->user, 'testpassword');
        $this->user->setPassword($hashedPassword);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up
        if ($this->entityManager && $this->user) {
            $expenses = $this->entityManager->getRepository(Expense::class)->findBy(['user' => $this->user]);
            foreach ($expenses as $expense) {
                $this->entityManager->remove($expense);
            }
            
            $this->entityManager->remove($this->user);
            $this->entityManager->flush();
        }
        
        parent::tearDown();
    }

    private function createSchema(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testCreateExpenseApiRequiresAuthentication(): void
    {
        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['label' => 'Test', 'amount' => 10])
        );
        
        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Integration test: verify the actual authentication error message from your app
        $this->assertSame('Authentication Required', $response['error']);
    }

    public function testCreateExpenseApiSuccess(): void
    {
        $this->client->loginUser($this->user);
        
        // Create a category first
        $category = new Category();
        $category->setName('API Test Category');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $expenseData = [
            'label' => 'API Test Expense',
            'amount' => 25.75,
            'date' => '2025-01-15',
            'category' => 'API Test Category'
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
            ->findOneBy(['label' => 'API Test Expense']);
        
        $this->assertNotNull($expense);
        $this->assertSame('API Test Expense', $expense->getLabel());
        $this->assertSame(25.75, $expense->getAmount());
        $this->assertSame($this->user, $expense->getUser());
        $this->assertSame($category, $expense->getCategory());
        
        // Clean up
        $this->entityManager->remove($expense);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testCreateExpenseWithInvalidCategory(): void
    {
        $this->client->loginUser($this->user);

        $expenseData = [
            'label' => 'Invalid Category Expense',
            'amount' => 15.00,
            'date' => '2025-01-15',
            'category' => 'NonExistentCategory'
        ];

        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );
        
        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Category not found', $response['error']);
    }

    public function testCreateExpenseWithMissingCategory(): void
    {
        $this->client->loginUser($this->user);

        $expenseData = [
            'label' => 'No Category Expense',
            'amount' => 15.00,
            'date' => '2025-01-15'
        ];

        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );
        
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Category is required', $response['error']);
    }

    public function testCreateExpenseWithInvalidJson(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request('POST', '/api/expense', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );
        
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Invalid JSON', $response['error']);
    }

    public function testGetExpenseListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Integration test: verify the actual authentication error message from your app
        $this->assertSame('Authentication Required', $response['error']);
    }

    public function testGetExpenseListSuccess(): void
    {
        $this->client->loginUser($this->user);
        
        // Create test expenses
        $category = new Category();
        $category->setName('List Test Category');
        $this->entityManager->persist($category);
        
        $expense1 = new Expense();
        $expense1->setLabel('Expense 1');
        $expense1->setAmount(10.00);
        $expense1->setDate(new \DateTimeImmutable('2025-01-10'));
        $expense1->setCategory($category);
        $expense1->setUser($this->user);
        
        $expense2 = new Expense();
        $expense2->setLabel('Expense 2');
        $expense2->setAmount(20.00);
        $expense2->setDate(new \DateTimeImmutable('2025-01-20'));
        $expense2->setCategory($category);
        $expense2->setUser($this->user);
        
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($response);
        $this->assertCount(2, $response);
        
        // Check first expense - Integration test: verify actual API data types
        $this->assertSame('Expense 1', $response[0]['label']);
        $this->assertEquals(10, $response[0]['amount']); // Use assertEquals for flexible type comparison
        
        // Check second expense
        $this->assertSame('Expense 2', $response[1]['label']);
        $this->assertEquals(20, $response[1]['amount']); // Use assertEquals for flexible type comparison
        
        // Clean up
        $this->entityManager->remove($expense1);
        $this->entityManager->remove($expense2);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testGetSingleExpenseSuccess(): void
    {
        $this->client->loginUser($this->user);
        
        // Create test expense
        $category = new Category();
        $category->setName('Single Test Category');
        $this->entityManager->persist($category);
        
        $expense = new Expense();
        $expense->setLabel('Single Expense');
        $expense->setAmount(30.00);
        $expense->setDate(new \DateTimeImmutable('2025-01-15'));
        $expense->setCategory($category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/expense/' . $expense->getId());
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertSame('Single Expense', $response['label']);
        $this->assertEquals(30, $response['amount']); // Integration test: flexible type comparison
        $this->assertSame('Single Test Category', $response['category']['name']);
        
        // Clean up
        $this->entityManager->remove($expense);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testGetExpenseUnauthorizedUser(): void
    {
        // Create another user and expense
        $otherUser = new User();
        $otherUser->setEmail('other-api@example.com');
        $passwordHasher = $this->getContainer()->get(UserPasswordHasherInterface::class);
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        
        $category = new Category();
        $category->setName('Other Category');
        $this->entityManager->persist($category);
        
        $otherExpense = new Expense();
        $otherExpense->setLabel('Other Expense');
        $otherExpense->setAmount(40.00);
        $otherExpense->setDate(new \DateTimeImmutable('2025-01-25'));
        $otherExpense->setCategory($category);
        $otherExpense->setUser($otherUser);
        
        $this->entityManager->persist($otherUser);
        $this->entityManager->persist($otherExpense);
        $this->entityManager->flush();

        $this->client->loginUser($this->user);
        $this->client->request('GET', '/api/expense/' . $otherExpense->getId());
        
        $this->assertResponseStatusCodeSame(403);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Unauthorized', $response['error']);
        
        // Clean up
        $this->entityManager->remove($otherExpense);
        $this->entityManager->remove($otherUser);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testDeleteExpenseSuccess(): void
    {
        $this->client->loginUser($this->user);
        
        // Create test expense
        $category = new Category();
        $category->setName('Delete Test Category');
        $this->entityManager->persist($category);
        
        $expense = new Expense();
        $expense->setLabel('To Delete via API');
        $expense->setAmount(15.00);
        $expense->setDate(new \DateTimeImmutable('2025-01-30'));
        $expense->setCategory($category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
        $expenseId = $expense->getId();

        $this->client->request('DELETE', '/api/expense/' . $expenseId);
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Expense deleted', $response['message']);
        
        // Verify expense was deleted
        $deletedExpense = $this->entityManager->find(Expense::class, $expenseId);
        $this->assertNull($deletedExpense);
        
        // Clean up category
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testUpdateExpenseSuccess(): void
    {
        $this->client->loginUser($this->user);
        
        // Create test expense and categories
        $category1 = new Category();
        $category1->setName('Original Category');
        $this->entityManager->persist($category1);
        
        $category2 = new Category();
        $category2->setName('Updated Category');
        $this->entityManager->persist($category2);
        
        $expense = new Expense();
        $expense->setLabel('Original Label');
        $expense->setAmount(25.00);
        $expense->setDate(new \DateTimeImmutable('2025-01-10'));
        $expense->setCategory($category1);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
        $expenseId = $expense->getId();

        $updateData = [
            'label' => 'Updated Label',
            'amount' => 35.50,
            'date' => '2025-01-20',
            'category' => 'Updated Category'
        ];

        $this->client->request('PUT', '/api/expense/' . $expenseId, [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Expense updated successfully', $response['message']);
        
        // Verify expense was updated
        $this->entityManager->clear();
        $updatedExpense = $this->entityManager->find(Expense::class, $expenseId);
        
        $this->assertSame('Updated Label', $updatedExpense->getLabel());
        $this->assertSame(35.5, $updatedExpense->getAmount());
        $this->assertSame('Updated Category', $updatedExpense->getCategory()->getName());
        
        // Clean up - refresh entities to avoid detached entity errors in integration tests
        $this->entityManager->remove($updatedExpense);
        
        // Find and remove categories properly
        $category1Fresh = $this->entityManager->find(Category::class, $category1->getId());
        $category2Fresh = $this->entityManager->find(Category::class, $category2->getId());
        
        if ($category1Fresh) {
            $this->entityManager->remove($category1Fresh);
        }
        if ($category2Fresh) {
            $this->entityManager->remove($category2Fresh);
        }
        
        $this->entityManager->flush();
    }
}
