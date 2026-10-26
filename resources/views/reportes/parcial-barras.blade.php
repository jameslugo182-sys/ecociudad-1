<h2>{{ $titulo }}</h2>
<table>
    <thead>
        <tr>
            <th scope="col">Concepto</th>
            <th scope="col">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($datos as $item)
            <tr>
                <td>{{ $item['label'] }}</td>
                <td>{{ $item['total'] }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="empty">Sin datos en el periodo.</td></tr>
        @endforelse
    </tbody>
</table>
