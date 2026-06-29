<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php snipvip_nav(); ?>

<!-- Hero -->
<section class="hero">
    <div class="container--narrow">

        <div class="hero__badge">
            ⚡ Built on WordPress VIP
        </div>

        <h1 class="hero__title">
            Shorten links.<br>
            <span>Track everything.</span>
        </h1>

        <p class="hero__sub">
            Create short, memorable links in seconds.
            Track clicks, referrers, and more — all in one dashboard.
        </p>

        <!-- Shorten box -->
        <div class="shorten-box">
            <div class="shorten-box__row">
                <input
                    type="url"
                    id="hero-url-input"
                    class="shorten-box__input"
                    placeholder="Paste a long URL here..."
                    autocomplete="off"
                />
                <button id="hero-shorten-btn" class="btn btn--primary btn--lg">
                    Shorten
                </button>
            </div>

            <!-- Alert -->
            <div id="hero-alert" class="alert mt-16"></div>

            <!-- Result -->
            <div id="hero-result" class="shorten-box__result">
                <div class="shorten-box__result-row">
                    <a id="hero-short-url" class="shorten-box__short-url" href="#" target="_blank"></a>
                    <button class="copy-btn" id="hero-copy-btn">Copy</button>
                </div>
                <p class="shorten-box__note">
                    🔒 Sign up free to save your links and see click analytics.
                </p>
            </div>
        </div>

        <!-- CTA row -->
        <div class="mt-24" style="display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;">
            <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="btn btn--primary btn--lg">
                Start for free
            </a>
            <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--outline btn--lg">
                See pricing
            </a>
        </div>

    </div>
</section>

<!-- Features -->
<section class="features">
    <div class="container">
        <div class="text-center">
            <p class="section-label">Why SnipVIP</p>
            <h2 class="section-title">Everything you need to manage links</h2>
            <p class="section-sub" style="margin:0 auto;">
                A professional link shortener built for teams and individuals who care about performance.
            </p>
        </div>

        <div class="features__grid">

            <div class="feature-card">
                <div class="feature-card__icon">⚡</div>
                <h3 class="feature-card__title">Lightning fast redirects</h3>
                <p class="feature-card__desc">
                    Every redirect is cached at the edge via Fastly CDN.
                    Sub-millisecond response times globally.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-card__icon">📊</div>
                <h3 class="feature-card__title">Real-time analytics</h3>
                <p class="feature-card__desc">
                    Track every click with referrer, timestamp, and geographic data.
                    Know exactly who's clicking your links.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-card__icon">🔒</div>
                <h3 class="feature-card__title">Enterprise security</h3>
                <p class="feature-card__desc">
                    Built on WordPress VIP Go — the same platform used by
                    News Corp, Meta, and Salesforce.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-card__icon">💳</div>
                <h3 class="feature-card__title">Simple billing</h3>
                <p class="feature-card__desc">
                    Stripe-powered subscriptions. Upgrade, downgrade, or cancel
                    anytime. No contracts, no surprises.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-card__icon">🔗</div>
                <h3 class="feature-card__title">Custom slugs coming soon</h3>
                <p class="feature-card__desc">
                    Pro and Enterprise users will be able to create branded
                    short links like snip.vip/my-brand.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-card__icon">📱</div>
                <h3 class="feature-card__title">Works everywhere</h3>
                <p class="feature-card__desc">
                    Share your short links on social media, email, SMS,
                    or anywhere else. They just work.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- Pricing teaser -->
<section class="pricing">
    <div class="container">
        <div class="text-center mb-24">
            <p class="section-label">Pricing</p>
            <h2 class="section-title">Start free, scale when ready</h2>
            <p class="section-sub" style="margin:0 auto;">
                No credit card required to get started.
                Upgrade when you need more links.
            </p>
        </div>

        <div class="pricing__grid">

            <!-- Free -->
            <div class="pricing-card">
                <div class="pricing-card__name">Free</div>
                <div class="pricing-card__price">$0</div>
                <div class="pricing-card__limit">3 short links</div>
                <ul class="pricing-card__features">
                    <li>3 active short links</li>
                    <li>Basic click tracking</li>
                    <li>Standard support</li>
                </ul>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="btn btn--outline btn--full">
                    Get started
                </a>
            </div>

            <!-- Starter -->
            <div class="pricing-card">
                <div class="pricing-card__name">Starter</div>
                <div class="pricing-card__price">$9<span>/mo</span></div>
                <div class="pricing-card__limit">100 short links</div>
                <ul class="pricing-card__features">
                    <li>100 active short links</li>
                    <li>Full click analytics</li>
                    <li>Referrer tracking</li>
                    <li>Email support</li>
                </ul>
                <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--outline btn--full">
                    Get Starter
                </a>
            </div>

            <!-- Pro -->
            <div class="pricing-card pricing-card--featured">
                <div class="pricing-card__badge">Most popular</div>
                <div class="pricing-card__name">Pro</div>
                <div class="pricing-card__price">$29<span>/mo</span></div>
                <div class="pricing-card__limit">1,000 short links</div>
                <ul class="pricing-card__features">
                    <li>1,000 active short links</li>
                    <li>Advanced analytics</li>
                    <li>Priority support</li>
                    <li>API access</li>
                </ul>
                <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--primary btn--full">
                    Get Pro
                </a>
            </div>

            <!-- Enterprise -->
            <div class="pricing-card">
                <div class="pricing-card__name">Enterprise</div>
                <div class="pricing-card__price">$99<span>/mo</span></div>
                <div class="pricing-card__limit">Unlimited short links</div>
                <ul class="pricing-card__features">
                    <li>Unlimited short links</li>
                    <li>Full analytics suite</li>
                    <li>Dedicated support</li>
                    <li>SLA guarantee</li>
                </ul>
                <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--outline btn--full">
                    Get Enterprise
                </a>
            </div>

        </div>
    </div>
</section>

<?php snipvip_footer(); ?>
<?php wp_footer(); ?>

</body>
</html>