<?php

if (!defined('WPINC')) {
    die;
}

if (!defined('FC_REVIEW_MIN_DAYS')) {
    define('FC_REVIEW_MIN_DAYS', 30);
}
if (!defined('FC_REVIEW_SNOOZE_DAYS')) {
    define('FC_REVIEW_SNOOZE_DAYS', 14);
}
if (!defined('FC_REVIEW_URL')) {
    define('FC_REVIEW_URL', 'https://wordpress.org/support/plugin/fastcomments/reviews/?rate=5#new-post');
}
if (!defined('FC_REVIEW_SUPPORT_URL')) {
    define('FC_REVIEW_SUPPORT_URL', 'mailto:support@fastcomments.com?subject=FastComments%20WordPress%20Plugin%20Feedback');
}

function fc_review_notice_should_show()
{
    if (!current_user_can('moderate_comments')) {
        return false;
    }
    if (!get_option('fastcomments_setup')) {
        return false;
    }
    if (get_option('fastcomments_review_dismissed')) {
        return false;
    }
    $snooze_until = (int) get_option('fastcomments_review_snooze_until', 0);
    if ($snooze_until > time()) {
        return false;
    }

    // Eligibility clock starts the first time we evaluate, so existing installs
    // get a fresh countdown after upgrade rather than firing immediately.
    $started = (int) get_option('fastcomments_review_eligibility_started', 0);
    if (!$started) {
        update_option('fastcomments_review_eligibility_started', time(), false);
        return false;
    }
    if ((time() - $started) < (FC_REVIEW_MIN_DAYS * DAY_IN_SECONDS)) {
        return false;
    }
    return true;
}

