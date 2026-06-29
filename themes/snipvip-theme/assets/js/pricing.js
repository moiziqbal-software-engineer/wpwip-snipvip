( function () {
    'use strict';

    const alert = document.getElementById( 'pricing-alert' );

    // ── Helpers ────────────────────────────────────────────────────
    function showAlert( msg, type ) {
        alert.textContent = msg;
        alert.className   = 'alert is-visible alert--' + type;
        alert.scrollIntoView( { behavior: 'smooth', block: 'center' } );
    }

    function setButtonLoading( btn, loading ) {
        btn.disabled     = loading;
        btn.dataset.orig = btn.dataset.orig || btn.textContent;
        btn.textContent  = loading ? 'Redirecting to Stripe...' : btn.dataset.orig;
    }

    // ── Checkout ───────────────────────────────────────────────────
    async function startCheckout( plan, btn ) {

        // Not logged in — send to login page
        if ( ! SnipVIP.user.loggedIn ) {
            window.location.href = SnipVIP.loginUrl;
            return;
        }

        setButtonLoading( btn, true );

        try {
            const response = await fetch( SnipVIP.apiUrl + '/stripe/checkout', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':  SnipVIP.nonce,
                },
                body: JSON.stringify( { plan } ),
            } );

            const data = await response.json();

            if ( ! response.ok ) {
                showAlert(
                    data.message || 'Could not start checkout. Please try again.',
                    'error'
                );
                setButtonLoading( btn, false );
                return;
            }

            // Redirect to Stripe Checkout
            window.location.href = data.checkout_url;

        } catch ( err ) {
            showAlert( 'Network error. Please check your connection.', 'error' );
            setButtonLoading( btn, false );
        }
    }

    // ── Bind checkout buttons ──────────────────────────────────────
    document.querySelectorAll( '.checkout-btn' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const plan = this.dataset.plan;
            if ( plan ) startCheckout( plan, this );
        } );
    } );

    // ── Show cancelled message ─────────────────────────────────────
    const params = new URLSearchParams( window.location.search );
    if ( params.get( 'cancelled' ) === '1' ) {
        showAlert( 'Checkout cancelled — no charge was made. Choose a plan when ready.', 'error' );
    }

} )();