<?php
defined( 'ABSPATH' ) || exit;

// Redirect already logged in users
if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/dashboard/' ) );
    exit;
}

$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'login';
$is_register = $action === 'register';
$error   = '';
$success = '';

// Handle form submission
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {

    if ( $is_register ) {
        // Registration
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
        $email    = isset( $_POST['email'] )    ? sanitize_email( wp_unslash( $_POST['email'] ) )   : '';
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] )                  : '';

        if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
            $error = 'Please fill in all fields.';
        } elseif ( ! is_email( $email ) ) {
            $error = 'Please enter a valid email address.';
        } elseif ( username_exists( $username ) ) {
            $error = 'That username is already taken.';
        } elseif ( email_exists( $email ) ) {
            $error = 'An account with that email already exists.';
        } elseif ( strlen( $password ) < 8 ) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $user_id = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                $error = $user_id->get_error_message();
            } else {
                // Set default free plan
                update_user_meta( $user_id, 'snipvip_plan', 'free' );

                // Auto login after register
                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id );

                wp_redirect( home_url( '/dashboard/' ) );
                exit;
            }
        }
    } else {
        // Login
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) )   : '';
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] )                    : '';
        $remember = isset( $_POST['remember'] );

        if ( empty( $username ) || empty( $password ) ) {
            $error = 'Please enter your username and password.';
        } else {
            $user = wp_signon( [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ], false );

            if ( is_wp_error( $user ) ) {
                $error = 'Incorrect username or password.';
            } else {
                wp_redirect( home_url( '/dashboard/' ) );
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
            padding: 24px;
        }
        .auth-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
        }
        .auth-logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .auth-logo a {
            font-size: 24px;
            font-weight: 800;
            color: var(--brand);
            letter-spacing: -.5px;
        }
        .auth-logo a span { color: var(--gray-900); }
        .auth-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 6px;
            text-align: center;
        }
        .auth-sub {
            font-size: 14px;
            color: var(--gray-600);
            text-align: center;
            margin-bottom: 28px;
        }
        .auth-field {
            margin-bottom: 16px;
        }
        .auth-field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-700, #374151);
            margin-bottom: 6px;
        }
        .auth-field input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 15px;
            outline: none;
            transition: border-color .15s;
            color: var(--gray-800);
        }
        .auth-field input:focus { border-color: var(--brand); }
        .auth-divider {
            text-align: center;
            margin: 20px 0;
            font-size: 13px;
            color: var(--gray-400);
        }
        .auth-switch {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--gray-600);
        }
        .auth-switch a { color: var(--brand); font-weight: 500; }
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--gray-600);
        }
    </style>
</head>
<body <?php body_class( 'page-auth' ); ?>>

<div class="auth-wrap">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                Snip<span>VIP</span>
            </a>
        </div>

        <?php if ( $is_register ) : ?>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-sub">Start with 3 free short links. No credit card needed.</p>
        <?php else : ?>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-sub">Log in to manage your short links.</p>
        <?php endif; ?>

        <!-- Error -->
        <?php if ( $error ) : ?>
            <div class="alert alert--error is-visible" style="margin-bottom:20px;">
                <?php echo esc_html( $error ); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="">
            <?php wp_nonce_field( 'snipvip_auth', 'snipvip_nonce' ); ?>

            <div class="auth-field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                    placeholder="<?php echo $is_register ? 'Choose a username' : 'Your username'; ?>"
                />
            </div>

            <?php if ( $is_register ) : ?>
                <div class="auth-field">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        required
                        placeholder="you@example.com"
                    />
                </div>
            <?php endif; ?>

            <div class="auth-field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="<?php echo $is_register ? 'new-password' : 'current-password'; ?>"
                    required
                    placeholder="<?php echo $is_register ? 'Min. 8 characters' : 'Your password'; ?>"
                />
            </div>

            <?php if ( ! $is_register ) : ?>
                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember" style="margin:0;font-weight:400;">Remember me</label>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn--primary btn--full btn--lg">
                <?php echo $is_register ? 'Create account' : 'Log in'; ?>
            </button>

        </form>

        <!-- Switch -->
        <div class="auth-switch">
            <?php if ( $is_register ) : ?>
                Already have an account?
                <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Log in</a>
            <?php else : ?>
                Don't have an account?
                <a href="<?php echo esc_url( home_url( '/login/?action=register' ) ); ?>">Sign up free</a>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>