function fc_review_notice()
{
    if (!fc_review_notice_should_show()) {
        return;
    }

    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('fc_review_notice');
    $review_url = FC_REVIEW_URL;
    $support_url = FC_REVIEW_SUPPORT_URL;
    ?>
    <div id="fc-review-notice" class="fc-review-notice" role="region" aria-label="FastComments feedback prompt">
        <button type="button" class="fc-review-close" aria-label="Dismiss" data-fc-action="snooze">
            <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true">
                <path d="M3 3 L13 13 M13 3 L3 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </button>

        <div class="fc-review-mark" aria-hidden="true">
            <span class="fc-review-mark-glow"></span>
            <svg viewBox="0 0 48 48" width="44" height="44">
                <defs>
                    <linearGradient id="fcReviewGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#5356ec"/>
                        <stop offset="100%" stop-color="#8453ed"/>
                    </linearGradient>
                </defs>
                <rect x="4" y="6" width="40" height="32" rx="9" fill="url(#fcReviewGrad)"/>
                <path d="M16 38 L20 31 L26 31 Z" fill="url(#fcReviewGrad)"/>
                <circle cx="16" cy="22" r="2.4" fill="#fff"/>
                <circle cx="24" cy="22" r="2.4" fill="#fff"/>
                <circle cx="32" cy="22" r="2.4" fill="#fff"/>
            </svg>
        </div>

        <div class="fc-review-body">
            <section class="fc-review-step is-active" data-fc-step="0">
                <div class="fc-review-eyebrow">A quick favor from the team</div>
                <h2 class="fc-review-question">Are you enjoying FastComments?</h2>
                <p class="fc-review-sub">Your honest answer shapes what we build next.</p>
                <div class="fc-review-actions">
                    <button type="button" class="fc-rev-btn fc-rev-btn-primary" data-fc-step-go="1">
                        <span class="fc-stars" aria-hidden="true">
                            <svg viewBox="0 0 12 12" width="13" height="13"><path d="M6 .8 7.6 4.5 11.5 4.8 8.5 7.4 9.4 11.2 6 9.2 2.6 11.2 3.5 7.4 .5 4.8 4.4 4.5z" fill="currentColor"/></svg>
                            <svg viewBox="0 0 12 12" width="13" height="13"><path d="M6 .8 7.6 4.5 11.5 4.8 8.5 7.4 9.4 11.2 6 9.2 2.6 11.2 3.5 7.4 .5 4.8 4.4 4.5z" fill="currentColor"/></svg>
                            <svg viewBox="0 0 12 12" width="13" height="13"><path d="M6 .8 7.6 4.5 11.5 4.8 8.5 7.4 9.4 11.2 6 9.2 2.6 11.2 3.5 7.4 .5 4.8 4.4 4.5z" fill="currentColor"/></svg>
                            <svg viewBox="0 0 12 12" width="13" height="13"><path d="M6 .8 7.6 4.5 11.5 4.8 8.5 7.4 9.4 11.2 6 9.2 2.6 11.2 3.5 7.4 .5 4.8 4.4 4.5z" fill="currentColor"/></svg>
                            <svg viewBox="0 0 12 12" width="13" height="13"><path d="M6 .8 7.6 4.5 11.5 4.8 8.5 7.4 9.4 11.2 6 9.2 2.6 11.2 3.5 7.4 .5 4.8 4.4 4.5z" fill="currentColor"/></svg>
                        </span>
                        Loving it
                    </button>
                    <button type="button" class="fc-rev-btn fc-rev-btn-ghost" data-fc-step-go="2">Not really</button>
                </div>
            </section>

            <section class="fc-review-step" data-fc-step="1" hidden>
                <div class="fc-review-eyebrow fc-eyebrow-warm">Wonderful, thank you</div>
                <h2 class="fc-review-question">Mind sharing that on WordPress.org?</h2>
                <p class="fc-review-sub">A quick public review on the plugin directory genuinely helps us reach more site owners. We are a small team and every word counts.</p>
                <div class="fc-review-actions">
                    <a class="fc-rev-btn fc-rev-btn-primary" href="<?php echo esc_url($review_url); ?>" target="_blank" rel="noopener noreferrer" data-fc-action="reviewed">Leave a 5-star review</a>
                    <button type="button" class="fc-rev-btn fc-rev-btn-ghost" data-fc-action="reviewed">I already did</button>
                    <button type="button" class="fc-rev-btn fc-rev-btn-ghost" data-fc-action="snooze">Maybe later</button>
                </div>
            </section>

            <section class="fc-review-step" data-fc-step="2" hidden>
                <div class="fc-review-eyebrow fc-eyebrow-cool">Sorry to hear that</div>
                <h2 class="fc-review-question">What is not working for you?</h2>
                <p class="fc-review-sub">Email us and a real person on the team will reply, fast. We would much rather fix the problem than lose you.</p>
                <div class="fc-review-actions">
                    <a class="fc-rev-btn fc-rev-btn-primary" href="<?php echo esc_url($support_url); ?>" data-fc-action="feedback">Email the team</a>
                    <button type="button" class="fc-rev-btn fc-rev-btn-ghost" data-fc-action="dismissed">No thanks</button>
                </div>
            </section>

            <section class="fc-review-step fc-review-thanks" data-fc-step="thanks" hidden>
                <div class="fc-review-eyebrow fc-eyebrow-warm">All set</div>
                <h2 class="fc-review-question">Thank you, truly.</h2>
                <p class="fc-review-sub">This panel will not appear again.</p>
            </section>
        </div>
    </div>

    <style>
        .fc-review-notice {
            position: relative;
            display: grid;
            grid-template-columns: 76px 1fr;
            gap: 22px;
            max-width: 760px;
            margin: 22px 20px 8px 0;
            padding: 26px 30px 26px 26px;
            background: #ffffff;
            border: 1px solid #E4E4E4;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 18px 38px rgba(83, 86, 236, 0.07);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            color: #030303;
            overflow: hidden;
            animation: fcReviewIn 360ms cubic-bezier(.2,.8,.2,1) both;
        }
        .fc-review-notice::before {
            content: "";
            position: absolute;
            top: -120px;
            left: -100px;
            width: 320px;
            height: 320px;
            background: radial-gradient(closest-side, rgba(132, 83, 237, 0.18), rgba(83, 86, 236, 0.08) 55%, transparent 75%);
            pointer-events: none;
            z-index: 0;
        }
        .fc-review-notice::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(83, 86, 236, 0.06) 1px, transparent 1px);
            background-size: 18px 18px;
            mask-image: linear-gradient(135deg, rgba(0,0,0,0.7), transparent 55%);
            -webkit-mask-image: linear-gradient(135deg, rgba(0,0,0,0.7), transparent 55%);
            pointer-events: none;
            z-index: 0;
        }
        .fc-review-mark {
            position: relative;
            z-index: 1;
            width: 76px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: linear-gradient(160deg, #f5f4ff 0%, #ffffff 100%);
            border: 1px solid #ECEAFE;
        }
        .fc-review-mark-glow {
            position: absolute;
            inset: -8px;
            background: radial-gradient(closest-side, rgba(132, 83, 237, 0.22), transparent 70%);
            border-radius: 24px;
            z-index: -1;
            animation: fcReviewBreathe 4.6s ease-in-out infinite;
        }
        .fc-review-mark svg {
            filter: drop-shadow(0 4px 12px rgba(83, 86, 236, 0.22));
        }
        .fc-review-body {
            position: relative;
            z-index: 1;
            min-width: 0;
        }
        .fc-review-step {
            animation: fcStepIn 260ms ease both;
        }
        .fc-review-eyebrow {
            display: inline-block;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #5356ec;
            background: rgba(83, 86, 236, 0.08);
            padding: 4px 9px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        .fc-eyebrow-warm {
            color: #8453ed;
            background: rgba(132, 83, 237, 0.10);
        }
        .fc-eyebrow-cool {
            color: #b7791f;
            background: rgba(255, 159, 56, 0.13);
        }
        .fc-review-question {
            font-family: 'Manrope', 'Inter', system-ui, sans-serif;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #121212;
            margin: 0 0 8px;
        }
        .fc-review-sub {
            font-size: 13.5px;
            line-height: 1.55;
            color: #5b5b66;
            margin: 0 0 18px;
            max-width: 52ch;
        }
        .fc-review-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .fc-rev-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 200ms ease, background 200ms ease, color 200ms ease, border-color 200ms ease;
            -webkit-appearance: none;
        }
        .fc-rev-btn-primary {
            background: linear-gradient(65.68deg, #5356ec -11.59%, #8453ed 72.49%);
            color: #ffffff !important;
            box-shadow: 0 6px 18px rgba(83, 86, 236, 0.28);
        }
        .fc-rev-btn-primary:hover,
        .fc-rev-btn-primary:focus-visible {
            background: linear-gradient(65.68deg, #8453ed -11.59%, #5356ec 72.49%);
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(83, 86, 236, 0.36);
            outline: none;
        }
        .fc-rev-btn-ghost {
            background: transparent;
            color: #4b4b55 !important;
            border-color: #e4e4ea;
        }
        .fc-rev-btn-ghost:hover,
        .fc-rev-btn-ghost:focus-visible {
            background: #fafafa;
            border-color: #d3d2dc;
            color: #121212 !important;
            outline: none;
        }
        .fc-stars {
            display: inline-flex;
            gap: 1px;
            color: #ffe28a;
            margin-right: 2px;
            line-height: 0;
        }
        .fc-stars svg {
            transform-origin: center;
            animation: fcStarPulse 2.4s ease-in-out infinite;
        }
        .fc-stars svg:nth-child(2) { animation-delay: 0.18s; }
        .fc-stars svg:nth-child(3) { animation-delay: 0.36s; }
        .fc-stars svg:nth-child(4) { animation-delay: 0.54s; }
        .fc-stars svg:nth-child(5) { animation-delay: 0.72s; }
        .fc-rev-btn-primary:hover .fc-stars { color: #ffffff; }
        .fc-review-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 0;
            border-radius: 50%;
            color: #9b9ba6;
            cursor: pointer;
            transition: background 160ms ease, color 160ms ease;
            z-index: 2;
        }
        .fc-review-close:hover,
        .fc-review-close:focus-visible {
            background: #f3f3f6;
            color: #121212;
            outline: none;
        }
        .fc-review-step[hidden] { display: none !important; }
        .fc-review-thanks .fc-review-question { font-size: 18px; }

        @keyframes fcReviewIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fcStepIn {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fcReviewBreathe {
            0%, 100% { opacity: 0.7; transform: scale(1); }
            50%      { opacity: 1;   transform: scale(1.04); }
        }
        @keyframes fcStarPulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.18); }
        }
        @media (prefers-reduced-motion: reduce) {
            .fc-review-notice, .fc-review-step, .fc-stars svg, .fc-review-mark-glow {
                animation: none !important;
            }
        }
        @media (max-width: 720px) {
            .fc-review-notice {
                grid-template-columns: 1fr;
                padding: 22px 22px 22px 22px;
                margin-right: 12px;
            }
            .fc-review-mark { width: 56px; height: 56px; }
            .fc-review-mark svg { width: 34px; height: 34px; }
            .fc-review-question { font-size: 19px; }
        }
    </style>

    <script>
    (function () {
        var root = document.getElementById('fc-review-notice');
        if (!root) return;

        var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
        var nonce = <?php echo wp_json_encode($nonce); ?>;
        var steps = root.querySelectorAll('.fc-review-step');

        function showStep(target) {
            steps.forEach(function (s) {
                var match = s.getAttribute('data-fc-step') === String(target);
                if (match) {
                    s.hidden = false;
                    s.classList.add('is-active');
                } else {
                    s.hidden = true;
                    s.classList.remove('is-active');
                }
            });
        }

        function postAction(action) {
            var body = new URLSearchParams();
            body.append('action', 'fc_review_notice');
            body.append('fc_action', action);
            body.append('nonce', nonce);
            return fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).catch(function () { /* swallow; UI dismisses regardless */ });
        }

        function dismiss(action) {
            postAction(action);
            if (action === 'reviewed' || action === 'feedback' || action === 'dismissed') {
                showStep('thanks');
                setTimeout(function () { root.remove(); }, 1800);
            } else {
                root.style.transition = 'opacity 220ms ease, transform 220ms ease';
                root.style.opacity = '0';
                root.style.transform = 'translateY(-4px)';
                setTimeout(function () { root.remove(); }, 220);
            }
        }

        root.addEventListener('click', function (e) {
            var stepBtn = e.target.closest('[data-fc-step-go]');
            if (stepBtn) {
                showStep(stepBtn.getAttribute('data-fc-step-go'));
                return;
            }
            var actionEl = e.target.closest('[data-fc-action]');
            if (actionEl) {
                var action = actionEl.getAttribute('data-fc-action');
                // Anchor tags still navigate naturally; we only intercept the dismiss flow.
                dismiss(action);
            }
        });
    })();
    </script>
    <?php
}

