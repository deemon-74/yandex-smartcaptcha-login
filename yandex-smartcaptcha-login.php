<?php
/**
 * Plugin Name: Yandex SmartCaptcha для входа и регистрации
 * Plugin URI: https://kolibri-art.ru
 * Description: Защита стандартных форм входа и регистрации WordPress с помощью Yandex SmartCaptcha.
 * Version: 1.0.2
 * Author: Дмитрий Ермаков
 * Author URI: https://kolibri-art.ru
 * GitHub Plugin URI: https://github.com/deemon-74/yandex-smartcaptcha-login
 * License: GPLv2 or later
 * Text Domain: ysc-login
 */

if (!defined('ABSPATH')) {
    exit;
}

// === 1. Подключаем скрипт SmartCaptcha на странице входа ===
function ysc_login_enqueue_scripts() {
    if ($GLOBALS['pagenow'] !== 'wp-login.php') {
        return;
    }

    wp_enqueue_script(
        'smartcaptcha',
        'https://smartcaptcha.yandexcloud.net/captcha.js',
        [],
        null,
        true
    );
}
add_action('login_enqueue_scripts', 'ysc_login_enqueue_scripts');

// === 2. Выводим виджет капчи и управляем кнопкой отправки ===
function ysc_add_captcha_to_login_form() {
    $sitekey = get_option('ysc_sitekey');
    if (!$sitekey) {
        return;
    }

    echo '<div class="smart-captcha" data-sitekey="' . esc_attr($sitekey) . '" data-callback="yscOnSuccess"></div>';
    echo '<input type="hidden" name="smartcaptcha_token" id="smartcaptcha_token" value="">';

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const submitBtn = document.getElementById('wp-submit');
        if (submitBtn) {
            submitBtn.disabled = true; // Блокируем до прохождения капчи
        }
    });

    function yscOnSuccess(token) {
        document.getElementById('smartcaptcha_token').value = token;
        const submitBtn = document.getElementById('wp-submit');
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }
    </script>
    <?php
}
add_action('login_form', 'ysc_add_captcha_to_login_form');       // Форма входа
add_action('register_form', 'ysc_add_captcha_to_login_form');   // Форма регистрации

// === 3. Проверка капчи при отправке формы входа ===
function ysc_block_login_without_captcha() {
    // Пропускаем GET-запросы и пустые POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (empty($_POST['log']) || empty($_POST['pwd'])) {
        return;
    }

    if (!ysc_verify_token_from_request()) {
        wp_die(
            'Пройдите проверку SmartCaptcha.',
            'Ошибка капчи',
            [
                'response'  => 400,
                'back_link' => true,
            ]
        );
    }
}
add_action('login_form_login', 'ysc_block_login_without_captcha');

// === 4. Проверка капчи при отправке формы регистрации ===
function ysc_block_register_without_captcha() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (empty($_POST['user_login']) || empty($_POST['user_email'])) {
        return;
    }

    if (!ysc_verify_token_from_request()) {
        wp_die(
            'Пройдите проверку SmartCaptcha.',
            'Ошибка капчи',
            [
                'response'  => 400,
                'back_link' => true,
            ]
        );
    }
}
add_action('login_form_register', 'ysc_block_register_without_captcha');

// === 5. Серверная валидация токена через Yandex API ===
function ysc_verify_token_from_request() {
    if (!isset($_POST['smartcaptcha_token']) || empty($_POST['smartcaptcha_token'])) {
        return false;
    }

    $token = sanitize_text_field($_POST['smartcaptcha_token']);
    $secret = get_option('ysc_secret');

    if (!$secret) {
        error_log('Yandex SmartCaptcha: secret key не задан в настройках.');
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
        error_log('Yandex SmartCaptcha: ошибка валидации токена: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($body['status']) && $body['status'] === 'ok';
}

// === 6. Настройки в админке WordPress ===
add_action('admin_menu', 'ysc_admin_menu');
add_action('admin_init', 'ysc_admin_init');

function ysc_admin_menu() {
    add_options_page(
        'Yandex SmartCaptcha для входа и регистрации',
        'SmartCaptcha Login',
        'manage_options',
        'ysc-login',
        'ysc_options_page'
    );
}

function ysc_admin_init() {
    register_setting('ysc_login_options', 'ysc_sitekey');
    register_setting('ysc_login_options', 'ysc_secret');
}

function ysc_options_page() {
    ?>
    <div class="wrap">
        <h1>Yandex SmartCaptcha для входа и регистрации</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ysc_login_options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Sitekey (публичный ключ)</th>
                    <td>
                        <input type="text" name="ysc_sitekey" value="<?php echo esc_attr(get_option('ysc_sitekey')); ?>" size="60" />
                        <p class="description">
                            Получите в <a href="https://console.cloud.yandex.ru/" target="_blank">Yandex Cloud Console</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Secret key (секретный ключ)</th>
                    <td>
                        <input type="password" name="ysc_secret" value="<?php echo esc_attr(get_option('ysc_secret')); ?>" size="60" />
                        <p class="description">Никогда не публикуйте этот ключ!</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
