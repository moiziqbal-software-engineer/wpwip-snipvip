( function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────
    let links = [];

    // ── Elements ───────────────────────────────────────────────────
    const tbody       = document.getElementById( 'links-tbody' );
    const newUrl      = document.getElementById( 'new-url' );
    const newTitle    = document.getElementById( 'new-title' );
    const createBtn   = document.getElementById( 'create-btn' );
    const createAlert = document.getElementById( 'create-alert' );
    const planName    = document.getElementById( 'plan-name' );
    const planUsage   = document.getElementById( 'plan-usage' );
    const planBar     = document.getElementById( 'plan-bar' );
    const planUpgrade = document.getElementById( 'plan-upgrade-link' );
    const upgradeBtn  = document.getElementById( 'upgrade-btn' );

    // ── Helpers ────────────────────────────────────────────────────
    function showAlert( msg, type ) {
        createAlert.textContent = msg;
        createAlert.className   = 'alert mt-16 is-visible alert--' + type;
        setTimeout( () => {
            createAlert.className = 'alert mt-16';
        }, 4000 );
    }

    function setLoading( loading ) {
        createBtn.disabled    = loading;
        createBtn.textContent = loading ? 'Shortening...' : 'Shorten';
    }

    function formatDate( dateStr ) {
        const d = new Date( dateStr );
        return d.toLocaleDateString( 'en-GB', {
            day:   '2-digit',
            month: 'short',
            year:  'numeric',
        } );
    }

    function escHtml( str ) {
        const d = document.createElement( 'div' );
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Render plan banner ─────────────────────────────────────────
    function renderPlan( plan ) {
        planName.textContent = plan.plan.charAt( 0 ).toUpperCase() + plan.plan.slice( 1 );

        if ( plan.unlimited ) {
            planUsage.textContent = plan.used + ' links created — unlimited plan';
            planBar.style.width   = '100%';
            planBar.style.background = 'var(--success)';
        } else {
            planUsage.textContent = plan.used + ' of ' + plan.limit + ' links used';
            const pct             = Math.min( 100, Math.round( ( plan.used / plan.limit ) * 100 ) );
            planBar.style.width   = pct + '%';

            // Turn bar amber when 80%+ used
            if ( pct >= 80 ) {
                planBar.style.background = 'var(--warning)';
            }

            // Show upgrade button when at limit
            if ( plan.remaining === 0 ) {
                if ( planUpgrade ) planUpgrade.style.display = 'inline-flex';
                if ( upgradeBtn )  upgradeBtn.style.display  = 'inline-flex';
            }
        }

        // Show upgrade button for free plan
        if ( plan.plan === 'free' ) {
            if ( planUpgrade ) planUpgrade.style.display = 'inline-flex';
            if ( upgradeBtn )  upgradeBtn.style.display  = 'inline-flex';
        }
    }

    // ── Render links table ─────────────────────────────────────────
    function renderLinks() {
        if ( links.length === 0 ) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="links-table__empty">
                        <div style="font-size:32px;margin-bottom:12px;">🔗</div>
                        <div style="font-weight:600;color:var(--gray-600);margin-bottom:4px;">No links yet</div>
                        <div style="font-size:13px;">Paste a URL above to create your first short link.</div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = links.map( function ( link ) {
            const dest    = link.destination.length > 50
                ? link.destination.substring( 0, 50 ) + '...'
                : link.destination;
            const clicks  = parseInt( link.click_count, 10 ) || 0;
            const title   = link.title || '—';

            return `
                <tr data-id="${ escHtml( String( link.id ) ) }">
                    <td>
                        <a class="links-table__short"
                           href="${ escHtml( link.short_url ) }"
                           target="_blank"
                           rel="noopener">
                            ${ escHtml( link.short_url.replace( /^https?:\/\//, '' ) ) }
                        </a>
                        <div style="font-size:12px;color:var(--gray-400);margin-top:2px;">
                            ${ escHtml( title ) }
                        </div>
                    </td>
                    <td>
                        <div class="links-table__dest" title="${ escHtml( link.destination ) }">
                            ${ escHtml( dest ) }
                        </div>
                    </td>
                    <td>
                        <span class="links-table__clicks">${ clicks }</span>
                    </td>
                    <td>
                        <span class="links-table__date">
                            ${ formatDate( link.created_at ) }
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:4px;">
                            <button
                                class="copy-btn"
                                data-url="${ escHtml( link.short_url ) }"
                            >Copy</button>
                            <button
                                class="btn btn--danger delete-btn"
                                data-id="${ escHtml( String( link.id ) ) }"
                            >Delete</button>
                        </div>
                    </td>
                </tr>`;
        } ).join( '' );

        // Bind copy buttons
        tbody.querySelectorAll( '.copy-btn' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                const url = this.dataset.url;
                navigator.clipboard.writeText( url ).then( () => {
                    this.textContent = 'Copied!';
                    this.classList.add( 'copied' );
                    setTimeout( () => {
                        this.textContent = 'Copy';
                        this.classList.remove( 'copied' );
                    }, 2000 );
                } );
            } );
        } );

        // Bind delete buttons
        tbody.querySelectorAll( '.delete-btn' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                const id = this.dataset.id;
                deleteLink( id );
            } );
        } );
    }

    // ── Load links + plan from API ─────────────────────────────────
    async function loadLinks() {
        try {
            const response = await fetch( SnipVIP.apiUrl + '/links', {
                headers: { 'X-WP-Nonce': SnipVIP.nonce },
            } );

            const data = await response.json();

            if ( ! response.ok ) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="links-table__empty">
                            Could not load links. Please refresh the page.
                        </td>
                    </tr>`;
                return;
            }

            links = data.links || [];
            renderLinks();
            renderPlan( data.plan );

        } catch ( err ) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="links-table__empty">
                        Network error. Please refresh the page.
                    </td>
                </tr>`;
        }
    }

    // ── Create a link ──────────────────────────────────────────────
    async function createLink() {
        const url   = newUrl.value.trim();
        const title = newTitle.value.trim();

        if ( ! url ) {
            showAlert( 'Please enter a URL.', 'error' );
            return;
        }

        try {
            new URL( url );
        } catch {
            showAlert( 'Please enter a valid URL including https://', 'error' );
            return;
        }

        setLoading( true );

        try {
            const response = await fetch( SnipVIP.apiUrl + '/shorten', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':  SnipVIP.nonce,
                },
                body: JSON.stringify( { url, title } ),
            } );

            const data = await response.json();

            if ( ! response.ok ) {
                showAlert( data.message || 'Could not create link. Please try again.', 'error' );
                return;
            }

            // Prepend new link to top of list
            const newLink = data.link;
            newLink.click_count = 0;
            links.unshift( newLink );
            renderLinks();
            renderPlan( data.plan );

            // Clear inputs
            newUrl.value   = '';
            newTitle.value = '';

            showAlert( '✓ Short link created: ' + newLink.short_url, 'success' );

        } catch ( err ) {
            showAlert( 'Network error. Please try again.', 'error' );
        } finally {
            setLoading( false );
        }
    }

    // ── Delete a link ──────────────────────────────────────────────
    async function deleteLink( id ) {
        if ( ! confirm( 'Delete this link? This cannot be undone.' ) ) return;

        try {
            const response = await fetch( SnipVIP.apiUrl + '/links/' + id, {
                method:  'DELETE',
                headers: { 'X-WP-Nonce': SnipVIP.nonce },
            } );

            if ( ! response.ok ) {
                showAlert( 'Could not delete link. Please try again.', 'error' );
                return;
            }

            // Remove from local state and re-render
            links = links.filter( function ( l ) {
                return String( l.id ) !== String( id );
            } );
            renderLinks();

            // Refresh plan quota
            loadPlan();

        } catch ( err ) {
            showAlert( 'Network error. Please try again.', 'error' );
        }
    }

    // ── Refresh plan only ──────────────────────────────────────────
    async function loadPlan() {
        try {
            const response = await fetch( SnipVIP.apiUrl + '/plan', {
                headers: { 'X-WP-Nonce': SnipVIP.nonce },
            } );
            const data = await response.json();
            if ( response.ok ) renderPlan( data );
        } catch {}
    }

    // ── Events ─────────────────────────────────────────────────────
    createBtn.addEventListener( 'click', createLink );

    newUrl.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Enter' ) createLink();
    } );

    // ── Boot ───────────────────────────────────────────────────────
    loadLinks();

} )();