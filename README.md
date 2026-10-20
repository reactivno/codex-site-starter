# Базовый проект сайта

Каркас сайта на HTML/CSS/JS с формой, которая уходит в PHP и отправляется письмом через `mail()`.
Зависимостей нет: ни npm, ни composer. Нужен только PHP 8.1+.

## Запуск локально

```bash
php -S localhost:8000
```

Откройте <http://localhost:8000>. Форма отправит POST в `php/send.php`.

Переменные для локальной проверки: скопируйте `.env.example` в `.env` и заполните.
Встроенный сервер PHP не читает `.env` сам — для локальной отправки задайте переменные в окружении:

```bash
MAIL_TO=you@example.com php -S localhost:8000
```

Без `MAIL_TO` обработчик вернёт 500 «Получатель не настроен» — это ожидаемо.

## Структура

```text
index.html            страница с формой
css/style.css         стили
js/main.js            отправка формы без перезагрузки
php/send.php          валидация и mail()
.github/workflows/    preview | cleanup | deploy
AGENTS.md             правила работы Codex
TZ.md                 требования и пайплайн
.env.example          список переменных
```

## Секреты GitHub (Settings → Secrets)

| Секрет | Значение |
| --- | --- |
| `FTP_SERVER` | хост FTP, например `ftp.example.com` |
| `FTP_USERNAME` | логин FTP |
| `FTP_PASSWORD` | пароль FTP |
| `FTP_SERVER_DIR` | папка сайта на сервере, с `/` на конце: `/public_html/` |
| `PREVIEW_BASE_URL` | адрес стендов без слэша: `https://staging.example.com/previews` |

## Переменные окружения сайта (на хостинге)

| Переменная | Значение |
| --- | --- |
| `MAIL_TO` | кому приходят заявки |
| `MAIL_FROM` | отправитель в письме, по умолчанию `MAIL_TO` |
| `MAIL_SUBJECT_PREFIX` | приписка к теме, например название сайта |

Как задать их на FastVPS — в панели сайта. `getenv()` видит только то, что реально попадает в окружение PHP-FPM; если пусто, проверьте настройки панели.

## Как идёт работа

1. Codex делает ветку, правки, PR.
2. Workflow `Preview` заливает код в `/previews/pr-<номер>/` и пишет ссылку в PR.
3. Вы смотрите стенд и одобряете PR.
4. После merge в `main` workflow `Deploy` заливает сайт в `FTP_SERVER_DIR`.
5. `Cleanup` удаляет папку стенда при закрытии PR.
