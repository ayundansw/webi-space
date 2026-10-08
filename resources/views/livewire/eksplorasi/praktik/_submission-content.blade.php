{{--
    Extracted so both member's own submission history (Show) and the
    reviewer's review page render a submission's content identically.
    Expects `$submission` in scope (inherited via @include).
--}}
@if ($submission->submission_type === 'link')
    <a href="{{ $submission->content }}" target="_blank" rel="noopener noreferrer" class="text-ink underline hover:text-accent">{{ $submission->content }}</a>
@elseif ($submission->submission_type === 'file')
    <a href="{{ url('/challenge-submissions/'.$submission->id.'/download') }}" class="text-ink underline hover:text-accent">{{ $submission->file_name }}</a>
    <span class="text-caption">({{ number_format($submission->file_size / 1024, 0) }} KB)</span>
@else
    <p class="whitespace-pre-line">{{ $submission->content }}</p>
@endif
