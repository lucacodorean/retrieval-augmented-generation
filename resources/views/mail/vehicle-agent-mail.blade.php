<x-mail::message>
{!! nl2br(e($agentText)) !!}

@if (count($vehicles) > 0)
## Vehicle details

<x-mail::table>
| Index | VIN | Brand | Model | HP | Fuel |
| :---- | :-- | :---- | :---- | -: | :--- |
@foreach ($vehicles as $result)
| {{ $result['record']['attributes']['index'] }} | {{ $result['record']['attributes']['vin'] }} | {{ $result['record']['relationships']['vehicle_details']['brand'] }} | {{ $result['record']['relationships']['vehicle_details']['model'] }} | {{ $result['record']['relationships']['vehicle_details']['hp'] }} | {{ $result['record']['relationships']['vehicle_details']['fuel'] }} |
@endforeach
</x-mail::table>
@endif
</x-mail::message>
