@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('admin/maintenances/general.asset_maintenances') }}
  @parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        <x-box name="maintenance">

            <x-slot:bulkactions>
                <x-table.bulk-maintenances
                    name="maintenance"
                    :show-complete="request()->input('completed') !== 'true'"
                />
            </x-slot:bulkactions>

            <x-table.maintenances
                name="maintenance"
                :route="route('api.maintenances.index').'?completed='.request()->input('completed', 'false').'&upcoming_status='.request()->input('upcoming_status', '')"
            />

        </x-box>
        <x-shiftclick/>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table', ['exportFile' => 'maintenances-export', 'search' => true])
    <x-modals.maintenance-complete />

    <script nonce="{{ csrf_token() }}">
        $(function() {
            $(document).on('post-body.bs.table', '.snipe-table', function() {
                $(this).find('tbody tr').each(function () {
                    var $nobr = $(this).find('nobr');
                    var $actionsContainer = $nobr.length ? $nobr : $(this).find('td').last();

                    if ($actionsContainer.length > 0 && $actionsContainer.find('.btn-print-gatepass').length === 0) {
                        
                        var editBtn = $actionsContainer.find('a[href*="/maintenances/"]').first();
                        
                        if (editBtn.length > 0) {
                            var link = editBtn.attr('href');
                            var match = link.match(/\/maintenances\/(\d+)/);
                            
                            if (match && match[1]) {
                                var maintenanceId = match[1];
                                var printUrl = '{{ url('/') }}/hardware/maintenances/' + maintenanceId + '/gate-pass';
                                
                                var printBtn = '<a href="' + printUrl + '" class="btn btn-sm bg-purple btn-print-gatepass" target="_blank" data-tooltip="true" title="Print Gate Pass" style="margin-right: 4px;"><i class="fas fa-print fa-fw"></i></a>';
                                
                                editBtn.before(printBtn);
                            }
                        }
                    }
                });
                $('[data-tooltip="true"]').tooltip();
            });
        });
    </script>
@stop