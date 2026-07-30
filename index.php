<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(120);

if (!file_exists('madeline.phar')) {
    die("Error: madeline.phar file does not exist!");
}

require_once 'madeline.phar';

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;

try {
    // تنظیمات استاندارد نسخه جدید
    $settings = new Settings();
    $appInfo = new AppInfo();
    $appInfo->setApiId(6);
    $appInfo->setApiHash('eb06d4abfb49d3eef5a3d84377c714d9');
    $settings->setAppInfo($appInfo);

    $MadelineProto = new API('session.madeline', $settings);
    
    // شروع فرایند لاگین
    $MadelineProto->start();

    if ($MadelineProto->getSelf()) {
        echo "<h1>لاگین با موفقیت انجام شد!</h1>";
        $MadelineProto->messages->sendMessage([
            'peer' => 'me',
            'message' => 'سلام علی! میدلاین با موفقیت متصل شد 🔥'
        ]);
    }

} catch (\Throwable $e) {
    echo "<br><strong>ارور رخ داده:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
// دریافت پیام
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_msg = trim($_POST['user_message'] ?? '');

    if ($user_msg === '') {
        $_SESSION['flash_error'] = 'لطفاً یک پیام بنویس.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (mb_strlen($user_msg) > 2000) {
        $_SESSION['flash_error'] = 'پیام خیلی طولانی است؛ حداکثر ۲۰۰۰ کاراکتر بنویس.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($api_key === '' || $api_key === 'کلید_جدید_اینجا') {
        $_SESSION['flash_error'] = 'کلید Gemini در فایل config.php تنظیم نشده است.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (!function_exists('curl_init')) {
        $_SESSION['flash_error'] = 'افزونه cURL روی این هاست فعال نیست.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $_SESSION['chat_history'][] = [
        'role' => 'user',
        'parts' => [
            ['text' => $user_msg]
        ]
    ];

    $data = [
        'systemInstruction' => [
            'parts' => [
                ['text' => $system_prompt]
            ]
        ],
        'contents' => $_SESSION['chat_history'],
        'generationConfig' => [
            'temperature' => 0.8,
            'maxOutputTokens' => 1000
        ]
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
         . rawurlencode($model)
         . ':generateContent';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $api_key
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response   = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $response_data = json_decode($response ?: '', true);

    if ($response === false) {
        $_SESSION['flash_error'] =
            'ارتباط با Gemini برقرار نشد: ' . $curl_error;

        array_pop($_SESSION['chat_history']);
    } elseif ($http_code < 200 || $http_code >= 300) {
        $api_error = $response_data['error']['message']
            ?? 'خطای نامشخص از Gemini';

        $_SESSION['flash_error'] =
            "خطای API با کد {$http_code}: {$api_error}";

        array_pop($_SESSION['chat_history']);
    } else {
        $parts = $response_data['candidates'][0]['content']['parts'] ?? [];
        $texts = [];

        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        $ai_response = trim(implode("\n", $texts));

        if ($ai_response === '') {
            $finish_reason =
                $response_data['candidates'][0]['finishReason']
                ?? 'UNKNOWN';

            $_SESSION['flash_error'] =
                "پاسخ متنی دریافت نشد. دلیل پایان: {$finish_reason}";

            array_pop($_SESSION['chat_history']);
        } else {
            $_SESSION['chat_history'][] = [
                'role' => 'model',
                'parts' => [
                    ['text' => $ai_response]
                ]
            ];

            // نگهداری حداکثر ۱۰ رفت‌وبرگشت برای جلوگیری از سنگین‌شدن Session
            if (count($_SESSION['chat_history']) > 20) {
                $_SESSION['chat_history'] = array_slice(
                    $_SESSION['chat_history'],
                    -20
                );
            }
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

        .mc-title {
            font-family: 'Minecraftia', sans-serif; color: var(--banana-yellow);
            margin-bottom: 25px; font-size: 22px; text-shadow: 2px 2px 0px #000;
        }

        header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 20px; background-color: rgba(0, 0, 0, 0.6); border-bottom: 4px solid var(--mc-dirt);
        }

        .logo { font-family: 'Minecraftia', sans-serif; font-size: 22px; color: var(--banana-yellow); text-shadow: 2px 2px 0px #000; }

        .workspace { flex: 1; display: flex; overflow: hidden; }

        main {
            flex: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 15px; padding: 20px; overflow-y: auto;
            background-image: url('https://www.transparenttextures.com/patterns/dark-matter.png');
        }

        .video-card {
            background-color: #1e1e1e; border: 4px solid var(--mc-dirt);
            position: relative; display: flex; justify-content: center; align-items: center;
            box-shadow: 4px 4px 0px rgba(0,0,0,0.5); height: 280px;
        }

        video { width: 100%; height: 100%; object-fit: cover; background: #000; }

        .user-tag {
            position: absolute; bottom: 10px; right: 10px;
            background-color: rgba(0, 0, 0, 0.8); padding: 4px 10px; font-size: 13px; border: 2px solid var(--mc-dirt);
        }

        .chat-sidebar { width: 280px; background: #111; border-left: 4px solid var(--mc-dirt); display: flex; flex-direction: column; padding: 10px; }
        .chat-messages { flex: 1; overflow-y: auto; background: #000; border: 2px solid var(--mc-light-gray); padding: 10px; font-size: 13px; margin-bottom: 10px; }

        .mc-btn {
            font-family: 'Minecraftia', sans-serif; background: #777;
            border: 2px solid #fff; border-right-color: #444; border-bottom-color: #444;
            color: #fff; padding: 10px 15px; cursor: pointer; text-shadow: 1px 1px 0px #000; font-size: 12px;
        }
        .mc-btn:active { border-top-color: #444; border-left-color: #444; border-right-color: #fff; border-bottom-color: #fff; }

        .controls-bar { background-color: #050505; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-top: 4px solid var(--mc-dirt); }
        .btn-toggle { width: 45px; height: 45px; font-size: 18px; border-radius: 6px; }
        .btn-on { background: var(--mc-green); }
        .btn-off { background: #ea4335; }

        input { padding: 10px; background: #000; border: 2px solid var(--mc-dirt); color: #fff; margin-bottom: 15px; width: 100%; text-align: center; }
        .action-divider { margin: 15px 0; display: flex; align-items: center; justify-content: center; font-family: 'Minecraftia', sans-serif; font-size: 12px; color: #aaa; }
        .action-divider::before, .action-divider::after { content: ""; flex: 1; height: 2px; background: var(--mc-dirt); margin: 0 10px; }
    </style>
</head>
<body>

    <!-- منوی اصلی -->
    <div id="login-modal" class="modal-overlay">
        <div class="mc-box">
            <div class="mc-title">⚔️ منوی اصلی موز میت ⚔️</div>
            <input type="text" id="username-input" placeholder="نام کاربری شما" value="Player">
            
            <button class="mc-btn" onclick="startSession(true)" style="background:var(--mc-green); width:100%;">➕ ساخت میت جدید (دریافت کد)</button>
            <div class="action-divider">یا</div>
            <input type="text" id="room-code-input" placeholder="کد عددی میتینگ رفیقت">
            <button class="mc-btn" onclick="startSession(false)" style="background:#29b6f6; width:100%;">🚪 ورود به میت</button>
        </div>
    </div>

    <header>
        <div class="logo">🍌 Moوز Meet</div>
        <div style="font-size:14px;">کد اتاق: <strong id="room-display" style="color:var(--banana-yellow); font-family:'Minecraftia'; text-shadow: 1px 1px 0px #000;">---</strong></div>
    </header>

    <div class="workspace">
        <main id="video-grid">
            <div class="video-card">
                <video id="localVideo" autoplay playsinline muted></video>
                <div class="user-tag">🎙️ <span id="my-name-tag">Player</span> (شما)</div>
            </div>
            <div class="video-card" id="remote-card" style="display:none;">
                <video id="remoteVideo" autoplay playsinline></video>
                <div class="user-tag">🎙️ <span id="remote-name-tag">Player 2</span></div>
            </div>
        </main>

        <div class="chat-sidebar">
            <div style="font-family:'Minecraftia'; font-size:11px; margin-bottom:5px; color:var(--banana-yellow);">💬 چت باکس:</div>
            <div class="chat-messages" id="chat-box"></div>
            <div style="display:flex; gap:5px;">
                <input type="text" id="chat-input" style="flex:1; margin:0;" placeholder="پیام...">
                <button class="mc-btn" onclick="sendMsg()">ارسال</button>
            </div>
        </div>
    </div>

    <div class="controls-bar">
        <div style="font-family:'Minecraftia'; font-size:12px; color:#aaa;">Ver 1.21.11</div>
        <div style="display:flex; gap:10px;">
            <button id="btn-mic" class="mc-btn btn-toggle btn-on" onclick="toggleMic()">🎙️</button>
            <button id="btn-cam" class="mc-btn btn-toggle btn-on" onclick="toggleCam()">📷</button>
            <button id="btn-screen" class="mc-btn btn-toggle" style="background:#29b6f6" onclick="toggleScreenShare()">🖥️</button>
            <button class="mc-btn btn-toggle" style="background:#ffa726" onclick="respawnStream()" title="توتم احیای استریم">🌾</button>
        </div>
        <button class="mc-btn" style="background:#ea4335;" onclick="leaveCall()">خروج از کال ❌</button>
    </div>

    <script>
        let localStream;
        let screenStream;
        let peer;
        let currentCall;
        let myUsername = "Player";
        let isMicOn = true;
        let isCamOn = true;
        let isSharingScreen = false;

        async function startSession(isHost) {
            const userIn = document.getElementById('username-input').value.trim();
            if(userIn) myUsername = userIn;
            document.getElementById('my-name-tag').innerText = myUsername;

            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('localVideo').srcObject = localStream;
            } catch (err) {
                alert('علی آقا! مرورگر جلوی دوربین رو گرفته. اون بالا سمت چپ آدرس‌بار روی علامت دوربین یا قفل کلیک کن و گزینه Allow رو بزن، بعد صفحه رو ریفرش کن.');
                return;
            }

            document.getElementById('login-modal').style.display = 'none';

            if (isHost) {
                const randomCode = Math.floor(1000 + Math.random() * 9000).toString();
                document.getElementById('room-display').innerText = randomCode;
                
                // هاست با شناسه ثابت اتاق بالا میاد
                peer = new Peer('mooz-meet-room-' + randomCode);
                
                peer.on('open', () => {
                    logChat(`[سیستم]: اتاق با شماره ${randomCode} ساخته شد. این عدد رو بده به رفیقت.`);
                });

                peer.on('call', call => {
                    call.answer(localStream);
                    handleCall(call);
                });
            } else {
                const targetCode = document.getElementById('room-code-input').value.trim();
                if(!targetCode) {
                    alert('کد عددی رو وارد کن!');
                    location.reload();
                    return;
                }
                document.getElementById('room-display').innerText = targetCode;
                
                // مهمان با یه آی‌دی کاملا رندوم بالا میاد تا تداخل ایجاد نشه
                peer = new Peer(); 
                
                peer.on('open', () => {
                    logChat('[سیستم]: در حال اتصال به اتاق هاست...');
                    // زنگ زدن مستقیم به هاستِ اون اتاق
                    const call = peer.call('mooz-meet-room-' + targetCode, localStream);
                    handleCall(call);
                });
            }

            peer.on('error', err => {
                console.error(err);
                logChat(`[خطا]: ارتباط برقرار نشد. کد اتاق رو چک کن.`);
            });
        }

        function handleCall(call) {
            currentCall = call;
            document.getElementById('remote-card').style.display = 'flex';
            
            call.on('stream', remoteStream => {
                const remoteVideo = document.getElementById('remoteVideo');
                remoteVideo.srcObject = remoteStream;
                logChat('[سیستم]: پلیر دوم متصل شد و تصویر لود شد! ✅');
            });

            call.on('close', () => { removeRemoteStream(); });
        }

        function removeRemoteStream() {
            document.getElementById('remote-card').style.display = 'none';
            document.getElementById('remoteVideo').srcObject = null;
            logChat('[سیستم]: پلیر مقابل از کال خارج شد.');
        }

        function toggleMic() {
            isMicOn = !isMicOn;
            localStream.getAudioTracks()[0].enabled = isMicOn;
            document.getElementById('btn-mic').className = `mc-btn btn-toggle ${isMicOn ? 'btn-on' : 'btn-off'}`;
        }

        function toggleCam() {
            isCamOn = !isCamOn;
            localStream.getVideoTracks()[0].enabled = isCamOn;
            document.getElementById('btn-cam').className = `mc-btn btn-toggle ${isCamOn ? 'btn-on' : 'btn-off'}`;
        }

        async function toggleScreenShare() {
            if (!isSharingScreen) {
                try {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    document.getElementById('localVideo').srcObject = screenStream;
                    isSharingScreen = true;
                    document.getElementById('btn-screen').style.background = '#ea4335';
                    if (currentCall) {
                        let videoTrack = screenStream.getVideoTracks()[0];
                        let sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
                        if(sender) sender.replaceTrack(videoTrack);
                    }
                    screenStream.getVideoTracks()[0].onended = () => { stopScreenShare(); };
                } catch (err) { console.log(err); }
            } else { stopScreenShare(); }
        }

        function stopScreenShare() {
            if(!isSharingScreen) return;
            screenStream.getTracks().forEach(track => track.stop());
            document.getElementById('localVideo').srcObject = localStream;
            isSharingScreen = false;
            document.getElementById('btn-screen').style.background = '#29b6f6';
        }

        async function respawnStream() {
            if(localStream) localStream.getTracks().forEach(track => track.stop());
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('localVideo').srcObject = localStream;
                isMicOn = true; isCamOn = true;
                document.getElementById('btn-mic').className = "mc-btn btn-toggle btn-on";
                document.getElementById('btn-cam').className = "mc-btn btn-toggle btn-on";
                logChat(`[توتم]: استریم با موفقیت احیا شد! 🌾`);
            } catch(e) { alert('خطا در احیای مدیا.'); }
        }

        function leaveCall() {
            if(localStream) localStream.getTracks().forEach(track => track.stop());
            if(peer) peer.destroy();
            removeRemoteStream();
            location.reload();
        }

        window.addEventListener('beforeunload', () => { leaveCall(); });

        function sendMsg() {
            const inp = document.getElementById('chat-input');
            if(!inp.value) return;
            logChat(`<strong>${myUsername}:</strong> ${inp.value}`);
            inp.value = '';
        }

        function logChat(text) {
            const box = document.getElementById('chat-box');
            box.innerHTML += `<p>${text}</p>`;
            box.scrollTop = box.scrollHeight;
        }
    </script>
</body>
</html>
