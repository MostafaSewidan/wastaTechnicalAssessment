<h1>Delivery Details</h1>
<h3>Dear {{$delivery_data['customer']}},</h3>
@if($delivery_data['status'] == 2)
	<p>Your Product is Delivering.</p>
@else
	<p>Your Product is Delivered.</p>
@endif
<p><strong>Sale Reference: </strong>{{$delivery_data['sale_reference']}}</p>
<p><strong>Delivery Reference: </strong>{{$delivery_data['delivery_reference']}}</p>
<p><strong>Destination: </strong>{{$delivery_data['address']}}</p>
@if($delivery_data['delivered_by'])
<p><strong>Delivered By: </strong>{{$delivery_data['delivered_by']}}</p>
@endif
<p>Thank You</p>