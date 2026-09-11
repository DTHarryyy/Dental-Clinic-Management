@extends('layouts.app')
@section('page_title', 'Settings - Public Website')

@section('content')
<div class="mb-6"><h1 class="page-title">Settings</h1><p class="page-subtitle">Configure public website content.</p></div>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
    <div class="xl:col-span-1">@include('settings._nav')</div>
    <div class="xl:col-span-3">
        <div class="responsive-card overflow-hidden">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="fa-solid fa-globe"></i></div><div><h2 class="font-semibold text-base text-slate-800">Public Website</h2><p class="mt-0.5 text-xs text-slate-500">Landing page, metadata, privacy, and terms.</p></div></div>
            <form action="{{ route('settings.public.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">@csrf @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Hero eyebrow</label><input name="hero_eyebrow" value="{{ old('hero_eyebrow', $site->hero_eyebrow) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Hero title</label><input name="hero_title" value="{{ old('hero_title', $site->hero_title) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Hero subtitle</label><textarea name="hero_subtitle" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">{{ old('hero_subtitle', $site->hero_subtitle) }}</textarea></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Hero image</label><input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">@if($site->hero_image_path)<label class="mt-2 flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="remove_hero_image" value="1"> Remove current image</label>@endif</div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Open Graph image</label><input type="file" name="og_image" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">@if($site->og_image_path)<label class="mt-2 flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="remove_og_image" value="1"> Remove current image</label>@endif</div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">About heading</label><input name="about_heading" value="{{ old('about_heading', $site->about_heading) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Map embed URL</label><input name="map_embed_url" value="{{ old('map_embed_url', $site->map_embed_url) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">About body</label><textarea name="about_body" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">{{ old('about_body', $site->about_body) }}</textarea></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Why choose us</label><textarea name="benefits" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">{{ old('benefits', collect($site->benefits)->join("\n")) }}</textarea><p class="mt-1 text-xs text-slate-500">One benefit per line.</p></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Facebook URL</label><input name="facebook_url" value="{{ old('facebook_url', $site->facebook_url) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Instagram URL</label><input name="instagram_url" value="{{ old('instagram_url', $site->instagram_url) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Meta title</label><input name="meta_title" value="{{ old('meta_title', $site->meta_title) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Meta description</label><input name="meta_description" value="{{ old('meta_description', $site->meta_description) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Privacy version</label><input name="privacy_policy_version" value="{{ old('privacy_policy_version', $site->privacy_policy_version) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Terms version</label><input name="terms_version" value="{{ old('terms_version', $site->terms_version) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Privacy policy</label><textarea name="privacy_policy" rows="6" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">{{ old('privacy_policy', $site->privacy_policy) }}</textarea></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Terms</label><textarea name="terms" rows="6" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">{{ old('terms', $site->terms) }}</textarea></div>
                </div>
                <div class="flex justify-end border-t border-slate-100 pt-5"><button class="primary-action"><i class="fa-solid fa-floppy-disk"></i> Save website</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
