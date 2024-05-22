@extends('backend.layout.main')
@section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif


<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{trans('file.Sale List')}}</h3>
            </div>
            {!! Form::open(['route' => 'sales.index', 'method' => 'get']) !!}
            <div class="row ml-1 mt-2">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{trans('file.Date')}}</strong></label>
                        <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required />
                        <input type="hidden" name="starting_date" value="{{$starting_date}}" />
                        <input type="hidden" name="ending_date" value="{{$ending_date}}" />
                    </div>
                </div>
                <div class="col-md-2 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                    <div class="form-group">
                        <label><strong>{{trans('file.Shipping Method')}}</strong></label>
                        <input type="hidden" id="warehouse_id" name="warehouse_id" value="0">
                            
                        <select  id="shipping-method" name="shipping_method" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                            <option value="0">{{trans('file.All')}}</option>
                            <option value="1">{{trans('file.Not Assigned')}}</option>
                            <?php
        $states = DB::table('places')->groupby('company')->get();
        ?>
        @foreach($states as $state)
        <option value="{{ $state->company }}">{{ $state->company }}</option>
        
        @endforeach
                        </select>
                        
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{trans('file.Sale Status')}}</strong></label>
                        <select id="sale-status" class="form-control" name="sale_status">
                            <option  value="0">{{trans('file.All')}}</option>
                            <option  value="1">{{trans('confirm')}}</option>
                            <option  value="2">{{trans('file.Due')}}</option>
                            <option  value="3">{{trans('file.Returned')}}</option>
                            <option  value="4">{{trans('Ready To Backup')}}</option>
                            <option  value="5">{{trans('Out of deliverey')}}</option>
                            <option  value="6">{{trans('Partial collected')}}</option>
                            <option  value="9">{{trans('Partial Returned')}}</option>
                            <option value="10">{{trans('Order delivered')}}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><strong>{{trans('file.Print Status')}}</strong></label>
                        <select id="print-status" class="form-control" name="print_status">
                            <option value="2">{{trans('file.All')}}</option>
                            <option value="1">{{trans('file.Printed')}}</option>
                            <option value="0">{{trans('file.Not Printed')}}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 mt-3">
                    <div class="form-group">
                        <button class="btn btn-primary" id="filter-btn" type="submit">{{trans('file.submit')}}</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>

                  


        @if(in_array("sales-add", $all_permission))
          
