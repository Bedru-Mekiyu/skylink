{{-- Test sites (any non-production environment, or MAIL_STAGING_GUARD=true like bedru): never indexed. Production: nothing. --}}
@if (! app()->isProduction() || config('mail.staging_guard.enabled'))
    <meta name="robots" content="noindex, nofollow">
@endif
