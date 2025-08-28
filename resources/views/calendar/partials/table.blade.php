        {{-- Tabella lezioni di OGGI --}}
        <div class="card" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
            <div style="padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:600;">
                Lezioni di oggi
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;background:#fcfcfd;">
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Ora</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Sala</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Operatore</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Capienza</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Iscritti</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Stato</th>
                            {{-- eventuale azione (prenota/gestisci) la metteremo dopo --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lessons as $lesson)
                            <tr>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">
                                    {{ $lesson->starts_at?->format('H:i') }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->room?->name ?? '—' }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->operator?->full_name ?? '—' }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->max_clients }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->clients_count ?? $lesson->clients->count() }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    @if ($lesson->canceled)
                                        <span
                                            style="display:inline-block;padding:.2rem .5rem;border-radius:9999px;background:#fee2e2;color:#991b1b;font-weight:600;">
                                            Cancellata
                                        </span>
                                    @elseif($lesson->starts_at->isPast())
                                        <span
                                            style="display:inline-block;padding:.2rem .5rem;border-radius:9999px;background:#e5e7eb;color:#374151;font-weight:600;">
                                            Conclusa
                                        </span>
                                    @else
                                        <span
                                            style="display:inline-block;padding:.2rem .5rem;border-radius:9999px;background:#dcfce7;color:#166534;font-weight:600;">
                                            Attiva
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:16px;color:#6b7280;text-align:center;">
                                    Nessuna lezione per oggi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
