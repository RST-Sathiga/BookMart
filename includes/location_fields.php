<?php

$location_grouped = $location_grouped ?? get_universities_grouped($conn);
$selected_university = $selected_university ?? ($_POST['university_id'] ?? $_GET['university_id'] ?? '');
$selected_campus = $selected_campus ?? ($_POST['campus_id'] ?? $_GET['campus_id'] ?? '');
$custom_institution = $custom_institution ?? ($_POST['custom_institution'] ?? $_GET['custom_institution'] ?? '');
$custom_city = $custom_city ?? ($_POST['custom_city'] ?? $_GET['filter_city'] ?? '');
$custom_campus = $custom_campus ?? ($_POST['custom_campus'] ?? '');
$custom_pickup = $custom_pickup ?? ($_POST['custom_pickup'] ?? '');
$location_required = $location_required ?? true;
$location_prefix = $location_prefix ?? '';
$location_mode = $location_mode ?? 'full';
$enable_gps = $location_enable_gps ?? true;
$show_manual = $location_show_manual ?? true;
?>

<div class="location-picker" data-prefix="<?= htmlspecialchars($location_prefix) ?>" data-mode="<?= htmlspecialchars($location_mode) ?>" data-enable-gps="<?= $enable_gps ? '1' : '0' ?>">
    <?php if ($enable_gps): ?>
        <div class="location-gps-bar border rounded p-3 mb-3 bg-light">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong><i class="bi bi-geo-alt-fill text-primary me-1"></i>Use your location</strong>
                    <div class="small text-muted location-gps-status">Sync with GPS to find nearby universities and colleges.</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary location-gps-btn">
                    <i class="bi bi-crosshair me-1"></i>Detect location
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">University / College <?= $location_required ? '*' : '' ?></label>
            <select name="university_id" id="<?= $location_prefix ?>university_id" class="form-select location-university" <?= $location_required ? 'required' : '' ?>>
                <?php if ($location_mode === 'filter'): ?>
                    <option value="">All institutions</option>
                <?php else: ?>
                    <option value="">Select institution</option>
                <?php endif; ?>
                <?php if (!empty($location_grouped['public'])): ?>
                    <optgroup label="Public Universities">
                        <?php foreach ($location_grouped['public'] as $uni): ?>
                            <option value="<?= (int) $uni['id'] ?>" <?= (string) $selected_university === (string) $uni['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($uni['name']) ?> (<?= htmlspecialchars($uni['city']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if (!empty($location_grouped['private'])): ?>
                    <optgroup label="Private Colleges &amp; Institutes">
                        <?php foreach ($location_grouped['private'] as $uni): ?>
                            <option value="<?= (int) $uni['id'] ?>" <?= (string) $selected_university === (string) $uni['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($uni['name']) ?> (<?= htmlspecialchars($uni['city']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if ($show_manual): ?>
                    <option value="other" <?= $selected_university === 'other' ? 'selected' : '' ?>>Other — enter manually</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3 location-campus-wrap">
            <label class="form-label">Campus <?= $location_required ? '*' : '' ?></label>
            <select name="campus_id" id="<?= $location_prefix ?>campus_id" class="form-select location-campus" data-selected="<?= htmlspecialchars((string) $selected_campus) ?>" <?= $location_required ? 'required' : '' ?>>
                <option value=""><?= $location_mode === 'filter' ? 'All campuses' : 'Select campus' ?></option>
            </select>
        </div>
    </div>

    <?php if ($show_manual): ?>
        <div class="location-custom-institution border rounded p-3 mb-3 bg-light" style="display:none;">
            <p class="small text-muted mb-3">Your institution is not listed. Enter it manually — it will be saved for future use.</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">College / University name *</label>
                    <input type="text" name="custom_institution" class="form-control location-custom-institution-input" value="<?= htmlspecialchars($custom_institution) ?>" placeholder="e.g. ABC Private College">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">City *</label>
                    <input type="text" name="<?= $location_mode === 'filter' ? 'filter_city' : 'custom_city' ?>" class="form-control location-custom-city-input" value="<?= htmlspecialchars($custom_city) ?>" placeholder="e.g. Pretoria">
                </div>
            </div>
        </div>

        <div class="location-custom-campus border rounded p-3 mb-3 bg-light" style="display:none;">
            <p class="small text-muted mb-3">Enter your campus and where buyers should meet you on campus.</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Campus name *</label>
                    <input type="text" name="custom_campus" class="form-control location-custom-campus-input" value="<?= htmlspecialchars($custom_campus) ?>" placeholder="e.g. Sandton Campus">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Pickup point on campus *</label>
                    <input type="text" name="custom_pickup" class="form-control location-custom-pickup-input" value="<?= htmlspecialchars($custom_pickup) ?>" placeholder="e.g. Main library entrance">
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
