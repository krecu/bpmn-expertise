Запуск и управление демонами через Supervisor
==================================

На сервере должен быть установлен **supervisord**

## Установка Supervisord
Устанавливаем easy_install

``` bash
yum install python-setuptools
```

Устанавливаем Supervisord с необходимыми плагинами

``` bash
easy_install supervisor superlance
```

## Настройка "программ"

Базовый шаблон конфигурации лежит в гите в файле **supervisord.conf.template**, который при деплое на сервер меняется на **supervisord.conf** и корректируются пути, переменные окружения при необходимости, кол-во экземпляров процессов и прочее.

``` ini
[program:integrations-dfcp]
; наша команда с абсолютными путями
command=/usr/bin/php /var/www/integrations.isz.gosbook.ru/www/app/console rabbitmq:rpc-server isz
; шаблон системного процесса, если программа пускается одним процессом, process_num не нужен
process_name=isz-%(program_name)s-%(process_num)s 
; кол-во параллельных процессов
numprocs=3
; папка для окружения и корректной работы всяких инклудов
directory=/var/www/integrations.isz.gosbook.ru/www 
autostart=true
autorestart=true
stdout_logfile=/var/www/integrations.isz.gosbook.ru/www/app/logs/gearman.info.log
stdout_logfile_maxbytes=1MB
stderr_logfile=/var/www/integrations.isz.gosbook.ru/www/app/logs/gearman.error.log
stderr_logfile_maxbytes=1MB
```

Для удобства можно объединять программы в группы:
``` ini
[group:integrations]
programs=integrations-dfcp,integrations-sstp
priority=1
```


## Запуск процессов
``` bash
supervisord -c PATH_TO_PROJECT/supervisord.conf;
```
Запускаем все процессы
``` bash
supervisorctl -c PATH_TO_PROJECT/supervisord.conf start all
```
Проверяем статусы сервисов
``` bash
supervisorctl -c PATH_TO_PROJECT/supervisord.conf status
```

Работа с одним процессом
``` bash
supervisorctl -c PATH_TO_PROJECT/supervisord.conf restart integrations:integrations-dfcp
```

Работа с группой процессов
``` bash
supervisorctl -c PATH_TO_PROJECT/supervisord.conf stop integrations:*
```

## Прочее

Прочее смотрите в комментариях в шаблоне и тут: http://supervisord.org/configuration.html