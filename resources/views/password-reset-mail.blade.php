<h3>Hi, {{ $user->name }}</h3>

<p>You are requesting to reset your password. To continue, please click <a href="https://plutus.nyxordinal.dev/password/reset?email={{ $user->email }}&reset-token={{ $user->reset_token }}">this link</a>.</p>
<p>Or you can copy and paste the link below on a new tab in your browser.</p>
<p><strong>https://plutus.nyxordinal.dev/password/reset?email={{ $user->email }}&reset-token={{ $user->reset_token }}</strong></p>
<p>If you don't think you're asking for a password reset, please ignore this email.</p>
