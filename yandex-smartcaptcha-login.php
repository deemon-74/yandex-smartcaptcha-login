<?php
/**
 * Plugin Name: Yandex SmartCaptcha для входа и регистрации
 * Plugin URI: https://example.com/yandex-smartcaptcha-login
 * Description: Защита форм входа и регистрации с помощью Yandex SmartCaptcha.
 * Version: 1.0.0
 * Author: Дмитрий Ермаков
 * Author URI: https://kolibri-art.ru
 * GitHub Plugin URI: https://github.com/deemon-74/yandex-smartcaptcha-login
 * License: GPLv2 or later
 * Text Domain: ysc-login
 */

if (!defined('ABSPATH')) exit;

// === 1. Подключаем скрипт SmartCaptcha на страницах входа и регистрации ===
function ysc_login_enqueue_scripts() {
    if (!in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'])) return;

    wp_enqueue_script(
        'smartcaptcha',
        'https://smartcaptcha.yandexcloud.net/captcha.js',
        [],
        null,
        true
    );
}
add_action('login_enqueue_scripts', 'ysc_login_enqueue_scripts');

// === 2. Добавляем контейнер капчи на форму входа и регистрации ===
function ysc_add_captcha_to_login_form() {
    $sitekey = get_option('ysc_sitekey');
    if (!$sitekey) return;

    echo '<div class="smart-captcha" data-sitekey="' . esc_attr($sitekey) . '" data-callback="yscOnSuccess"></div>';
    echo '<input type="hidden" name="smartcaptcha_token" id="smartcaptcha_token" value="">';
    ?>
    <script>
    function yscOnSuccess(token) {
        document.getElementById('smartcaptcha_token').value = token;
        // Разблокируем кнопку, если нужно
        const submitBtn = document.querySelector('#wp-submit');
        if (submitBtn) submitBtn.disabled = false;
    }
    </script>
    <?php
}
add_action('login_form', 'ysc_add_captcha_to_login_form'); // для входа
add_action('register_form', 'ysc_add_captcha_to_login_form'); // для регистрации (если включена)

// === 3. Проверка токена при попытке входа ===
function ysc_verify_login_captcha($user, $username, $password) {
    if (!is_a($user, 'WP_User') && !is_wp_error($user)) {
        // Проверяем только если ещё не ошибка
        if (!ysc_verify_token_from_request()) {
            return new WP_Error('smartcaptcha_failed', 'Пройдите проверку SmartCaptcha.');
        }
    }
    return $user;
}
add_filter('authenticate', 'ysc_verify_login_captcha', 30, 3);

// === 4. Проверка токена при регистрации ===
function ysc_verify_registration_captcha($errors) {
    if (!ysc_verify_token_from_request()) {
        $errors->add('smartcaptcha_failed', 'Пройдите проверку SmartCaptcha.');
    }
    return $errors;
}
add_filter('registration_errors', 'ysc_verify_registration_captcha');

// === 5. Вспомогательная функция: получение и проверка токена ===
function ysc_verify_token_from_request() {
    if (!isset($_POST['smartcaptcha_token']) || empty($_POST['smartcaptcha_token'])) {
        return false;
    }

    $token = sanitize_text_field($_POST['smartcaptcha_token']);
    $secret = get_option('ysc_secret');

    if (!$secret) {
        error_log('Yandex SmartCaptcha: secret key не задан');
        return false;
    }

    $response = wp_remote_post('https://smartcaptcha.yandexcloud.net/validate', [
        'timeout' => 10,
        'body'    => [
            'secret' => $secret,
            'token'  => $token,
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('Yandex SmartCaptcha: ошибка при валидации токена: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($body['status']) && $body['status'] === 'ok';
}

// === 6. Настройки в админке (sitekey и secret) ===
add_action('admin_menu', 'ysc_admin_menu');
add_action('admin_init', 'ysc_admin_init');

function ysc_admin_menu() {
    add_options_page('SmartCaptcha Login', 'SmartCaptcha Login', 'manage_options', 'ysc-login', 'ysc_options_page');
}

function ysc_admin_init() {
    register_setting('ysc_login_options', 'ysc_sitekey');
    register_setting('ysc_login_options', 'ysc_secret');
}

function ysc_options_page() {
    ?>
    <div class="wrap">
        <h2>Yandex SmartCaptcha для входа и регистрации</h2>
        <form method="post" action="options.php">
            <?php settings_fields('ysc_login_options'); ?>
            <table class="form-table">
                <tr>
                    <th>Sitekey (публичный ключ)</th>
                    <td><input type="text" name="ysc_sitekey" value="<?php echo esc_attr(get_option('ysc_sitekey')); ?>" size="60"></td>
                </tr>
                <tr>
                    <th>Secret key (секретный ключ)</th>
                    <td><input type="password" name="ysc_secret" value="<?php echo esc_attr(get_option('ysc_secret')); ?>" size="60"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}