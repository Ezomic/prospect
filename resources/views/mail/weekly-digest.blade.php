Weekoverzicht sinds {{ $digest['since'] }}

@if ($digest['quiet'])
Er is deze week niets verstuurd, en er kwamen geen reacties of bounces binnen.
@else
Verstuurd: {{ $digest['sent'] }}
Reacties: {{ $digest['replies'] }}
Bounces: {{ $digest['bounces'] }}
@endif

Opvolging achterstallig: {{ $digest['overdue'] }}
Opvolging komende week: {{ $digest['dueSoon'] }}
Bedrijven zonder e-mailadres: {{ $digest['missingEmail'] }}

{{ route('dashboard') }}
