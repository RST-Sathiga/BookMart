(function () {
    if (!window.BOOKMART_CHAT) {
        return;
    }

    const cfg = window.BOOKMART_CHAT;
    let pc = null;
    let localStream = null;
    let pollTimer = null;
    let lastSignalId = 0;
    let isCaller = false;

    const startBtn = document.getElementById('startCallBtn');
    const endBtn = document.getElementById('endCallBtn');
    const panel = document.getElementById('chatCallPanel');
    const statusText = document.getElementById('callStatusText');
    const remoteAudio = document.getElementById('remoteAudio');

    startBtn?.addEventListener('click', startCall);
    endBtn?.addEventListener('click', endCall);

    startPolling();

    function startPolling() {
        pollTimer = setInterval(pollSignals, 2000);
    }

    async function pollSignals() {
        try {
            const res = await fetch(cfg.signalUrl + '?chat_key=' + encodeURIComponent(cfg.chatKey) + '&since_id=' + lastSignalId);
            const data = await res.json();
            if (!data.signals) {
                return;
            }
            for (const signal of data.signals) {
                lastSignalId = Math.max(lastSignalId, parseInt(signal.id, 10));
                await handleSignal(signal);
            }
        } catch (e) {
            /* ignore polling errors */
        }
    }

    async function handleSignal(signal) {
        if (signal.signal_type === 'offer' && !pc) {
            panel.classList.remove('d-none');
            statusText.textContent = 'Incoming call…';
            await setupPeer(false);
            const offer = JSON.parse(signal.payload);
            await pc.setRemoteDescription(offer);
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            await sendSignal('answer', JSON.stringify(answer));
            statusText.textContent = 'Call connected';
        } else if (signal.signal_type === 'answer' && pc && isCaller) {
            const answer = JSON.parse(signal.payload);
            await pc.setRemoteDescription(answer);
            statusText.textContent = 'Call connected';
        } else if (signal.signal_type === 'ice' && pc) {
            const candidate = JSON.parse(signal.payload);
            if (candidate) {
                await pc.addIceCandidate(candidate);
            }
        } else if (signal.signal_type === 'hangup') {
            cleanupCall('Call ended');
        }
    }

    async function startCall() {
        panel.classList.remove('d-none');
        statusText.textContent = 'Calling…';
        isCaller = true;
        await setupPeer(true);
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        await sendSignal('offer', JSON.stringify(offer));
        await sendSignal('ringing', '{}');
    }

    async function setupPeer(caller) {
        pc = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        });

        pc.ontrack = function (event) {
            remoteAudio.srcObject = event.streams[0];
            remoteAudio.classList.remove('d-none');
        };

        pc.onicecandidate = function (event) {
            if (event.candidate) {
                sendSignal('ice', JSON.stringify(event.candidate));
            }
        };

        localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        localStream.getTracks().forEach(function (track) {
            pc.addTrack(track, localStream);
        });
    }

    async function sendSignal(type, payload) {
        const body = new FormData();
        body.append('chat_key', cfg.chatKey);
        body.append('type', type);
        body.append('payload', payload);
        await fetch(cfg.signalUrl, { method: 'POST', body: body });
    }

    function endCall() {
        sendSignal('hangup', '{}');
        cleanupCall('Call ended');
    }

    function cleanupCall(message) {
        statusText.textContent = message;
        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        if (pc) {
            pc.close();
            pc = null;
        }
        isCaller = false;
        setTimeout(function () {
            panel.classList.add('d-none');
        }, 1500);
    }
})();
