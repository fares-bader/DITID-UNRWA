@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.activity_report') }} 
@parent
@stop

@section('header_right')
    <form method="POST" action="{{ route('reports.activity.post') }}" accept-charset="UTF-8" class="form-horizontal">
    {{csrf_field()}}
    <button type="submit" class="btn btn-default">
        <x-icon type="download" />
        {{ trans('general.download_all') }}
    </button>
    </form>
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        <x-box>
                <table
                    data-columns="{{ \App\Presenters\HistoryPresenter::dataTableLayout() }}"
                        data-cookie-id-table="activityReport"
                        data-id-table="activityReport"
                        data-side-pagination="server"
                        data-advanced-search="false"
                        data-sort-order="desc"
                        data-sort-name="created_at"
                        id="activityReport"
                        data-url="{{ route('api.activity.index') }}"
                    class="table table-striped snipe-table snipe-table--sticky-right-1"
                        data-export-options='{
                        "fileName": "activity-report-{{ date('Y-m-d') }}",
                        "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                </table>
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'activity-export', 'search' => true])

<script nonce="{{ csrf_token() }}">
    function loadNoteFormatter(value, row) {
        if (!value) return '-';
        
        if (row.action_type === 'checkout' || (row.action_type && row.action_type.includes('checkout'))) {
            return '<a href="{{ url('/') }}/hardware/load-note/' + value + '" target="_blank" class="btn btn-sm btn-info" data-tooltip="true" title="Print Load Note"><i class="fas fa-print"></i> Print Note</a>';
        } 
        else if (row.action_type === 'checkin from' || (row.action_type && row.action_type.includes('checkin'))) {
            return '<a href="{{ url('/') }}/hardware/checkin-receipt/' + value + '" target="_blank" class="btn btn-sm btn-success" data-tooltip="true" title="Print Receipt"><i class="fas fa-print"></i> Print Receipt</a>';
        }
        
        return '-';
    }
</script>
@stop