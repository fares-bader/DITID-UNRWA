@props([
    'item' => null,
    'route' => null,
    'count' => null,
    'type' => 'item',
    'wide' => false,
])

@can('delete', $item)
    <!-- start delete button component -->
    @if ((method_exists($item, 'isDeletable')) && ($item->deleted_at==''))
        @if (!$item->isDeletable())
            <button type="button" class="pull-right btn btn-sm btn-danger hidden-print disabled {{ $wide == 'true' ? ' btn-block btn-social' : '' }}" aria-disabled="true" data-tooltip="true" data-placement="top" title="{{ trans('general.cannot_be_deleted') }}" aria-label="{{ trans('general.cannot_be_deleted') }}">
                <x-icon type="delete" class="fa-fw"  />
            </button>
        @else
            <button type="button" class="pull-right btn btn-sm btn-danger delete-asset{{ $wide == 'true' ? ' btn-block btn-social' : '' }}" data-toggle="modal" title="{{ ($item->assignedTo) ? trans('general.checkin_and_delete') : trans('general.delete') }}" aria-label="{{ ($item->assignedTo) ? trans('general.checkin_and_delete') : trans('general.delete') }}" data-title="{{ ($item->assignedTo) ? trans('general.checkin_and_delete') : trans('general.delete') }}" data-content="{{ trans('general.sure_to_delete_var', ['item' => $item->display_name]) }}" data-target="#dataConfirmModal" data-tooltip="true" data-icon="fa fa-trash" data-placement="top" onClick="return false;">
                <x-icon type="delete" class="fa-fw" />
            </button>
        @endif
    @endif
    <!-- end delete button component -->
@endif

