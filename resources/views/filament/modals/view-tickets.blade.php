<div class="max-w-5xl mx-auto space-y-4 bg-gray-900 text-white p-6">
    <h1 class="text-2xl font-bold mb-6">{{$trip->tripType->name}}</h1>   
    <div class="text-lg font-medium mb-4">{{ \Carbon\Carbon::parse($trip->date)->format('d F Y') }}</div>
  
@if($tickets->count() > 0)
    <div class="ticket-details">
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-700">
                        <th class="px-6 py-3 text-left font-medium">Hotel/Other</th>
                        <th class="px-6 py-3 text-left font-medium">Number of Passengers</th>
                        <th class="px-6 py-3 text-left font-medium">Price ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                        <tr>
                            <td class="px-6 py-4">{{ $hotel}}</td>
                            <td class="px-6 py-4">{{ $passengers }}</td>
                            <td class="px-6 py-4">${{ number_format($total_usd, 2) }}</td>
                        </tr>
                </tbody>
                <tfoot class="bg-gray-700">
                    <tr>
                        <td colspan="2" class="px-6 py-3 font-medium">Total Ticket Price:</td>
                        <td class="px-6 py-3 font-medium">${{ number_format($total_usd, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-600">
                        <td colspan="2" class="px-6 py-3 font-bold text-lg">Grand Total:</td>
                        <td class="px-6 py-3 font-bold text-lg">${{ number_format($total_usd 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg p-4 mt-4 text-center">
        Total Tickets: {{ $tickets->count() }} | Total Passengers: {{ $tickets->sum('passengers') }}
    </div>
@else
    <div class="bg-gray-800 rounded-lg p-6 text-center text-gray-400">
        No tickets found for this trip.
    </div>
@endif
</div>