<h1>FacebookOfflineConvertion</h1>
# Introduction
Error notices Facebook offline conversion : ({{ $detail['type'] }})<br><br>

Error Descriptions : {{ $detail['error_desc'] }}<br><br>
Total error  : {{ $detail['total_error'] }}<br>
Error file are stored in S3 folder, see from below link.

@component('mail::button', ['url' => $detail['file_url'] ])
Click here to view error file
@endcomponent

Thanks,<br>

