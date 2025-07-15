<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DatabaseIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $kernel->getContainer()
            ->get(UserPasswordHasherInterface::class);
    }

    public function testUserCreationAndPersistence(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        // Act
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Assert
        $this->assertNotNull($user->getId());
        
        // Verify user can be retrieved from database
        $retrievedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertInstanceOf(User::class, $retrievedUser);
        $this->assertEquals('test@example.com', $retrievedUser->getEmail());
    }

    public function testExpenseCreationWithCategoryAndUser(): void
    {
        // Arrange - Create User
        $user = new User();
        $user->setEmail('expense-test@example.com');
        $user->setFirstName('Jane');
        $user->setLastName('Smith');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        // Create Category
        $category = new Category();
        $category->setName('Food');
        $category->setDescription('Food and drinks');
        $category->setColor('#FF5733');

        // Create Expense
        $expense = new Expense();
        $expense->setAmount(25.50);
        $expense->setDescription('Lunch at restaurant');
        $expense->setUser($user);
        $expense->setCategory($category);
        $expense->setCreatedAt(new \DateTime());

        // Act
        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Assert
        $this->assertNotNull($expense->getId());
        $this->assertEquals($user->getId(), $expense->getUser()->getId());
        $this->assertEquals($category->getId(), $expense->getCategory()->getId());
        
        // Test database relationships
        $retrievedExpense = $this->entityManager->getRepository(Expense::class)->find($expense->getId());
        $this->assertEquals('Lunch at restaurant', $retrievedExpense->getDescription());
        $this->assertEquals(25.50, $retrievedExpense->getAmount());
        $this->assertEquals('Food', $retrievedExpense->getCategory()->getName());
    }

    public function testUserCanHaveMultipleExpenses(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('multi-expense@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $category1 = new Category();
        $category1->setName('Transport');
        $category1->setDescription('Transportation costs');
        $category1->setColor('#3498DB');

        $category2 = new Category();
        $category2->setName('Entertainment');
        $category2->setDescription('Fun activities');
        $category2->setColor('#9B59B6');

        $expense1 = new Expense();
        $expense1->setAmount(15.00);
        $expense1->setDescription('Bus ticket');
        $expense1->setUser($user);
        $expense1->setCategory($category1);
        $expense1->setCreatedAt(new \DateTime());

        $expense2 = new Expense();
        $expense2->setAmount(30.00);
        $expense2->setDescription('Movie ticket');
        $expense2->setUser($user);
        $expense2->setCategory($category2);
        $expense2->setCreatedAt(new \DateTime());

        // Act
        $this->entityManager->persist($user);
        $this->entityManager->persist($category1);
        $this->entityManager->persist($category2);
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        // Assert
        $userExpenses = $this->entityManager->getRepository(Expense::class)
            ->findBy(['user' => $user]);
        
        $this->assertCount(2, $userExpenses);
        
        $totalAmount = array_sum(array_map(fn($exp) => $exp->getAmount(), $userExpenses));
        $this->assertEquals(45.00, $totalAmount);
    }

    public function testCategoryCanHaveMultipleExpenses(): void
    {
        // Arrange
        $category = new Category();
        $category->setName('Groceries');
        $category->setDescription('Food shopping');
        $category->setColor('#2ECC71');

        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setFirstName('User');
        $user1->setLastName('One');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setFirstName('User');
        $user2->setLastName('Two');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));

        $expense1 = new Expense();
        $expense1->setAmount(50.00);
        $expense1->setDescription('Weekly groceries');
        $expense1->setUser($user1);
        $expense1->setCategory($category);
        $expense1->setCreatedAt(new \DateTime());

        $expense2 = new Expense();
        $expense2->setAmount(25.00);
        $expense2->setDescription('Fruits and vegetables');
        $expense2->setUser($user2);
        $expense2->setCategory($category);
        $expense2->setCreatedAt(new \DateTime());

        // Act
        $this->entityManager->persist($category);
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($expense1);
        $this->entityManager->persist($expense2);
        $this->entityManager->flush();

        // Assert
        $categoryExpenses = $this->entityManager->getRepository(Expense::class)
            ->findBy(['category' => $category]);
        
        $this->assertCount(2, $categoryExpenses);
        
        $totalCategoryAmount = array_sum(array_map(fn($exp) => $exp->getAmount(), $categoryExpenses));
        $this->assertEquals(75.00, $totalCategoryAmount);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->close();
        $this->entityManager = null;
    }
}