<div class="row mt-3">
    
        
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>{{trans('file.Sale Status')}}</strong></label>
                        <div class="row">
                        <div class="col-md-6">
                        <select id="bulksalestat" class="form-control" >
                            @if(\Auth::user()->role_id < 2){
                            <option value="1">{{trans('confirm')}}</option>
                            <option value="2">{{trans('file.Due')}}</option>
                            @endif
                            <option value="3">{{trans('file.Returned')}}</option>
                            <option value="4">{{trans('Ready To Backup')}}</option>
                            <option value="5">{{trans('Out of deliverey')}}</option>
                            <option value="6">{{trans('Partial collected')}}</option>
                            <option value="10">{{trans('Order Delivered')}}</option>
                        </select></div>
                        <div class="col-md-6">
                                                            <button id="updatesales" class="btn btn-primary">{{trans('file.Submit')}}</button>
</div></div>



                    </div>
                    </div>
    
        
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>{{trans('file.Shipping By')}}</strong></label>
                        <div class="row">
                        <div class="col-md-6">
                        <select id="bulkship" class="form-control" >
                        <?php
        $states = DB::table('places')->groupby('company')->get();
        ?>
        @foreach($states as $state)
        <option value="{{ $state->company }}">{{ $state->company }}</option>
        
        @endforeach
                        </select></div>
                        <div class="col-md-6">
                                                            <button id="updatesalesship" class="btn btn-primary">{{trans('file.Submit')}}</button>
</div></div>



                    </div>
                    </div>
    
                </div>
        @endif
    </div>

    <div class="table-responsive">
        <table id="sale-table" class="table sale-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.reference')}}</th>
                    <th>{{trans('file.customer')}}</th>
                    <th>{{trans('file.phone')}}</th>
                    <th>{{trans('file.phone2')}}</th>
                    <th>{{trans('file.address')}}</th>
                    <th>{{trans('file.state')}}</th>
                    <th>{{trans('file.city')}}</th>
                    <th>{{trans('file.products')}}</th>
                    <th>{{trans('file.Sale Status')}}</th>
                    <th>{{trans('file.Shipping Cost')}}</th>
                    <th>{{trans('file.By')}}</th>
                    <th>{{trans('file.shipping_method')}}</th>
                    <th>{{trans('file.Print')}}</th>
                    
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <th></th>
                <th>{{trans('file.Total')}}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

<div id="sale-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button id="print-btn" type="button" class="btn btn-default btn-sm"><i class="dripicons-print"></i> {{trans('file.Print')}}</button>

                        {{ Form::open(['route' => 'sale.sendmail', 'method' => 'post', 'class' => 'sendmail-form'] ) }}
                            <input type="hidden" name="sale_id">
                            <button class="btn btn-default btn-sm d-print-none"><i class="dripicons-mail"></i> {{trans('file.Email')}}</button>
                        {{ Form::close() }}
                    </div>
                    <div class="col-md-6 d-print-none">
                        <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="col-md-12">
                        <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">{{$general_setting->site_title}}</h3>
                    </div>
                    <div class="col-md-12 text-center">
                        <i style="font-size: 15px;">{{trans('file.Sale Details')}}</i>
                    </div>
                </div>
            </div>
            <div id="sale-content" class="modal-body">
            </div>
            <br>
            <table class="table table-bordered product-sale-list">
                <thead>
                    <th>#</th>
                    <th>{{trans('file.product')}}</th>
                    <th>{{trans('file.Batch No')}}</th>
                    <th>{{trans('file.Qty')}}</th>
                    <th>{{trans('file.Returned')}}</th>
                    <th>{{trans('file.Unit Price')}}</th>
                    <th>{{trans('file.Tax')}}</th>
                    <th>{{trans('file.Discount')}}</th>
                    <th>{{trans('file.image')}}</th>
                    <th>{{trans('file.Subtotal')}}</th>
                </thead>
                <tbody>
                </tbody>
            </table>
            <div id="sale-footer" class="modal-body"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="saleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saleModalLabel">Sale Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="saleDetails">
                <!-- Sale details will be displayed here -->
            </div>
        </div>
    </div>
</div>



<div id="view-log" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.All')}} {{trans('file.Logs')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover viewlogtbl">
                    <thead>
                        <tr>
                            <th>{{trans('file.Log')}}</th>
                            <th>{{trans('file.Time')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#custom-sale").siblings('a').attr('aria-expanded','true');
    $("ul#custom-sale").addClass("show");
    $("ul#custom-sale #custom-sale-list-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        if(localStorage.getItem("message")) {
            alert(localStorage.getItem("message"));
            localStorage.removeItem("message");
        }

        numberOfInvoice = <?php echo json_encode($numberOfInvoice)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $general_setting->package_id)}}',
            success: function(data) {
                if(data['number_of_invoice'] > 0 && data['number_of_product'] <= numberOfInvoice) {
                    $("a.add-sale-btn").addClass('d-none');
                }
            }
        });
    @endif


    var columns = [{"data": "key"}, {"data": "date"}, {"data": "reference_no"},  {"data": "customer_name"}, {"data": "phone_number"}, {"data": "phone_number2"}, {"data": "address"}, {"data": "state"}, {"data": "city"}, {"data": "products"}, {"data": "sale_status"}, {"data": "shipping_cost"}, {"data": "user_name"}, {"data": "shipping_method"}, {"data": "printed"}, {"data": "options"}];
    
    
    var all_permission = <?php echo json_encode($all_permission) ?>;
    
    var sale_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var sale_status = <?php if(isset($_GET['sale_status'])){echo $_GET['sale_status'];}else{ echo 0;} ?>;
    var showdeleted = <?php if(isset($_GET['trash'])){echo 1;}else{ echo 0;} ?>;
    var user_id = <?php if(isset($_GET['user'])){echo $_GET['user'];}else{ echo 0;} ?>;
    var shipping_method = <?php if(isset($_GET['shipping_method'])){echo '"'.$_GET['shipping_method'].'"';}else{ echo 0;} ?>;
    var customer_id = <?php if(isset($_GET['customer'])){echo $_GET['customer'];}else{ echo 0;} ?>;
    var print_status = <?php if(isset($_GET['print_status'])){echo $_GET['print_status'];}else{ echo 2;} ?>;
    var payment_status = <?php echo json_encode($payment_status); ?>;
    var current_date = <?php echo json_encode(date("Y-m-d")) ?>;
    var payment_date = [];
    var payment_reference = [];
    var paid_amount = [];
    var paying_method = [];
    var payment_id = [];
    var payment_note = [];
    var account = [];
    var deposit;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#warehouse_id").val(warehouse_id);
    $("#sale-status").val(sale_status);
    $("#shipping-method").val(shipping_method);
    $("#print-status").val(print_status);
    $("#payment-status").val(payment_status);
