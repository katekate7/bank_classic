<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Expense;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashedpassword');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
        $this->assertSame('hashedpassword', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserExpenseRelation(): void
    {
        $user = new User();
        $expense = new Expense();

        $this->assertCount(0, $user->getExpenses());

        $user->addExpense($expense);
        $this->assertCount(1, $user->getExpenses());
        $this->assertSame($user, $expense->getUser());

        $user->removeExpense($expense);
        $this->assertCount(0, $user->getExpenses());
        $this->assertNull($expense->getUser());
    }

    public function testRoleManagement(): void
    {
        $user = new User();
        
        // Default role should be ROLE_USER
        $this->assertContains('ROLE_USER', $user->getRoles());

        $user->setRoles(['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testUserSalt(): void
    {
        $user = new User();
        $this->assertNull($user->getSalt());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        // Should not throw exception
        $user->eraseCredentials();
        $this->assertTrue(true);
    }
}
