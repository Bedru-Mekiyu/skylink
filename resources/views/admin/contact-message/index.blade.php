@extends('admin.layouts.master')

@section('title', 'Contact Messages')
@section('page-title', 'Inbox')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Contact Messages</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4" style="width: 5%;">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Contact</th>
                        <th scope="col" style="width: 40%;">Message Preview</th>
                        <th scope="col" style="width: 15%;">Received</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                    <tr>
                        <td data-label="#" class="fw-bold ps-4">{{ $loop->iteration + ($messages->currentPage() - 1) * $messages->perPage() }}</td>
                        <td data-label="Name">{{ $message->first_name }} {{ $message->last_name }}</td>
                        <td data-label="Contact">{{ $message->email }}</td>
                        <td data-label="Message">{{ Str::limit($message->message, 70) }}</td>
                        <td data-label="Received">{{ $message->created_at->format('M d, Y') }}</td>
                        <td data-label="Actions" class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#messageModal-{{ $message->id }}">
                                <i class="fas fa-eye me-1"></i> View
                            </button>
                        </td>
                    </tr>

                    <!-- View Message Modal -->
                    <div class="modal fade" id="messageModal-{{ $message->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Message from: {{ $message->first_name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Full Name</dt>
                                        <dd class="col-sm-9">{{ $message->first_name }} {{ $message->last_name }}</dd>
                                        <dt class="col-sm-3">Email</dt>
                                        <dd class="col-sm-9"><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>
                                        @if($message->phone)<dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $message->phone }}</dd>@endif
                                        @if($message->company)<dt class="col-sm-3">Company</dt><dd class="col-sm-9">{{ $message->company }}</dd>@endif
                                        <dt class="col-sm-12 mt-3">Message</dt>
                                        <dd class="col-sm-12 bg-light p-3 rounded">{{ $message->message }}</dd>
                                    </dl>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No messages found.</h5>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($messages->hasPages())
    <div class="card-footer">
        {{ $messages->links() }}
    </div>
    @endif
</div>
@endsection