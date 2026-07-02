<?php

$face_input_name = $face_input_name ?? 'profile_image';
$face_preview_id = $face_preview_id ?? 'profilePreview';
$face_required = $face_required ?? true;
?>

<div class="face-capture-widget mb-3" data-input-name="<?= htmlspecialchars($face_input_name) ?>" data-preview-id="<?= htmlspecialchars($face_preview_id) ?>">
    <div class="face-capture-preview text-center mb-3">
        <div class="face-avatar-ring mx-auto" id="<?= htmlspecialchars($face_preview_id) ?>Ring">
            <img src="<?= profile_image_url($account_user['profile_image'] ?? $_SESSION['profile_image'] ?? null) ?>"
                 alt="Profile preview"
                 class="face-avatar-img"
                 id="<?= htmlspecialchars($face_preview_id) ?>"
                 width="120" height="120">
        </div>
        <p class="small text-muted mt-2 mb-0">Your photo appears here after capture</p>
    </div>

    <input type="file" name="<?= htmlspecialchars($face_input_name) ?>" id="<?= htmlspecialchars($face_input_name) ?>" class="d-none face-capture-file" accept="image/*" <?= $face_required ? 'required' : '' ?>>

    <div class="face-capture-rules border rounded p-3 mb-3 bg-light small">
        <strong><i class="bi bi-shield-check me-1"></i>ID-style photo rules</strong>
        <ul class="mb-0 mt-2 ps-3">
            <li>Face the camera directly — no side angles</li>
            <li>Remove glasses and hats</li>
            <li>Use even, bright lighting (avoid shadows on your face)</li>
            <li>Plain background if possible</li>
            <li>Wait for the <span class="text-success fw-bold">green light</span> before capturing</li>
        </ul>
    </div>

    <button type="button" class="btn btn-primary w-100 face-capture-open">
        <i class="bi bi-camera me-1"></i>Open Camera
    </button>
</div>

<div class="modal fade" id="faceCaptureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Take Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="face-capture-video-wrap mx-auto mb-3">
                    <video id="faceCaptureVideo" autoplay playsinline muted></video>
                    <canvas id="faceCaptureCanvas" class="d-none"></canvas>
                    <div class="face-capture-status" id="faceCaptureStatus">
                        <span class="status-dot status-red"></span>
                        <span class="status-text">Starting camera…</span>
                    </div>
                </div>
                <div id="faceCaptureHints" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="faceCaptureBtn" disabled>
                    <i class="bi bi-check-circle me-1"></i>Capture Photo
                </button>
            </div>
        </div>
    </div>
</div>
