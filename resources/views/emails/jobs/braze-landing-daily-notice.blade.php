@component('mail::message')
# Introduction

Braze Landing Daily notice :: Total Success 
<br><br>
@foreach ($jobs as $job)
    <p>Total {{ $job['event_name'] }} : {{ $job['total_files'] }} File(s)</p><br>
@endforeach
<br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent
