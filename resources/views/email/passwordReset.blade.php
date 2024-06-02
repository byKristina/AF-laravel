<x-mail::message>
# Password Reset

Click the button below to reset your password. Your reset link will expire in 30 minutes.

<x-mail::button :url="'http://localhost:4200/reset-password?token='.$token . '&email=' . $email">
Reset Password
</x-mail::button>

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
