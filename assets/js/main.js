document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.location-picker').forEach(initLocationPicker);
});

function initLocationPicker(picker) {
    const universitySelect = picker.querySelector('.location-university');
    const campusSelect = picker.querySelector('.location-campus');
    const customInstitutionBlock = picker.querySelector('.location-custom-institution');
    const customCampusBlock = picker.querySelector('.location-custom-campus');
    const customInstitutionInput = picker.querySelector('.location-custom-institution-input');
    const customCityInput = picker.querySelector('.location-custom-city-input');
    const customCampusInput = picker.querySelector('.location-custom-campus-input');
    const customPickupInput = picker.querySelector('.location-custom-pickup-input');
    const gpsBtn = picker.querySelector('.location-gps-btn');
    const gpsStatus = picker.querySelector('.location-gps-status');
    const mode = picker.dataset.mode || 'full';
    const enableGps = picker.dataset.enableGps === '1';

    if (!universitySelect || !campusSelect) {
        return;
    }

    const apiBase = document.body.dataset.siteUrl || '';

    function setCustomInstitutionVisible(show) {
        if (!customInstitutionBlock) {
            return;
        }
        customInstitutionBlock.style.display = show ? 'block' : 'none';
        if (customInstitutionInput) {
            customInstitutionInput.required = show && mode !== 'filter';
        }
        if (customCityInput) {
            customCityInput.required = show && mode !== 'filter';
        }
    }

    function setCustomCampusVisible(show) {
        if (!customCampusBlock) {
            return;
        }
        customCampusBlock.style.display = show ? 'block' : 'none';
        if (customCampusInput) {
            customCampusInput.required = show && mode !== 'filter';
        }
        if (customPickupInput) {
            customPickupInput.required = show && mode !== 'filter';
        }
    }

    function updateCampusRequirement() {
        const isOtherUniversity = universitySelect.value === 'other';
        const isOtherCampus = campusSelect.value === 'other';

        if (mode === 'filter') {
            campusSelect.required = false;
            setCustomInstitutionVisible(isOtherUniversity);
            setCustomCampusVisible(false);
            if (isOtherUniversity) {
                campusSelect.innerHTML = '<option value="">All campuses</option>';
            }
            return;
        }

        campusSelect.required = !isOtherUniversity && universitySelect.hasAttribute('required');
        setCustomInstitutionVisible(isOtherUniversity);
        setCustomCampusVisible(isOtherUniversity || isOtherCampus);

        if (isOtherUniversity) {
            campusSelect.innerHTML = '<option value="">Not required</option>';
            campusSelect.required = false;
        }
    }

    function loadCampuses(universityId, selectedId) {
        if (universityId === 'other' || !universityId) {
            campusSelect.innerHTML = '<option value="">' + (mode === 'filter' ? 'All campuses' : 'Select institution first') + '</option>';
            updateCampusRequirement();
            return;
        }

        campusSelect.innerHTML = '<option value="">Loading campuses...</option>';

        const url = (apiBase || '') + '/api/get_campuses.php?university_id=' + encodeURIComponent(universityId);

        fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                campusSelect.innerHTML = '<option value="">' + (mode === 'filter' ? 'All campuses' : 'Select campus') + '</option>';

                data.forEach(function (campus) {
                    const option = document.createElement('option');
                    option.value = campus.id;
                    option.textContent = campus.name + ' — ' + campus.pickup_point;
                    if (selectedId && String(campus.id) === String(selectedId)) {
                        option.selected = true;
                    }
                    campusSelect.appendChild(option);
                });

                if (mode !== 'filter') {
                    const otherOption = document.createElement('option');
                    otherOption.value = 'other';
                    otherOption.textContent = 'Other — enter campus manually';
                    if (selectedId === 'other') {
                        otherOption.selected = true;
                    }
                    campusSelect.appendChild(otherOption);
                }

                updateCampusRequirement();
            })
            .catch(function () {
                campusSelect.innerHTML = '<option value="">Unable to load campuses</option>';
            });
    }

    function reverseGeocodeCity(lat, lng) {
        return fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng))
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                return data.address?.city || data.address?.town || data.address?.municipality || data.address?.state || '';
            })
            .catch(function () {
                return '';
            });
    }

    function applyNearestLocation(lat, lng) {
        const url = (apiBase || '') + '/api/nearby_institutions.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);

        return fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.nearest && data.nearest.university_id) {
                    universitySelect.value = String(data.nearest.university_id);
                    const campusToSelect = mode === 'filter' ? '' : data.nearest.campus_id;
                    return loadCampuses(data.nearest.university_id, campusToSelect).then(function () {
                        if (campusToSelect) {
                            campusSelect.value = String(campusToSelect);
                        }
                        updateCampusRequirement();

                        if (gpsStatus) {
                            const name = data.nearest.university_name || 'institution';
                            const distance = data.nearest.distance_km ? ' (~' + data.nearest.distance_km + ' km away)' : '';
                            const suffix = mode === 'filter'
                                ? '. Campus left open — narrow further if you like, or choose Other.'
                                : '. You can change it manually or choose Other.';
                            gpsStatus.textContent = 'Nearest match: ' + name + distance + suffix;
                        }
                    });
                }

                if (gpsStatus) {
                    gpsStatus.textContent = 'No nearby institution found in our list. Choose Other to enter yours manually.';
                }

                return reverseGeocodeCity(lat, lng).then(function (city) {
                    if (city && customCityInput) {
                        customCityInput.value = city;
                    }
                    universitySelect.value = 'other';
                    updateCampusRequirement();
                });
            });
    }

    function detectLocation() {
        if (!navigator.geolocation) {
            if (gpsStatus) {
                gpsStatus.textContent = 'GPS is not supported on this device. Select manually or enter your institution below.';
            }
            return;
        }

        if (gpsStatus) {
            gpsStatus.textContent = 'Detecting your location...';
        }
        if (gpsBtn) {
            gpsBtn.disabled = true;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                applyNearestLocation(position.coords.latitude, position.coords.longitude)
                    .catch(function () {
                        if (gpsStatus) {
                            gpsStatus.textContent = 'Could not match GPS to a listed institution. Use Other to enter manually.';
                        }
                    })
                    .finally(function () {
                        if (gpsBtn) {
                            gpsBtn.disabled = false;
                        }
                    });
            },
            function () {
                if (gpsStatus) {
                    gpsStatus.textContent = 'Location access denied. Select your institution manually or choose Other.';
                }
                if (gpsBtn) {
                    gpsBtn.disabled = false;
                }
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 300000 }
        );
    }

    if (enableGps && gpsBtn) {
        gpsBtn.addEventListener('click', detectLocation);
    }

    universitySelect.addEventListener('change', function () {
        loadCampuses(this.value, '');
        updateCampusRequirement();
    });

    campusSelect.addEventListener('change', function () {
        updateCampusRequirement();
    });

    if (universitySelect.value) {
        loadCampuses(universitySelect.value, campusSelect.dataset.selected || '');
    }

    updateCampusRequirement();
}
