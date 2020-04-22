@component('mail::message')
# Introduction

Error notices ({{ $jobs['type'] }})

Error Descriptions : {{ $jobs['error_desc'] }}<br>
Total error from Braze's response : {{ $jobs['total_error'] }}<br>
Error file are stored in S3 folder, see from below link.

@component('mail::button', ['url' => $jobs['file_url'] ])
Click here to view error file
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