console.log(print_status);
    $(".daterangepicker-field").daterangepicker({
      callback: function(startDate, endDate, period){
        var starting_date = startDate.format('YYYY-MM-DD');
        var ending_date = endDate.format('YYYY-MM-DD');
        var title = starting_date + ' To ' + ending_date;
        $(this).val(title);
        $('input[name="starting_date"]').val(starting_date);
        $('input[name="ending_date"]').val(ending_date);
      }
    });

    $(".gift-card").hide();
    $(".card-element").hide();
    $("#cheque").hide();
    $('#view-payment').modal('hide');

    $('.selectpicker').selectpicker('refresh');

    $(document).on("click", "tr.sale-link td:not(:first-child, :last-child)", function() {
        var sale = $(this).parent().data('sale');
         $.ajax({
                url: '{{route("customsales.getsale",'')}}/' + sale,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Handle response data and display in a pop-up
                    console.log(response);
                    // Example: Open a modal and display sale details
                    $('#saleModal').modal('show');
                    // Display sale details and associated products
                    $('#saleDetails').html('<h3>Reference No : ' + response.reference_no + '</h3><br>');
                    $('#saleDetails').append('<h4>Date : ' + response.date + '</h4><br>');
                    $('#saleDetails').append('<h4>Customer : ' + response.customer + '</h4><br>');
                    $('#saleDetails').append('<h4>Customer Phone : ' + response.phone + '</h4><br>');
                    $('#saleDetails').append('<h4>Customer Phone 2 : ' + response.phone2 + '</h4><br>');
                    $('#saleDetails').append('<h4>Created By : ' + response.user + '</h4><br>');
                    $('#saleDetails').append('<h4>Total Items : ' + response.items + '</h4><br>');
                    $('#saleDetails').append('<h4>Total Qty : ' + response.total_qty + '</h4><br>');
                    $('#saleDetails').append('<h4>Shipping Cost : ' + response.shipping_cost + '</h4><br>');
                    var body = '';
                    $.each(response.products, function(index, product) {
                        var imgs = '';
                    $.each(product.images, function(index, image) {
                        imgs += '<img src="../images/product/'+ image +'" style="height:100px;"/>';
                    });

                        body += '<tr><td>' + product.name + '</td><td>' + product.describtion + '</td><td>' + product.qty + '</td><td>'+ imgs +'</td></tr>';
                    });
                    $('#saleDetails').append('<div class="table-responsive ml-2"><table class="table table-hover"><thead><tr><th>Name</th><th>Describtion</th><th>Qty</th><th>Images</th></tr></thead><tbody>'+ body +'</tbody></table></div>');
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
    });

    $(document).on("click", ".view", function(){
        var sale = $(this).parent().parent().parent().parent().parent().data('sale');
         $.ajax({
                url: '{{route("customsales.getsale",'')}}/' + sale,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Handle response data and display in a pop-up
                    console.log(response);
                    // Example: Open a modal and display sale details
                    $('#saleModal').modal('show');
                    // Display sale details and associated products
                    $('#saleDetails').html('<h3>Reference No : ' + response.reference_no + '</h3><br>');
                    $('#saleDetails').append('<h4>Date : ' + response.date + '</h4><br>');
                    $('#saleDetails').append('<h4>Customer : ' + response.customer + '</h4><br>');
                    $('#saleDetails').append('<h4>Created By : ' + response.user + '</h4><br>');
                    $('#saleDetails').append('<h4>Total Items : ' + response.items + '</h4><br>');
                    $('#saleDetails').append('<h4>Total Qty : ' + response.total_qty + '</h4><br>');
                    $('#saleDetails').append('<h4>Shipping Cost : ' + response.shipping_cost + '</h4><br>');
                    $('#saleDetails').append('<div class="table-responsive ml-2"><table><thead><tr><th>Name</th><th>Describtion</th><th>Qty</th><th>Images</th></tr></thead>');
                    $('#saleDetails').append('<tbody>');
                    $.each(response.products, function(index, product) {
                        $('#saleDetails').append('<tr>');
                        $('#saleDetails').append('<td>' + product.name + '</td>');
                        $('#saleDetails').append('<td>' + product.describtion + '</td>');
                        $('#saleDetails').append('<td>' + product.qty + '</td>');
                        $('#saleDetails').append('<td>');
                    $.each(product.images, function(index, image) {
                        $('#saleDetails').append('<img src="images/product/'+ image +'" style="height:100px;"/>');
                    });
                        $('#saleDetails').append('</td>');
                        $('#saleDetails').append('</tr>');
                    });
                    $('#saleDetails').append('</tbody></table></div>');
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
    });
    var filterBtn = document.getElementById('filter-btn');

    var urlParams = new URLSearchParams(window.location.search);
    var fileCsvParam = urlParams.get('filecsv');

    if (fileCsvParam) {
        var hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'filecsv';
        hiddenInput.value = fileCsvParam;
        filterBtn.appendChild(hiddenInput);
    }
    $(document).on("click", ".bulkprint", function(){
                         var arr = [];
        
                        sale_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var sale = $(this).closest('tr').data('sale');
                                sale_id[i-1] = sale;
                                arr.push(sale);

                            }
                        });
                        $("#ides").val(arr);
                        $("#bulkinvoice").submit();

        
    });

    $(document).on("click", "#print-btn", function() {
        var divContents = document.getElementById("sale-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body>');
        a.document.write('<style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function(){a.close();},10);
        a.print();
    });



    $(document).on("click", "#updatesalesship", function() {
       var status = $("#bulkship").val();
                    if(user_verified == '1') {
                        sale_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var sale = $(this).closest('tr').data('sale');
                                sale_id[i-1] = sale;
                            }
                        });
                        if(sale_id.length) {
                            $.ajax({
                                type:'POST',
                                url:'sales/updateShipBySelection',
                                data:{
                                    saleIdArray: sale_id,
                                    value:status
                                },
                                success:function(data){
                                    alert(data);
                                    $('#sale-table').DataTable().ajax.reload();
                                }
                            });
                        }
                        else if(!sale_id.length)
                            alert('Nothing is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
    });

    $(document).on("click", "#updatesales", function() {
       var status = $("#bulksalestat").val();
                    if(user_verified == '1') {
                        sale_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var sale = $(this).closest('tr').data('sale');
                                sale_id[i-1] = sale;
                            }
                        });
                        if(sale_id.length) {
                            $.ajax({
                                type:'POST',
                                url:'updatesaleBySelection',
                                data:{
                                    saleIdArray: sale_id,
                                    value:status
                                },
                                success:function(data){
                                    alert(data);
                                    $('#sale-table').DataTable().ajax.reload();
                                }
                            });
                        }
                        else if(!sale_id.length)
                            alert('Nothing is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
    });

    $(document).on("click", "table.sale-list tbody .add-payment", function() {
        $("#cheque").hide();
        $(".gift-card").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        $('.selectpicker').selectpicker('refresh');
        rowindex = $(this).closest('tr').index();
        deposit = $('table.sale-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.deposit').val();
        var sale_id = $(this).data('id').toString();
        var balance = $('table.sale-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(12)').text();
        balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="paying_amount"]').val(balance);
        $('#add-payment input[name="balance"]').val(balance);
        $('input[name="amount"]').val(balance);
        $('input[name="sale_id"]').val(sale_id);
    });

    $(document).on("click", "table.sale-list tbody .viewlog", function(event) {
        rowindex = $(this).closest('tr').index();
        var id = $(this).data('id').toString();
        console.log('{{route("customsale.getsalelog",'')}}/' + id);
        $.get('{{route("customsale.getsalelog",'')}}/' + id, function(data) {
            $(".viewlogtbl tbody").remove();
            var newBody = $("<tbody>");
            text  = data[0];
            time = data[1];
            $.each(text, function(index) {
                var newRow = $("<tr>");
                var cols = '';

                cols += '<td>' + text[index] + '</td>';
                cols += '<td>' + time[index] + '</td></tr>';
                newRow.append(cols);
                newBody.append(newRow);
        });
                $("table.viewlogtbl").append(newBody);
            $('#view-log').modal('show');
        });
    });






    <?php 
