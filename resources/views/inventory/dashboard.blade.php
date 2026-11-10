@extends('layouts/default')

@section('title')
{{ trans('general.inventory_dashboard_title') }}
@parent
@stop

@section('content')
@push('css')
<style>
    .inventory-dashboard { --inventory-ink: var(--header-color,#20364b); --inventory-muted: var(--ui-text-muted,#718096); --inventory-line: var(--ui-border-color,#e4eaf0); }
    .inventory-dashboard .inventory-hero { display:flex; justify-content:space-between; align-items:center; gap:20px; padding:24px 28px; margin-bottom:20px; color:#fff; background:linear-gradient(115deg,#124f7c,#087e8b); border-radius:8px; }
    .inventory-dashboard .inventory-hero h1 { margin:0 0 6px; font-size:24px; font-weight:700; }
    .inventory-dashboard .inventory-hero p { margin:0; opacity:.9; }
    .inventory-dashboard .inventory-cards { display:flex; gap:12px; margin-bottom:20px; overflow-x:auto; padding:2px 2px 10px; }
    .inventory-dashboard .inventory-card { display:block; flex:1 0 calc((100% - 60px) / 6); min-width:178px; padding:14px; color:var(--inventory-ink); background:var(--box-bg,#fff); border:1px solid var(--inventory-line); border-radius:8px; transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease; }
    .inventory-dashboard .inventory-card:hover,.inventory-dashboard .inventory-card:focus { color:var(--inventory-ink); text-decoration:none; transform:translateY(-2px); border-color:#8abbd0; box-shadow:0 7px 18px rgba(28,57,79,.1); }
    .inventory-dashboard .inventory-card:focus-visible { outline:3px solid #54a9d5; outline-offset:2px; }
    .inventory-dashboard .inventory-card-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; font-weight:700; }
    .inventory-dashboard .inventory-card-icon { display:grid; place-items:center; width:34px; height:34px; color:#12638a; background:#e8f4f8; border-radius:9px; }
    .inventory-dashboard .inventory-card-total { margin-inline-start:auto; color:var(--inventory-muted); font-size:12px; font-weight:500; }
    .inventory-dashboard .inventory-card-metrics { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .inventory-dashboard .inventory-metric { padding-top:9px; border-top:1px solid var(--inventory-line); }
    .inventory-dashboard .inventory-metric strong { display:block; font-size:19px; line-height:1.2; }
    .inventory-dashboard .inventory-metric span { color:var(--inventory-muted); font-size:11px; }
    .inventory-dashboard .inventory-filter-grid { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:12px; align-items:end; }
    .inventory-dashboard .inventory-filter-grid .form-group { margin:0; }
    .inventory-dashboard .inventory-filter-grid label { color:var(--inventory-ink); font-size:12px; }
    .inventory-dashboard .inventory-table-wrap { overflow-x:auto; }
    .inventory-dashboard .inventory-table { min-width:780px; margin-bottom:0; }
    .inventory-dashboard .inventory-table th { white-space:nowrap; background:var(--ui-table-head-bg,#f5f8fa); color:var(--header-color,#4c6275); }
    .inventory-dashboard .inventory-table td { vertical-align:middle; }
    .inventory-dashboard .inventory-number { font-variant-numeric:tabular-nums; font-weight:700; }
    .inventory-dashboard .inventory-remaining-value { color:#16804a; }
    .inventory-dashboard .inventory-group-link { display:inline-flex; align-items:center; gap:7px; font-weight:600; }
    .inventory-dashboard .inventory-empty { padding:34px 15px; text-align:center; color:var(--inventory-muted); }
    .inventory-dashboard .inventory-empty i { display:block; margin-bottom:10px; font-size:26px; color:#91a2b1; }
    [data-theme="dark"] .inventory-dashboard .inventory-remaining-value { color:#7bd6a1; }
    @media (max-width: 767px) {
        .inventory-dashboard .inventory-hero { align-items:flex-start; padding:20px 16px; }
        .inventory-dashboard .inventory-hero h1 { font-size:21px; }
        .inventory-dashboard .inventory-filter-grid { grid-template-columns:1fr 1fr; }
        .inventory-dashboard .inventory-filter-grid .inventory-filter-actions { grid-column:1 / -1; display:flex; gap:8px; }
        .inventory-dashboard .inventory-filter-grid .inventory-filter-actions .btn { flex:1; }
    }
    @media (prefers-reduced-motion: reduce) { .inventory-dashboard .inventory-card { transition:none; } }
</style>
@endpush

<div class="inventory-dashboard">
    <header class="inventory-hero">
        <div>
            <h1><i class="fas fa-chart-pie" aria-hidden="true"></i> {{ trans('general.inventory_dashboard_title') }}</h1>
            <p>{{ trans('general.inventory_dashboard_intro') }}</p>
        </div>
        <span class="label label-default"><i class="fas fa-warehouse" aria-hidden="true"></i> {{ trans('general.inventory_remaining_only') }}</span>
    </header>

    <section class="inventory-cards" aria-label="{{ trans('general.inventory_quick_access') }}">
        @foreach ($availableTypes as $inventoryType)
            @php
                $cardIcons = ['assets' => 'fa-laptop', 'cmdb' => 'fa-network-wired', 'licenses' => 'fa-key', 'accessories' => 'fa-headphones', 'consumables' => 'fa-box-open', 'components' => 'fa-microchip'];
                $cardUrls = ['assets' => route('hardware.index'), 'cmdb' => route('cmdb.objects.index'), 'licenses' => route('licenses.index'), 'accessories' => route('accessories.index'), 'consumables' => route('consumables.index'), 'components' => route('components.index')];
                $cardSummary = $summary[$inventoryType];
                $typeLabel = trans('general.inventory_type_'.$inventoryType);
            @endphp
            <a class="inventory-card" href="{{ $cardUrls[$inventoryType] }}" aria-label="{{ $typeLabel }} &mdash; {{ trans('general.inventory_remaining') }}: {{ number_format($cardSummary['remaining']) }}">
                <span class="inventory-card-head">
                    <span class="inventory-card-icon"><i class="fas {{ $cardIcons[$inventoryType] }}" aria-hidden="true"></i></span>
                    <span>{{ $typeLabel }}</span>
                    <span class="inventory-card-total">{{ number_format($cardSummary['total']) }} {{ trans('general.inventory_total') }}</span>
                </span>
                <span class="inventory-card-metrics">
                    <span class="inventory-metric"><strong class="inventory-remaining-value">{{ number_format($cardSummary['remaining']) }}</strong><span>{{ $inventoryType === 'cmdb' ? trans('general.inventory_unlinked') : trans('general.inventory_remaining') }}</span></span>
                    <span class="inventory-metric"><strong>{{ number_format($cardSummary['issued']) }}</strong><span>{{ $inventoryType === 'cmdb' ? trans('general.inventory_linked') : trans('general.inventory_issued') }}</span></span>
                </span>
            </a>
        @endforeach
    </section>

    <section class="box box-default" aria-labelledby="inventory-filter-title">
        <div class="box-header with-border">
            <h2 class="box-title" id="inventory-filter-title"><i class="fas fa-filter" aria-hidden="true"></i> {{ trans('general.inventory_filter') }}</h2>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('inventory.dashboard') }}" class="inventory-filter-grid">
                <div class="form-group">
                    <label for="inventory-type">{{ trans('general.inventory_type') }}</label>
                    <select class="form-control" id="inventory-type" name="type">
                        @foreach ($availableTypes as $inventoryType)
                            <option value="{{ $inventoryType }}" @selected($type === $inventoryType)>{{ trans('general.inventory_type_'.$inventoryType) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="inventory-group-by">{{ trans('general.inventory_group_by') }}</label>
                    <select class="form-control" id="inventory-group-by" name="group_by">
                        @foreach (['category', 'model', 'supplier', 'manufacturer'] as $dimension)
                            <option value="{{ $dimension }}" @selected($groupBy === $dimension)>{{ trans('general.inventory_'.$dimension) }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($type !== 'cmdb')
                    <div class="form-group">
                        <label for="inventory-category">{{ trans('general.inventory_category') }}</label>
                        <select class="form-control" id="inventory-category" name="category">
                            <option value="">{{ trans('general.all') }}</option>
                            @foreach ($filterOptions['categories'] as $category)
                                <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="form-group">
                        <label for="inventory-category">{{ trans('general.inventory_category') }}</label>
                        <input class="form-control" id="inventory-category" value="&mdash;" disabled>
                    </div>
                @endif
                <div class="form-group">
                    <label for="inventory-model">{{ trans('general.inventory_model') }}</label>
                    <select class="form-control" id="inventory-model" name="model">
                        <option value="">{{ trans('general.all') }}</option>
                        @foreach ($filterOptions['models'] as $model)
                            <option value="{{ $model->id }}" @selected($filters['model'] === (string) $model->id)>{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="inventory-supplier">{{ trans('general.inventory_supplier') }}</label>
                    <select class="form-control" id="inventory-supplier" name="supplier" @disabled($type === 'cmdb')>
                        <option value="">{{ trans('general.all') }}</option>
                        @foreach ($filterOptions['suppliers'] as $supplier)
                            <option value="{{ $supplier->id }}" @selected($filters['supplier'] === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="inventory-manufacturer">{{ trans('general.inventory_manufacturer') }}</label>
                    <select class="form-control" id="inventory-manufacturer" name="manufacturer" @disabled($type === 'cmdb')>
                        <option value="">{{ trans('general.all') }}</option>
                        @foreach ($filterOptions['manufacturers'] as $manufacturer)
                            <option value="{{ $manufacturer->id }}" @selected($filters['manufacturer'] === (string) $manufacturer->id)>{{ $manufacturer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="inventory-status">{{ trans('general.inventory_status') }}</label>
                    <select class="form-control" id="inventory-status" name="status">
                        <option value="">{{ trans('general.all') }}</option>
                        @foreach ($filterOptions['statuses'] as $key => $status)
                            @php($statusValue = is_object($status) ? $status->id : $key)
                            @php($statusLabel = is_object($status) ? $status->name : $status)
                            <option value="{{ $statusValue }}" @selected($filters['status'] === (string) $statusValue)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="inventory-filter-actions">
                    <input type="hidden" name="availability" value="{{ $filters['availability'] }}">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> {{ trans('general.search') }}</button>
                    <a class="btn btn-default" href="{{ route('inventory.dashboard', ['type' => $type, 'group_by' => $groupBy, 'availability' => 'remaining']) }}">{{ trans('general.inventory_clear_filters') }}</a>
                    <a class="btn btn-default" href="{{ route('inventory.dashboard', ['type' => $type, 'group_by' => $groupBy, 'availability' => 'all']) }}">{{ trans('general.inventory_show_all') }}</a>
                    @if ($filters['availability'] === 'all')
                        <a class="btn btn-default" href="{{ route('inventory.dashboard', ['type' => $type, 'group_by' => $groupBy, 'availability' => 'remaining']) }}">{{ trans('general.inventory_remaining_only') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="box box-default" aria-labelledby="inventory-groups-title">
        <div class="box-header with-border">
            <h2 class="box-title" id="inventory-groups-title"><i class="fas fa-layer-group" aria-hidden="true"></i> {{ trans('general.inventory_group_summary') }} &middot; {{ trans('general.inventory_'.$groupBy) }}</h2>
            <div class="box-tools pull-right">
                @if ($type === 'assets')
                    @can('create', \App\Models\Asset::class)
                        <a class="btn btn-primary btn-sm" href="{{ route('hardware.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> {{ trans('general.create') }}</a>
                    @else
                        <a class="btn btn-primary btn-sm" href="{{ route('hardware.index') }}"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ trans('general.inventory_quick_access') }}</a>
                    @endcan
                @elseif ($type === 'cmdb')
                    <a class="btn btn-primary btn-sm" href="{{ route('cmdb.objects.index') }}"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ trans('general.inventory_quick_access') }}</a>
                @elseif ($type === 'licenses')
                    <a class="btn btn-primary btn-sm" href="{{ route('licenses.index') }}"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ trans('general.inventory_quick_access') }}</a>
                @elseif ($type === 'accessories')
                    <a class="btn btn-primary btn-sm" href="{{ route('accessories.index') }}"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ trans('general.inventory_quick_access') }}</a>
                @elseif ($type === 'consumables')
                    <a class="btn btn-primary btn-sm" href="{{ route('consumables.index') }}"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ trans('general.inventory_quick_access') }}</a>
                @else
                    <a class="btn btn-primary btn-sm" href="{{ route('components.index') }}"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ trans('general.inventory_quick_access') }}</a>
                @endif
            </div>
        </div>
        <div class="box-body">
            @if ($groups->count())
                <div class="inventory-table-wrap" tabindex="0" role="region" aria-label="{{ trans('general.inventory_group_summary') }}">
                    <table class="table table-hover inventory-table">
                        <thead><tr>
                            <th>{{ trans('general.inventory_'.$groupBy) }}</th>
                            <th class="text-right">{{ trans('general.inventory_items') }}</th>
                            <th class="text-right">{{ trans('general.inventory_total') }}</th>
                            <th class="text-right">{{ trans('general.inventory_issued') }}</th>
                            <th class="text-right">{{ trans('general.inventory_remaining') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($groups as $group)
                                @php($groupQuery = array_merge(request()->except(['page', 'group']), ['type' => $type, 'group_by' => $groupBy, 'group' => (string) $group->group_key]))
                                <tr>
                                    <td><a class="inventory-group-link" href="{{ route('inventory.dashboard', $groupQuery) }}#inventory-details"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ $group->group_name }}</a></td>
                                    <td class="text-right inventory-number">{{ number_format($group->item_count) }}</td>
                                    <td class="text-right inventory-number">{{ number_format($group->total_count) }}</td>
                                    <td class="text-right inventory-number">{{ number_format($group->issued_count) }}</td>
                                    <td class="text-right inventory-number inventory-remaining-value">{{ number_format($group->remaining_count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center">{{ $groups->links() }}</div>
            @else
                <div class="inventory-empty"><i class="fas fa-box-open" aria-hidden="true"></i>{{ trans('general.inventory_no_results') }}</div>
            @endif
        </div>
    </section>

    @if ($items)
        <section class="box box-default" id="inventory-details" aria-labelledby="inventory-details-title">
            <div class="box-header with-border">
                <h2 class="box-title" id="inventory-details-title"><i class="fas fa-table-list" aria-hidden="true"></i> {{ trans('general.inventory_details') }} &middot; {{ trans('general.inventory_type_'.$type) }}</h2>
            </div>
            <div class="box-body">
                @if ($selectedGroup)
                    <p class="text-muted"><i class="fas fa-filter" aria-hidden="true"></i> {{ trans('general.inventory_'.$groupBy) }}: <strong>{{ $selectedGroup->group_name }}</strong></p>
                @endif
                @if ($items->count())
                    <div class="inventory-table-wrap" tabindex="0" role="region" aria-label="{{ trans('general.inventory_details') }}">
                        <table class="table table-hover inventory-table">
                            <thead><tr>
                                <th>{{ trans('general.inventory_item_name') }}</th>
                                <th>{{ trans('general.inventory_model') }}</th>
                                <th>{{ trans('general.inventory_category') }}</th>
                                <th>{{ trans('general.inventory_supplier') }}</th>
                                <th>{{ trans('general.inventory_manufacturer') }}</th>
                                <th>{{ trans('general.inventory_status') }}</th>
                                <th class="text-right">{{ trans('general.inventory_total') }}</th>
                                <th class="text-right">{{ trans('general.inventory_issued') }}</th>
                                <th class="text-right">{{ trans('general.inventory_remaining') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td><a href="{{ $item['url'] }}">{{ $item['name'] }}</a></td>
                                        <td>{{ $item['model'] }}</td>
                                        <td>{{ $item['category'] }}</td>
                                        <td>{{ $item['supplier'] }}</td>
                                        <td>{{ $item['manufacturer'] }}</td>
                                        <td>{{ $item['status'] }}</td>
                                        <td class="text-right inventory-number">{{ number_format($item['total']) }}</td>
                                        <td class="text-right inventory-number">{{ number_format($item['issued']) }}</td>
                                        <td class="text-right inventory-number inventory-remaining-value">{{ number_format($item['remaining']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">{{ $items->links() }}</div>
                @else
                    <div class="inventory-empty"><i class="fas fa-box-open" aria-hidden="true"></i>{{ trans('general.inventory_no_results') }}</div>
                @endif
            </div>
        </section>
    @endif
</div>
@stop