function fc_review_notice_handle_action()
{
    if (!current_user_can('moderate_comments')) {
        wp_send_json_error(array('message' => 'forbidden'), 403);
    }
    check_ajax_referer('fc_review_notice', 'nonce');

    $action = isset($_POST['fc_action']) ? sanitize_key(wp_unslash($_POST['fc_action'])) : '';
    switch ($action) {
        case 'reviewed':
            update_option('fastcomments_review_dismissed', 1, false);
            update_option('fastcomments_review_action_taken', 'reviewed', false);
            break;
        case 'feedback':
            update_option('fastcomments_review_dismissed', 1, false);
            update_option('fastcomments_review_action_taken', 'feedback', false);
            break;
        case 'dismissed':
            update_option('fastcomments_review_dismissed', 1, false);
            update_option('fastcomments_review_action_taken', 'dismissed', false);
            break;
        case 'snooze':
            update_option('fastcomments_review_snooze_until', time() + (FC_REVIEW_SNOOZE_DAYS * DAY_IN_SECONDS), false);
            break;
        default:
            wp_send_json_error(array('message' => 'bad_action'), 400);
    }
    wp_send_json_success();
}

function fc_review_notice_enqueue_fonts()
{
    if (!fc_review_notice_should_show()) {
        return;
    }
    wp_enqueue_style(
        'fc-review-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&family=Manrope:wght@600;700&display=swap',
        array(),
        null
    );
}

add_action('admin_enqueue_scripts', 'fc_review_notice_enqueue_fonts');
add_action('wp_ajax_fc_review_notice', 'fc_review_notice_handle_action');
add_action('admin_notices', 'fc_review_notice');
