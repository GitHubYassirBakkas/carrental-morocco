
<?php
function safe_mode()
{
    return config('app.safe_mode');
}

function abort_if_safe($condition, $message)
{
    if (safe_mode() && $condition) {
        abort(403, $message);
    }
}
