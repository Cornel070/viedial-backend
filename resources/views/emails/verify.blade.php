@component('mail::message')
<h1>Email verification code</h1>
	Dear {{$name}}, <br/><br/>
	To finish setting up your Viedial account, we just need to make sure this email address is yours.<br>
	To verify your email address, please click on the verification button below:

@component('mail::button', ['url' => $url])
Verify
@endcomponent

If you didn't carry out this action, you can safely ignore this email.

Thanks,<br>
Viedial Healthcare
@endcomponent
