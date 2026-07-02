(function () {
    const MODEL_BASE = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model/';
    let modelsLoaded = false;
    let stream = null;
    let checkInterval = null;
    let readyStreak = 0;
    let activeWidget = null;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.face-capture-open').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeWidget = btn.closest('.face-capture-widget');
                openModal();
            });
        });

        const modal = document.getElementById('faceCaptureModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', stopCamera);
        }

        document.getElementById('faceCaptureBtn')?.addEventListener('click', capturePhoto);
    });

    async function loadModels() {
        if (modelsLoaded) {
            return;
        }
        if (typeof faceapi === 'undefined') {
            throw new Error('Face detection library not loaded.');
        }
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_BASE);
        await faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_BASE);
        modelsLoaded = true;
    }

    async function openModal() {
        const modalEl = document.getElementById('faceCaptureModal');
        const video = document.getElementById('faceCaptureVideo');
        const statusText = document.querySelector('#faceCaptureStatus .status-text');
        const statusDot = document.querySelector('#faceCaptureStatus .status-dot');
        const hints = document.getElementById('faceCaptureHints');
        const captureBtn = document.getElementById('faceCaptureBtn');

        readyStreak = 0;
        captureBtn.disabled = true;
        statusDot.className = 'status-dot status-red';
        statusText.textContent = 'Starting camera…';
        hints.textContent = '';

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        try {
            await loadModels();
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false
            });
            video.srcObject = stream;
            await video.play();
            startChecks(video, statusDot, statusText, hints, captureBtn);
        } catch (err) {
            statusText.textContent = 'Camera unavailable. Allow camera access or upload a file.';
            hints.textContent = err.message || '';
        }
    }

    function startChecks(video, statusDot, statusText, hints, captureBtn) {
        if (checkInterval) {
            clearInterval(checkInterval);
        }

        checkInterval = setInterval(async function () {
            if (video.readyState < 2) {
                return;
            }

            const result = await evaluateFrame(video);
            updateStatus(result, statusDot, statusText, hints, captureBtn);
        }, 350);
    }

    async function evaluateFrame(video) {
        const detection = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
            .withFaceLandmarks(true);

        if (!detection) {
            return { ok: false, message: 'No face detected — centre your face in the frame.' };
        }

        const box = detection.detection.box;
        const videoW = video.videoWidth;
        const videoH = video.videoHeight;

        if (box.width / videoW < 0.22) {
            return { ok: false, message: 'Move closer to the camera.' };
        }

        if (box.width / videoW > 0.75) {
            return { ok: false, message: 'Move slightly back from the camera.' };
        }

        const centerX = box.x + box.width / 2;
        const centerY = box.y + box.height / 2;
        if (Math.abs(centerX - videoW / 2) > videoW * 0.15 || Math.abs(centerY - videoH / 2) > videoH * 0.15) {
            return { ok: false, message: 'Centre your face in the circle.' };
        }

        const brightness = sampleBrightness(video, box);
        if (brightness < 70) {
            return { ok: false, message: 'Lighting too low — move to a brighter area.' };
        }
        if (brightness > 215) {
            return { ok: false, message: 'Too bright — reduce glare or move away from direct light.' };
        }

        const glasses = detectPossibleGlasses(video, detection.landmarks);
        if (glasses) {
            return { ok: false, message: 'Remove glasses before capturing (ID-style photo).' };
        }

        return { ok: true, message: 'Good lighting and face position — ready to capture.' };
    }

    function sampleBrightness(video, box) {
        const canvas = document.getElementById('faceCaptureCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        const data = ctx.getImageData(box.x, box.y, box.width, box.height).data;
        let total = 0;
        for (let i = 0; i < data.length; i += 4) {
            total += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
        }
        return total / (data.length / 4);
    }

    function detectPossibleGlasses(video, landmarks) {
        const canvas = document.getElementById('faceCaptureCanvas');
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const leftEye = landmarks.getLeftEye();
        const rightEye = landmarks.getRightEye();
        const nose = landmarks.getNose();

        const eyeBrightness = (points) => {
            let minX = Infinity, minY = Infinity, maxX = 0, maxY = 0;
            points.forEach(function (p) {
                minX = Math.min(minX, p.x);
                minY = Math.min(minY, p.y);
                maxX = Math.max(maxX, p.x);
                maxY = Math.max(maxY, p.y);
            });
            const w = Math.max(1, maxX - minX);
            const h = Math.max(1, maxY - minY);
            const data = ctx.getImageData(minX, minY, w, h).data;
            let total = 0;
            for (let i = 0; i < data.length; i += 4) {
                total += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
            }
            return total / (data.length / 4);
        };

        const eyeAvg = (eyeBrightness(leftEye) + eyeBrightness(rightEye)) / 2;
        const cheekPoints = nose.slice(0, 4);
        let cheekTotal = 0;
        cheekPoints.forEach(function (p) {
            const pixel = ctx.getImageData(p.x, p.y, 1, 1).data;
            cheekTotal += 0.299 * pixel[0] + 0.587 * pixel[1] + 0.114 * pixel[2];
        });
        const cheekAvg = cheekTotal / cheekPoints.length;

        return eyeAvg < cheekAvg * 0.55;
    }

    function updateStatus(result, statusDot, statusText, hints, captureBtn) {
        if (result.ok) {
            readyStreak++;
            statusDot.className = 'status-dot status-green';
            statusText.textContent = 'Ready — hold still';
            hints.textContent = result.message;
            captureBtn.disabled = readyStreak < 2;
        } else {
            readyStreak = 0;
            statusDot.className = 'status-dot status-red';
            statusText.textContent = 'Adjust position';
            hints.textContent = result.message;
            captureBtn.disabled = true;
        }
    }

    function capturePhoto() {
        const video = document.getElementById('faceCaptureVideo');
        const canvas = document.getElementById('faceCaptureCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);

        canvas.toBlob(function (blob) {
            if (!blob || !activeWidget) {
                return;
            }

            const inputName = activeWidget.dataset.inputName;
            const previewId = activeWidget.dataset.previewId;
            const file = new File([blob], 'profile_capture.jpg', { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            const input = activeWidget.querySelector('.face-capture-file') || document.getElementById(inputName);
            if (input) {
                input.files = dataTransfer.files;
            }

            const preview = document.getElementById(previewId);
            const sidebarAvatar = document.getElementById('accountSidebarAvatar');
            const url = URL.createObjectURL(blob);

            if (preview) {
                preview.src = url;
            }
            if (sidebarAvatar) {
                sidebarAvatar.src = url;
            }

            const ring = document.getElementById(previewId + 'Ring');
            if (ring) {
                ring.classList.add('face-avatar-ready');
            }

            bootstrap.Modal.getInstance(document.getElementById('faceCaptureModal')).hide();
        }, 'image/jpeg', 0.92);
    }

    function stopCamera() {
        if (checkInterval) {
            clearInterval(checkInterval);
            checkInterval = null;
        }
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        readyStreak = 0;
    }
})();
