@if (count($rows))
    <div class="hidden overflow-x-auto md:block">
        <table class="w-full text-left text-sm">
            <thead><tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                @foreach ($headers as $header)<th scope="col" class="whitespace-nowrap px-3 py-3 font-semibold">{{ $header }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($rows as $row)<tr class="align-top hover:bg-slate-50">@foreach ($row as $cell)<td class="max-w-xs px-3 py-3 text-slate-700">{{ $cell }}</td>@endforeach</tr>@endforeach
            </tbody>
        </table>
    </div>
    <div class="space-y-3 md:hidden">
        @foreach ($rows as $row)
            <dl class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                @foreach ($headers as $index => $header)
                    <div class="grid grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] gap-3 py-1.5 text-sm">
                        <dt class="font-medium text-slate-500">{{ $header }}</dt><dd class="break-words text-right font-semibold text-slate-800">{{ $row[$index] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endforeach
    </div>
@else
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">{{ $empty }}</div>
@endif
