/* Same-origin Laravel endpoints protect Supabase credentials and enforce roles. */
(() => {
    'use strict';
    let searchRequest, searchTimer, searchVersion = 0;
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
        const data = await response.json().catch(() => ({}));
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
    async function updatePage(target) {
        const url = new URL(target, location.origin);
        if (url.origin !== location.origin) throw new Error('Unexpected navigation response.');
        searchRequest?.abort();
        clearTimeout(searchTimer);
        ++searchVersion;
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error('Saved, but the page could not refresh. Refresh manually before making another change.');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const main = page.querySelector('#main');
        if (!main) throw new Error('Saved. Refresh this page to see the latest information.');
        document.querySelector('#main').replaceWith(main);
        document.title = page.title;
        history.replaceState(null, '', new URL(response.url).pathname + new URL(response.url).search);
        const nav = page.querySelector('.sidebar nav');
        if (nav) document.querySelector('.sidebar nav').replaceWith(nav);
        const member = page.querySelector('.member');
        if (member) document.querySelector('.member').replaceWith(member);
        main.setAttribute('tabindex', '-1');
        main.focus({ preventScroll: true });
    }
    document.addEventListener('input', event => {
        if (event.target.setCustomValidity) event.target.setCustomValidity('');
        event.target.form?.querySelectorAll('[data-date-validation]').forEach(el => el.setCustomValidity(''));
        const form = event.target.closest('[data-activity-search]');
        if (form && event.target.name === 'q') {
            searchRequest?.abort();
            ++searchVersion;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => search(form), 300);
        }
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-clear-search]');
        if (!button) return;
        const form = button.closest('form');
        for (const name of ['q', 'category', 'status']) form.elements.namedItem(name).value = '';
        search(form);
    });
    document.addEventListener('change', event => {
        const form = event.target.closest('[data-activity-search]');
        if (form) search(form);
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
        if (form.dataset.busy) return;
        clearErrors(form);
        validateDates(form);
        if (!form.reportValidity()) return;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
        const body = new FormData(form);
        const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type])')];
        form.dataset.busy = 'true';
        form.setAttribute('aria-busy', 'true');
        buttons.forEach(button => button.disabled = true);
        let saved = false;
        try {
            const data = await request(form.action, {
                method: 'POST', body,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            saved = true;
            await updatePage(data.redirect || location.href);
            notice(data.message || 'Changes saved.');
        } catch (error) {
            notice(saved ? 'Your change was saved, but the view could not refresh. Refresh the page before submitting again.' : error.message, true);
            fieldErrors(form, error.errors);
            if (error.errors) {
                const all = Object.values(error.errors).flat().join(' ');
                notice(all, true);
            }
        } finally {
            // A saved mutation must not be retried if only its refresh failed.
            if (!saved) {
                delete form.dataset.busy;
                form.removeAttribute('aria-busy');
                buttons.forEach(button => button.disabled = false);
            }
        }
    });
})();
