@component('mail::message')
<h1>Email verification code</h1>
	Dear {{$name}}, <br/><br/>
	To finish setting up your Viedial account, we just need to make sure this email address is yours.<br>
	To verify your email address, please use the verification code below:

@component('mail::panel')
<div style="text-align: center; font-size: 20px; font-weight: bold;">
	<h2>{{ $code }}</h2>
</div>
@endcomponent

If you didn't request this code, you can safely ignore this email. Someone else might have typed your email address by mistake.

Thanks,<br>
Viedial
@endcomponent
