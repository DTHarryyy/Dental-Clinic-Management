@include('settings._partials._flash')

<div class="settings-tab-body">
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-base text-slate-800">Payment Channels</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Choose which payment methods are offered, and where patients send GCash, Maya, or bank transfers before submitting a payment for verification</p>
                </div>
            </div>

            <form action="{{ route('settings.payment-channels.update') }}" method="POST" enctype="multipart/form-data" data-ajax-form data-loading-text="Saving..." class="p-6 space-y-6">
                @csrf
                @method('PUT')

                @foreach ($methods as $method)
                    @php $channel = $channels->get($method->value); @endphp
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="flex items-center gap-2 font-semibold text-slate-800">
                                <i class="fa-solid {{ $method->icon() }} text-slate-400"></i>{{ $method->label() }}
                            </h3>
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <input type="checkbox" name="channels[{{ $method->value }}][is_enabled]" value="1" {{ old("channels.{$method->value}.is_enabled", $channel?->is_enabled) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-200">
                                Enabled
                            </label>
                        </div>

                        @if ($method->isRemoteTransfer())
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @if ($method->value === 'Bank Transfer')
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bank name</label>
                                        <input type="text" name="channels[{{ $method->value }}][bank_name]" value="{{ old("channels.{$method->value}.bank_name", $channel?->bank_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm">
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Account name</label>
                                    <input type="text" name="channels[{{ $method->value }}][account_name]" value="{{ old("channels.{$method->value}.account_name", $channel?->account_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Account / mobile number</label>
                                    <input type="text" name="channels[{{ $method->value }}][account_number]" value="{{ old("channels.{$method->value}.account_number", $channel?->account_number) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Instructions <span class="font-normal text-slate-400">(optional)</span></label>
                                    <textarea name="channels[{{ $method->value }}][instructions]" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm resize-none">{{ old("channels.{$method->value}.instructions", $channel?->instructions) }}</textarea>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">QR code <span class="font-normal text-slate-400">(optional)</span></label>
                                    @if ($channel?->qr_url)
                                        <div class="mb-2 flex items-center gap-3">
                                            <img src="{{ $channel->qr_url }}" alt="{{ $method->label() }} QR" class="h-16 w-16 rounded-lg border border-slate-200 object-contain p-1">
                                            <label class="flex items-center gap-1.5 text-xs text-slate-500"><input type="checkbox" name="channels[{{ $method->value }}][remove_qr]" value="1" class="rounded text-red-500 focus:ring-red-200"> Remove</label>
                                        </div>
                                    @endif
                                    <input type="file" name="channels[{{ $method->value }}][qr]" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                </div>
                            </div>
                        @else
                            <p class="mt-3 text-xs text-slate-500">
                                @if ($method->requiresReference())
                                    Recorded by staff with a reference number when payment is taken.
                                @else
                                    No receiving details needed — settled directly at the clinic.
                                @endif
                            </p>
                        @endif
                    </div>
                @endforeach

                <div class="flex justify-end pt-2 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Save Payment Channels
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
