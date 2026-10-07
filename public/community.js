/* Same-origin Laravel endpoints protect Supabase credentials and enforce roles. */
(() => {
    'use strict';
    let searchRequest, searchTimer, searchVersion = 0;
    let mutationBusy = false;
    const shell = document.querySelector('[data-app-shell]');
    const sidebar = document.querySelector('#site-sidebar');
    const sidebarBreakpoint = window.matchMedia('(max-width: 1180px)');
    const sidebarToggles = () => [...document.querySelectorAll('[data-sidebar-toggle]')];
    const readDesktopSidebarState = () => {
        try { return localStorage.getItem('komuniedad-sidebar-collapsed') === '1'; }
        catch { return false; }
    };
    const writeDesktopSidebarState = collapsed => {
        try { localStorage.setItem('komuniedad-sidebar-collapsed', collapsed ? '1' : '0'); }
        catch {}
    };
    const syncSidebar = () => {
        if (!shell || !sidebar) return;
        const mobile = sidebarBreakpoint.matches;
        const open = mobile ? shell.classList.contains('sidebar-open') : !shell.classList.contains('sidebar-desktop-collapsed');
        sidebarToggles().forEach(button => button.setAttribute('aria-expanded', String(open)));
        sidebar.toggleAttribute('inert', !open);
        sidebar.setAttribute('aria-hidden', String(!open));
        document.body.classList.toggle('sidebar-lock', mobile && open);
        document.querySelector('.workspace')?.toggleAttribute('inert', mobile && open);
    };
    const setSidebarOpen = open => {
        if (!shell || !sidebar) return;
        if (sidebarBreakpoint.matches) {
            shell.classList.toggle('sidebar-open', open);
        } else {
            shell.classList.toggle('sidebar-desktop-collapsed', !open);
            writeDesktopSidebarState(!open);
        }
        syncSidebar();
    };
    const resetSidebarForViewport = () => {
        if (!shell) return;
        shell.classList.remove('sidebar-open');
        if (sidebarBreakpoint.matches) shell.classList.remove('sidebar-desktop-collapsed');
        else shell.classList.toggle('sidebar-desktop-collapsed', readDesktopSidebarState());
        syncSidebar();
    };
    resetSidebarForViewport();
    sidebarBreakpoint.addEventListener?.('change', resetSidebarForViewport);
    const notice = (message, error = false) => {
        const box = document.querySelector('#request-status');
        box.hidden = false;
        box.className = `alert ${error ? 'alert-danger' : 'alert-success'} request-status`;
        box.textContent = message;
        if (error) box.focus();
    };
    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin', ...options,
            headers: { Accept: 'application/json', ...options.headers },
        });
        const data = await response.json().catch(() => {
            throw new Error('The server response could not be confirmed. Refresh before trying again.');
        });
        if (!response.ok) {
            let message = data.message || 'The request failed. Please try again.';
            if (response.status === 401) message = 'Your session expired. Sign in again to continue.';
            if (response.status === 419) message = 'This form expired. Refresh the page and try again.';
            throw Object.assign(new Error(message), { errors: data.errors, status: response.status });
        }
        return data;
    };
    async function search(form) {
        clearTimeout(searchTimer);
        const version = ++searchVersion;
        searchRequest?.abort();
        searchRequest = new AbortController();
        const url = new URL('/', location.origin);
        url.search = new URLSearchParams(new FormData(form));
        const status = document.querySelector('#search-status');
        const results = document.querySelector('#activity-results');
        status.textContent = 'Finding activities…';
        results.setAttribute('aria-busy', 'true');
        try {
            const data = await request(url, { signal: searchRequest.signal });
            if (version !== searchVersion || !form.isConnected) return;
            // HTML comes only from our escaped Blade template, never user strings.
            results.innerHTML = data.html;
            document.querySelector('#result-count').textContent = `${data.count} ${data.count === 1 ? 'activity' : 'activities'}`;
            status.textContent = data.count ? 'Results updated.' : 'No matching activities. Try another search.';
            history.replaceState(null, '', url);
        } catch (error) {
            if (error.name !== 'AbortError' && version === searchVersion) status.textContent = error.message;
        } finally {
            if (version === searchVersion) results.removeAttribute('aria-busy');
        }
    }
    async function normalizedProfilePhoto(form) {
        const input = form.querySelector('[data-profile-photo]');
        const file = input?.files?.[0];
        if (!file) return null;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            throw Object.assign(new Error('Choose a JPG, PNG, or WebP profile photo.'), { status: 422 });
        }

        if (file.size > 10 * 1024 * 1024) {
            throw Object.assign(new Error('Choose a profile photo smaller than 10 MB.'), { status: 422 });
        }

        const objectUrl = URL.createObjectURL(file);
        const image = new Image();
        try {
            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error('The selected image could not be read.'));
                image.src = objectUrl;
            });

            const side = Math.min(image.naturalWidth, image.naturalHeight);
            const sourceX = Math.floor((image.naturalWidth - side) / 2);
            const sourceY = Math.floor((image.naturalHeight - side) / 2);

            const canvas = document.createElement('canvas');
            canvas.width = 512;
            canvas.height = 512;
            const context = canvas.getContext('2d', { alpha: false });
            if (!context) throw new Error('The profile photo could not be prepared.');

            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = 'high';
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, 512, 512);
            context.drawImage(image, sourceX, sourceY, side, side, 0, 0, 512, 512);

            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.82));
            if (!blob) throw new Error('The profile photo could not be prepared.');

            return new File([blob], 'profile-photo.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
        } finally {
            URL.revokeObjectURL(objectUrl);
        }

    }

    function clearErrors(form) {
        form.querySelectorAll('.field-error').forEach(el => el.remove());
        form.querySelectorAll('[aria-invalid]').forEach(el => {
            el.removeAttribute('aria-invalid');
            const previous = el.dataset.previousDescription;
            if (previous) el.setAttribute('aria-describedby', previous);
            else el.removeAttribute('aria-describedby');
        });
        form.querySelectorAll('[data-date-validation]').forEach(el => el.setCustomValidity(''));
    }
    function validateDates(form) {
        const start = form.elements.namedItem('start_at');
        const end = form.elements.namedItem('end_at');
        const cutoff = form.elements.namedItem('cutoff_at');
        if (start?.value && end?.value && end.value <= start.value) {
            end.dataset.dateValidation = '1';
            end.setCustomValidity('The end must be after the start.');
        }
        if (start?.value && cutoff?.value && cutoff.value > start.value) {
            cutoff.dataset.dateValidation = '1';
            cutoff.setCustomValidity('Registration must close before or at the start.');
        }
    }
    function fieldErrors(form, errors) {
        let first;
        for (const [name, messages] of Object.entries(errors || {})) {
            const field = form.elements.namedItem(name);
            if (!(field instanceof HTMLElement)) continue;
            const feedback = document.createElement('p');
            feedback.className = 'field-error text-danger';
            feedback.id = `error-${crypto.randomUUID()}`;
            feedback.textContent = messages.join(' ');
            field.dataset.previousDescription = field.getAttribute('aria-describedby') || '';
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', `${field.dataset.previousDescription} ${feedback.id}`.trim());
            field.after(feedback);
            first ||= field;
        }
        first?.focus();
    }
    const localUrl = target => {
        const parsed = new URL(target || location.href, location.origin);
        const path = '/' + parsed.pathname.replace(/^[\/\\]+/, '');
        return new URL(path + parsed.search + parsed.hash, location.origin);
    };

    async function updatePage(target) {
        const url = localUrl(target);
        searchRequest?.abort();
        clearTimeout(searchTimer);
        ++searchVersion;

        const response = await fetch(url, {
            credentials: 'same-origin',
            redirect: 'follow',
            headers: { Accept: 'text/html' },
        });

        if (!response.ok) {
            throw Object.assign(new Error('The updated page could not be loaded.'), {
                refreshTarget: url.pathname + url.search,
            });
        }

        const finalUrl = localUrl(response.url);
        if (finalUrl.pathname === '/login') {
            location.assign('/login');
            return false;
        }

        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const main = page.querySelector('#main');
        if (!main) {
            throw Object.assign(new Error('The updated page could not be loaded.'), {
                refreshTarget: url.pathname + url.search,
            });
        }

        document.querySelector('#main').replaceWith(main);
        syncPaymentFields(main);
        document.title = page.title;
        history.replaceState(null, '', finalUrl.pathname + finalUrl.search);
        const nav = page.querySelector('.sidebar nav');
        if (nav) document.querySelector('.sidebar nav').replaceWith(nav);
        const member = page.querySelector('.member');
        if (member) document.querySelector('.member').replaceWith(member);
        const mobileNav = page.querySelector('.mobile-bottom-nav');
        if (mobileNav) document.querySelector('.mobile-bottom-nav')?.replaceWith(mobileNav);
        initMergedUi();
        main.setAttribute('tabindex', '-1');
        main.focus({ preventScroll: true });

        return true;
    }
    document.addEventListener('input', event => {
        if (event.target.setCustomValidity) event.target.setCustomValidity('');
        event.target.form?.querySelectorAll('[data-date-validation]').forEach(el => el.setCustomValidity(''));
        if (event.target.closest?.('#activity-form')) syncActivityPreview();

        if (event.target.matches?.('[data-profile-photo]')) {
            const name = document.querySelector('[data-profile-photo-name]');
            if (name) {
                name.textContent = event.target.files?.[0]?.name || 'No new photo selected';
            }
        }

        if (event.target.matches?.('[data-participant-search]')) {
            const term = event.target.value.trim().toLowerCase();
            const cards = [...document.querySelectorAll('[data-participant-name]')];
            let visible = 0;
            cards.forEach(card => {
                const match = !term || (card.dataset.participantName || '').includes(term);
                card.hidden = !match;
                if (match) visible++;
            });
            const empty = document.querySelector('[data-participant-empty]');
            if (empty) empty.hidden = visible !== 0;
        }

        if (event.target.matches?.('[data-admin-user-search]')) {
            const term = event.target.value.trim().toLowerCase();
            const rows = [...document.querySelectorAll('[data-admin-user]')];
            let visible = 0;
            rows.forEach(row => {
                const match = !term || (row.dataset.adminUser || '').includes(term);
                row.hidden = !match;
                if (match) visible++;
            });
            const empty = document.querySelector('[data-admin-user-empty]');
            if (empty) empty.hidden = visible !== 0;
        }

        const form = event.target.closest('[data-activity-search]');
        if (form && event.target.name === 'q') {
            searchRequest?.abort();
            ++searchVersion;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => search(form), 300);
        }
    });
    document.addEventListener('click', event => {
        const card = event.target.closest('[data-card-href]');
        if (card && !event.target.closest('a,button,input,select,textarea,summary,details,label,form')) {
            location.assign(card.dataset.cardHref);
            return;
        }

        const workspaceView = event.target.closest('[data-workspace-view]');
        if (workspaceView) {
            setWorkspaceView(workspaceView.dataset.workspaceView);
            return;
        }
        const sidebarToggle = event.target.closest('[data-sidebar-toggle]');
        if (sidebarToggle) {
            const isOpen = sidebarBreakpoint.matches ? shell?.classList.contains('sidebar-open') : !shell?.classList.contains('sidebar-desktop-collapsed');
            setSidebarOpen(!isOpen);
            if (!isOpen && sidebarBreakpoint.matches) requestAnimationFrame(() => sidebar?.querySelector('a,button')?.focus({ preventScroll: true }));
            return;
        }
        if (event.target.closest('[data-sidebar-dismiss]')) {
            setSidebarOpen(false);
            document.querySelector('[data-sidebar-toggle]')?.focus({ preventScroll: true });
            return;
        }
        if (sidebarBreakpoint.matches && event.target.closest('#site-sidebar nav a')) setSidebarOpen(false);
        const button = event.target.closest('[data-clear-search]');
        if (!button) return;
        const form = button.closest('form');
        for (const name of ['q', 'category', 'status', 'tag']) {
            const field = form.elements.namedItem(name);
            if (field) field.value = '';
        }
        search(form);
    });
    document.addEventListener('keydown', event => {
        const card = event.target.closest?.('[data-card-href]');
        if (card && event.target === card && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            location.assign(card.dataset.cardHref);
            return;
        }

        if (event.key === 'Tab' && sidebarBreakpoint.matches && shell?.classList.contains('sidebar-open')) {
            const controls = [...sidebar.querySelectorAll('a[href],button:not([disabled]),select,input,[tabindex="0"]')].filter(el => el.getClientRects().length);
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
        if (event.key === 'Escape' && sidebarBreakpoint.matches && shell?.classList.contains('sidebar-open')) {
            setSidebarOpen(false);
            document.querySelector('[data-sidebar-toggle]')?.focus({ preventScroll: true });
        }
    });

    function setWorkspaceView(view) {
        const tableContainer = document.getElementById('view-table-container');
        const gridContainer = document.getElementById('view-grid-container');
        const btnTable = document.getElementById('btn-table');
        const btnGrid = document.getElementById('btn-grid');
        if (!tableContainer || !gridContainer || !btnTable || !btnGrid) return;

        const grid = view === 'grid';
        tableContainer.style.display = grid ? 'none' : 'block';
        gridContainer.style.display = grid ? 'flex' : 'none';
        btnGrid.classList.toggle('active', grid);
        btnTable.classList.toggle('active', !grid);
        try { localStorage.setItem('workspace_view', grid ? 'grid' : 'table'); } catch {}
    }

    function initWorkspaceView() {
        if (!document.getElementById('view-table-container')) return;
        let saved = 'table';
        try { saved = localStorage.getItem('workspace_view') || 'table'; } catch {}
        setWorkspaceView(saved);
    }

    function syncActivityPreview() {
        const form = document.getElementById('activity-form');
        if (!form) return;

        const isFree = form.elements.namedItem('is_free');
        const fee = form.elements.namedItem('fee');
        const feeWrapper = document.getElementById('fee-wrapper');
        if (isFree && fee && feeWrapper) {
            const free = isFree.value === '1';
            feeWrapper.style.display = free ? 'none' : '';
            if (free) fee.value = '0';
        }

        const tagInput = form.elements.namedItem('tags');
        const tagPreview = document.querySelector('[data-tag-preview]');
        const cardTagPreview = document.getElementById('preview-tags');
        if (tagInput) {
            const tags = [...new Set(String(tagInput.value || '')
                .split(',')
                .map(tag => tag.trim().toLowerCase().replace(/\s+/g, ' '))
                .filter(Boolean))]
                .slice(0, 6);

            const renderTags = (container, editable = false) => {
                if (!container) return;
                container.replaceChildren();
                tags.forEach(tag => {
                    const chip = document.createElement('span');
                    chip.className = editable ? 'activity-tag activity-tag-edit-preview' : 'activity-tag';
                    chip.textContent = tag.charAt(0).toUpperCase() + tag.slice(1);
                    container.appendChild(chip);
                });
            };

            renderTags(tagPreview, true);
            renderTags(cardTagPreview);
        }

        const category = form.elements.namedItem('category_id');
        const previewCategory = document.getElementById('preview-category');
        if (category && previewCategory) {
            previewCategory.textContent = category.options?.[category.selectedIndex]?.text || 'General';
        }

        const status = form.elements.namedItem('status');
        const previewStatus = document.getElementById('preview-status');
        if (status && previewStatus) {
            const value = status.value || 'draft';
            previewStatus.textContent = value.charAt(0).toUpperCase() + value.slice(1);
            previewStatus.className = 'badge ' + (value.toLowerCase() === 'open' ? 'bg-success' : 'bg-warning text-dark');
        }

        const bindings = [
            ['title', 'preview-title', 'Activity title'],
            ['description', 'preview-desc', 'Description will appear here...'],
            ['venue', 'preview-venue', 'Venue location'],
        ];
        bindings.forEach(([name, id, fallback]) => {
            const field = form.elements.namedItem(name);
            const preview = document.getElementById(id);
            if (field && preview) preview.textContent = field.value || fallback;
        });

        const start = form.elements.namedItem('start_at');
        const schedule = document.getElementById('preview-schedule');
        if (start && schedule) {
            if (start.value) {
                const d = new Date(start.value);
                schedule.textContent = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    + ' • ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            } else {
                schedule.textContent = 'TBD';
            }
        }
    }

    function initTransientAlerts() {
        if (document.querySelector('.senior-shell')) return;
        document.querySelectorAll('.alert-success:not([data-auto-hide-bound])').forEach(alert => {
            alert.dataset.autoHideBound = '1';
            window.setTimeout(() => {
                if (!alert.isConnected) return;
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                window.setTimeout(() => alert.remove(), 500);
            }, 3000);
        });
    }

    function drawReportChart(canvas) {
        if (!(canvas instanceof HTMLCanvasElement)) return;

        let chart;
        try {
            chart = JSON.parse(canvas.dataset.chart || '{}');
        } catch {
            return;
        }

        const labels = Array.isArray(chart.labels) ? chart.labels : [];
        const series = Array.isArray(chart.series) ? chart.series : [];
        if (!labels.length || !series.length) {
            const context = canvas.getContext('2d');
            context?.clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        const cssWidth = Math.max(320, Math.floor(canvas.parentElement?.clientWidth || canvas.clientWidth || 640));
        const cssHeight = 320;
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.style.width = cssWidth + 'px';
        canvas.style.height = cssHeight + 'px';
        canvas.width = Math.floor(cssWidth * ratio);
        canvas.height = Math.floor(cssHeight * ratio);

        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, cssWidth, cssHeight);

        const style = getComputedStyle(document.documentElement);
        const ink = style.getPropertyValue('--ink').trim() || '#243029';
        const muted = style.getPropertyValue('--muted').trim() || '#6b756f';
        const line = style.getPropertyValue('--line').trim() || '#dce2dc';
        const primary = style.getPropertyValue('--green').trim() || '#245c48';
        const secondary = '#9a6a20';

        const colors = [primary, secondary];
        const values = series.flatMap(item => Array.isArray(item.values) ? item.values.map(Number) : []);
        const maxValue = Math.max(1, ...values.filter(Number.isFinite));
        const max = Math.max(1, Math.ceil(maxValue * 1.15));

        const left = 50;
        const right = 18;
        const top = 28;
        const bottom = labels.length > 6 ? 74 : 54;
        const width = cssWidth - left - right;
        const height = cssHeight - top - bottom;

        ctx.font = '12px system-ui, sans-serif';
        ctx.textBaseline = 'middle';

        for (let i = 0; i <= 4; i++) {
            const y = top + height - (height * i / 4);
            const value = Math.round(max * i / 4);
            ctx.strokeStyle = line;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(left, y);
            ctx.lineTo(left + width, y);
            ctx.stroke();
            ctx.fillStyle = muted;
            ctx.textAlign = 'right';
            ctx.fillText(String(value), left - 8, y);
        }

        const type = canvas.dataset.chartType || 'bar';
        const groupWidth = width / Math.max(labels.length, 1);

        if (type === 'line') {
            series.forEach((item, seriesIndex) => {
                const data = Array.isArray(item.values) ? item.values.map(Number) : [];
                ctx.strokeStyle = colors[seriesIndex % colors.length];
                ctx.fillStyle = colors[seriesIndex % colors.length];
                ctx.lineWidth = 3;
                ctx.beginPath();

                data.forEach((value, index) => {
                    const x = left + groupWidth * index + groupWidth / 2;
                    const y = top + height - (Math.max(0, value) / max * height);
                    if (index === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                });
                ctx.stroke();

                data.forEach((value, index) => {
                    const x = left + groupWidth * index + groupWidth / 2;
                    const y = top + height - (Math.max(0, value) / max * height);
                    ctx.beginPath();
                    ctx.arc(x, y, 4, 0, Math.PI * 2);
                    ctx.fill();
                });
            });
        } else {
            const innerWidth = Math.min(groupWidth * 0.72, 64);
            const barWidth = Math.max(4, innerWidth / Math.max(series.length, 1));

            labels.forEach((_, labelIndex) => {
                series.forEach((item, seriesIndex) => {
                    const value = Number(item.values?.[labelIndex] || 0);
                    const h = Math.max(0, value) / max * height;
                    const groupLeft = left + groupWidth * labelIndex + (groupWidth - innerWidth) / 2;
                    const x = groupLeft + seriesIndex * barWidth;
                    const y = top + height - h;
                    ctx.fillStyle = colors[seriesIndex % colors.length];
                    ctx.fillRect(x, y, Math.max(2, barWidth - 2), h);
                });
            });
        }

        ctx.fillStyle = ink;
        ctx.textAlign = 'center';
        labels.forEach((label, index) => {
            const x = left + groupWidth * index + groupWidth / 2;
            const y = top + height + 18;
            const text = String(label);
            const shortened = text.length > 16 ? text.slice(0, 15) + '…' : text;
            if (labels.length > 6) {
                ctx.save();
                ctx.translate(x, y);
                ctx.rotate(-Math.PI / 5);
                ctx.textAlign = 'right';
                ctx.fillText(shortened, 0, 0);
                ctx.restore();
            } else {
                ctx.fillText(shortened, x, y);
            }
        });

        let legendX = left;
        const legendY = 12;
        series.forEach((item, index) => {
            ctx.fillStyle = colors[index % colors.length];
            ctx.fillRect(legendX, legendY - 5, 11, 11);
            ctx.fillStyle = ink;
            ctx.textAlign = 'left';
            const label = String(item.label || 'Series');
            ctx.fillText(label, legendX + 17, legendY);
            legendX += Math.max(92, ctx.measureText(label).width + 42);
        });
    }

    let reportChartObserver;
    function initReportCharts() {
        const charts = [...document.querySelectorAll('[data-report-chart]')];
        if (!charts.length) return;

        charts.forEach(drawReportChart);

        reportChartObserver?.disconnect();
        if ('ResizeObserver' in window) {
            reportChartObserver = new ResizeObserver(entries => {
                entries.forEach(entry => {
                    const canvas = entry.target.querySelector?.('[data-report-chart]');
                    if (canvas) drawReportChart(canvas);
                });
            });
            charts.forEach(chart => {
                if (chart.parentElement) reportChartObserver.observe(chart.parentElement);
            });
        }
    }

    function initMergedUi() {
        initWorkspaceView();
        syncActivityPreview();
        initTransientAlerts();
        initReportCharts();
    }

    function syncPaymentFields(scope = document) {
        scope.querySelectorAll('[data-payment-mode]').forEach(select => {
            const fee = select.form?.querySelector('[data-payment-fee]');
            if (!fee) return;
            const free = select.value === '1';
            fee.disabled = free;
            fee.required = !free;
            if (free) fee.value = '0';
        });
    }
    syncPaymentFields();
    document.addEventListener('change', event => {
        if (event.target.matches?.('[data-show-password]')) {
            event.target.form?.querySelectorAll('input[name="password"], input[name="password_confirmation"]').forEach(input => {
                input.type = event.target.checked ? 'text' : 'password';
            });
        }
        const form = event.target.closest('[data-activity-search]');
        if (form) search(form);
        if (event.target.matches?.('[data-payment-mode]')) syncPaymentFields(event.target.form || document);
        if (event.target.closest?.('#activity-form')) {
            if (event.target.matches?.('input[type="file"][name="image"]')) {
                const file = event.target.files?.[0];
                const preview = document.getElementById('preview-img');
                if (file && preview) {
                    const reader = new FileReader();
                    reader.addEventListener('load', () => { preview.src = String(reader.result || ''); }, { once: true });
                    reader.readAsDataURL(file);
                }
            }
            syncActivityPreview();
        }
    });
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.matches('[data-activity-search]')) {
            event.preventDefault();
            search(form);
            return;
        }
        const path = new URL(form.action).pathname;
        if (form.method.toLowerCase() !== 'post' || ['/login', '/register', '/logout', '/demo/role'].includes(path)) return;
        event.preventDefault();
        if (form.dataset.busy || mutationBusy) return;
        clearErrors(form);
        validateDates(form);
        if (!form.reportValidity()) return;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
        const body = new FormData(form);
        const profilePhoto = form.querySelector('[data-profile-photo]');
        if (profilePhoto?.files?.[0]) {
            try {
                body.set('profile_photo', await normalizedProfilePhoto(form));
            } catch (error) {
                notice(error.message || 'The profile photo could not be prepared.', true);
                profilePhoto.focus();
                return;
            }
        }
        const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type])')];
        form.dataset.busy = 'true';
        mutationBusy = true;
        form.setAttribute('aria-busy', 'true');
        buttons.forEach(button => button.disabled = true);
        let saved = false;
        let uncertain = false;
        try {
            const data = await request(form.action, {
                method: 'POST', body,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            saved = true;
            const refreshed = await updatePage(data.redirect || location.href);
            if (refreshed === false) return;
            mutationBusy = false;
            notice(data.message || 'Changes saved.');
        } catch (error) {
            if (saved) {
                const fallback = localUrl(error.refreshTarget || location.href);
                location.assign(fallback.pathname + fallback.search);
                return;
            }

            uncertain = !error.status || error.status >= 500;
            notice(error.message, true);
            fieldErrors(form, error.errors);
            if (error.errors) {
                const all = Object.values(error.errors).flat().join(' ');
                notice(all, true);
            }
        } finally {
            if (!saved && !uncertain) {
                mutationBusy = false;
                delete form.dataset.busy;
                form.removeAttribute('aria-busy');
                buttons.forEach(button => button.disabled = false);
            }
        }
    });

    let activityDiscussionTimer;
    async function refreshActivityDiscussion() {
        const section = document.querySelector('[data-discussion]');
        const list = section?.querySelector('[data-discussion-list]');
        if (!section || !list || document.hidden) return;
        try {
            const data = await request(section.dataset.discussionUrl);
            if (section.isConnected) list.innerHTML = data.html;
        } catch {}
    }
    function initActivityDiscussion() {
        clearInterval(activityDiscussionTimer);
        activityDiscussionTimer = null;
        if (!document.querySelector('[data-discussion]')) return;
        refreshActivityDiscussion();
        activityDiscussionTimer = window.setInterval(refreshActivityDiscussion, 8000);
    }
    initActivityDiscussion();
    initMergedUi();
})();
