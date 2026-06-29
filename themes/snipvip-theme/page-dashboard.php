<?php
defined( 'ABSPATH' ) || exit;

// Redirect if not logged in — double protection alongside functions.php
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( home_url( '/dashboard/' ) ) );
    exit;
}

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'page-dashboard' ); ?>>

<?php snipvip_nav(); ?>

<main class="dashboard">
    <div class="container">

        <!-- Header -->
        <div class="dashboard__header">
            <div>
                <h1 class="dashboard__title">
                    Welcome back, <?php echo esc_html( $current_user->display_name ); ?> 👋
                </h1>
                <p style="color:var(--gray-600);font-size:14px;margin-top:4px;">
                    Manage your short links and track performance
                </p>
            </div>
            <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--outline" id="upgrade-btn" style="display:none;">
                ⚡ Upgrade plan
            </a>
        </div>

        <!-- Upgrade success alert -->
        <?php if ( isset( $_GET['upgraded'] ) && '1' === $_GET['upgraded'] ) : ?>
            <div class="alert alert--success is-visible mb-24">
                🎉 Your plan has been upgraded successfully! Enjoy your new limits.
            </div>
        <?php endif; ?>

        <!-- Plan banner — filled by JS -->
        <div class="plan-banner" id="plan-banner">
            <div class="plan-banner__info">
                <div class="plan-banner__badge" id="plan-name">Loading...</div>
                <div>
                    <div class="plan-banner__usage" id="plan-usage"></div>
                    <div class="plan-banner__bar">
                        <div class="plan-banner__bar-fill" id="plan-bar" style="width:0%"></div>
                    </div>
                </div>
            </div>
            <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="btn btn--outline" id="plan-upgrade-link" style="display:none;">
                Upgrade
            </a>
        </div>

        <!-- Create link form -->
        <div class="create-link">
            <h2 style="font-size:16px;font-weight:600;color:var(--gray-900);margin-bottom:16px;">
                Create a short link
            </h2>
            <div class="create-link__row">
                <input
                    type="url"
                    id="new-url"
                    class="create-link__input"
                    placeholder="https://example.com/your-very-long-url"
                    autocomplete="off"
                />
                <input
                    type="text"
                    id="new-title"
                    class="create-link__input"
                    placeholder="Title (optional)"
                    style="max-width:200px;"
                />
                <button id="create-btn" class="btn btn--primary">
                    Shorten
                </button>
            </div>
            <div id="create-alert" class="alert mt-16"></div>
        </div>

        <!-- Links table -->
        <div class="links-table-wrap">
            <table class="links-table">
                <thead>
                    <tr>
                        <th>Short link</th>
                        <th>Destination</th>
                        <th>Clicks</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="links-tbody">
                    <tr>
                        <td colspan="5" class="links-table__empty">
                            Loading your links...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</main>

<?php snipvip_footer(); ?>
<?php wp_footer(); ?>

</body>
</html>