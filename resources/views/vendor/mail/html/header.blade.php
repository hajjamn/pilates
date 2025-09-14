@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <a href="{{ config('app.url') }}" style="display:inline-block;">
                    <img src="{{ asset('img/logo-mail.png') }}" alt="{{ config('app.name') }}" height="40"
                        style="height:40px;">
                </a>
            @else
                {{ $slot }}
            @endif
        </a>
    </td>
</tr>
