@component('mail::message')
<h1>Welcome to Viedial Health Care</h1>
	Hi {{ $user->name }},<br>
	<p>
	We're excited to be part of your health journey and can't wait to help you reach your goals. We'll be here to keep you motivated and informed every step of the way.</p><br>
	<br>
	<h3>Choose Your Program</h3><br>
	<p>Whether you're just getting started with implementing lifestyle changes or have been managing {{ $user->program }} for a while, the daily updates and check-ins will help you stay on track and provide helpful reminders along the way.</p><br>
	<h3>Track Your Progress</h3>
	<p>View your health stats, including your blood pressure, blood sugar. Your entire health history is always one tap away.</p><br>
	<h3>Learn About Benefits of Implementing Lifestyle Changes</h3>
	<p>we’ve curated the best articles, videos, recipes and studies on {{ $user->program }} — consider us your own personal {{ $user->program }} library.</p>
	@component('mail::panel')
	<h2>Account Key</h2><br>
	<p>Your account key is: <strong>{{ $user->acct_key }}</strong></p><br>
	<p>This key is unique to your account and should be kept private. This key is also used to protect your data. It is also needed to log into your account and to access your health records on the app.</p>
	@endcomponent

Cheers,<br>
Team Viedial
@endcomponent