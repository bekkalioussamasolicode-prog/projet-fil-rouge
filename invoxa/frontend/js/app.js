/* =========================================================================
   INVOXA APP JS (simplified version - no MVC routing)
   =========================================================================
   PART 1: Dashboard Charts (Chart.js)
   PART 2: Drag & Drop file upload + simulated OCR
   PART 3: Global search bar
   ========================================================================= */

document.addEventListener('DOMContentLoaded', function() {

    // ========================================================
    // PART 0: MOBILE SIDEBAR TOGGLE
    // ========================================================
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var hamburgerBtn = document.getElementById('hamburgerBtn');
    var sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (sidebarOverlay) sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restore scroll
    }

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', openSidebar);
    if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // Close sidebar when a nav link is clicked (on mobile)
    var sidebarLinks = document.querySelectorAll('.sidebar .menu-link');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });

    // Close sidebar on window resize if going above 1024px
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });

    // ========================================================
    // PART 1: DASHBOARD CHARTS
    // ========================================================

    var lineCanvas = document.getElementById('monthlySpendingChart');
    if (lineCanvas && typeof monthlySpendingData !== 'undefined') {
        var ctx = lineCanvas.getContext('2d');
        var labels = monthlySpendingData.map(function(item) { return item.month; });
        var dataValues = monthlySpendingData.map(function(item) { return item.total; });

        var gradient = ctx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, 'rgba(0, 120, 187, 0.2)');
        gradient.addColorStop(1, 'rgba(0, 120, 187, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Spend',
                    data: dataValues,
                    borderColor: '#0078BB',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0078BB',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#E6ECF1' },
                        ticks: { callback: function(value) { return value + ' MAD'; }, font: { family: 'Poppins', size: 11 } }
                    },
                    x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } }
                }
            }
        });
    }

    var donutCanvas = document.getElementById('expensesByCategoryChart');
    if (donutCanvas && typeof categoryBreakdownData !== 'undefined') {
        var ctx2 = donutCanvas.getContext('2d');
        var labels2 = categoryBreakdownData.map(function(item) { return item.category_name; });
        var values2 = categoryBreakdownData.map(function(item) { return item.total; });
        var colors = ['#1C335C', '#0078BB', '#98A3A9', '#10B981', '#F59E0B', '#64748B'];

        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: labels2,
                datasets: [{ data: values2, backgroundColor: colors, borderWidth: 2, hoverOffset: 4 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // ========================================================
    // PART 2: DRAG & DROP + OCR UPLOAD
    // ========================================================

    function setupDragDrop(zone, input, onFile) {
        if (!zone || !input) return;
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
            zone.addEventListener(eventName, function(e) { e.preventDefault(); e.stopPropagation(); }, false);
        });
        zone.addEventListener('dragover', function() { zone.classList.add('dragover'); }, false);
        zone.addEventListener('dragleave', function() { zone.classList.remove('dragover'); }, false);
        zone.addEventListener('drop', function(e) {
            zone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) onFile(e.dataTransfer.files[0]);
        }, false);
        input.addEventListener('change', function() {
            if (input.files.length > 0) onFile(input.files[0]);
        });
    }

    function uploadFile(file, callbacks) {
        var formData = new FormData();
        formData.append('file', file);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api-upload.php', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percent = Math.round((e.loaded / e.total) * 100);
                if (callbacks.onProgress) callbacks.onProgress(percent, 'Uploading file...');
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                if (callbacks.onProgress) callbacks.onProgress(100, 'AI Processing (Extracting details)...');
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        setTimeout(function() { if (callbacks.onSuccess) callbacks.onSuccess(response); }, 1500);
                    } else {
                        if (callbacks.onError) callbacks.onError(response.message);
                    }
                } catch (err) {
                    if (callbacks.onError) callbacks.onError('Error parsing server response.');
                }
            } else {
                if (callbacks.onError) callbacks.onError('Upload HTTP error: ' + xhr.status);
            }
        };
        xhr.onerror = function() { if (callbacks.onError) callbacks.onError('Upload request failed.'); };
        xhr.send(formData);
    }

    // --- OCR upload (create/edit invoice page) ---
    var ocrDragZone = document.getElementById('ocr-drag-zone');
    var ocrFileInput = document.getElementById('ocr-file-input');
    var ocrProgress = document.getElementById('ocr-upload-progress');
    var ocrFileDisplay = document.getElementById('ocr-uploaded-file-display');

    setupDragDrop(ocrDragZone, ocrFileInput, function(file) {
        if (ocrDragZone) ocrDragZone.style.display = 'none';
        if (ocrProgress) ocrProgress.style.display = 'block';
        document.getElementById('ocr-progress-filename').innerText = file.name;

        uploadFile(file, {
            onProgress: function(percent, statusText) {
                document.getElementById('ocr-progress-percentage').innerText = percent + '%';
                document.getElementById('ocr-progress-bar-fill').style.width = percent + '%';
                document.getElementById('ocr-status-text').innerText = statusText;
            },
            onSuccess: function(response) {
                document.getElementById('amount').value = response.data.amount;
                document.getElementById('invoice_date').value = response.data.date;
                document.getElementById('title').value = response.data.vendor;
                document.getElementById('description').value = response.data.description;
                document.getElementById('category_id').value = response.data.category_id;
                document.getElementById('form-file-path').value = response.file_path;

                document.getElementById('ocr-amount-badge').style.display = 'inline-flex';
                document.getElementById('ocr-date-badge').style.display = 'inline-flex';
                document.getElementById('ocr-vendor-badge').style.display = 'inline-flex';
                document.getElementById('ocr-category-badge').style.display = 'inline-flex';

                document.getElementById('uploaded-file-name').innerText = response.file_name;
                document.getElementById('uploaded-file-meta').innerText = response.file_size + ' - Scanned';
                var ext = response.file_name.split('.').pop().toLowerCase();
                document.getElementById('uploaded-file-icon').className = (ext === 'pdf') ? 'file-icon fa-regular fa-file-pdf' : 'file-icon fa-regular fa-file-image';

                if (ocrProgress) ocrProgress.style.display = 'none';
                if (ocrFileDisplay) ocrFileDisplay.style.display = 'flex';
            },
            onError: function(message) { alert('Upload failed: ' + message); resetOcrZone(); }
        });
    });

    window.clearOcrDocument = function() {
        document.getElementById('form-file-path').value = '';
        document.getElementById('ocr-amount-badge').style.display = 'none';
        document.getElementById('ocr-date-badge').style.display = 'none';
        document.getElementById('ocr-vendor-badge').style.display = 'none';
        document.getElementById('ocr-category-badge').style.display = 'none';
        resetOcrZone();
    };

    function resetOcrZone() {
        if (ocrFileDisplay) ocrFileDisplay.style.display = 'none';
        if (ocrProgress) ocrProgress.style.display = 'none';
        if (ocrDragZone) ocrDragZone.style.display = 'block';
        if (ocrFileInput) ocrFileInput.value = '';
    }

    // --- Bulk upload (smart upload page) ---
    var bulkDragZone = document.getElementById('bulk-drag-zone');
    var bulkFileInput = document.getElementById('bulk-file-input');
    var bulkActiveCard = document.getElementById('bulk-active-upload-card');
    var bulkPlaceholder = document.getElementById('bulk-activity-placeholder');

    setupDragDrop(bulkDragZone, bulkFileInput, function(file) {
        if (bulkPlaceholder) bulkPlaceholder.style.display = 'none';
        if (bulkActiveCard) bulkActiveCard.style.display = 'flex';
        document.getElementById('bulk-active-filename').innerText = file.name;

        uploadFile(file, {
            onProgress: function(percent, statusText) {
                document.getElementById('bulk-active-percentage').innerText = percent + '%';
                document.getElementById('bulk-active-bar-fill').style.width = percent + '%';
                document.getElementById('bulk-active-status').innerText = (percent >= 100) ? 'AI Processing...' : 'Uploading...';
            },
            onSuccess: function(response) {
                if (bulkActiveCard) bulkActiveCard.style.display = 'none';
                if (bulkPlaceholder) bulkPlaceholder.style.display = 'block';

                var processedList = document.getElementById('bulk-processed-list');
                var ext = file.name.split('.').pop().toLowerCase();
                var iconClass = (ext === 'pdf') ? 'fa-regular fa-file-pdf' : 'fa-regular fa-file-image';
                var iconColor = (ext === 'pdf') ? 'var(--danger)' : 'var(--secondary)';
                var safeVendor = response.data.vendor.replace(/</g, '&lt;').replace(/>/g, '&gt;');

                var newItemHTML = '<div class="file-item" style="background-color:var(--white);border:1px solid var(--border-color);padding:12px 14px;">' +
                    '<div class="file-info" style="gap:10px;">' +
                    '<i class="' + iconClass + ' file-icon" style="color:' + iconColor + ';font-size:18px;"></i>' +
                    '<div class="file-details">' +
                    '<div class="file-name" style="font-size:12px;font-weight:600;color:var(--primary);">' + safeVendor + '</div>' +
                    '<div class="file-meta" style="font-size:11px;color:var(--gray-text);">Just now - <a href="invoice-create.php?amount=' + response.data.amount + '&date=' + response.data.date + '&vendor=' + encodeURIComponent(response.data.vendor) + '&cat=' + response.data.category_id + '&desc=' + encodeURIComponent(response.data.description) + '&file=' + encodeURIComponent(response.file_path) + '" style="color:var(--secondary);font-weight:600;text-decoration:underline;">Review & Save</a></div>' +
                    '</div></div>' +
                    '<span style="color:var(--success);font-size:16px;"><i class="fa-solid fa-circle-check"></i></span></div>';

                processedList.insertAdjacentHTML('afterbegin', newItemHTML);
            },
            onError: function(message) {
                alert('Upload failed: ' + message);
                if (bulkActiveCard) bulkActiveCard.style.display = 'none';
                if (bulkPlaceholder) bulkPlaceholder.style.display = 'block';
            }
        });
    });

    // ========================================================
    // PRE-FILL FORM FROM URL PARAMETERS
    // ========================================================
    var urlParams = new URLSearchParams(window.location.search);
    if (window.location.pathname.includes('invoice-create') && urlParams.get('amount')) {
        document.getElementById('amount').value = urlParams.get('amount');
        document.getElementById('invoice_date').value = urlParams.get('date');
        document.getElementById('title').value = urlParams.get('vendor');
        document.getElementById('category_id').value = urlParams.get('cat');
        document.getElementById('description').value = urlParams.get('desc');
        document.getElementById('form-file-path').value = urlParams.get('file');

        document.getElementById('ocr-amount-badge').style.display = 'inline-flex';
        document.getElementById('ocr-date-badge').style.display = 'inline-flex';
        document.getElementById('ocr-vendor-badge').style.display = 'inline-flex';
        document.getElementById('ocr-category-badge').style.display = 'inline-flex';

        var fileDisplay = document.getElementById('ocr-uploaded-file-display');
        var dragZone = document.getElementById('ocr-drag-zone');
        if (fileDisplay && dragZone && urlParams.get('file')) {
            dragZone.style.display = 'none';
            fileDisplay.style.display = 'flex';
            document.getElementById('uploaded-file-name').innerText = urlParams.get('file').split('/').pop();
            document.getElementById('uploaded-file-meta').innerText = 'Extracted via link - Scanned';
        }
    }

    // ========================================================
    // PART 3: GLOBAL SEARCH BAR
    // ========================================================
    var globalSearch = document.getElementById('global-search-input');
    if (globalSearch) {
        var currentSearch = urlParams.get('search');
        if (currentSearch) globalSearch.value = currentSearch;

        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                var keyword = encodeURIComponent(globalSearch.value.trim());
                window.location.href = 'invoices.php?search=' + keyword;
            }
        });
    }

});