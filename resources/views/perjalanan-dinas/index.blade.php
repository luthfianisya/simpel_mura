@extends('layouts.app')

@section('title', 'Semua Perjalanan Dinas')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Semua Perjalanan Dinas</h4>
        <p class="text-muted mb-0">Rekap perjalanan dinas dari seluruh pemohon.</p>
    </div>

    @include('perjalanan-dinas._rekap-table', ['cardTitle' => 'Semua Perjalanan Dinas'])
@endsection
