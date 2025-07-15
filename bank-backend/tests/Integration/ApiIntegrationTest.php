<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiIntegrationTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);
    }

    public function testHealthEndpoint(): void
    {
        $this->client->request('GET', '/api/health');
        
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function testCreateExpenseCompleteFlow(): void
    {
        // Step 1: Create test user and category
        $user = new User();
        $user->setEmail('integration-test@example.com');
        $user->setFirstName('Integration');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Test Category');
        $category->setDescription('Test category description');
        $category->setColor('#FF0000');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Step 2: Authenticate user (simulate login)
        $this->client->loginUser($user);

        // Step 3: Create expense via API
        $expenseData = [
            'amount' => 42.50,
            'description' => 'Integration test expense',
            'category' => $category->getId(),
            'date' => '2024-01-15'
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );

        // Step 4: Verify response
        $this->assertResponseStatusCodeSame(201);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals(42.50, $responseData['amount']);
        $this->assertEquals('Integration test expense', $responseData['description']);

        // Step 5: Verify expense was saved in database
        $expense = $this->entityManager->getRepository(Expense::class)->find($responseData['id']);
        $this->assertNotNull($expense);
        $this->assertEquals(42.50, $expense->getAmount());
        $this->assertEquals($user->getId(), $expense->getUser()->getId());
        $this->assertEquals($category->getId(), $expense->getCategory()->getId());
    }

    public function testExpenseListApiWithPagination(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('pagination-test@example.com');
        $user->setFirstName('Pagination');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Pagination Category');
        $category->setDescription('Test category for pagination');
        $category->setColor('#00FF00');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);

        // Create multiple expenses
        for ($i = 1; $i <= 15; $i++) {
            $expense = new Expense();
            $expense->setAmount($i * 10);
            $expense->setDescription("Test expense $i");
            $expense->setUser($user);
            $expense->setCategory($category);
            $expense->setCreatedAt(new \DateTime());
            
            $this->entityManager->persist($expense);
        }

        $this->entityManager->flush();

        // Login user
        $this->client->loginUser($user);

        // Test first page
        $this->client->request('GET', '/api/expenses?page=1&limit=10');
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(10, $data['data']);
        $this->assertEquals(15, $data['total']);
        $this->assertEquals(2, $data['pages']);

        // Test second page
        $this->client->request('GET', '/api/expenses?page=2&limit=10');
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $data['data']);
    }

    public function testExpenseUpdateFlow(): void
    {
        // Create test data
        $user = new User();
        $user->setEmail('update-test@example.com');
        $user->setFirstName('Update');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Original Category');
        $category->setDescription('Original category');
        $category->setColor('#0000FF');

        $newCategory = new Category();
        $newCategory->setName('Updated Category');
        $newCategory->setDescription('Updated category');
        $newCategory->setColor('#FFFF00');

        $expense = new Expense();
        $expense->setAmount(100.00);
        $expense->setDescription('Original expense');
        $expense->setUser($user);
        $expense->setCategory($category);
        $expense->setCreatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->persist($newCategory);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Login user
        $this->client->loginUser($user);

        // Update expense via API
        $updateData = [
            'amount' => 150.75,
            'description' => 'Updated expense description',
            'category' => $newCategory->getId()
        ];

        $this->client->request(
            'PUT',
            '/api/expenses/' . $expense->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful();

        // Verify changes in database
        $this->entityManager->refresh($expense);
        $this->assertEquals(150.75, $expense->getAmount());
        $this->assertEquals('Updated expense description', $expense->getDescription());
        $this->assertEquals($newCategory->getId(), $expense->getCategory()->getId());
    }

    public function testExpenseDeleteFlow(): void
    {
        // Create test data
        $user = new User();
        $user->setEmail('delete-test@example.com');
        $user->setFirstName('Delete');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Delete Category');
        $category->setDescription('Category for delete test');
        $category->setColor('#FF00FF');

        $expense = new Expense();
        $expense->setAmount(75.25);
        $expense->setDescription('Expense to be deleted');
        $expense->setUser($user);
        $expense->setCategory($category);
        $expense->setCreatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        $expenseId = $expense->getId();

        // Login user
        $this->client->loginUser($user);

        // Delete expense via API
        $this->client->request('DELETE', '/api/expenses/' . $expenseId);
        $this->assertResponseStatusCodeSame(204);

        // Verify expense is deleted from database
        $deletedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        $this->assertNull($deletedExpense);
    }

    public function testCategoryManagementFlow(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('category-test@example.com');
        $user->setFirstName('Category');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login user
        $this->client->loginUser($user);

        // Create category via API
        $categoryData = [
            'name' => 'API Created Category',
            'description' => 'Category created via API',
            'color' => '#123456'
        ];

        $this->client->request(
            'POST',
            '/api/categories',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($categoryData)
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $categoryId = $responseData['id'];

        // Get categories list
        $this->client->request('GET', '/api/categories');
        $this->assertResponseIsSuccessful();
        
        $categoriesData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThan(0, count($categoriesData));

        // Find our category in the list
        $foundCategory = array_filter($categoriesData, fn($cat) => $cat['id'] === $categoryId);
        $this->assertCount(1, $foundCategory);

        // Update category
        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated description'
        ];

        $this->client->request(
            'PUT',
            '/api/categories/' . $categoryId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful();

        // Verify update in database
        $updatedCategory = $this->entityManager->getRepository(Category::class)->find($categoryId);
        $this->assertEquals('Updated Category Name', $updatedCategory->getName());
        $this->assertEquals('Updated description', $updatedCategory->getDescription());
    }

    public function testInvalidDataHandling(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('validation-test@example.com');
        $user->setFirstName('Validation');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // Test invalid expense data
        $invalidExpenseData = [
            'amount' => -50.00,  // Negative amount
            'description' => '',  // Empty description
            'category' => 99999   // Non-existent category
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidExpenseData)
        );

        $this->assertResponseStatusCodeSame(400);
        
        $errorData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $errorData);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
