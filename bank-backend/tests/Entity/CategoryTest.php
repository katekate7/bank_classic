<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Expense;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryCreation(): void
    {
        $category = new Category();
        $category->setName('Food');

        $this->assertSame('Food', $category->getName());
        $this->assertNull($category->getId()); // ID is null before persistence
    }

    public function testCategoryToString(): void
    {
        $category = new Category();
        $category->setName('Transportation');

        $this->assertSame('Transportation', (string) $category);
        $this->assertSame('Transportation', $category->__toString());
    }

    public function testCategoryToStringEmpty(): void
    {
        $category = new Category();
        // Name is null by default
        $this->assertSame('', (string) $category);
    }

    public function testCategoryExpenseRelation(): void
    {
        $category = new Category();
        $expense1 = new Expense();
        $expense2 = new Expense();

        $this->assertCount(0, $category->getExpenses());

        $category->addExpense($expense1);
        $this->assertCount(1, $category->getExpenses());
        $this->assertSame($category, $expense1->getCategory());

        $category->addExpense($expense2);
        $this->assertCount(2, $category->getExpenses());
        $this->assertSame($category, $expense2->getCategory());

        // Adding same expense again should not increase count
        $category->addExpense($expense1);
        $this->assertCount(2, $category->getExpenses());

        $category->removeExpense($expense1);
        $this->assertCount(1, $category->getExpenses());
        $this->assertNull($expense1->getCategory());

        $category->removeExpense($expense2);
        $this->assertCount(0, $category->getExpenses());
        $this->assertNull($expense2->getCategory());
    }

    public function testCategoryWithExpenseData(): void
    {
        $category = new Category();
        $category->setName('Entertainment');

        $expense = new Expense();
        $expense->setLabel('Movie Ticket');
        $expense->setAmount(12.50);
        $expense->setDate(new \DateTimeImmutable('2025-01-10'));

        $category->addExpense($expense);

        $this->assertSame('Entertainment', $category->getName());
        $this->assertCount(1, $category->getExpenses());
        $this->assertSame('Movie Ticket', $category->getExpenses()->first()->getLabel());
        $this->assertSame(12.50, $category->getExpenses()->first()->getAmount());
    }
}
