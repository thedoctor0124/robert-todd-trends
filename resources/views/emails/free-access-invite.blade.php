<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Gill Sans', 'Gill Sans MT', 'Helvetica Neue', Arial, sans-serif; color: rgb(56, 56, 56); background-color: #faf8f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #fff; border: 1px solid rgba(56, 56, 56, 0.06); padding: 40px; }
        .logo { font-family: 'Adobe Garamond', Garamond, Georgia, serif; font-size: 24px; font-weight: 400; letter-spacing: 0.1em; color: rgb(56, 56, 56); margin-bottom: 30px; }
        h1 { font-family: 'Adobe Garamond', Garamond, Georgia, serif; font-size: 22px; font-weight: 400; letter-spacing: 0.1em; color: rgb(56, 56, 56); margin-bottom: 15px; }
        p { font-size: 15px; line-height: 1.6; color: #6b6b6b; }
        .highlight { background-color: #faf8f5; border-left: 3px solid #c9a96e; padding: 15px 20px; margin: 20px 0; }
        .highlight strong { color: rgb(56, 56, 56); }
        .btn { display: inline-block; background-color: rgb(56, 56, 56); color: #fff; text-decoration: none; padding: 12px 30px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 20px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
        .muted { font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">{{ config('app.name') }}</div>
            <h1>You've been invited to view a report</h1>
            <p>Hi{{ $invite->invited_name ? ' '.$invite->invited_name : '' }},</p>
            <p>You've been sent complimentary access to the following on {{ config('app.name') }}:</p>

            <div class="highlight">
                @if($invite->access_type === 'publication' && $invite->publication)
                    <strong>Publication:</strong> {{ $invite->publication->title }}<br>
                    <strong>Season:</strong> {{ $invite->publication->season->name }} ({{ $invite->publication->season->year }})
                @elseif($invite->access_type === 'subscription' && $invite->season)
                    <strong>Season subscription:</strong> {{ $invite->season->name }} ({{ $invite->season->year }})<br>
                    <small>Includes all publications in this season.</small>
                @endif
            </div>

            <p>Click the button below to create your account or sign in and open your report. This link is for <strong>{{ $invite->email }}</strong> and expires on {{ $invite->expires_at->format('j F Y') }}.</p>

            <a href="{{ $invite->claimUrl() }}" class="btn">Claim free access</a>

            <p class="muted" style="margin-top: 24px;">If the button doesn't work, copy this link into your browser:<br>{{ $invite->claimUrl() }}</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
