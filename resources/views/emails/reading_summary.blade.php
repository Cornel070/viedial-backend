@component('mail::message')
<h1>{{ $period }} Remote Monitoring Summary</h1>
	Dear {{$user->name}}, <br/><br/>
	Kindly find below your Blood Pressure and Blood Sugar Remote Monitoring Readings for this {{ $period }}.

	@component('mail::panel')
	<h2>Blood Pressure</h2><br>
	<table class="table table-responsive">
		<thead>
			<th>Date</th>
			<th>Reading</th>
			<th>Level</th>
		</thead>
		<tbody>
			@foreach($bp_records as $bp)
			<tr>
				<td>{{ $bp->created_at->format('l jS F Y \a\t g:i a') }}</td>
				<td>{{ $bp->systolic }}/{{ $bp->diastolic }}</td>
				<td>
					@php
						$emoji = "<span style='font-size:50px;'>&#128528;</span>";
						if($bp->level == 'Normal'){
							$emoji = "<span style='font-size:50px;'>&#128522;</span>"
						}elseif ($bp->level == 'Slightly High') {
							$emoji = "<span style='font-size:50px;'>&#128533;</span>"
						}elseif ($bp->level == 'Really High') {
							$emoji = "<span style='font-size:50px;'>&#128543;</span>"
						}elseif ($bp->level == 'Dangerously High') {
							$emoji = "<span style='font-size:50px;'>&#128542;</span>"
						}
					@endphp
					{{ $bp->level }} {!! $emoji !!}
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<h2>Blood Sugar</h2><br>
	<table class="table table-responsive">
		<thead>
			<th>Date</th>
			<th>Reading</th>
			<th>Level</th>
		</thead>
		<tbody>
			@foreach($bs_records as $bs)
			<tr>
				<td>{{ $bs->created_at->format('l jS F Y \a\t g:i a') }}</td>
				<td>{{ $bs->blood_sugar_val }}</td>
				<td>
					@php
						$emoji = "<span style='font-size:50px;'>&#128528;</span>";
						if($bs->level == 'Normal'){
							$emoji = "<span style='font-size:50px;'>&#128522;</span>"
						}elseif ($bs->level == 'Low') {
							$emoji = "<span style='font-size:50px;'>&#128532;</span>"
						}elseif ($bs->level == 'Dangerously Low') {
							$emoji = "<span style='font-size:50px;'>&#128542;</span>"
						}elseif ($bs->level == 'Very very high') {
							$emoji = "<span style='font-size:50px;'>&#128553;</span>"
						}elseif ($bs->level == 'Very High') {
							$emoji = "<span style='font-size:50px;'>&#128543;</span>"
						}elseif ($bs->level == 'Slightly High') {
							$emoji = "<span style='font-size:50px;'>&#128533;</span>"
						}elseif ($bs->level == 'High') {
							$emoji = "<span style='font-size:50px;'>&#128543;</span>"
						}
					@endphp
					{{ $bp->level }} {!! $emoji !!}
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
	@endcomponent

Thanks,<br>
Viedial Healthcare
@endcomponent
