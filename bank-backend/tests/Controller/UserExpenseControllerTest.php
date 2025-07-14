<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserExpenseControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;
    private static bool $schemaCreated = false;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Get entity manager from the already booted kernel
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        
        // Create test database schema only once per test run
        if (!self::$schemaCreated) {
            $this->createSchema();
            self::$schemaCreated = true;
        }
        
        // Create a test user with proper setup
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->user, 'testpassword');
        $this->user->setPassword($hashedPassword);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
        
        // Don't clear here - keep entities attached
    }

    protected function tearDown(): void
    {
        // Clean up - Remove test data with proper cleanup
        if ($this->entityManager) {
            try {
                // Use DQL to avoid detached entity issues
                $this->entityManager->createQuery('DELETE FROM App\Entity\Expense')->execute();
                $this->entityManager->createQuery('DELETE FROM App\Entity\Category')->execute(); 
                $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
            } catch (\Exception $e) {
                // Ignore cleanup errors in tests
            }
        }
        
        $this->user = null;
        $this->entityManager = null;
        parent::tearDown();
    }

    /**
     * Creates the database schema for integration testing
     * This is essential for true integration tests that verify 
     * frontend, backend, and database working together
     */
    private function createSchema(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        try {
            // Drop and recreate schema
            $schemaTool->dropSchema($metadata);
        } catch (\Exception $e) {
            // Database might not exist, ignore
        }
        
        $schemaTool->createSchema($metadata);
    }

    /**
     * Helper method to ensure we have a valid user reference
     */
    private function getValidUser(): User
    {
        if ($this->user && $this->entityManager->contains($this->user)) {
            return $this->user;
        }
        
        // Refresh user from database
        if ($this->user && $this->user->getId()) {
            $this->user = $this->entityManager->find(User::class, $this->user->getId());
        }
        
        return $this->user;
    }

    public function testExpenseIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/user/expense/');
        // Expect either redirect to login or 401 unauthorized
        $this->assertTrue(
            $this->client->getResponse()->isRedirection() || 
            $this->client->getResponse()->getStatusCode() === 401
        );
    }

    public function testExpenseIndexWithAuthenticatedUser(): void
    {
        $this->client->loginUser($this->getValidUser());
        $this->client->request('GET', '/user/expense/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Expense index');
    }

    public function testExpenseNewPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/user/expense/new');
        // Expect either redirect to login or 401 unauthorized
        $this->assertTrue(
            $this->client->getResponse()->isRedirection() || 
            $this->client->getResponse()->getStatusCode() === 401
        );
    }

    public function testExpenseNewPageWithAuthenticatedUser(): void
    {
        $this->client->loginUser($this->getValidUser());
        $this->client->request('GET', '/user/expense/new');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Create new Expense');
    }

    public function testCreateExpense(): void
    {
        $this->client->loginUser($this->getValidUser());
        
        // Create a category first
        $category = new Category();
        $category->setName('Test Category');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/user/expense/new');
        $form = $crawler->selectButton('Save')->form();
        
        $form['expense[label]'] = 'Test Expense';
        $form['expense[amout]'] = '25.50'; // Note: typo in field name matches your code
        $form['expense[date]'] = '2025-01-15';
        $form['expense[category]'] = $category->getId();

        $this->client->submit($form);
        
        $this->assertResponseRedirects('/user/expense/');
        
        // Verify expense was created
        $expense = $this->entityManager->getRepository(Expense::class)
            ->findOneBy(['label' => 'Test Expense']);
        
        $this->assertNotNull($expense);
        $this->assertSame('Test Expense', $expense->getLabel());
        $this->assertSame(25.50, $expense->getAmount());
        $this->assertSame($this->user, $expense->getUser());
        $this->assertSame($category, $expense->getCategory());
        
        // Clean up
        $this->entityManager->remove($expense);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testShowExpense(): void
    {
        $this->client->loginUser($this->getValidUser());
        
        // Create a test expense
        $category = new Category();
        $category->setName('Test Category');
        $this->entityManager->persist($category);
        
        $expense = new Expense();
        $expense->setLabel('Show Test Expense');
        $expense->setAmount(15.00);
        $expense->setDate(new \DateTimeImmutable('2025-01-20'));
        $expense->setCategory($category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        $this->client->request('GET', '/user/expense/' . $expense->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Expense');
        $this->assertSelectorTextContains('td', 'Show Test Expense');
        $this->assertSelectorTextContains('td', '15');
        
        // Clean up
        $this->entityManager->remove($expense);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testEditExpense(): void
    {
        $this->client->loginUser($this->getValidUser());
        
        // Create a test expense
        $category = new Category();
        $category->setName('Test Category');
        $this->entityManager->persist($category);
        
        $expense = new Expense();
        $expense->setLabel('Original Label');
        $expense->setAmount(10.00);
        $expense->setDate(new \DateTimeImmutable('2025-01-25'));
        $expense->setCategory($category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
        $expenseId = $expense->getId();

        $crawler = $this->client->request('GET', '/user/expense/' . $expenseId . '/edit');
        $form = $crawler->selectButton('Update')->form();
        
        $form['expense[label]'] = 'Updated Label';
        $form['expense[amount]'] = '20.00';

        $this->client->submit($form);
        
        $this->assertResponseRedirects('/user/expense/');
        
        // Verify expense was updated
        $this->entityManager->clear();
        $updatedExpense = $this->entityManager->find(Expense::class, $expenseId);
        
        $this->assertSame('Updated Label', $updatedExpense->getLabel());
        $this->assertSame(20.00, $updatedExpense->getAmount());
        
        // Clean up
        $this->entityManager->remove($updatedExpense);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testDeleteExpense(): void
    {
        $this->client->loginUser($this->getValidUser());
        
        // Create a test expense
        $category = new Category();
        $category->setName('Test Category');
        $this->entityManager->persist($category);
        
        $expense = new Expense();
        $expense->setLabel('To Delete');
        $expense->setAmount(5.00);
        $expense->setDate(new \DateTimeImmutable('2025-01-30'));
        $expense->setCategory($category);
        $expense->setUser($this->user);
        
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
        $expenseId = $expense->getId();

        $crawler = $this->client->request('GET', '/user/expense/' . $expenseId . '/edit');
        $form = $crawler->selectButton('Delete')->form();

        $this->client->submit($form);
        
        $this->assertResponseRedirects('/user/expense/');
        
        // Verify expense was deleted
        $deletedExpense = $this->entityManager->find(Expense::class, $expenseId);
        $this->assertNull($deletedExpense);
        
        // Clean up category
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function testUserCanOnlyAccessOwnExpenses(): void
    {
        $this->client->loginUser($this->getValidUser());
        
        // Create another user and expense
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');
        $passwordHasher = $this->getContainer()->get(UserPasswordHasherInterface::class);
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'password'));
        
        $category = new Category();
        $category->setName('Test Category');
        $this->entityManager->persist($category);
        
        $otherExpense = new Expense();
        $otherExpense->setLabel('Other User Expense');
        $otherExpense->setAmount(50.00);
        $otherExpense->setDate(new \DateTimeImmutable('2025-02-01'));
        $otherExpense->setCategory($category);
        $otherExpense->setUser($otherUser);
        
        $this->entityManager->persist($otherUser);
        $this->entityManager->persist($otherExpense);
        $this->entityManager->flush();

        // Try to access other user's expense
        $this->client->request('GET', '/user/expense/' . $otherExpense->getId());
        // This might throw 404 or access denied depending on your security setup
        $this->assertTrue($this->client->getResponse()->getStatusCode() >= 400);
        
        // Clean up
        $this->entityManager->remove($otherExpense);
        $this->entityManager->remove($otherUser);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
