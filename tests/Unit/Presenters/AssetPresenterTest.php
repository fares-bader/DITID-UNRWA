<?php

namespace Tests\Unit\Presenters;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Presenters\AssetPresenter;
use Tests\TestCase;

class AssetPresenterTest extends TestCase
{
    public function test_dynamic_url()
    {
        $this->settings->set(['locale' => 'en-US']);

        $assetModel = AssetModel::factory()->create([
            'model_number' => 'MN-123',
            'name' => 'Macbook',
        ]);

        $asset = Asset::factory()
            ->for($assetModel, 'model')
            ->create(['serial' => 'SN-123']);

        $this->assertEquals(
            'https://example.com/en-US/SN-123/MN-123/Macbook',
            $asset->present()->dynamicUrl('https://example.com/{LOCALE}/{SERIAL}/{MODEL_NUMBER}/{MODEL_NAME}')
        );
    }

    public function test_asset_table_uses_status_icon_and_places_in_out_actions_in_actions_column()
    {
        $this->settings->set(['locale' => 'en-US']);

        $layout = json_decode(AssetPresenter::dataTableLayout(['deleted_at']), true);
        $statusColumn = collect($layout)->firstWhere('field', 'status');
        $actionsColumn = collect($layout)->firstWhere('field', 'actions');
        $loadNoteColumn = collect($layout)->firstWhere('field', 'load_note');
        $fields = array_column($layout, 'field');

        $this->assertSame('assetStatusIconFormatter', $statusColumn['formatter']);
        $this->assertSame('hardwareActionsFormatter', $actionsColumn['formatter']);
        $this->assertStringContainsString('asset-actions-column', $actionsColumn['class']);
        $this->assertSame('LN', $loadNoteColumn['title']);
        $this->assertStringContainsString('asset-load-note-column', $loadNoteColumn['class']);
        $this->assertSame(array_search('load_note', $fields) + 1, array_search('actions', $fields));
        $this->assertNotContains('checkincheckout', $fields);
    }
}
