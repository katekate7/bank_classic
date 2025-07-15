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
    private ?EntityManagerInterface $entityManager = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

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
        // Arrange
        $user = new User();
        $user->setEmail('expense-test@example.com');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        $category = new Category();
        $category->setName('Food');

        $expense = new Expense();
        $expense->setLabel('Lunch at restaurant');
        $expense->setAmount(25.50);
        $expense->setDate(new \DateTimeImmutable('2024-01-15'));
        $expense->setCategory($category);
        $expense->setUser($user);

        // Act
        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Assert
        $this->assertNotNull($expense->getId());
        $this->assertEquals('Lunch at restaurant', $expense->getLabel());
        $this->assertEquals(25.50, $expense->getAmount());
        $this->assertEquals('Food', $expense->getCategory()->getName());
        $this->assertEquals('expense-test@example.com', $expense->getUser()->getEmail());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
