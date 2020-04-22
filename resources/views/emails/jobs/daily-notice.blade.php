@component('mail::message')
# Introduction

Daily notice :: Total Success 
<br><br>
New Customer : {{ $jobs['total_new_cus_success'] }} groups<br>
Update Customer : {{ $jobs['total_update_cus_success'] }} groups<br>
Delete Customer : {{ $jobs['total_del_cus_success'] }} persons<br>
Event : {{ $jobs['total_event_success'] }} groups<br>
Purchase : {{ $jobs['total_purchase_success'] }} groups<br>
Mail Status : {{ $jobs['total_sub_mail_success'] }} accounts<br>
<br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent
