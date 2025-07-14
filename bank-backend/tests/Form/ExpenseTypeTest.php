<?php

namespace App\Tests\Form;

use App\Entity\Expense;
use App\Entity\Category;
use App\Form\ExpenseType;
use Symfony\Component\Form\Test\TypeTestCase;

class ExpenseTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $category = new Category();
        $category->setName('Test Category');

        $formData = [
            'label' => 'Test Expense',
            'amout' => 25.50, // Note: using 'amout' to match your field name
            'date' => new \DateTimeImmutable('2025-01-15'),
            'category' => $category,
        ];

        $expense = new Expense();
        $form = $this->factory->create(ExpenseType::class, $expense);

        // Submit the data to the form directly
        $form->submit($formData);

        // Check that the form is valid
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        // Check that the data was correctly set on the expense object
        $this->assertSame('Test Expense', $expense->getLabel());
        $this->assertSame(25.5, $expense->getAmount());
        $this->assertEquals(new \DateTimeImmutable('2025-01-15'), $expense->getDate());
        $this->assertSame($category, $expense->getCategory());
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(ExpenseType::class);

        // Check that all expected fields are present
        $this->assertTrue($form->has('label'));
        $this->assertTrue($form->has('amout')); // Note: using 'amout' to match your field name
        $this->assertTrue($form->has('date'));
        $this->assertTrue($form->has('category'));
    }

    public function testFormWithEmptyData(): void
    {
        $formData = [
            'label' => '',
            'amout' => null,
            'date' => null,
            'category' => null,
        ];

        $form = $this->factory->create(ExpenseType::class);
        $form->submit($formData);

        // The form should still be synchronized but might not be valid
        // depending on your validation constraints
        $this->assertTrue($form->isSynchronized());
        
        // You can add more specific validation tests here based on your entity constraints
    }

    public function testFormOptions(): void
    {
        $expense = new Expense();
        $form = $this->factory->create(ExpenseType::class, $expense);

        // Check that the form's data class is correctly set
        $config = $form->getConfig();
        $this->assertSame(Expense::class, $config->getOption('data_class'));
    }

    public function testSubmitValidDataWithStrings(): void
    {
        // Test with string values that would come from a web form
        $formData = [
            'label' => 'String Test Expense',
            'amout' => '35.75',
            'date' => '2025-01-20',
            'category' => null, // This would normally be handled by a choice field
        ];

        $expense = new Expense();
        $form = $this->factory->create(ExpenseType::class, $expense);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('String Test Expense', $expense->getLabel());
        // The form transformer should convert string to float
        $this->assertSame(35.75, $expense->getAmount());
    }
}
