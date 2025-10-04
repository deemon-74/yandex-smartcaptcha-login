=== Yandex SmartCaptcha для входа и регистрации ===
Contributors: deemon-74
Tags: captcha, smartcaptcha, yandex, login, registration, security, anti-bot
Requires at least: 5.6
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Защита стандартных форм входа и регистрации WordPress с помощью Yandex SmartCaptcha.

== Description ==

Этот плагин добавляет виджет **Yandex SmartCaptcha** на страницы входа (`/wp-login.php`) и регистрации (`/wp-login.php?action=register`) в WordPress.

Он использует **автоматический метод подключения** SmartCaptcha и выполняет **серверную проверку токена** с использованием вашего секретного ключа, чтобы надёжно блокировать ботов.

Подходит для сайтов, которым нужна современная, ненавязчивая и эффективная защита от автоматических регистраций и брутфорса.

**Требуется аккаунт в Yandex Cloud и настроенная SmartCaptcha.**

== Installation ==

1. Перейдите в [Yandex Cloud Console](https://console.cloud.yandex.ru/) и создайте ресурс SmartCaptcha.
2. Скопируйте **Sitekey** (публичный ключ) и **Secret key** (секретный ключ).
3. Загрузите папку плагина в `/wp-content/plugins/` или установите через админку WordPress.
4. Активируйте плагин через меню «Плагины».
5. Перейдите в **Настройки → SmartCaptcha Login** и введите полученные ключи.
6. Готово! Капча появится на страницах входа и регистрации.

== Frequently Asked Questions ==

= Нужен ли аккаунт Yandex Cloud? =

Да. Плагин работает только с Yandex SmartCaptcha, для которой требуется ресурс в Yandex Cloud.

= Будет ли работать с WooCommerce или другими формами? =

Нет, только со **стандартными формами WordPress**: `/wp-login.php` и регистрацией через неё.  
Для других форм (WooCommerce, Contact Form 7 и т.д.) потребуется отдельная интеграция.

= Где хранится Secret key? =

Secret key хранится в таблице `wp_options` вашей базы данных и **никогда не передаётся в браузер**.

= Поддерживает ли невидимую капчу? =

В текущей версии — нет. Используется стандартный виджет. Поддержка расширенного метода (включая `invisible`) может быть добавлена в будущем.

== Changelog ==

= 1.0.0 =
* Первая публичная версия.
* Поддержка форм входа и регистрации.
* Серверная проверка токена через Yandex SmartCaptcha API.
* Настройки в админке WordPress.
* Совместимость с GitHub Updater.

== Upgrade Notice ==

= 1.0.0 =
Начальная версия. Убедитесь, что вы указали корректные Sitekey и Secret key в настройках.

== Screenshots ==

1. Форма входа с виджетом SmartCaptcha
2. Страница настроек плагина в админке WordPress

== Additional Notes ==

- Плагин использует автоматический метод подключения SmartCaptcha согласно [официальной документации](https://yandex.cloud/ru/docs/smartcaptcha/concepts/widget-methods).
- Для работы регистрации убедитесь, что включена опция **«Любой может зарегистрироваться»** в Настройки → Общие.
- Не коммитьте Secret key в репозиторий! Он вводится только через админку.
