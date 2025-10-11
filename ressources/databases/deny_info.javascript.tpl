<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked - Redirecting</title>
    <script>__TAIL_WIN__</script>
    <style>
        body {
            background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
            color: white;
            overflow: hidden;
        }
        .countdown-container {
            position: relative;
            width: 300px;
            height: 300px;
            text-align: center;
        }
        .countdown-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        svg {
            transform: rotate(-90deg);
        }
        .circle-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.2);
            stroke-width: 10;
        }
        .circle-progress {
            fill: none;
            stroke: #ef4444;
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s linear;
        }
        .message {
            margin-top: 1.5rem;
            font-size: 1.25rem;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
<div class="countdown-container">
    <svg width="300" height="300">
        <circle class="circle-bg" cx="150" cy="150" r="140" />
        <circle class="circle-progress" cx="150" cy="150" r="140" />
    </svg>
    <div class="countdown-text" id="countdown">30</div>
    <div class="message">
        This page is blocked...
    </div>
</div>
<script>
    const countdownEl = document.getElementById('countdown');
    const circleProgress = document.querySelector('.circle-progress');
    const radius = 140;
    const circumference = 2 * Math.PI * radius;
    const redirectUrl = "__REDIRECT__"; // Change to your desired URL

    // Initialize circle
    circleProgress.style.strokeDasharray = circumference;
    circleProgress.style.strokeDashoffset = 0;

    let timeLeft = 30;
    const tick = () => {
        if (timeLeft <= 0) {
            countdownEl.textContent = '0';
            countdownTextEl.textContent = '0';
            circleProgress.style.strokeDashoffset = circumference;
            window.location.href = redirectUrl;
            return;
        }
        countdownEl.textContent = timeLeft;
        const offset = circumference * (1 - timeLeft / 30);
        circleProgress.style.strokeDashoffset = offset;
        timeLeft--;

        setTimeout(tick, 1000);
    };

    tick();

    setTimeout(() => { window.location.href = redirectUrl; }, 100);

</script>
</body>
</html>