( function () {
    'use strict';

    const input     = document.getElementById( 'hero-url-input' );
    const btn       = document.getElementById( 'hero-shorten-btn' );
    const alert     = document.getElementById( 'hero-alert' );
    const result    = document.getElementById( 'hero-result' );
    const shortUrl  = document.getElementById( 'hero-short-url' );
    const copyBtn   = document.getElementById( 'hero-copy-btn' );

    if ( ! input || ! btn ) return;

    // ── Helpers ────────────────────────────────────────────────────
    function showAlert( msg, type ) {
        alert.textContent   = msg;
        alert.className     = 'alert mt-16 is-visible alert--' + type;
    }

    function hideAlert() {
        alert.className = 'alert mt-16';
    }

    function showResult( url ) {
        shortUrl.textContent = url;
        shortUrl.href        = url;
        result.classList.add( 'is-visible' );
    }

    function setLoading( loading ) {
        btn.disabled    = loading;
        btn.textContent = loading ? 'Shortening...' : 'Shorten';
    }

    // ── Shorten ────────────────────────────────────────────────────
    async function shorten() {
        const url = input.value.trim();

        if ( ! url ) {
            showAlert( 'Please paste a URL first.', 'error' );
            return;
        }

        // Basic URL validation
        try {
            new URL( url );
        } catch {
            showAlert( 'Please enter a valid URL including https://', 'error' );
            return;
        }

        hideAlert();
        setLoading( true );

        // If user is not logged in — show signup prompt after preview
        if ( ! SnipVIP.user.loggedIn ) {
            // Simulate a short link to show the UI
            // Real shortening requires an account
            setTimeout( () => {
                setLoading( false );
                showResult( 'Sign up to shorten this link →' );
                shortUrl.href = SnipVIP.loginUrl;
                document.querySelector( '.shorten-box__note' ).innerHTML =
                    '🔒 <a href="' + SnipVIP.loginUrl + '">Create a free account</a> to get your short link.';
            }, 400 );
            return;
        }

        // Logged in — call the real API
        try {
            const response = await fetch( SnipVIP.apiUrl + '/shorten', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':   SnipVIP.nonce,
                },
                body: JSON.stringify( { url } ),
            } );

            const data = await response.json();

            if ( ! response.ok ) {
                showAlert( data.message || 'Something went wrong. Please try again.', 'error' );
                return;
            }

            showResult( data.link.short_url );
            input.value = '';

        } catch ( err ) {
            showAlert( 'Network error. Please check your connection.', 'error' );
        } finally {
            setLoading( false );
        }
    }

    // ── Copy ───────────────────────────────────────────────────────
    copyBtn.addEventListener( 'click', function () {
        const url = shortUrl.textContent.trim();
        if ( ! url || url.startsWith( 'Sign up' ) ) return;

        navigator.clipboard.writeText( url ).then( () => {
            copyBtn.textContent = 'Copied!';
            copyBtn.classList.add( 'copied' );
            setTimeout( () => {
                copyBtn.textContent = 'Copy';
                copyBtn.classList.remove( 'copied' );
            }, 2000 );
        } );
    } );

    // ── Events ─────────────────────────────────────────────────────
    btn.addEventListener( 'click', shorten );

    input.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Enter' ) shorten();
    } );

} )();