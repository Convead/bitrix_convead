Системные требования
====================

Редакция Битрикс: «Первый сайт», «Старт», «Стандарт», «Малый бизнес», «Эксперт», «Бизнес».  
Версия платформы: от 13 и выше (плагин тестируется с версиями 15+).


Установка плагина через Marketplace Битрикс
===========================================

Посмотрите [презентацию](https://docs.google.com/a/convead.com/presentation/d/1jLIN1OkTSb0_X4QLUhtG8CLSSd3ZvSwtTuQVGpEaBXs/embed?start=false&loop=false&delayms=3000#slide=id.p) о том, как установить Convead через Marketplace Битрикс.

Установка плагина вручную
=========================

1. Скачайте [архив с последней версией плагина](https://github.com/Convead/bitrix_convead/archive/master.zip) и распакуйте его.
2. Скопируйте папку `platina.conveadtracker` в директорию сайта `bitrix/modules/`.
3. В панели администратора перейдите на страницу "Marketplace -> Установленные решения":  
   `<адрес сайта>/bitrix/admin/partner_modules.php`
4. В контекстном меню решения "Трекер для Convead" выберите пункт "Установить".
5. Следуйте [инструкции по установке](https://docs.google.com/a/convead.com/presentation/d/1jLIN1OkTSb0_X4QLUhtG8CLSSd3ZvSwtTuQVGpEaBXs/embed?start=false&loop=false&delayms=3000#slide=id.p).
6. Не забудьте очистить кеш сайта в разделе "Настройки" - "Настройки продукта" - "Автокеширование" на вкладке "Очистка файлов кеша", опция "Все", кнопка "Начать".

Настройка передаваемых данных о пользователе
============================================

Из коробки плагин передает в Convead следующую информацию о зарегистрированном пользователе магазина (информация берется из указанных полей объекта "Пользователь" в Битрикс):

* `first_name` - имя (из поля `NAME`);
* `last_name` - фамилия (из поля `LAST_NAME`);
* `email` - эл. почта (из поля `EMAIL`);
* `phone` - телефон (из поля `PERSONAL_PHONE`);
* `data_of_birth` - дата рождения (из поля `PERSONAL_BIRTHDAY`);
* `gender` - пол (из поля `PERSONAL_GENDER`) 

Если вам для целей сегментации и анатилики требуется передавать в Convead дополнительные данные о ваших пользователях, то вы можете расширить этот набор данных следующим образом:

1. Создайте файл `bitrix/php_interface/include/helper/ConveadHelper.php` с классом `ConveadHelper` и функцией `GetAddInfo`.
2. Данная функция должна принимать три аргумента:  
   - ID пользователя в Битрикс;  
   - базовый массив `visitor_info` для этого пользователя;  
   - объект пользователя Битрикс.
3. Функция должна вернуть результирующий массив с информацией о пользователе. Его содержимое будет передано в Convead в параметре `visitor_info`.

**Пример:**

Допустим, о вашем пользователе Битрикс знает только имя и эл. почту. Тогда в Convead будет передаваться следующая информация:

```javascript
visitor_info: {
  first_name: "Ivan",
  email: "ivan@example.net"
}
```

Вы хотите передавать дополнительные атрибуты, необходимые вам в Convead. Для этого вы создаете файл `bitrix/php_interface/include/helper/ConveadHelper.php` со следующим содержимым:

```php
<?php

class ConveadHelper
  {
    static function GetAddInfo($user_id, $visitor_info, $arUser)
      {
        $result = array();
        $result["some_test_key"] = self::getSomeTestKeyValue();
        $result["some_other_test_key"] = "Hey!";

        return array_merge($visitor_info, $result);
      }

    static function getSomeTestKeyValue()
      {
        return "some test key value";
      }
  }
```

В результате в Convead будет передана следующая информация о пользователе:

```javascript
visitor_info: {
  first_name: "Ivan",
  email: "ivan@example.net",
  some_test_key: "some test key value",
  some_other_test_key: "Hey!"
}
```
