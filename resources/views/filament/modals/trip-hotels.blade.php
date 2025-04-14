<div class="p-4">
    <div class="mb-4">
        <p><strong>Trip Date:</strong> {{ $trip->date }}</p>
        <p><strong>Trip Type:</strong> {{ $trip->tripType->name ?? '-' }}</p>
        <p><strong>Total Hotels:</strong> {{ $hotels->count() }}</p>
        <p><strong>Total Passengers:</strong> {{ $hotels->sum('passengers') }}</p>
    </div>
    
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Hotel</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Passengers</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total (USD)</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Payment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($hotels as $hotel)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">{{ $hotel['hotel'] }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $hotel['passengers'] }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${{ number_format($hotel['total_usd'], 2) }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $hotel['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($hotel['payment_status']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>