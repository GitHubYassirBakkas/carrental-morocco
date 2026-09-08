{{-- Shared styles for centered auth pages (confirm, forgot, reset, verify) --}}
<style>
    :root {
        --gold:       #C89D66;
        --gold-light: #D4AB76;
        --gold-dark:  #B8935E;
        --bg-input:   #141414;
        --border:     #2a2a2a;
        --text-primary:#f0f0f0;
        --text-muted: #888;
        --text-hint:  #555;
        --radius-sm:  8px;
        --transition: 0.2s ease;
    }

    /* ── Centered layout ── */
    .auth-centered {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        background:
            radial-gradient(ellipse at 20% 50%, rgba(200,157,102,0.04) 0%, transparent 60%),
            radial-gradient(ellipse at 80% 20%, rgba(200,157,102,0.03) 0%, transparent 50%),
            #0a0a0a;
    }

    /* ── Card ── */
    .auth-card {
        width: 100%;
        max-width: 440px;
        background: #0f0f0f;
        border: 1px solid #1e1e1e;
        border-radius: 20px;
        padding: 2.5rem 2rem;
        animation: fadeUp 0.45s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Top icon ── */
    .card-icon {
        width: 56px; height: 56px;
        background: rgba(200,157,102,0.12);
        border: 1px solid rgba(200,157,102,0.2);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.5rem;
        color: var(--gold);
    }

    .card-icon svg { width: 26px; height: 26px; }

    .card-icon--green {
        background: rgba(16,185,129,0.1);
        border-color: rgba(16,185,129,0.2);
        color: #10b981;
    }

    /* ── Logo ── */
    .logo-link {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        margin-bottom: 1.25rem;
        justify-content: center;
    }

    .logo-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, var(--gold), var(--gold-dark));
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 16px rgba(200,157,102,0.2);
        flex-shrink: 0;
    }

    .logo-icon svg { width: 20px; height: 20px; color: #fff; }

    .logo-name {
        display: block;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--gold);
        letter-spacing: -0.02em;
    }

    .logo-sub {
        display: block;
        font-size: 0.65rem;
        color: var(--text-hint);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    /* ── Lang switcher ── */
    .lang-switcher {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-bottom: 1.5rem;
    }

    .lang-btn {
        padding: 4px 11px;
        border-radius: 100px;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-hint);
        border: 1px solid #2a2a2a;
        background: transparent;
        transition: all var(--transition);
    }

    .lang-btn:hover { border-color: var(--gold); color: var(--gold); }

    .lang-active {
        background: rgba(200,157,102,0.12);
        border-color: rgba(200,157,102,0.35) !important;
        color: var(--gold) !important;
    }

    /* ── Title / desc ── */
    .card-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.03em;
        margin-bottom: 0.4rem;
        text-align: center;
    }

    .card-desc {
        font-size: 0.85rem;
        color: var(--text-muted);
        text-align: center;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    /* ── Alerts ── */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 0.875rem 1rem;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        margin-bottom: 1.25rem;
        line-height: 1.5;
    }

    .alert svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }
    .alert p { margin: 0; }

    .alert-success {
        background: rgba(16,185,129,0.08);
        border: 1px solid rgba(16,185,129,0.2);
        color: #6ee7b7;
    }

    .alert-error {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.2);
        color: #fca5a5;
    }

    /* ── Form ── */
    .auth-form { display: flex; flex-direction: column; gap: 1rem; }

    .field label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 0.4rem;
    }

    .input-wrap { position: relative; }

    .field-icon {
        position: absolute;
        left: 13px; top: 50%;
        transform: translateY(-50%);
        width: 16px; height: 16px;
        color: var(--text-hint);
        pointer-events: none;
    }

    .input-wrap input {
        width: 100% !important;
        background: #141414 !important;
        background-color: #141414 !important;
        border: 1px solid #2a2a2a !important;
        border-radius: var(--radius-sm) !important;
        color: #f0f0f0 !important;
        font-size: 0.9rem !important;
        padding: 0.72rem 3rem 0.72rem 2.6rem !important;
        outline: none !important;
        -webkit-text-fill-color: #f0f0f0 !important;
        caret-color: var(--gold) !important;
        transition: border-color var(--transition), box-shadow var(--transition);
    }

    .input-wrap input:-webkit-autofill,
    .input-wrap input:-webkit-autofill:hover,
    .input-wrap input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 1000px #141414 inset !important;
        -webkit-text-fill-color: #f0f0f0 !important;
        border-color: #2a2a2a !important;
    }

    .input-wrap input::placeholder { color: #555 !important; }

    .input-wrap input:focus {
        border-color: var(--gold) !important;
        box-shadow: 0 0 0 3px rgba(200,157,102,0.1) !important;
    }

    .toggle-pw {
        position: absolute;
        right: 11px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        cursor: pointer; padding: 4px;
        color: var(--text-hint);
        line-height: 0;
        transition: color var(--transition);
    }

    .toggle-pw:hover { color: var(--gold); }
    .toggle-pw svg { width: 16px; height: 16px; display: block; }

    /* Strength */
    .pw-strength {
        height: 3px;
        background: #1e1e1e;
        border-radius: 10px;
        margin-top: 6px;
        overflow: hidden;
    }

    .pw-bar {
        height: 100%;
        width: 0%;
        border-radius: 10px;
        transition: width 0.3s ease, background 0.3s ease;
    }

    .pw-hint { font-size: 0.68rem; color: var(--text-hint); margin-top: 4px; }

    /* ── Submit ── */
    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 0.85rem;
        background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
        border: none;
        border-radius: var(--radius-sm);
        color: #fff;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity var(--transition), box-shadow var(--transition), transform 0.15s;
        box-shadow: 0 4px 20px rgba(200,157,102,0.22);
    }

    .btn-submit svg { width: 17px; height: 17px; transition: transform 0.2s; }
    .btn-submit:hover { opacity: 0.9; box-shadow: 0 6px 28px rgba(200,157,102,0.38); }
    .btn-submit:hover svg { transform: translateX(3px); }
    .btn-submit:active { transform: scale(0.985); }

    /* ── Divider ── */
    .divider {
        display: flex; align-items: center;
        gap: 12px; margin: 1.25rem 0;
    }

    .divider::before, .divider::after {
        content: ''; flex: 1;
        height: 1px; background: var(--border);
    }

    .divider span { font-size: 0.72rem; color: var(--text-hint); }

    /* ── Links ── */
    .register-text {
        text-align: center;
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
    }

    .register-text a {
        color: var(--gold);
        font-weight: 600;
        text-decoration: none;
    }

    .register-text a:hover { color: var(--gold-light); }

    .back-home {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 0.8rem;
        color: var(--text-hint);
        text-decoration: none;
        margin-top: 1rem;
        transition: color var(--transition);
    }

    .back-home:hover { color: var(--text-muted); }
    .back-home svg { width: 14px; height: 14px; }

    /* RTL */
    [dir="rtl"] .input-wrap input { padding: 0.72rem 2.6rem 0.72rem 3rem !important; }
    [dir="rtl"] .field-icon { left: auto; right: 13px; }
    [dir="rtl"] .toggle-pw  { right: auto; left: 11px; }
    [dir="rtl"] .btn-submit svg { transform: scaleX(-1); }
    [dir="rtl"] .back-home svg  { transform: scaleX(-1); }
    [dir="rtl"] .lang-switcher  { flex-direction: row-reverse; }
</style>