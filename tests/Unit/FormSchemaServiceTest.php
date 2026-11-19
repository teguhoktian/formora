<?php

namespace Tests\Unit;

use App\Services\FormSchemaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormSchemaServiceTest extends TestCase
{
    public function test_schema_starts_with_stable_page_and_settings_structure(): void
    {
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();

        $this->assertSame(1, $schema['schema_version']);
        $this->assertNotEmpty($schema['pages'][0]['id']);
        $this->assertSame([], $schema['pages'][0]['fields']);
        $this->assertSame('Kirim', $schema['settings']['submit_label']);
        $this->assertEquals($schema, $schemas->validate($schema));
    }

    public function test_only_supported_field_types_are_accepted(): void
    {
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();

        foreach (FormSchemaService::FIELD_TYPES as $type) {
            $schema['pages'][0]['fields'][] = $schemas->newField($type, $schema);
        }

        $validated = $schemas->validate($schema);

        $this->assertCount(count(FormSchemaService::FIELD_TYPES), $validated['pages'][0]['fields']);
        $this->assertCount(count(FormSchemaService::FIELD_TYPES), array_unique(array_column($validated['pages'][0]['fields'], 'id')));
    }

    public function test_arbitrary_field_type_and_duplicate_names_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        $first = $schemas->newField('text', $schema);
        $second = $schemas->newField('text', $schema);
        $second['name'] = strtoupper($first['name']);
        $second['type'] = 'script';
        $schema['pages'][0]['fields'] = [$first, $second];

        $schemas->validate($schema);
    }

    public function test_choice_fields_require_a_valid_option_list(): void
    {
        $this->expectException(ValidationException::class);

        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        $field = $schemas->newField('select', $schema);
        $field['settings']['options'] = [];
        $schema['pages'][0]['fields'][] = $field;

        $schemas->validate($schema);
    }

    public function test_conditional_visibility_requires_an_unconditional_choice_field_on_the_same_page(): void
    {
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        $source = $schemas->newField('select', $schema);
        $source['settings']['options'] = [['label' => 'Ya', 'value' => 'yes']];
        $target = $schemas->newField('text', $schema);
        $target['visibility'] = ['field_id' => $source['id'], 'operator' => 'equals', 'value' => 'yes'];
        $schema['pages'][0]['fields'] = [$source, $target];

        $this->assertEquals($schema, $schemas->validate($schema));

        $schema['pages'][0]['fields'][1]['visibility']['value'] = 'unknown';
        $this->expectException(ValidationException::class);
        $schemas->validate($schema);
    }

    public function test_malformed_pages_are_rejected_as_validation_errors(): void
    {
        $this->expectException(ValidationException::class);

        $schema = app(FormSchemaService::class)->empty();
        $schema['pages'] = 'invalid';

        app(FormSchemaService::class)->validate($schema);
    }
}
