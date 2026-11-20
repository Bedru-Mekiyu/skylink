@extends('admin.layouts.master')

@section('title', 'Contact Messages')
@section('page-title', 'Inbox')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">{{ $messages->total() }} older {{ \Illuminate\Support\Str::plural("message", $messages->total()) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">From</th>
                        <th scope="col">Message</th>
                        <th scope="col">Received</th>
                        <th scope="col" class="text-end pe-4"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                    <tr>
                        <td data-label="From" class="ps-4"><div class="fw-semibold">{{ $message->first_name }} {{ $message->last_name }}</div><div class="small text-muted text-break">{{ $message->email }}</div></td>
                        <td data-label="Message">{{ Str::limit($message->message, 70) }}</td>
                        <td data-label="Received" class="text-nowrap text-muted">{{ $message->created_at->format('M j, Y') }}</td>
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
                        <td colspan="4" class="text-center py-5">
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

@push('scripts')
<script>
    // Opening a message here also marks it read in the inbox, so the unread badge stays right.
    document.querySelectorAll('[id^="messageModal-"]').forEach(function (modal) {
        modal.addEventListener('show.bs.modal', function () {
            fetch('{{ url('admin/contact-messages') }}/' + modal.id.replace('messageModal-', '') + '/read', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin'
            });
        }, { once: true });
    });
</script>
@endpush