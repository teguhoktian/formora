<?php

namespace Tests\Feature;

use App\Livewire\FormBuilder;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\FormPublishingService;
use App\Services\FormSchemaService;
use App\Services\IndonesiaRegionCatalog;
use App\Services\PublicSubmissionService;
use App\Services\SmartFieldRegistry;
use App\Services\SubmissionResponsePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SmartFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_exposes_all_indonesia_smart_fields_and_builder_adds_them(): void
    {
        $this->assertCount(12, app(SmartFieldRegistry::class)->definitions());
        $this->assertCount(38, app(IndonesiaRegionCatalog::class)->provinces());
        [$owner, $form] = $this->makeForm();

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('addField', 'smart_nik')
            ->assertSet('saveState', 'saved');

        $field = $form->versions()->where('is_published', false)->firstOrFail()->fresh()->schema['pages'][0]['fields'][0];
        $this->assertSame('smart_nik', $field['type']);
        $this->assertSame('NIK', $field['label']);
    }

    public function test_public_form_renders_smart_controls_and_accepts_normalized_answers(): void
    {
        [$form, $version, $validAnswers] = $this->publishedSmartForm();

        $this->get(route('public.forms.show', $form->slug))
            ->assertOk()
            ->assertSee('Nomor rekening bank')
            ->assertSee('Pilih nama bank')
            ->assertSee('PT BANK CENTRAL ASIA Tbk')
            ->assertSee('Pilih provinsi')
            ->assertSee('data-address-region="regency" disabled', false)
            ->assertSee('data-address-region="district" disabled', false)
            ->assertSee('data-address-region="village" disabled', false)
            ->assertSee('data-digits-only', false)
            ->assertSee('Jawa Barat')
            ->assertSee('Kabupaten/Kota')
            ->assertSee('NISN');

        $this->get(route('public.smart-fields.regencies', ['provinceCode' => '32']))
            ->assertOk()->assertJsonFragment(['code' => '32.04', 'name' => 'Kabupaten Bandung']);
        $this->get(route('public.smart-fields.districts', ['regencyCode' => '32.04']))
            ->assertOk()->assertJsonFragment(['code' => '32.04.05', 'name' => 'Cileunyi']);
        $this->get(route('public.smart-fields.villages', ['districtCode' => '32.04.05']))
            ->assertOk()->assertJsonFragment(['code' => '32.04.05.2001', 'name' => 'Cileunyi Kulon']);

        $this->post(route('public.forms.submit', $form->slug), ['answers' => $validAnswers])
            ->assertRedirect(route('public.forms.show', $form->slug));

        $submission = FormSubmission::query()->where('form_id', $form->id)->firstOrFail();
        $this->assertSame($version->id, $submission->form_version_id);
        $this->assertSame('123456789012345', $submission->response_data['npwp']);
        $this->assertSame('12345678', $submission->response_data['rekening']);
        $this->assertSame('+6281234567890', $submission->response_data['telepon']);
        $this->assertSame('32.04', $submission->response_data['alamat']['regency']);
    }

    public function test_smart_identifiers_and_amounts_reject_invalid_formats(): void
    {
        [, $version, $answers] = $this->publishedSmartForm();
        $service = app(PublicSubmissionService::class);
        $schemas = app(FormSchemaService::class);

        foreach ([
            'nik' => '1234',
            'npwp' => 'abc',
            'lahir' => now()->addDay()->toDateString(),
            'rekening' => '12-AB',
            'bank' => 'Bank Contoh',
            'rupiah' => '-1',
            'telepon' => '021123456',
            'nip' => '123',
            'nisn' => '123',
            'nuptk' => '123',
        ] as $name => $value) {
            $payload = ['answers' => $answers];
            $payload['answers'][$name] = $value;
            try {
                $service->validate($version, $payload, $schemas);
                $this->fail("Invalid smart field {$name} should be rejected.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('answers.'.$name, $exception->errors());
                if (in_array($name, ['nik', 'nip'], true)) {
                    $this->assertStringContainsString('digit angka', $exception->errors()['answers.'.$name][0]);
                    $this->assertStringNotContainsString('validation.digits', $exception->errors()['answers.'.$name][0]);
                }
            }
        }

        $crossProvincePayload = ['answers' => $answers];
        $crossProvincePayload['answers']['alamat']['regency'] = '31.71';
        try {
            $service->validate($version, $crossProvincePayload, $schemas);
            $this->fail('Kabupaten/kota harus berasal dari provinsi yang dipilih.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('answers.alamat.regency', $exception->errors());
        }

        $crossDistrictPayload = ['answers' => $answers];
        $crossDistrictPayload['answers']['alamat']['village'] = '32.04.06.1001';
        try {
            $service->validate($version, $crossDistrictPayload, $schemas);
            $this->fail('Desa/kelurahan harus berasal dari kecamatan yang dipilih.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('answers.alamat.village', $exception->errors());
        }
    }

    public function test_public_form_shows_localized_digit_validation_message(): void
    {
        [$form, , $answers] = $this->publishedSmartForm();
        $answers['nik'] = '123ABC';

        $this->followingRedirects()
            ->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), ['answers' => $answers])
            ->assertOk()
            ->assertSee('Isian NIK harus terdiri dari 16 digit angka.')
            ->assertDontSee('validation.digits');
    }

    public function test_presenter_masks_sensitive_smart_fields_and_formats_rupiah(): void
    {
        [$form, $version] = $this->publishedSmartForm();
        $submission = FormSubmission::factory()->create([
            'workspace_id' => $form->workspace_id,
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'response_data' => [
                'nik' => '1234567890123456', 'rekening' => '12345678', 'lahir' => '1990-01-02',
                'telepon' => '+6281234567890', 'rupiah' => 1250000,
                'alamat' => ['province' => '32', 'regency' => '32.04', 'district' => '32.04.05', 'village' => '32.04.05.2001'],
            ],
        ]);

        $answers = app(SubmissionResponsePresenter::class)->present($submission);
        $this->assertSame('••••••••', $answers['NIK']);
        $this->assertSame('••••••••', $answers['Nomor rekening bank']);
        $this->assertSame('••••••••', $answers['Tanggal lahir']);
        $this->assertSame('••••••••', $answers['Nomor telepon Indonesia']);
        $this->assertSame('Rp 1.250.000', $answers['Nominal Rupiah']);
        $this->assertSame('Provinsi: Jawa Barat, Kabupaten/Kota: Kabupaten Bandung, Kecamatan: Cileunyi, Desa/Kelurahan: Cileunyi Kulon', $answers['Alamat bertingkat']);
    }

    /** @return array{User, Form} */
    private function makeForm(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $form->versions()->create([
            'version_number' => 1,
            'schema' => app(FormSchemaService::class)->empty(),
            'is_published' => false,
        ]);

        return [$owner, $form];
    }

    /** @return array{Form, FormVersion, array<string, mixed>} */
    private function publishedSmartForm(): array
    {
        [, $form] = $this->makeForm();
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        $answers = [
            'nik' => '1234567890123456',
            'npwp' => '12.345.678.9-012.345',
            'lahir' => '1990-01-02',
            'rekening' => '1234-5678',
            'bank' => 'PT BANK CENTRAL ASIA Tbk',
            'rupiah' => '1250000',
            'telepon' => '+62 812-3456-7890',
            'alamat' => ['province' => '32', 'regency' => '32.04', 'district' => '32.04.05', 'village' => '32.04.05.2001'],
            'nip' => '198001012005011001',
            'nisn' => '0123456789',
            'nuptk' => '1234567890123456',
            'nim' => 'IF-2024-001',
        ];
        foreach (SmartFieldRegistry::TYPES as $type) {
            $field = $schemas->newField($type, $schema);
            $field['name'] = match ($type) {
                'smart_nik' => 'nik', 'smart_npwp' => 'npwp', 'smart_birth_date' => 'lahir',
                'smart_bank_account' => 'rekening', 'smart_bank_name' => 'bank', 'smart_rupiah' => 'rupiah',
                'smart_id_phone' => 'telepon', 'smart_address' => 'alamat', 'smart_nip' => 'nip',
                'smart_nisn' => 'nisn', 'smart_nuptk' => 'nuptk', 'smart_nim' => 'nim',
            };
            $field['required'] = true;
            $schema['pages'][0]['fields'][] = $field;
        }
        $draft = $form->versions()->firstOrFail();
        $draft->update(['schema' => $schema]);
        $published = app(FormPublishingService::class)->publishDraft($form, $draft->id, $schemas);

        return [$form->fresh(), $published, $answers];
    }
}
