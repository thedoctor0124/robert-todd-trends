<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signed in</title>
</head>
<body>
    <script>
        if (window.opener && !window.opener.closed) {
            window.opener.postMessage({ type: 'google-login-complete' }, window.location.origin);
            window.opener.location.reload();
        }

        window.close();
    </script>
    <p>You are signed in. You can close this window.</p>
</body>
</html>
