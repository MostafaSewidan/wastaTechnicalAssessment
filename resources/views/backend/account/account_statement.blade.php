@extends('backend.layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <h3>{{trans('file.Account Statement')}}</h3>
        <strong>{{trans('file.Account')}}:</strong> {{$lims_account_data->name}} [{{$lims_account_data->account_no}}]
    </div>
    <div class="table-responsive mb-4">
        <table id="account-table" class="table table-hover">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.date')}}</th>
                    <th>{{trans('file.Reference No')}}</th>
                    <th>{{trans('file.Related Transaction')}}</th>
                    <th>{{trans('file.Credit')}}</th>
                    <th>{{trans('file.Debit')}}</th>
                    <th>{{trans('file.Balance')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($all_transaction_list as $key => $data)
                <?php
                    $transaction = '';
                    if($data->sale_id)
                        $transaction = App\Models\Sale::select('reference_no')->find($data->sale_id);
                    elseif($data->purchase_id)
                        $transaction = App\Models\Purchase::select('reference_no')->find($data->purchase_id);
                    if(str_contains($data->reference_no, 'spr') || str_contains($data->reference_no, 'prr') || (str_contains($data->reference_no, 'mtr') && $data->to_account_id == $lims_account_data->id) ) {
                        $balance += $data->amount;
                        $credit = $data->amount;
                        $debit = 0;
                    }
                    else {
                        $balance -= $data->amount;
                        $debit = $data->amount;
                        $credit = 0;
                    }
                ?>
                <tr>
                    <td>{{$key}}</td>
                    <td data-sort="{{date('Y-m-d', strtotime($data->created_at->toDateString()))}}">{{date($general_setting->date_format, strtotime($data->created_at->toDateString()))}}</td>
                    <td>{{$data->reference_no}}</td>
                    @if($transaction)
                        <td>{{$transaction->reference_no}}</td>
                    @else
                        <td></td>
                    @endif
                    <td>{{number_format((float)$credit, $general_setting->decimal, '.', '')}}</td>
                    <td>{{number_format((float)$debit, $general_setting->decimal, '.', '')}}</td>
                    <td>{{number_format((float)$balance, $general_setting->decimal, '.', '')}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection
@push('scripts')
<script type="text/javascript">
    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #account-statement-menu").addClass("active");


    jQuery.extend(jQuery.fn.dataTableExt.oSort, {
        "extract-date-pre": function(value) {
            var date = $(value, 'span')[0].innerHTML;
            date = date.split('/');
            return Date.parse(date[1] + '/' + date[0] + '/' + date[2])
        },
        "extract-date-asc": function(a, b) {
            return ((a < b) ? -1 : ((a > b) ? 1 : 0));
        },
        "extract-date-desc": function(a, b) {
            return ((a < b) ? 1 : ((a > b) ? -1 : 0));
        }
    });

    $('#account-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
             "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{trans("file.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                type : 'extract-date',
                'targets': 0
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );
</script>
@endpush
