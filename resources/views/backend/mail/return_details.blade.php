<h1>Return Details</h1>
<p><strong>Reference: </strong>{{$return_data['reference_no']}}</p>
<h3>Order Table</h3>
<table style="border-collapse: collapse; width: 100%;">
	<thead>
		<th style="border: 1px solid #000; padding: 5px">#</th>
		<th style="border: 1px solid #000; padding: 5px">Product</th>
		<th style="border: 1px solid #000; padding: 5px">Qty</th>
		<th style="border: 1px solid #000; padding: 5px">Unit Price</th>
		<th style="border: 1px solid #000; padding: 5px">SubTotal</th>
	</thead>
	<tbody>
		@foreach($return_data['products'] as $key=>$product)
		<tr>
			<td style="border: 1px solid #000; padding: 5px">{{$key+1}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$product}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$return_data['qty'][$key].' '.$return_data['unit'][$key]}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{number_format((float)($return_data['total'][$key] / $return_data['qty'][$key]), $general_setting->decimal, '.', '')}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$return_data['total'][$key]}}</td>
		</tr>
		@endforeach
		<tr>
			<td colspan="2" style="border: 1px solid #000; padding: 5px"><strong>Total </strong></td>
			<td style="border: 1px solid #000; padding: 5px">{{$return_data['total_qty']}}</td>
			<td style="border: 1px solid #000; padding: 5px"></td>
			<td style="border: 1px solid #000; padding: 5px">{{$return_data['total_price']}}</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>Order Tax </strong> </td>
			<td style="border: 1px solid #000; padding: 5px">{{$return_data['order_tax'].'('.$return_data['order_tax_rate'].'%)'}}</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>Grand Total</strong></td>
			<td style="border: 1px solid #000; padding: 5px">{{$return_data['grand_total']}}</td>
		</tr>
	</tbody>
</table>

<p>Thank You</p>