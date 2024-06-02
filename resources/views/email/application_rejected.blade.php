<!DOCTYPE html>
<html>
<head>
    <title>Application Rejected</title>
</head>
<body>
    <h3>Application Rejected</h3>
    <p>Dear {{ $user_rejected->first_name }},</p>
    <p>We regret to inform you that your application for the activity "{{ $activity->name }}" has been rejected.</p>
    <p>Best regards,<br>{{ config('app.name') }}</p>
</body>
</html>
