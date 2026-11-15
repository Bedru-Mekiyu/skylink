@extends('admin.layouts.master')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="row">
    <!-- Portfolios Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-label">Portfolios</div>
                    <div class="stat-value">{{ $portfolioCount }}</div>
                </div>
                <div class="stat-card-icon bg-primary">
                    <i class="fas fa-briefcase"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- News Articles Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-label">News Articles</div>
                    <div class="stat-value">{{ $newsCount }}</div>
                </div>
                <div class="stat-card-icon bg-success">
                    <i class="fas fa-newspaper"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Videos Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-label">Videos</div>
                    <div class="stat-value">{{ $videoCount }}</div>
                </div>
                <div class="stat-card-icon bg-warning">
                    <i class="fas fa-video"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscribers Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-label">Subscribers</div>
                    <div class="stat-value">{{ $subscriberCount }}</div>
                </div>
                <div class="stat-card-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity Placeholder -->
    <div class="col-lg-7 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Recent Activity
            </div>
            <div class="placeholder-card-body">
                <i class="fas fa-stream fa-3x text-muted mb-3"></i>
                <p class="text-muted">Recent activity feed will be displayed here.</p>
                <small class="text-muted">(Coming soon: Latest news and portfolios)</small>
            </div>
        </div>
    </div>

    <!-- Stats Overview Placeholder -->
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Content Overview
            </div>
            <div class="placeholder-card-body">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">A chart will be displayed here.</p>
                 <small class="text-muted">Integrate with Chart.js for data visualization</small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- You could add Chart.js here later --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> --}}
<script>
    // Dashboard-specific scripts can go here.
</script>
@endpush