if (isset($_GET['filecsv'])) {
    ?>
    var references = $('#references').val();
    console.log(references);
    <?php
}
    ?>
console.log(all_permission);
    $('#sale-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"{{route('customsales.getdata')}}",
            data:{
                all_permission: all_permission,
                starting_date: starting_date,
                ending_date: ending_date,
                warehouse_id: warehouse_id,
                <?php 
if (isset($_GET['filecsv'])) {
    ?>
                references: references,
    <?php
}
    ?>
                sale_status: sale_status,
                user_id: user_id,
                shipping_method: shipping_method,
                showdeleted: showdeleted,
                customer_id: customer_id,
                print_status: print_status,
                payment_status: payment_status
            },
            dataType: "json",
            type:"post"
        },
        /*rowId: function(data) {
              return 'row_'+data['id'];
        },*/
        "createdRow": function( row, data, dataIndex ) {

            //alert(data);
            $(row).addClass('sale-link');
            $(row).attr('data-sale', data['sale']);
        },
        "columns": columns,
        'language': {

            'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
             "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{trans("file.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        order:[['1', 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 3, 4, 5, 6, 7, 10, 11, 12]
            },
            { "Targets": [ 1 ], "Sortable": true },
            { "Targets": [ 2 ], "Sortable": true },
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
        'lengthMenu': [[10, 25, 50, 100, 250, 300, 500, -1], [10, 25, 50, 100, 250, 300, 500, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        sale_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var sale = $(this).closest('tr').data('sale');
                                sale_id[i-1] = sale;
                            }
                        });
                        if(sale_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'sales/deletebyselection',
                                data:{
                                    saleIdArray: sale_id
                                },
                                success:function(data){
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!sale_id.length)
                            alert('Nothing is selected!');
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
        ],
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    } );

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 11 ).footer() ).html(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
                   }
        else {
            $( dt_selector.column( 11 ).footer() ).html(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
                   }
    }



    if(all_permission.indexOf("sales-delete") == -1)
        $('.buttons-delete').addClass('d-none');

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

    function confirmPaymentDelete() {
        if (confirm("Are you sure want to delete? If you delete this money will be refunded.")) {
            return true;
        }
        return false;
    }

</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
