@extends('layouts.app')

@section('title', 'Semua Perjalanan Dinas')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Semua Perjalanan Dinas</h4>
        <p class="text-muted mb-0">Rekap perjalanan dinas dari seluruh pemohon.</p>
    </div>

    @include('perjalanan-dinas._rekap-table', ['cardTitle' => 'Semua Perjalanan Dinas'])
@endsection
