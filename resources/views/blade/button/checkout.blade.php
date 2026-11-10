@props([
    'item' => null,
    'permission' => null,
    'route',
    'wide' => false,
])

@can('checkout', $item)
    @if ($item->showCheckoutButton($item) == 'show-active')
        <a href="{{ $route  }}" class="btn btn-sm bg-maroon hidden-print" data-tooltip="true" data-placement="top" title="{{ trans('general.checkout') }}" aria-label="{{ trans('general.checkout') }}">
            <x-icon type="checkout" class="fa-fw" />
            @if ($wide=='true')
                {{ trans('general.checkout') }}
            @endif
        </a>
    @elseif ($item->showCheckoutButton($item) == 'show-disabled')
        <button type="button" class="btn btn-sm bg-maroon hidden-print disabled" aria-disabled="true" data-tooltip="true" data-placement="top" title="{{ ($item::class =='App\Models\Asset') ? trans('admin/hardware/general.undeployable_tooltip') : trans('general.undeployable_tooltip') }}" aria-label="{{ ($item::class =='App\Models\Asset') ? trans('admin/hardware/general.undeployable_tooltip') : trans('general.undeployable_tooltip') }}">
            <x-icon type="checkout" class="fa-fw"/>
            @if ($wide=='true')
                {{ trans('general.checkout') }}
            @endif
        </button>
    @endif
@endcan
