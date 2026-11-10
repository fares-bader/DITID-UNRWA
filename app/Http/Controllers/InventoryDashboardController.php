<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CmdbObject;
use App\Models\CmdbObjectType;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Manufacturer;
use App\Models\Statuslabel;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InventoryDashboardController extends Controller
{
    private const TYPES = ['assets', 'cmdb', 'licenses', 'accessories', 'consumables', 'components'];

    private const GROUPS = ['category', 'model', 'supplier', 'manufacturer'];

    public function index(Request $request): View
    {
        $availableTypes = $this->availableTypes();
        abort_if($availableTypes === [], 403);

        $type = in_array($request->query('type'), $availableTypes, true) ? $request->query('type') : $availableTypes[0];
        $groupBy = in_array($request->query('group_by'), self::GROUPS, true) ? $request->query('group_by') : 'model';
        $groupValue = $request->query('group');
        $filters = [
            'category' => $request->string('category')->toString(),
            'model' => $request->string('model')->toString(),
            'supplier' => $request->string('supplier')->toString(),
            'manufacturer' => $request->string('manufacturer')->toString(),
            'status' => $request->string('status')->toString(),
            'availability' => $request->query('availability', 'remaining') === 'all' ? 'all' : 'remaining',
            'group' => is_string($groupValue) && preg_match('/^-?\d+$/', $groupValue) === 1 ? $groupValue : null,
        ];

        $summary = $this->summary($availableTypes);
        $filterOptions = $this->filterOptions($type);
        $groups = $this->groupRows($type, $groupBy, $filters)->withQueryString();
        $selectedGroup = $filters['group'] !== null ? $groups->getCollection()->firstWhere('group_key', $filters['group']) : null;
        $items = $filters['group'] !== null
            ? $this->inventoryRows($type, $groupBy, $filters)->withQueryString()
            : null;

        return view('inventory.dashboard', compact('availableTypes', 'summary', 'type', 'groupBy', 'filters', 'filterOptions', 'groups', 'items', 'selectedGroup'));
    }

    /** @return array<string> */
    private function availableTypes(): array
    {
        return array_values(array_filter(self::TYPES, fn (string $type): bool => match ($type) {
            'assets' => Gate::allows('index', Asset::class),
            'cmdb' => Gate::allows('cmdb.view'),
            'licenses' => Gate::allows('view', License::class),
            'accessories' => Gate::allows('index', Accessory::class),
            'consumables' => Gate::allows('view', Consumable::class),
            'components' => Gate::allows('view', Component::class),
        }));
    }

    /** @param array<string> $types
     *  @return array<string, array{total: int, issued: int, remaining: int}>
     */
    private function summary(array $types): array
    {
        $summary = [];

        if (in_array('assets', $types, true)) {
            $total = Asset::count();
            $issued = Asset::whereNotNull('assigned_to')->count();
            $summary['assets'] = ['total' => $total, 'issued' => $issued, 'remaining' => max(0, $total - $issued)];
        }

        if (in_array('cmdb', $types, true)) {
            $total = CmdbObject::count();
            $linked = CmdbObject::has('assets')->count();
            $summary['cmdb'] = ['total' => $total, 'issued' => min($total, $linked), 'remaining' => max(0, $total - $linked)];
        }

        if (in_array('licenses', $types, true)) {
            $totals = License::query()->selectRaw('COALESCE(SUM((SELECT COUNT(*) FROM license_seats WHERE license_seats.license_id = licenses.id AND license_seats.deleted_at IS NULL AND (license_seats.assigned_to IS NOT NULL OR license_seats.asset_id IS NOT NULL))), 0) as issued, COALESCE(SUM((SELECT COUNT(*) FROM license_seats WHERE license_seats.license_id = licenses.id AND license_seats.deleted_at IS NULL AND license_seats.assigned_to IS NULL AND license_seats.asset_id IS NULL AND license_seats.unreassignable_seat = 0)), 0) as remaining')->first();
            $issued = (int) $totals->issued;
            $remaining = (int) $totals->remaining;
            $summary['licenses'] = ['total' => $issued + $remaining, 'issued' => $issued, 'remaining' => $remaining];
        }

        foreach (['accessories' => [Accessory::class, 'accessories', 'accessories_checkout', 'accessory_id', 'qty'], 'consumables' => [Consumable::class, 'consumables', 'consumables_users', 'consumable_id', 'qty']] as $key => [$model, $table, $checkoutTable, $foreignKey, $quantityColumn]) {
            if (in_array($key, $types, true)) {
                $totals = $model::query()->leftJoinSub(
                    DB::table($checkoutTable)->select($foreignKey)->selectRaw('COUNT(*) as issued')->groupBy($foreignKey),
                    'issued_items', 'issued_items.'.$foreignKey, '=', $table.'.id'
                )->selectRaw("COALESCE(SUM({$table}.{$quantityColumn}), 0) as total, COALESCE(SUM(CASE WHEN issued_items.issued > {$table}.{$quantityColumn} THEN {$table}.{$quantityColumn} ELSE COALESCE(issued_items.issued, 0) END), 0) as issued")->first();
                $total = (int) $totals->total;
                $issued = (int) $totals->issued;
                $summary[$key] = ['total' => $total, 'issued' => $issued, 'remaining' => max(0, $total - $issued)];
            }
        }

        if (in_array('components', $types, true)) {
            $totals = Component::query()->leftJoinSub(
                DB::table('components_assets')->select('component_id')->selectRaw('SUM(assigned_qty) as issued')->groupBy('component_id'),
                'issued_items', 'issued_items.component_id', '=', 'components.id'
            )->selectRaw('COALESCE(SUM(components.qty), 0) as total, COALESCE(SUM(CASE WHEN issued_items.issued > components.qty THEN components.qty ELSE COALESCE(issued_items.issued, 0) END), 0) as issued')->first();
            $total = (int) $totals->total;
            $issued = (int) $totals->issued;
            $summary['components'] = ['total' => $total, 'issued' => $issued, 'remaining' => max(0, $total - $issued)];
        }

        return $summary;
    }

    /** @return array<string, Collection<int, mixed>> */
    private function filterOptions(string $type): array
    {
        $categories = match ($type) {
            'assets' => Category::whereHas('models.assets')->orderBy('name')->get(['id', 'name']),
            'cmdb' => collect(),
            default => Category::whereHas($type)->orderBy('name')->get(['id', 'name']),
        };
        $models = match ($type) {
            'assets' => AssetModel::orderBy('name')->get(['id', 'name']),
            'cmdb' => CmdbObjectType::orderBy('name')->get(['id', 'name']),
            'licenses' => License::orderBy('name')->get(['id', 'name']),
            'accessories' => Accessory::orderBy('name')->get(['id', 'name']),
            'consumables' => Consumable::orderBy('name')->get(['id', 'name']),
            default => Component::orderBy('name')->get(['id', 'name']),
        };

        return [
            'categories' => $categories,
            'models' => $models,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'manufacturers' => Manufacturer::orderBy('name')->get(['id', 'name']),
            'statuses' => match ($type) {
                'assets' => Statuslabel::orderBy('name')->get(['id', 'name']),
                'cmdb' => collect(['linked' => trans('general.inventory_linked'), 'unlinked' => trans('general.inventory_unlinked')]),
                'licenses' => collect(['available' => trans('general.inventory_available_status'), 'empty' => trans('general.inventory_no_stock')]),
                default => collect(['available' => trans('general.inventory_available_status'), 'low' => trans('general.inventory_low_stock'), 'empty' => trans('general.inventory_no_stock')]),
            },
        ];
    }

    private function groupRows(string $type, string $groupBy, array $filters): LengthAwarePaginator
    {
        [$groupId, $groupName, $base, $total, $issued, $remaining] = $this->aggregateBase($type, $groupBy, $filters);
        if ($filters['group'] !== null) {
            $filters['group'] === '-1'
                ? $base->whereRaw($groupId.' IS NULL')
                : $base->whereRaw($groupId.' = ?', [$filters['group']]);
        }

        return $base->selectRaw("COALESCE({$groupId}, -1) as group_key, COALESCE({$groupName}, ?) as group_name, COALESCE(SUM({$total}), 0) as total_count, COALESCE(SUM({$issued}), 0) as issued_count, COALESCE(SUM({$remaining}), 0) as remaining_count, COUNT(*) as item_count", [trans('general.inventory_not_set')])
            ->groupByRaw("{$groupId}, {$groupName}")
            ->orderBy('group_name')
            ->paginate(30);
    }

    /** @return array{string, string, Builder, string, string, string} */
    private function aggregateBase(string $type, string $groupBy, array $filters): array
    {
        if ($type === 'assets') {
            $query = Asset::query()->join('models as inventory_models', 'inventory_models.id', '=', 'assets.model_id')
                ->leftJoin('categories as inventory_categories', 'inventory_categories.id', '=', 'inventory_models.category_id')
                ->leftJoin('suppliers as inventory_suppliers', 'inventory_suppliers.id', '=', 'assets.supplier_id')
                ->leftJoin('manufacturers as inventory_manufacturers', 'inventory_manufacturers.id', '=', 'inventory_models.manufacturer_id');
            $this->applyCommonFilters($query, $type, $filters);
            if ($filters['status'] !== '') { $query->where('assets.status_id', $filters['status']); }
            if ($filters['availability'] === 'remaining' && $filters['status'] === '') { $query->whereNull('assets.assigned_to'); }
            [$id, $name] = $this->groupColumns($groupBy, 'inventory_categories', 'inventory_models', 'inventory_suppliers', 'inventory_manufacturers', 'assets.supplier_id');

            return [$id, $name, $query, '1', 'CASE WHEN assets.assigned_to IS NULL THEN 0 ELSE 1 END', 'CASE WHEN assets.assigned_to IS NULL THEN 1 ELSE 0 END'];
        }

        if ($type === 'cmdb') {
            $query = CmdbObject::query()->leftJoin('cmdb_object_types as inventory_types', 'inventory_types.id', '=', 'cmdb_objects.type_id');
            if ($filters['model'] !== '') { $query->where('cmdb_objects.type_id', $filters['model']); }
            if ($filters['status'] === 'linked') { $query->has('assets'); }
            elseif ($filters['status'] === 'unlinked' || ($filters['status'] === '' && $filters['availability'] === 'remaining')) { $query->doesntHave('assets'); }
            $id = $groupBy === 'model' ? 'inventory_types.id' : 'NULL';
            $name = $groupBy === 'model' ? 'inventory_types.name' : 'NULL';

            $linkedAssetExists = 'EXISTS (SELECT 1 FROM asset_cmdb_object JOIN assets as linked_assets ON linked_assets.id = asset_cmdb_object.asset_id AND linked_assets.deleted_at IS NULL WHERE asset_cmdb_object.cmdb_object_id = cmdb_objects.id)';

            return [$id, $name, $query, '1', 'CASE WHEN '.$linkedAssetExists.' THEN 1 ELSE 0 END', 'CASE WHEN '.$linkedAssetExists.' THEN 0 ELSE 1 END'];
        }

        if ($type === 'licenses') {
            $query = License::query()->leftJoin('categories as inventory_categories', 'inventory_categories.id', '=', 'licenses.category_id')
                ->leftJoin('suppliers as inventory_suppliers', 'inventory_suppliers.id', '=', 'licenses.supplier_id')
                ->leftJoin('manufacturers as inventory_manufacturers', 'inventory_manufacturers.id', '=', 'licenses.manufacturer_id');
            $this->applyCommonFilters($query, $type, $filters);
            $available = '(SELECT COUNT(*) FROM license_seats WHERE license_seats.license_id = licenses.id AND license_seats.deleted_at IS NULL AND license_seats.assigned_to IS NULL AND license_seats.asset_id IS NULL AND license_seats.unreassignable_seat = 0)';
            if ($filters['availability'] === 'remaining' && $filters['status'] === '') { $query->whereRaw($available.' > 0'); }
            if ($filters['status'] === 'available') { $query->whereRaw($available.' > 0'); }
            if ($filters['status'] === 'empty') { $query->whereRaw($available.' = 0'); }
            [$id, $name] = $this->groupColumns($groupBy, 'inventory_categories', 'licenses', 'inventory_suppliers', 'inventory_manufacturers', 'licenses.supplier_id');
            $issued = '(SELECT COUNT(*) FROM license_seats WHERE license_seats.license_id = licenses.id AND license_seats.deleted_at IS NULL AND (license_seats.assigned_to IS NOT NULL OR license_seats.asset_id IS NOT NULL))';

            return [$id, $name, $query, '('.$issued.' + '.$available.')', $issued, $available];
        }

        $table = $type;
        $model = match ($type) {
            'accessories' => Accessory::class,
            'consumables' => Consumable::class,
            default => Component::class,
        };
        $query = $model::query()->leftJoin('categories as inventory_categories', 'inventory_categories.id', '=', $table.'.category_id')
            ->leftJoin('suppliers as inventory_suppliers', 'inventory_suppliers.id', '=', $table.'.default_supplier_id')
            ->leftJoin('manufacturers as inventory_manufacturers', 'inventory_manufacturers.id', '=', $table.'.manufacturer_id');
        $this->applyCommonFilters($query, $type, $filters);

        if ($type === 'accessories') {
            $issued = '(SELECT COUNT(*) FROM accessories_checkout WHERE accessories_checkout.accessory_id = accessories.id)';
        } elseif ($type === 'consumables') {
            $issued = '(SELECT COUNT(*) FROM consumables_users WHERE consumables_users.consumable_id = consumables.id)';
        } else {
            $issued = '(SELECT COALESCE(SUM(components_assets.assigned_qty), 0) FROM components_assets WHERE components_assets.component_id = components.id)';
        }
        $available = '(CASE WHEN COALESCE('.$table.'.qty, 0) > '.$issued.' THEN COALESCE('.$table.'.qty, 0) - '.$issued.' ELSE 0 END)';
        if ($filters['availability'] === 'remaining' && $filters['status'] === '') { $query->whereRaw($available.' > 0'); }
        if ($filters['status'] === 'available') { $query->whereRaw($available.' > COALESCE('.$table.'.min_amt, 0)'); }
        if ($filters['status'] === 'low') { $query->whereRaw($available.' > 0 AND '.$available.' <= COALESCE('.$table.'.min_amt, 0)'); }
        if ($filters['status'] === 'empty') { $query->whereRaw($available.' = 0'); }
        [$id, $name] = $this->groupColumns($groupBy, 'inventory_categories', $table, 'inventory_suppliers', 'inventory_manufacturers', $table.'.default_supplier_id');

        $issued = '(CASE WHEN '.$issued.' > COALESCE('.$table.'.qty, 0) THEN COALESCE('.$table.'.qty, 0) ELSE '.$issued.' END)';

        return [$id, $name, $query, 'COALESCE('.$table.'.qty, 0)', $issued, $available];
    }

    /** @return array{string, string} */
    private function groupColumns(string $groupBy, string $categoryAlias, string $modelAlias, string $supplierAlias, string $manufacturerAlias, string $supplierId): array
    {
        return match ($groupBy) {
            'model' => [$modelAlias.'.id', in_array($modelAlias, ['accessories', 'consumables', 'components'], true) ? "COALESCE(NULLIF({$modelAlias}.model_number, ''), {$modelAlias}.name)" : $modelAlias.'.name'],
            'supplier' => [$supplierId, $supplierAlias.'.name'],
            'manufacturer' => [$manufacturerAlias.'.id', $manufacturerAlias.'.name'],
            default => [$categoryAlias.'.id', $categoryAlias.'.name'],
        };
    }

    private function applyCommonFilters(Builder $query, string $type, array $filters, bool $hasAggregateJoins = true): void
    {
        $table = match ($type) {
            'accessories' => 'accessories',
            'consumables' => 'consumables',
            'components' => 'components',
            'licenses' => 'licenses',
            default => 'assets',
        };

        if ($filters['category'] !== '') {
            if ($type === 'assets' && $hasAggregateJoins) { $query->where('inventory_models.category_id', $filters['category']); }
            elseif ($type === 'assets') { $query->whereHas('model', fn ($models) => $models->where('category_id', $filters['category'])); }
            else { $query->where($table.'.category_id', $filters['category']); }
        }
        if ($filters['model'] !== '') {
            if ($type === 'assets') { $query->where('assets.model_id', $filters['model']); }
            elseif ($type !== 'cmdb') { $query->where($table.'.id', $filters['model']); }
        }
        if ($filters['supplier'] !== '') {
            $column = in_array($type, ['accessories', 'consumables', 'components'], true) ? 'default_supplier_id' : 'supplier_id';
            $query->where($table.'.'.$column, $filters['supplier']);
        }
        if ($filters['manufacturer'] !== '') {
            if ($type === 'assets' && ! $hasAggregateJoins) {
                $query->whereHas('model', fn ($models) => $models->where('manufacturer_id', $filters['manufacturer']));
            } else {
                $column = $type === 'assets' ? 'inventory_models.manufacturer_id' : $table.'.manufacturer_id';
                $query->where($column, $filters['manufacturer']);
            }
        }
    }

    private function inventoryRows(string $type, string $groupBy, array $filters): LengthAwarePaginator
    {
        if ($type === 'assets') {
            $query = Asset::query()->with(['model.category', 'model.manufacturer', 'supplier', 'status'])->select(['id', 'asset_tag', 'model_id', 'status_id', 'assigned_to', 'supplier_id']);
            $this->applyCommonFilters($query, $type, $filters, false);
            if ($filters['status'] !== '') { $query->where('status_id', $filters['status']); }
            if ($filters['availability'] === 'remaining' && $filters['status'] === '') { $query->whereNull('assigned_to'); }
            $this->applyGroupConstraint($query, $type, $groupBy, $filters['group']);

            return $query->paginate(30)->through(fn (Asset $asset): array => [
                'name' => $asset->asset_tag ?: trans('general.asset').' #'.$asset->id,
                'model' => $asset->model?->name ?? '—',
                'category' => $asset->model?->category?->name ?? '—',
                'supplier' => $asset->supplier?->name ?? trans('general.inventory_not_set'),
                'manufacturer' => $asset->model?->manufacturer?->name ?? trans('general.inventory_not_set'),
                'status' => $asset->status?->name ?? trans('general.inventory_unknown_status'),
                'total' => 1,
                'issued' => $asset->assigned_to ? 1 : 0,
                'remaining' => $asset->assigned_to ? 0 : 1,
                'url' => route('hardware.show', $asset->id),
            ]);
        }

        if ($type === 'cmdb') {
            $query = CmdbObject::query()->with('type')->withCount('assets');
            if ($filters['model'] !== '') { $query->where('type_id', $filters['model']); }
            if ($filters['status'] === 'linked') { $query->has('assets'); }
            elseif ($filters['status'] === 'unlinked' || ($filters['status'] === '' && $filters['availability'] === 'remaining')) { $query->doesntHave('assets'); }
            $this->applyGroupConstraint($query, $type, $groupBy, $filters['group']);

            return $query->paginate(30)->through(fn (CmdbObject $item): array => [
                'name' => $item->name,
                'model' => $item->type?->name ?? '—',
                'category' => '—',
                'supplier' => '—',
                'manufacturer' => '—',
                'status' => $item->assets_count > 0 ? trans('general.inventory_linked') : trans('general.inventory_unlinked'),
                'total' => 1,
                'issued' => $item->assets_count > 0 ? 1 : 0,
                'remaining' => $item->assets_count > 0 ? 0 : 1,
                'url' => route('cmdb.objects.index'),
            ]);
        }

        $class = match ($type) {
            'licenses' => License::class,
            'accessories' => Accessory::class,
            'consumables' => Consumable::class,
            default => Component::class,
        };
        $query = match ($type) {
            'licenses' => $class::query()->with(['category', 'supplier', 'manufacturer'])->withCount([
                'licenseseats as issued_count' => fn ($seats) => $seats->where(fn ($seatQuery) => $seatQuery->whereNotNull('assigned_to')->orWhereNotNull('asset_id')),
                'licenseseats as available_count' => fn ($seats) => $seats->whereNull('assigned_to')->whereNull('asset_id')->where('unreassignable_seat', false),
            ]),
            'accessories' => $class::query()->with(['category', 'defaultSupplier', 'manufacturer'])->withCount('checkouts as issued_count'),
            'consumables' => $class::query()->with(['category', 'defaultSupplier', 'manufacturer'])->withCount('users as issued_count'),
            default => $class::query()->with(['category', 'defaultSupplier', 'manufacturer'])->withSum('unconstrainedAssets as issued_count', 'components_assets.assigned_qty'),
        };
        $this->applyCommonFilters($query, $type, $filters, false);
        $this->applyStatusAndAvailability($query, $type, $filters);
        $this->applyGroupConstraint($query, $type, $groupBy, $filters['group']);

        return $query->paginate(30)->through(function ($item) use ($type): array {
            if ($type === 'licenses') {
                $total = (int) $item->issued_count + (int) $item->available_count;
                $issued = (int) $item->issued_count;
                $remaining = (int) $item->available_count;
                $supplier = $item->supplier?->name;
            } else {
                $total = (int) $item->qty;
                $issued = min($total, (int) $item->issued_count);
                $remaining = max(0, $total - $issued);
                $supplier = $item->defaultSupplier?->name;
            }

            return [
                'name' => $item->name,
                'model' => trim((string) ($item->model_number ?? '')) ?: $item->name,
                'category' => $item->category?->name ?? '—',
                'supplier' => $supplier ?? trans('general.inventory_not_set'),
                'manufacturer' => $item->manufacturer?->name ?? trans('general.inventory_not_set'),
                'status' => $remaining === 0 ? trans('general.inventory_no_stock') : ($remaining <= (int) ($item->min_amt ?? 0) ? trans('general.inventory_low_stock') : trans('general.inventory_available_status')),
                'total' => $total,
                'issued' => $issued,
                'remaining' => $remaining,
                'url' => route($type.'.show', $item->id),
            ];
        });
    }

    private function applyStatusAndAvailability(Builder $query, string $type, array $filters): void
    {
        if ($type === 'licenses') {
            $available = '(SELECT COUNT(*) FROM license_seats WHERE license_seats.license_id = licenses.id AND license_seats.deleted_at IS NULL AND license_seats.assigned_to IS NULL AND license_seats.asset_id IS NULL AND license_seats.unreassignable_seat = 0)';
            if (($filters['availability'] === 'remaining' && $filters['status'] === '') || $filters['status'] === 'available') { $query->whereRaw($available.' > 0'); }
            if ($filters['status'] === 'empty') { $query->whereRaw($available.' = 0'); }

            return;
        }

        $table = $type;
        if ($type === 'accessories') { $issued = '(SELECT COUNT(*) FROM accessories_checkout WHERE accessories_checkout.accessory_id = accessories.id)'; }
        elseif ($type === 'consumables') { $issued = '(SELECT COUNT(*) FROM consumables_users WHERE consumables_users.consumable_id = consumables.id)'; }
        else { $issued = '(SELECT COALESCE(SUM(components_assets.assigned_qty), 0) FROM components_assets WHERE components_assets.component_id = components.id)'; }
        $remaining = '(CASE WHEN COALESCE('.$table.'.qty, 0) > '.$issued.' THEN COALESCE('.$table.'.qty, 0) - '.$issued.' ELSE 0 END)';

        if ($filters['availability'] === 'remaining' && $filters['status'] === '') { $query->whereRaw($remaining.' > 0'); }
        if ($filters['status'] === 'available') { $query->whereRaw($remaining.' > COALESCE('.$table.'.min_amt, 0)'); }
        if ($filters['status'] === 'low') { $query->whereRaw($remaining.' > 0 AND '.$remaining.' <= COALESCE('.$table.'.min_amt, 0)'); }
        if ($filters['status'] === 'empty') { $query->whereRaw($remaining.' = 0'); }
    }

    private function applyGroupConstraint(Builder $query, string $type, string $groupBy, ?string $groupValue): void
    {
        if ($groupValue === null) { return; }
        if ($type === 'cmdb') {
            if ($groupBy === 'model') {
                $groupValue === '-1' ? $query->whereNull('type_id') : $query->where('type_id', $groupValue);
            }

            return;
        }

        if ($groupValue === '-1') {
            if ($groupBy === 'category' && $type === 'assets') { $query->whereDoesntHave('model.category'); }
            elseif ($groupBy === 'category') { $query->whereNull($type.'.category_id'); }
            elseif ($groupBy === 'supplier') { $query->whereNull(in_array($type, ['accessories', 'consumables', 'components'], true) ? $type.'.default_supplier_id' : $type.'.supplier_id'); }
            elseif ($groupBy === 'manufacturer' && $type === 'assets') { $query->whereDoesntHave('model.manufacturer'); }
            elseif ($groupBy === 'manufacturer') { $query->whereNull($type.'.manufacturer_id'); }

            return;
        }

        if ($groupBy === 'category') {
            $type === 'assets'
                ? $query->whereHas('model', fn ($models) => $models->where('category_id', $groupValue))
                : $query->where('category_id', $groupValue);
        } elseif ($groupBy === 'model') {
            $query->where($type === 'assets' ? 'model_id' : ($type === 'cmdb' ? 'type_id' : 'id'), $groupValue);
        } elseif ($groupBy === 'supplier') {
            $column = in_array($type, ['accessories', 'consumables', 'components'], true) ? 'default_supplier_id' : 'supplier_id';
            $query->where($column, $groupValue);
        } elseif ($groupBy === 'manufacturer') {
            $type === 'assets'
                ? $query->whereHas('model', fn ($models) => $models->where('manufacturer_id', $groupValue))
                : $query->where('manufacturer_id', $groupValue);
        }
    }
}
