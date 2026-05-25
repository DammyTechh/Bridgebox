@extends('student.layout')

@section('title', 'Take ' . ucfirst($type))

@php
    $routePrefix = $type === 'exam' ? 'student.exams' : 'student.quizzes';
    $timeLimit   = (int) ($assessment->time_limit_minutes ?? 0);
    $totalQ      = $questions->count();
@endphp

@section('main')
<main class="main quiz-main">

    {{-- ── Sticky timer bar ── --}}
    <div class="quiz-bar" id="quiz-bar">
        <div class="quiz-bar-left">
            <span class="quiz-bar-type">{{ ucfirst($type) }}</span>
            <span class="quiz-bar-title">{{ $assessment->title }}</span>
        </div>
        <div class="quiz-bar-center">
            @if ($timeLimit)
                <div class="quiz-timer" id="quiz-timer"
                     data-time-limit="{{ $timeLimit }}"
                     data-started-at="{{ $attempt->started_at?->timestamp ?? '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span id="timer-display">--:--</span>
                </div>
            @else
                <div class="quiz-timer quiz-timer--nolimit">
                    <i class="fa-solid fa-infinity"></i>
                    <span>{{ __('No limit') }}</span>
                </div>
            @endif
        </div>
        <div class="quiz-bar-right">
            <span class="quiz-progress" id="quiz-progress">0 / {{ $totalQ }} answered</span>
        </div>
    </div>

    {{-- ── Question cards ── --}}
    <div class="quiz-body" id="quiz-body">
        <form id="assessment-form"
              action="{{ route($routePrefix . '.submit', $attempt) }}"
              method="post">
            @csrf

            @foreach ($questions as $index => $question)
                <div class="q-card" id="q-card-{{ $index + 1 }}" data-question="{{ $question->id }}">
                    <div class="q-card-header">
                        <span class="q-number">Q{{ $index + 1 }}</span>
                        @if ($question->points)
                            <span class="q-points">{{ $question->points }} pt{{ $question->points != 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                    <p class="q-prompt">{{ $question->prompt }}</p>
                    <div class="q-options">
                        @foreach ($question->options->sortBy('order') as $opt)
                            <label class="q-opt" data-for="{{ $question->id }}">
                                <input class="q-radio" type="radio"
                                       name="answers[{{ $question->id }}]"
                                       value="{{ $opt->id }}"
                                       data-question="{{ $question->id }}">
                                <span class="q-opt-box">
                                    <span class="q-opt-dot"></span>
                                </span>
                                <span class="q-opt-text">{{ $opt->option_text }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="quiz-submit-row">
                <button class="btn primary quiz-submit-btn" type="submit" id="submit-btn">
                    <i class="fa-solid fa-paper-plane"></i>
                    Submit {{ ucfirst($type) }}
                </button>
            </div>
        </form>
    </div>

    {{-- ── Hidden guard data ── --}}
    <div id="attempt-guard"
         data-back-url="{{ route($routePrefix . '.index') }}"
         data-result-url="{{ route($routePrefix . '.result', $attempt) }}"
         data-forfeit-url="{{ route($routePrefix . '.forfeit', $attempt) }}"
         data-csrf="{{ csrf_token() }}"
         style="display:none;">
    </div>

    {{-- ── MODAL: Rules (shown on load) ── --}}
    <div class="qmodal-backdrop" id="rules-backdrop" aria-hidden="false">
        <div class="qmodal" role="dialog" aria-modal="true" aria-labelledby="rules-title">
            <div class="qmodal-icon">
                <i class="fa-solid fa-{{ $type === 'exam' ? 'graduation-cap' : 'clipboard-question' }}"></i>
            </div>
            <h2 class="qmodal-title" id="rules-title">{{ $assessment->title }}</h2>
            <div class="qmodal-meta">
                @if ($assessment->subject)
                    <span><i class="fa-solid fa-book"></i> {{ $assessment->subject->name }}</span>
                @endif
                @if ($assessment->topic)
                    <span><i class="fa-solid fa-tag"></i> {{ $assessment->topic->title }}</span>
                @endif
                <span><i class="fa-solid fa-list-ol"></i> {{ $totalQ }} question{{ $totalQ != 1 ? 's' : '' }}</span>
                @if ($timeLimit)
                    <span><i class="fa-solid fa-clock"></i> {{ $timeLimit }} minute{{ $timeLimit != 1 ? 's' : '' }}</span>
                @else
                    <span><i class="fa-solid fa-infinity"></i> {{ __('No time limit') }}</span>
                @endif
            </div>
            @if ($assessment->description)
                <p class="qmodal-desc">{{ $assessment->description }}</p>
            @endif
            <div class="qmodal-rules">
                <p class="qmodal-rules-title"><i class="fa-solid fa-circle-info"></i> {{ __('Instructions') }}</p>
                <ul>
                    <li>{{ __('Read each question carefully before selecting your answer.') }}</li>
                    <li>Each question has only <strong>one correct answer</strong>.</li>
                    <li>{{ __('You can change your answer at any time before submitting.') }}</li>
                    @if ($timeLimit)
                        <li>You have <strong>{{ $timeLimit }} minute{{ $timeLimit != 1 ? 's' : '' }}</strong> to complete this {{ $type }}. The timer is already running.</li>
                        <li>When time runs out, your {{ $type }} will be submitted automatically.</li>
                    @endif
                    <li>Do <strong>not</strong> refresh or navigate away - this will forfeit your attempt with a score of zero.</li>
                    <li>Click <strong>Submit</strong> once you have answered all questions.</li>
                </ul>
            </div>
            <button class="qmodal-begin-btn" id="begin-btn" type="button">
                Begin {{ ucfirst($type) }}
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>

    {{-- ── MODAL: Leave confirmation ── --}}
    <div class="qmodal-backdrop qmodal-backdrop--dark" id="leave-backdrop" aria-hidden="true" style="display:none;">
        <div class="qmodal qmodal--sm" role="dialog" aria-modal="true" aria-labelledby="leave-title">
            <div class="qmodal-icon qmodal-icon--warn">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h2 class="qmodal-title" id="leave-title">{{ __('Leave Attempt?') }}</h2>
            <p class="qmodal-desc">Leaving now will submit this {{ $type }} with a <strong>score of zero</strong>. This action cannot be undone.</p>
            <div class="qmodal-actions">
                <button class="btn ghost" type="button" data-leave-cancel>{{ __('Stay') }}</button>
                <button class="btn primary" type="button" data-leave-confirm style="background:#ef4444;">{{ __('Leave & Score Zero') }}</button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Time's up (force submit) ── --}}
    <div class="qmodal-backdrop qmodal-backdrop--dark" id="timesup-backdrop" aria-hidden="true" style="display:none;">
        <div class="qmodal qmodal--sm" role="dialog" aria-modal="true" aria-labelledby="timesup-title">
            <div class="qmodal-icon qmodal-icon--timeout">
                <i class="fa-solid fa-hourglass-end"></i>
            </div>
            <h2 class="qmodal-title" id="timesup-title">{{ __("Time's Up!") }}</h2>
            <p class="qmodal-desc">Your time has expired. Your {{ $type }} will be submitted automatically.</p>
            <p class="qmodal-countdown">Submitting in <strong id="timesup-count">5</strong>&hellip;</p>
            <div class="qmodal-actions">
                <button class="btn primary timesup-submit-btn" type="button" id="timesup-submit">{{ __('Submit Now') }}</button>
            </div>
        </div>
    </div>

</main>
@endsection

@push('scripts')
<style>
/* ── Quiz layout ── */
.quiz-main { padding: 0; }

/* Sticky bar */
.quiz-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #fff;
    border-bottom: 2px solid #eef0f5;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 28px;
    box-shadow: 0 2px 12px rgba(30,40,80,.07);
}
.quiz-bar-left  { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.quiz-bar-center{ display:flex; justify-content:center; flex:1; }
.quiz-bar-right  { display:flex; justify-content:flex-end; flex:1; }
.quiz-bar-type {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #fff;
    background: #4a7bd1; border-radius: 999px; padding: 3px 10px;
    flex-shrink: 0;
}
.quiz-bar-title {
    font-size: 15px; font-weight: 700; color: #1a2236;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.quiz-progress { font-size: 13px; color: #7b8ba0; font-weight: 600; white-space: nowrap; }

/* Timer */
.quiz-timer {
    display: inline-flex; align-items: center; gap: 8px;
    background: #f0f4ff; border: 2px solid #c8d4ef;
    border-radius: 12px; padding: 8px 20px;
    font-size: 22px; font-weight: 800; color: #1e3a8a;
    letter-spacing: .04em; font-variant-numeric: tabular-nums;
    transition: background .3s, border-color .3s, color .3s;
    min-width: 130px; justify-content: center;
}
.quiz-timer i { font-size: 18px; }
.quiz-timer--nolimit {
    font-size: 16px; font-weight: 600;
    background: #f4f7f9; border-color: #dde2ea; color: #6b7a8d;
    min-width: unset;
}
.quiz-timer--warn   { background: #fff8e6; border-color: #f2b84b; color: #92600a; }
.quiz-timer--danger { background: #fff0f0; border-color: #ef4444; color: #b91c1c; }
.quiz-timer--blink  { animation: timer-blink 0.7s step-start infinite; }
@keyframes timer-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: .25; }
}

/* Question cards */
.quiz-body { max-width: 780px; margin: 32px auto; padding: 0 20px 60px; }
.q-card {
    background: #fff; border: 1px solid #eef0f5;
    border-radius: 18px; padding: 28px 28px 24px;
    margin-bottom: 20px;
    transition: border-color .2s, box-shadow .2s;
}
.q-card.q-answered { border-color: #a7c8a7; background: #f6fff6; }
.q-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.q-number {
    font-size: 12px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .07em; color: #fff;
    background: #4a7bd1; border-radius: 999px; padding: 4px 12px;
}
.q-points { font-size: 12px; color: #9aaac0; font-weight: 600; }
.q-prompt { font-size: 16px; font-weight: 600; color: #1a2236; line-height: 1.6; margin: 0 0 18px; }
.q-options { display:flex; flex-direction:column; gap:10px; }
.q-opt {
    display: flex; align-items: center; gap: 12px;
    border: 2px solid #eef0f5; border-radius: 12px;
    padding: 13px 16px; cursor: pointer;
    transition: border-color .15s, background .15s;
    user-select: none;
}
.q-opt:hover { border-color: #a8c0ef; background: #f5f8ff; }
.q-opt:has(.q-radio:checked) { border-color: #4a7bd1; background: #eef4ff; }
.q-radio { display:none; }
.q-opt-box {
    width: 20px; height: 20px; border-radius: 50%;
    border: 2px solid #c8d4ef; background: #fff;
    display:flex; align-items:center; justify-content:center;
    flex-shrink: 0; transition: border-color .15s, background .15s;
}
.q-opt:has(.q-radio:checked) .q-opt-box { border-color: #4a7bd1; background: #4a7bd1; }
.q-opt-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #fff; opacity: 0; transform: scale(0);
    transition: opacity .15s, transform .15s;
}
.q-opt:has(.q-radio:checked) .q-opt-dot { opacity: 1; transform: scale(1); }
.q-opt-text { font-size: 15px; color: #253045; line-height: 1.4; }

/* Submit row */
.quiz-submit-row { display:flex; justify-content:center; padding: 16px 0 24px; }
.quiz-submit-btn { padding: 14px 40px; font-size: 16px; gap: 8px; }

/* ── Modals ── */
.qmodal-backdrop {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(10, 18, 40, 0.72);
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
    padding: 20px;
}
.qmodal-backdrop--dark { background: rgba(10, 18, 40, 0.85); }
.qmodal {
    background: #fff; border-radius: 24px;
    padding: 40px 44px 36px;
    max-width: 560px; width: 100%;
    box-shadow: 0 20px 60px rgba(10,18,40,.25);
    animation: qmodal-in .25s ease;
    max-height: 90vh; overflow-y: auto;
}
.qmodal--sm { max-width: 440px; padding: 36px 36px 30px; }
@keyframes qmodal-in {
    from { opacity:0; transform:translateY(-16px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.qmodal-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: #eef4ff; color: #4a7bd1;
    display: flex; align-items:center; justify-content:center;
    font-size: 28px; margin: 0 auto 20px;
}
.qmodal-icon--warn    { background: #fff8e6; color: #c47f0a; }
.qmodal-icon--timeout { background: #fff0f0; color: #ef4444; }
.qmodal-title { font-size: 22px; font-weight: 800; color: #1a2236; text-align:center; margin: 0 0 12px; }
.qmodal-meta {
    display: flex; flex-wrap: wrap; gap: 8px; justify-content:center;
    margin-bottom: 18px;
}
.qmodal-meta span {
    display:inline-flex; align-items:center; gap:5px;
    font-size: 12px; font-weight: 600; color: #5a6a80;
    background: #f4f6fa; border-radius: 999px; padding: 4px 12px;
}
.qmodal-desc { font-size: 14px; color: #5a6a80; text-align:center; line-height: 1.6; margin: 0 0 18px; }
.qmodal-rules {
    background: #f8faff; border: 1px solid #dde6f5; border-radius: 14px;
    padding: 16px 20px; margin-bottom: 28px; text-align:left;
}
.qmodal-rules-title {
    font-size: 13px; font-weight: 700; color: #4a7bd1;
    margin: 0 0 10px; display:flex; align-items:center; gap:6px;
}
.qmodal-rules ul { margin: 0; padding-left: 20px; }
.qmodal-rules li { font-size: 13px; color: #3d4d60; line-height: 1.7; margin-bottom: 2px; }
.qmodal-begin-btn {
    display: flex; align-items:center; justify-content:center; gap: 10px;
    width: 100%; padding: 16px; border-radius: 14px;
    background: #4a7bd1; color: #fff; border: none;
    font-size: 16px; font-weight: 700; cursor: pointer;
    transition: background .15s, transform .1s;
}
.qmodal-begin-btn:hover  { background: #3563b8; transform: translateY(-1px); }
.qmodal-begin-btn:active { transform: translateY(0); }
.qmodal-countdown { font-size: 15px; color: #5a6a80; text-align:center; margin: 0 0 20px; }
.qmodal-countdown strong { font-size: 24px; color: #ef4444; }
.qmodal-actions { display:flex; gap:10px; justify-content:center; margin-top:4px; }

@media (max-width: 640px) {
    .quiz-bar { padding: 12px 16px; }
    .quiz-bar-left,
    .quiz-bar-right { display:none; }
    .quiz-bar-center { flex:unset; }
    .quiz-body { padding: 0 12px 60px; }
    .q-card { padding: 20px 16px; }
    .qmodal { padding: 28px 20px 24px; }
}
</style>
<script>
(function () {
    /* ── DOM refs ── */
    const guard           = document.getElementById('attempt-guard');
    const assessForm      = document.getElementById('assessment-form');
    const submitBtn       = document.getElementById('submit-btn');
    const progressEl      = document.getElementById('quiz-progress');
    const timerEl         = document.getElementById('quiz-timer');
    const timerDisplay    = document.getElementById('timer-display');
    const rulesBackdrop   = document.getElementById('rules-backdrop');
    const beginBtn        = document.getElementById('begin-btn');
    const leaveBackdrop   = document.getElementById('leave-backdrop');
    const timesupBackdrop = document.getElementById('timesup-backdrop');
    const timesupCount    = document.getElementById('timesup-count');
    const timesupSubmitBtn = document.getElementById('timesup-submit');

    const backUrl    = guard?.dataset.backUrl    ?? null;
    const resultUrl  = guard?.dataset.resultUrl  ?? null;
    const forfeitUrl = guard?.dataset.forfeitUrl ?? null;
    const csrfToken  = guard?.dataset.csrf       ?? null;

    const timeLimit  = timerEl ? parseInt(timerEl.dataset.timeLimit  || '0', 10) : 0;
    const startedAt  = timerEl ? parseInt(timerEl.dataset.startedAt  || '0', 10) * 1000 : 0;

    let allowNavigation = false;
    let forfeiting      = false;
    let pendingUrl      = null;
    let pendingForm     = null;
    let timesupFired    = false;

    /* ── Progress tracker ── */
    const totalQ = document.querySelectorAll('.q-card').length;
    const updateProgress = () => {
        const answered = new Set(
            [...document.querySelectorAll('.q-radio:checked')]
                .map(r => r.dataset.question)
        ).size;
        if (progressEl) progressEl.textContent = `${answered} / ${totalQ} answered`;
        document.querySelectorAll('.q-card').forEach(card => {
            const checked = card.querySelector('.q-radio:checked');
            card.classList.toggle('q-answered', !!checked);
        });
    };
    document.querySelectorAll('.q-radio').forEach(r => r.addEventListener('change', updateProgress));
    updateProgress();

    /* ── Modal helpers ── */
    const showBackdrop = el => {
        if (!el) return;
        el.style.display = 'flex';
        el.setAttribute('aria-hidden', 'false');
    };
    const hideBackdrop = el => {
        if (!el) return;
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
    };

    /* ── Rules modal (shown on load) ── */
    if (beginBtn) {
        beginBtn.addEventListener('click', () => hideBackdrop(rulesBackdrop));
    }

    /* ── Timer ── */
    const WARN_SECS   = 5 * 60;  /* 5 min → orange  */
    const DANGER_SECS = 60;      /* 1 min → red     */
    const BLINK_SECS  = 30;      /* 30 s  → blink   */

    const formatTime = ms => {
        const total = Math.max(0, Math.floor(ms / 1000));
        const h = Math.floor(total / 3600);
        const m = Math.floor((total % 3600) / 60);
        const s = total % 60;
        if (h > 0) {
            return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }
        return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    };

    const submitAssessment = () => {
        allowNavigation = true;
        if (assessForm) assessForm.submit();
    };

    const launchTimesUp = () => {
        if (timesupFired) return;
        timesupFired = true;
        allowNavigation = true;
        showBackdrop(timesupBackdrop);
        let n = 5;
        if (timesupCount) timesupCount.textContent = n;
        const id = setInterval(() => {
            n--;
            if (timesupCount) timesupCount.textContent = n;
            if (n <= 0) { clearInterval(id); submitAssessment(); }
        }, 1000);
    };

    if (timesupSubmitBtn) timesupSubmitBtn.addEventListener('click', submitAssessment);

    if (timeLimit > 0 && startedAt > 0) {
        const endAt = startedAt + timeLimit * 60 * 1000;
        const tick = () => {
            const rem     = endAt - Date.now();
            const remSecs = Math.floor(rem / 1000);
            if (timerDisplay) timerDisplay.textContent = formatTime(rem);
            timerEl.classList.toggle('quiz-timer--warn',   remSecs <= WARN_SECS  && remSecs > DANGER_SECS);
            timerEl.classList.toggle('quiz-timer--danger', remSecs <= DANGER_SECS && remSecs > 0);
            timerEl.classList.toggle('quiz-timer--blink',  remSecs <= BLINK_SECS && remSecs > 0);
            if (rem <= 0) launchTimesUp();
        };
        tick();
        setInterval(tick, 1000);
    }

    /* ── Leave / forfeit ── */
    const openLeave  = () => showBackdrop(leaveBackdrop);
    const closeLeave = () => {
        hideBackdrop(leaveBackdrop);
        pendingUrl = null;
        pendingForm = null;
    };

    const sendForfeit = async () => {
        if (forfeiting || !forfeitUrl || !csrfToken) return;
        forfeiting = true;
        try {
            await fetch(forfeitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new URLSearchParams({ _token: csrfToken }),
                credentials: 'same-origin',
                keepalive: true,
            });
        } catch (_) { /* ignore */ }
    };

    const confirmLeave = async () => {
        await sendForfeit();
        allowNavigation = true;
        closeLeave();
        if (pendingForm) { pendingForm.submit(); return; }
        window.location.href = pendingUrl || backUrl;
    };

    document.querySelectorAll('[data-leave-cancel]').forEach(b  => b.addEventListener('click', closeLeave));
    document.querySelectorAll('[data-leave-confirm]').forEach(b => b.addEventListener('click', confirmLeave));

    if (assessForm) assessForm.addEventListener('submit', () => { allowNavigation = true; });
    if (submitBtn)  submitBtn.addEventListener('click',   () => { allowNavigation = true; });

    const guardNav = (event, url, form) => {
        if (allowNavigation || forfeiting) return;
        if (event) event.preventDefault();
        pendingUrl  = url  || null;
        pendingForm = form || null;
        openLeave();
    };

    document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        link.addEventListener('click', e => guardNav(e, href, null));
    });
    document.querySelectorAll('form').forEach(form => {
        if (form === assessForm) return;
        form.addEventListener('submit', e => guardNav(e, null, form));
    });

    if (window.history?.pushState) {
        window.history.pushState({ guard: true }, '', window.location.href);
        window.addEventListener('popstate', () => {
            if (allowNavigation) return;
            window.history.pushState({ guard: true }, '', window.location.href);
            guardNav(null, backUrl, null);
        });
    }

    window.addEventListener('beforeunload', e => {
        if (allowNavigation || forfeiting) return;
        e.preventDefault();
        e.returnValue = '';
    });

    window.addEventListener('pagehide', () => {
        if (allowNavigation || forfeiting) return;
        if (!forfeitUrl || !csrfToken) return;
        const data = new FormData();
        data.append('_token', csrfToken);
        if (navigator.sendBeacon) {
            navigator.sendBeacon(forfeitUrl, data);
        } else {
            fetch(forfeitUrl, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true });
        }
    });
})();
</script>
@endpush
