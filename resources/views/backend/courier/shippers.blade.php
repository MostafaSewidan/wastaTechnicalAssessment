@extends('backend.layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">
        <button class="btn btn-info" data-toggle="modal" data-target="#create-modal"><i class="dripicons-plus"></i> {{trans('file.Add Shipping Method')}}</button>
        <?php 
            if(isset($_GET['shipper'])){
            ?>
            <a class="btn btn-success" href="{{ route('sales.shippers') }}" >Go Back</a>
            <?php 
            }
            ?>
    </div>
    <div class="table-responsive" style="overflow-x:unset!important;">
        <?php 
            if(isset($_GET['shipper'])){
            ?>
                        {!! Form::open(['route' => 'sales.updateplaces', 'method' => 'post']) !!}
<?php } ?>
        <table id="courier-table" class="table" style="width: 100%">
            <?php 
            if(!isset($_GET['shipper'])){
            ?>
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Company')}}</th>
                    <th>{{trans('file.State')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_courier_all as $key=>$courier)

                <tr data-id="{{$courier->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $courier->company }}</td>
                    <td>
        <?php
        $states = DB::table('places')->where('company',$courier->company)->groupby('state')->get();
        $first = DB::table('places')->where('company',$courier->company)->groupby('state')->first();
        ?>
                        <select name="state" onchange="setlink('{{ $courier->company }}')" id="state_{{ $courier->company }}" class="form-control" >
        @foreach($states as $state)
        <option value="{{ $state->state }}">{{ $state->state }}</option>
        
        @endforeach
    </select>      
    <input type="hidden" name="shipper" value="{{ $courier->company }}" >

                        </td>
                    <td>
                        <a href="?shipper={{ $courier->company }}&state={{$first->state}}" id="link_{{ $courier->company }}" class="btn btn-primary" >Submit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
            <?php
            }
            else
            {
               ?>
               
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.city')}}</th>
                    <th>{{trans('file.Shipping')}}</th>
                    <th>{{trans('file.Fee')}}</th>
                </tr>
            </thead>
            <tbody>

                @foreach($lims_courier_all as $key=>$courier)
                <tr data-id="{{$courier->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $courier->city }}</td>
                    <td>
                        <input name="id[]" value="{{$courier->id}}" type="hidden">
                        <input name="shipping[]" value="{{$courier->shipping}}" class="form-control">
                    </td>
                    <td>
                        <input name="fee[]" value="{{$courier->fee}}" class="form-control">

                    </td>
                </tr>
                @endforeach
                
                     
            </tbody>
            <tfoot class="tfoot active">
                <th></th>
                <th></th>
                <th></th>
                <th><button type="submit" class="btn btn-primary" >Submit</button></th>
            </tfoot>   
                   
               <?php
            }
            ?>
        </table>
                <?php 
            if(isset($_GET['shipper'])){
            ?>
                {{ Form::close() }}
<?php } ?>
    </div>
</section>

<div id="create-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Add Shipping Method')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                  <div class="row">
                      <div class="col-md-12 form-group">
                          <label>{{trans('file.name')}} *</label>
                          <input type="text" id="shippingname" name="name" class="form-control">
                      </div>
                  </div>
                  <div class="form-group">
                      <button type="submit" id="createsubmit" class="btn btn-primary">{{trans('file.submit')}}</button>
                  </div>
            </div>
        </div>
    </div>
</div>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Update Courier')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
          </div>
          <div class="modal-body">
            <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
              {!! Form::open(['route' => ['couriers.update', 1], 'method' => 'put']) !!}
              <div class="row">
                  <div class="col-md-6 form-group">
                      <label>{{trans('file.name')}} *</label>
                      <input type="text" name="name" class="form-control">
                  </div>
                  <div class="col-md-6 form-group">
                      <label>{{trans('file.Phone Number')}} *</label>
                      <input type="text" name="phone_number" class="form-control">
                  </div>
                  <div class="col-md-12 form-group">
                      <label>{{trans('file.Address')}} *</label>
                      <input type="text" name="address" class="form-control">
                  </div>
                  <input type="hidden" name="id">
              </div>
              <div class="form-group">
                  <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
              </div>
              {{ Form::close() }}
          </div>
      </div>
  </div>
</div>


@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #shipping-methods").addClass("active");

        $(document).on('click', '.edit-btn', function() {
            $("#editModal input[name='id']").val($(this).data('id'));
            $("#editModal input[name='name']").val($(this).data('name'));
            $("#editModal input[name='phone_number']").val($(this).data('phone_number'));
            $("#editModal input[name='address']").val($(this).data('address'));
        });
        $(document).on('click', '#createsubmit', function() {
           var name = $("#shippingname").val();
           // Simulate an HTTP redirect:
         window.location.replace("/shippers/new/"+name);
        });
function setlink(company){
   var state =  $('#state_'+company).val();
   $('#link_'+company).attr('href','?shipper='+company+'&state='+state);
}
function confirmDelete() {
    if (confirm("Are you sure want to delete?")) {
        return true;
    }
    return false;
}

    var table = $('#courier-table').DataTable( {
        responsive: true,
        fixedHeader: {
            header: true,
            footer: true
        },
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
                'targets': [0, 2, 3]
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
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
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
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        courier_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                courier_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(courier_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'couriers/deletebyselection',
                                data:{
                                    courierIdArray: courier_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!courier_id.length)
                            alert('No courier is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ]
    } );

</script>
@endpush
