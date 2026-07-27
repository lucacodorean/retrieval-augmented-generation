<x-mail::message>
{{ $agentText }}

@if (count($vehicles) > 0)
@php
    $escapeTableCell = static function (mixed $value): string {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', (string) $value);
        $punctuation = str_split('\\!#$%()*+,-./:;=?@[]^_`{|}~');

        return str_replace(
            $punctuation,
            array_map(static fn (string $character): string => '\\'.$character, $punctuation),
            $value,
        );
    };
@endphp
## Vehicle details

<x-mail::table>
| Index | VIN | Brand | Model | HP | Fuel |
| :---- | :-- | :---- | :---- | -: | :--- |
@foreach ($vehicles as $result)
| {{ $escapeTableCell($result['record']['attributes']['index']) }} | {{ $escapeTableCell($result['record']['attributes']['vin']) }} | {{ $escapeTableCell($result['record']['relationships']['vehicle_details']['brand']) }} | {{ $escapeTableCell($result['record']['relationships']['vehicle_details']['model']) }} | {{ $escapeTableCell($result['record']['relationships']['vehicle_details']['hp']) }} | {{ $escapeTableCell($result['record']['relationships']['vehicle_details']['fuel']) }} |
@endforeach
</x-mail::table>
@endif
</x-mail::message>
