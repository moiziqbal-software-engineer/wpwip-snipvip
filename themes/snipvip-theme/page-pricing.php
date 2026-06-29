<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'page-pricing' ); ?>>

<?php snipvip_nav(); ?>

<section class="pricing" style="padding-top:64px;">
    <div class="container">

        <div class="text-center mb-24">
            <p class="section-label">Pricing</p>
            <h1 class="section-title">Simple, transparent pricing</h1>
            <p class="section-sub" style="margin:0 auto 16px;">
                Start free. Upgrade when you need more.
                Cancel anytime — no questions asked.
            </p>
            <div id="pricing-alert" class="alert" style="max-width:480px;margin:16px auto 0;"></div>
        </div>

        <div class="pricing__grid">

            <!-- Free -->
            <div class="pricing-card">
                <div class="pricing-card__name">Free</div>
                <p style="font-size:14px;color:var(--gray-600);margin-top:4px;">
                    Perfect for trying it out
                </p>
                <div class="pricing-card__price">$0</div>
                <div class="pricing-card__limit">3 short links forever</div>
                <ul class="pricing-card__features">
                    <li>3 active short links</li>
                    <li>Basic click tracking</li>
                    <li>Standard redirects</li>
                    <li>Community support</li>
                </ul>
                <?php if ( is_user_logged_in() ) : ?>
                    <button class="btn btn--outline btn--full" disabled>
                        Current free plan
                    </button>
                <?php else : ?>
                    <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="btn btn--outline btn--full">
                        Get started free
                    </a>
                <?php endif; ?>
            </div>

            <!-- Starter -->
            <div class="pricing-card">
                <div class="pricing-card__name">Starter</div>
                <p style="font-size:14px;color:var(--gray-600);margin-top:4px;">
                    For individuals and freelancers
                </p>
                <div class="pricing-card__price">$9<span>/mo</span></div>
                <div class="pricing-card__limit">100 short links</div>
                <ul class="pricing-card__features">
                    <li>100 active short links</li>
                    <li>Full click analytics</li>
                    <li>Referrer tracking</li>
                    <li>CSV export</li>
                    <li>Email support</li>
                </ul>
                <button
                    class="btn btn--outline btn--full checkout-btn"
                    data-plan="starter"
                >
                    Get Starter
                </button>
            </div>

            <!-- Pro -->
            <div class="pricing-card pricing-card--featured">
                <div class="pricing-card__badge">Most popular</div>
                <div class="pricing-card__name">Pro</div>
                <p style="font-size:14px;color:var(--gray-600);margin-top:4px;">
                    For growing teams
                </p>
                <div class="pricing-card__price">$29<span>/mo</span></div>
                <div class="pricing-card__limit">1,000 short links</div>
                <ul class="pricing-card__features">
                    <li>1,000 active short links</li>
                    <li>Advanced analytics dashboard</li>
                    <li>Referrer + geo tracking</li>
                    <li>Priority support</li>
                    <li>REST API access</li>
                    <li>Team members (coming soon)</li>
                </ul>
                <button
                    class="btn btn--primary btn--full checkout-btn"
                    data-plan="pro"
                >
                    Get Pro
                </button>
            </div>

            <!-- Enterprise -->
            <div class="pricing-card">
                <div class="pricing-card__name">Enterprise</div>
                <p style="font-size:14px;color:var(--gray-600);margin-top:4px;">
                    For large organisations
                </p>
                <div class="pricing-card__price">$99<span>/mo</span></div>
                <div class="pricing-card__limit">Unlimited short links</div>
                <ul class="pricing-card__features">
                    <li>Unlimited short links</li>
                    <li>Full analytics suite</li>
                    <li>Dedicated support</li>
                    <li>SLA guarantee</li>
                    <li>Custom integrations</li>
                    <li>Invoiced billing available</li>
                </ul>
                <button
                    class="btn btn--outline btn--full checkout-btn"
                    data-plan="enterprise"
                >
                    Get Enterprise
                </button>
            </div>

        </div>

        <!-- FAQ -->
        <div style="max-width:640px;margin:64px auto 0;">
            <h2 class="section-title text-center" style="margin-bottom:32px;">
                Frequently asked questions
            </h2>

            <div style="display:flex;flex-direction:column;gap:20px;">

                <div style="background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:24px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--gray-900);margin-bottom:8px;">
                        Can I cancel anytime?
                    </h3>
                    <p style="font-size:14px;color:var(--gray-600);line-height:1.6;">
                        Yes. Cancel from your billing portal anytime.
                        You keep your plan until the end of the billing period,
                        then drop to the free tier automatically.
                    </p>
                </div>

                <div style="background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:24px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--gray-900);margin-bottom:8px;">
                        What happens to my links if I downgrade?
                    </h3>
                    <p style="font-size:14px;color:var(--gray-600);line-height:1.6;">
                        Your existing links keep working. You just can't create
                        new ones until you're back within your plan's limit.
                    </p>
                </div>

                <div style="background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:24px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--gray-900);margin-bottom:8px;">
                        Is payment secure?
                    </h3>
                    <p style="font-size:14px;color:var(--gray-600);line-height:1.6;">
                        All payments are processed by Stripe — the same payment
                        infrastructure used by Amazon, Shopify, and millions of
                        other businesses. We never store your card details.
                    </p>
                </div>

                <div style="background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:24px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--gray-900);margin-bottom:8px;">
                        Do short links expire?
                    </h3>
                    <p style="font-size:14px;color:var(--gray-600);line-height:1.6;">
                        No. All short links are permanent as long as your account
                        is active. Even free links never expire.
                    </p>
                </div>

            </div>
        </div>

    </div>
</section>

<?php snipvip_footer(); ?>
<?php wp_footer(); ?>

</body>
</html>