<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\IntakeForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_intake_form_belongs_to_conversation()
    {
        $intakeForm = IntakeForm::factory()->create();
        
        $this->assertInstanceOf(Conversation::class, $intakeForm->conversation);
    }

    public function test_intake_form_has_required_fields()
    {
        $intakeForm = IntakeForm::factory()->create([
            'description' => 'A beautiful rose',
            'placement' => 'Upper Arm',
            'size' => 'Medium (4-6 inches)',
            'budget_range' => '$301-500',
        ]);

        $this->assertNotNull($intakeForm->description);
        $this->assertNotNull($intakeForm->placement);
        $this->assertNotNull($intakeForm->size);
        $this->assertNotNull($intakeForm->budget_range);
    }

    public function test_intake_form_can_have_optional_fields()
    {
        $intakeForm = IntakeForm::factory()->create([
            'reference_images' => ['test1.jpg', 'test2.jpg'],
        ]);

        $this->assertIsArray($intakeForm->reference_images);
    }

    public function test_intake_form_reference_images_is_cast_to_array()
    {
        $intakeForm = IntakeForm::factory()->create([
            'reference_images' => ['test1.jpg', 'test2.jpg'],
        ]);

        $this->assertIsArray($intakeForm->reference_images);
        $this->assertCount(2, $intakeForm->reference_images);
    }
}
