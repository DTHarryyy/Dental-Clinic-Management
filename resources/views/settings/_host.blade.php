{{-- Settings has no standalone page. A direct/bookmarked visit to any /settings/* URL
     lands here: the normal app shell, with settings-popover.js reading the marker below
     and opening the dialog straight onto that tab. --}}
@extends('layouts.app')
@section('page_title', 'Settings')

@section('content')
<div data-settings-autoopen="{{ $autoOpenUrl }}"></div>
@endsection
