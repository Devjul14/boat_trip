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
                    @foreach($tickets as $ticket)
                        <tr>
                            <td class="px-6 py-4">{{ $ticket['hotel'] }}</td>
                            <td class="px-6 py-4">{{ $ticket['passengers'] }}</td>
                            <td class="px-6 py-4">${{ number_format($ticket['price'], 2) }}</td>
                        </tr>
                        @php
                            // Get expense data using the ticket ID
                            $ticketExpenses = App\Models\Expenses::whereHas('tickets', function($query) use ($ticket) {
                                $query->where('ticket_id', $ticket['id']);
                            })->with('expenseType')->get();
                            $totalExpenses = 0;
                        @endphp
                        
                        @if($ticketExpenses->count() > 0)
                            <tr>
                                <td colspan="3" class="px-0 py-0">
                                    <div class="bg-gray-900 p-4 m-4 rounded-lg">
                                        <h3 class="text-lg font-medium mb-3">Expenses for this ticket:</h3>
                                        <table class="w-full">
                                            <thead>
                                                <tr class="bg-gray-800 rounded-t-lg">
                                                    <th class="px-4 py-2 text-left font-medium">Expense Type</th>
                                                    <th class="px-4 py-2 text-left font-medium">Amount per Person</th>
                                                    <th class="px-4 py-2 text-left font-medium">Total</th>
                                                    <th class="px-4 py-2 text-left font-medium">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-800">
                                                @foreach($ticketExpenses as $expense)
                                                    @php 
                                                        $totalForExpense = $expense->amount * $ticket['passengers'];
                                                        $totalExpenses += $totalForExpense;
                                                    @endphp
                                                    <tr>
                                                        <td class="px-4 py-3">{{ $expense->expenseType->name }}</td>
                                                        <td class="px-4 py-3">${{ number_format($expense->amount, 2) }}</td>
                                                        <td class="px-4 py-3">${{ number_format($totalForExpense, 2) }}</td>
                                                        <td class="px-4 py-3">{{ $expense->notes }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr class="bg-gray-800">
                                                    <td colspan="2" class="px-4 py-3 font-medium">Total Expenses:</td>
                                                    <td colspan="2" class="px-4 py-3 font-medium">${{ number_format($totalExpenses, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-700">
                    <tr>
                        <td colspan="2" class="px-6 py-3 font-medium">Total Ticket Price:</td>
                        <td class="px-6 py-3 font-medium">${{ number_format($tickets->sum('price'), 2) }}</td>
                    </tr>
                    @php
                        $totalAllExpenses = 0;
                        foreach($tickets as $ticket) {
                            $ticketExpenses = App\Models\Expenses::whereHas('tickets', function($query) use ($ticket) {
                                $query->where('ticket_id', $ticket['id']);
                            })->get();
                            
                            foreach($ticketExpenses as $expense) {
                                $totalAllExpenses += $expense->amount * $ticket['passengers'];
                            }
                        }
                    @endphp
                    <tr>
                        <td colspan="2" class="px-6 py-3 font-medium">Total Expenses:</td>
                        <td class="px-6 py-3 font-medium">${{ number_format($totalAllExpenses, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-600">
                        <td colspan="2" class="px-6 py-3 font-bold text-lg">Grand Total:</td>
                        <td class="px-6 py-3 font-bold text-lg">${{ number_format($tickets->sum('price') + $totalAllExpenses, 2) }}</td>
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