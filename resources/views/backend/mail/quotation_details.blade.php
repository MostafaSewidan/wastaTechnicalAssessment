<h1>Quotation Details</h1>
<p><strong>Reference: </strong>{{$quotation_data['reference_no']}}</p>
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
		@foreach($quotation_data['products'] as $key=>$product)
		<tr>
			<td style="border: 1px solid #000; padding: 5px">{{$key+1}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$product}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$quotation_data['qty'][$key].' '.$quotation_data['unit'][$key]}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{number_format((float)($quotation_data['total'][$key] / $quotation_data['qty'][$key]), $general_setting->decimal, '.', '')}}</td>
			<td style="border: 1px solid #000; padding: 5px">{{$quotation_data['total'][$key]}}</td>
		</tr>
		@endforeach
		<tr>
			<td colspan="2" style="border: 1px solid #000; padding: 5px"><strong>Total </strong></td>
			<td style="border: 1px solid #000; padding: 5px">{{$quotation_data['total_qty']}}</td>
			<td style="border: 1px solid #000; padding: 5px"></td>
			<td style="border: 1px solid #000; padding: 5px">{{$quotation_data['total_price']}}</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>Order Tax </strong> </td>
			<td style="border: 1px solid #000; padding: 5px">{{$quotation_data['order_tax'].'('.$quotation_data['order_tax_rate'].'%)'}}</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>Order Discount </strong> </td>
			<td style="border: 1px solid #000; padding: 5px">
				@if($quotation_data['order_discount']){{$quotation_data['order_discount']}}
				@else 0 @endif
			</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>Shipping Cost</strong> </td>
			<td style="border: 1px solid #000; padding: 5px">
				@if($quotation_data['shipping_cost']){{$quotation_data['shipping_cost']}}
				@else 0 @endif
			</td>
		</tr>
		<tr>
			<td colspan="4" style="border: 1px solid #000; padding: 5px"><strong>Grand Total</strong></td>
			<td style="border: 1px solid #000; padding: 5px">{{$quotation_data['grand_total']}}</td>
		</tr>
	</tbody>
</table>

<p>Thank You</p>