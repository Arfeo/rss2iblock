# rss2iblock
Модуль 1С-Битрикс для сбора контента с внешних ресурсов в инфоблоки посредством RSS

Поддерживает форматы **RSS 2.0** и **Atom**

## Установка
* В папке `rss2iblock` проекта выполните команду:

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`php composer.phar install`

* Скопируйте папку `rss2iblock` проекта в директорию `/bitrix/modules/`.
* В административном интерфейсе на странице _Настройки > Настройки продукта > Модули_ нажмите кнопку [Установить] напротив названия модуля.

## Работа с модулем
Настройки модуля: _Настройки > Настройки продукта > Настройки модулей > Rss2IBlock_.

![Настройки](http://static.arfeo.net/rss2iblock/settings.png "Настройки модуля")

Интерфейс управления источниками: _Сервисы > Rss2IBlock > Источники_.

![Интерфейс модуля](http://static.arfeo.net/rss2iblock/sources.png "Список источников")

![Интерфейс модуля](http://static.arfeo.net/rss2iblock/add_source.png "Добавление нового источника")

## Powered by

* [Readability.php](https://github.com/andreskrey/readability.php) (Apache-2.0)
