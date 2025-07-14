<?php

namespace App\Tests\Entity;

use App\Entity\Expense;
use App\Entity\Category;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ExpenseTest extends TestCase
{
    public function testExpenseCreation(): void
    {
        $expense = new Expense();
        $expense->setLabel('Test Expense');
        $expense->setAmount(100.50);
        
        $date = new \DateTimeImmutable('2025-01-01');
        $expense->setDate($date);

        $this->assertSame('Test Expense', $expense->getLabel());
        $this->assertSame(100.50, $expense->getAmount());
        $this->assertSame($date, $expense->getDate());
        $this->assertNull($expense->getId()); // ID is null before persistence
    }

    public function testExpenseCategoryRelation(): void
    {
        $expense = new Expense();
        $category = new Category();
        $category->setName('Food');

        $expense->setCategory($category);
        
        $this->assertSame($category, $expense->getCategory());
        $this->assertSame('Food', $expense->getCategory()->getName());
    }

    public function testExpenseUserRelation(): void
    {
        $expense = new Expense();
        $user = new User();
        $user->setEmail('test@example.com');

        $expense->setUser($user);
        
        $this->assertSame($user, $expense->getUser());
        $this->assertSame('test@example.com', $expense->getUser()->getEmail());
    }

    public function testExpenseAmountTypes(): void
    {
        $expense = new Expense();
        
        // Test integer
        $expense->setAmount(100);
        $this->assertSame(100.0, $expense->getAmount());
        
        // Test float
        $expense->setAmount(99.99);
        $this->assertSame(99.99, $expense->getAmount());
        
        // Test zero
        $expense->setAmount(0);
        $this->assertSame(0.0, $expense->getAmount());
    }

    public function testExpenseFullConfiguration(): void
    {
        $expense = new Expense();
        $category = new Category();
        $user = new User();
        $date = new \DateTimeImmutable('2025-01-15');

        $category->setName('Transportation');
        $user->setEmail('user@test.com');
        
        $expense->setLabel('Bus Ticket');
        $expense->setAmount(2.50);
        $expense->setDate($date);
        $expense->setCategory($category);
        $expense->setUser($user);

        $this->assertSame('Bus Ticket', $expense->getLabel());
        $this->assertSame(2.50, $expense->getAmount());
        $this->assertSame($date, $expense->getDate());
        $this->assertSame($category, $expense->getCategory());
        $this->assertSame($user, $expense->getUser());
        $this->assertSame('Transportation', $expense->getCategory()->getName());
        $this->assertSame('user@test.com', $expense->getUser()->getEmail());
    }
}
