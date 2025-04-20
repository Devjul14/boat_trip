<div class="space-y-4">
    <div class="text-lg font-medium">{{ $trip->date }}</div>
    
    @if($tickets->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full rounded-lg overflow-hidden">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-yellow-600">Hotel/Other</th>
                        <th class="px-4 py-2 text-center text-yellow-600">Number of Passengers</th>
                        <th class="px-4 py-2 text-right text-yellow-600">Excursion Charge ($)</th>
                        <th class="px-4 py-2 text-right text-yellow-600">Boat Charge ($)</th>
                        <th class="px-4 py-2 text-right text-yellow-600">Charter Charge ($)</th>
                        <th class="px-4 py-2 text-right text-yellow-600">Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        @php
                            $total = $ticket['excursion_charge'] + $ticket['boat_charge'] + $ticket['charter_charge'];
                        @endphp
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $ticket['hotel'] }}</td>
                            <td class="px-4 py-2 text-center">{{ $ticket['passengers'] }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($ticket['excursion_charge'], 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($ticket['boat_charge'], 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($ticket['charter_charge'], 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ number_format($total, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t">
                        <td colspan="5" class="px-4 py-2 text-right font-medium text-amber-600">Total Amount</td>
                        <td class="px-4 py-2 text-right font-bold">${{ number_format($tickets->sum(function($ticket) {
                            return $ticket['excursion_charge'] + $ticket['boat_charge'] + $ticket['charter_charge'];
                        }), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="text-sm text-amber-600">Total Tickets: {{ $tickets->count() }} | Total Passengers: {{ $tickets->sum('passengers') }}</div>
    @else
        <div class="p-4 rounded-lg text-amber-600">No tickets found for this trip.</div>
    @endif
</div>